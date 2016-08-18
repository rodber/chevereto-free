<?php

/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
			<inbox@rodolfoberrios.com>

  Copyright (C) Rodolfo Berrios A. All rights reserved.
  
  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */

namespace CHV;
use G, Exception;

class Notification {
	static $content_types = ['image', 'like', 'follow'];
	
	// Get core
	public static function get($args=[]) {
		try {
			if(!is_array($args)) {
				throw new Exception('Expecting array values, '.gettype($args).' given in '. __METHOD__, 100);
			}
			$tables = DB::getTables();
			$db = DB::getInstance();
			// 1) join action tables + content tables
			$db->query('SELECT * FROM '.$tables['notifications'].'
				LEFT JOIN '.$tables['likes'].' ON notification_type = "like" AND notification_type_id = like_id AND notification_type_id > 0
				LEFT JOIN '.$tables['follows'].' ON notification_type = "follow" AND notification_type_id = follow_id
				LEFT JOIN '.$tables['images'].' ON notification_content_type = "image" AND like_content_type = "image" AND like_content_id = image_id
				LEFT JOIN '.$tables['users'].' ON user_id = (
					CASE notification_type
						WHEN "like" THEN like_user_id
						WHEN "follow" THEN follow_user_id
						ELSE NULL
					END
				)
			WHERE notification_user_id = :user_id AND notification_type_id > 0 ORDER BY notification_id DESC LIMIT 50;');
			$db->bind(':user_id', $args['user_id']);
			$get = $db->fetchAll();
			if($get[0]) {
				foreach($get as $k => $v) {
					DB::formatRowValues($get[$k], $v);
					self::fill($get[$k]);
				}
			} else {
				if($get) {
					DB::formatRowValues($get);
					self::fill($get);
				}
			}
			return $get;
		} catch(Exception $e) {
			throw new NotificationException($e->getMessage(), 400);
		}
	}
	
	// Insert notification
	public static function insert($args=[]) {
		try {
			if(!is_array($args)) {
				throw new NotificationException('Expecting array, '.gettype($args).' given in ' . __METHOD__, 100);
			}
			foreach(['user_id', 'trigger_user_id', 'type_id'] as $v) {
				if(empty($args[$v])) {
					throw new NotificationException('Missing '.$v.' value in ' . __METHOD__, 101);
				}
			}
			$tables = DB::getTables();
			$sql_tpl = 'INSERT INTO `%table_notifications` (notification_date_gmt, notification_user_id, notification_trigger_user_id, notification_type, notification_content_type, notification_type_id) VALUES ("%date_gmt", %user_id, %trigger_user_id, "%action", "%content_type", %type_id) ON DUPLICATE KEY UPDATE notification_is_read = 0;';
						
			switch($args['table']) {
				case 'likes':
					$action = 'like';
					$content_type = 'image';
				break;
				case 'follows':
					$action = 'follow';
					$content_type = 'user';
				break;
			}
			$sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_notifications_unread = user_notifications_unread + 1 WHERE user_id = %user_id;';
			$sql = strtr($sql_tpl, [
				'%date_gmt'			=> G\datetimegmt(),
				'%action'			=> $action,
				'%content_type'		=> $content_type,
				'%user_id'			=> $args['user_id'],
				'%trigger_user_id'	=> $args['trigger_user_id'],
				'%type_id'			=> $args['type_id'],
				'%table_users'		=> $tables['users'],
				'%table_notifications'	=> $tables['notifications'],
			]);
			try {
				DB::queryExec($sql);
			} catch(Exception $e) {
				throw new Exception($e->getMessage(), 400);
			}
		} catch(Exception $e) {
			throw new NotificationException($e->getMessage(), 400);
		}
	}
	
	// Delete notification
	public static function delete($args=[]) {
		try {
			$tables = DB::getTables();
			switch($args['table']) {
				case 'images':
					$sql_tpl = 'DELETE IGNORE `%table_notifications` FROM `%table_notifications` INNER JOIN `%table_likes` ON like_content_id = %image_id WHERE notification_type = "like" AND notification_content_type = "image" AND notification_type_id = like_id;';
				break;
				case 'users':
					$sql_tpl = 
						// Update user_notifications_unread related to notifications triggered by this user_id
						'UPDATE IGNORE `%table_users` AS U
							INNER JOIN (
								SELECT notification_user_id, COUNT(*) AS cnt
								FROM `%table_notifications`
									WHERE notification_trigger_user_id = %user_id AND notification_is_read = 0
								GROUP BY notification_user_id
							) AS N ON U.user_id = N.notification_user_id
						SET U.user_notifications_unread = GREATEST(U.user_notifications_unread - COALESCE(N.cnt, "0"), 0);' . "\n" .
						// Delete every notification triggered by this user_id
						'DELETE IGNORE `%table_notifications` FROM `%table_notifications`
							LEFT JOIN `%table_follows` ON notification_type_id = follow_id AND follow_user_id = %user_id
							LEFT JOIN `%table_likes` ON notification_type_id = like_id AND like_user_id = %user_id
						WHERE (notification_type = "follow" AND notification_type_id = follow_id) OR (notification_type = "like" AND notification_type_id = like_id);' . "\n";
					// And then delete every notification tied to this user_id
					$sql_tpl .= 
						'DELETE IGNORE FROM `%table_notifications` WHERE notification_user_id = %user_id;';
				break;
				default: // likes, follows
					if(isset($args['user_id'])) {
						$sql_tpl = 'DELETE IGNORE FROM `%table_notifications` WHERE notification_user_id = %user_id AND notification_type = "%type" AND notification_type_id = %type_id;';
					}
				break;
			}
			// Update unread notifications for like|follow|image actions when user_id is set
			if(isset($args['user_id']) and $args['table'] !== 'users') {
				$sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_notifications_unread = COALESCE((SELECT COUNT(*) FROM `%table_notifications` WHERE notification_user_id = %user_id AND notification_is_read = 0), 0) WHERE user_id = %user_id;';
			}
			$table_to_types = [
				'likes'		=> 'like',
				'follows'	=> 'follow'
			];
			$sql = strtr($sql_tpl, [
				'%table_notifications'	=> $tables['notifications'],
				'%table_likes'			=> $tables['likes'],
				'%table_users'			=> $tables['users'],
				'%table_follows'		=> $tables['follows'],
				'%image_id'				=> $args['image_id'],
				'%user_id'				=> $args['user_id'],
				'%type'					=> $table_to_types[$args['table']],
				'%type_id'				=> $args['type_id'],
			]);
			try {
				if(!empty($sql)) {
					DB::queryExec($sql);
				}
			} catch(Exception $e) {
				throw new NotificationException($e->getMessage(), 400);
			}
		} catch(Exception $e) {
			throw new NotificationException($e->getMessage(), 400);
		}
	}
	
	// Mark as read
	public static function markAsRead($args=[]) {
		try {
			if(!is_array($args)) {
				throw new Exception('Expecting array values, '.gettype($args).' given in '. __METHOD__, 100);
			}
			DB::update('notifications', ['is_read' => 1], $args);
			DB::update('users', ['notifications_unread' => 0], ['id' => $args['user_id']]);
		} catch(Exception $e) {
			throw new NotificationException($e->getMessage(), 400);
		}
	}
	
	protected static function fill(&$row) {
		foreach(self::$content_types as $k) {
			if(!isset($row[$k]['id'])) {
				unset($row[$k]);
			} else {
				switch($k) {
					case 'image':
						Image::fill($row[$k]);
					break;
				}
			}
		}
		if(isset($row['user']['id'])) {
			User::fill($row['user']);
		}
		switch($row['type']) {
			case 'like':
				$message = _s('%u liked your %t %c', [
					'%t' => _s($row['content_type']),
					'%c' => '<a href="'.$row['image']['url_viewer'].'">'.$row['image']['title_truncated_html'].'</a>'
				]);
			break;
			case 'follow':
				$message = _s('%u is now following you');
			break;
		}
		$row['message'] = strtr($message, [
			'%u' => '<a href="'.$row['user']['url'].'">'.$row['user']['name_short_html'].'</a>',
		]);
	}
}
class NotificationException extends Exception {}