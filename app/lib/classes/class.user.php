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

class User {

	public static function getSingle($var, $by='id', $pretty=true) {
		try {
			$user_db = DB::get('users', [$by => $var], 'AND', NULL, 1);
			if(!$user_db) {
				return NULL;
			}
			$logins_db = Login::get(['user_id' => $user_db['user_id']]);
			$logins_db_aux = [];
			foreach($logins_db as $k => $v) {
				$logins_db_aux[$v['login_type']] = DB::formatRow($v);
			}
			$user_db['user_login'] = $logins_db_aux;

			// Count labels
			foreach(['user_image_count', 'user_album_count'] as $v) {
				if(is_null($user_db[$v]) or $user_db[$v] < 0) {
					$user_db[$v] = 0;
				}
			}

			if(!array_key_exists('user_following', $user_db)) {
				$user_db['user_following'] = 0;
			}
			if(!array_key_exists('user_followers', $user_db)) {
				$user_db['user_followers'] = 0;
			}

			// Remove any unwanted tag from user_name
			$user_db['user_name'] = self::sanitizeUserName($user_db['user_name']);

			if($pretty) {
				$user_db = self::formatArray($user_db);
			}

			return $user_db;

		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}
	}

	public static function getPrivate() {
		return [
			'name'			=> _s('Private profile'),
			'username'		=> 'private',
			'name_short'	=> _s('Private'),
			'url'			=> G\get_base_url(),
			'album_count' 	=> 0,
			'image_count' 	=> 0,
			'image_count_label'	=> _n('image', 'images', 0),
			'album_count_display' => 0,
			'image_count_display' => 0,
			'is_private'	=> TRUE
		];
	}

	public static function getAlbums($var) {
		try {

			$id = is_array($var) ? $var['id'] : $var;

			$user_albums = [];

			// Build the user stream
			$user_stream = self::getStreamAlbum($id);

			if(is_array($user_stream)) {
				$user_albums['stream'] = $user_stream;
			}

			$db = DB::getInstance();
			$db->query('SELECT * FROM '. DB::getTable('albums') . ' WHERE album_user_id=:image_user_id ORDER BY album_name ASC');
			$db->bind(':image_user_id', $id);
			$user_albums_db = $db->fetchAll();

			if($user_albums_db) {
				$user_albums += $user_albums_db;
			}

			foreach($user_albums as $k => &$v) {
				if($v['album_image_count'] < 0) {
					$v['album_image_count'] = 0;
				}
				$user_albums[$k] = DB::formatRow($v, 'album');
				Album::fill($user_albums[$k]);
			}

			return $user_albums;

		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}
	}

	public static function getStreamAlbum($user) {
		try {
			// Need to ask for user?
			if(!is_array($user)) {
				$user = self::getSingle($user, 'id', true);
			}
			if($user) {
				return array(
					'album_id' 			=> NULL,
					'album_id_encoded'	=> NULL,
					'album_name' 		=> _s("%s's images", $user['name_short']),
					'album_user_id' 	=> $user['id'],
					'album_privacy'		=> 'public',
					'album_url'			=> $user['url']
				);
			}
		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}
	}

	public static function getUrl($handle) {
		$username = is_array($handle) ? $handle[$handle['user_username'] ? 'user_username' : 'username'] : $handle;
		$id = is_array($handle) ? $handle[$handle['user_id'] ? 'user_id' : 'id'] : NULL;
		$path = getSetting('user_routing') ? NULL : 'user/';
		$url = $path . $username;
		// Single user mode on
		if(is_array($handle) and getSetting('website_mode') == 'personal' and $id == getSetting('website_mode_personal_uid')) {
			$url = getSetting('website_mode_personal_routing') !== '/' ? getSetting('website_mode_personal_routing') : NULL;
		}
		return G\get_base_url($url);
	}

	public static function getUrlAlbums($user_url) {
		return rtrim($user_url, '/') . '/albums';
	}

	/* Insert user
	 * @returns uid
	 */
	public static function insert($values) {
		try {
			if(!is_array($values)) {
				throw new DBException('Expecting array values, '.gettype($values).' given in ' . __METHOD__, 100);
			}
			if(!$values['date']) {
				$values['date'] = G\datetime();
			}
			if(!$values['date_gmt']) {
				$values['date_gmt'] = G\datetimegmt();
			}
			if(!$values['language']) {
				$values['language'] = getSetting('default_language');
			}
			if(!$values['timezone']) {
				$values['timezone'] = getSetting('default_timezone');
			}
			if(isset($values['name'])) {
				$values['name'] = self::sanitizeUserName($values['name']);
			}
			if(empty($values['registration_ip'])) {
				$values['registration_ip'] = G\get_client_ip();
			}

			// Detect flood (son 48 horas que hay que aprovechar)
			if(!Login::getUser()['is_admin']) {
				$db = DB::getInstance();
				$db->query('SELECT COUNT(*) c FROM ' . DB::getTable('users') . ' WHERE user_registration_ip=:ip AND user_status != "valid" AND user_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)');
				$db->bind(':ip', $values['registration_ip']);
				if($db->fetchSingle()['c'] > 5) {
					throw new Exception('Flood detected', 666);
				}
			}

			$user_id = DB::insert('users', $values);

			// Email notify
			if(!Login::getUser()['is_admin'] && Settings::get('notify_user_signups')) {
				$message = implode('<br>', [
					'A new user has just signed up %user (%edit)',
					'',
					'Username: %username',
					'Email: %email',
					'Status: %status',
					'IP: %registration_ip',
					'Date (GMT): %date_gmt',
					'',
					'You can disable these notifications on %configure'
				]);
				foreach(['username', 'email', 'status', 'registration_ip', 'date_gmt'] as $k) {
					$table['%' . $k] = $values[$k];
				}
				$table['%edit'] = '<a href="' . G\get_base_url('dashboard/user/' . $user_id) . '">edit</a>';
				$table['%user'] = '<a href="' . self::getUrl($values['username']) . '">' . $values['username'] . '</a>';
				$table['%configure'] = '<a href="'. G\get_base_url('dashboard/settings/users') .'">dashboard/settings/users</a>';
				system_notification_email([
					'subject' => sprintf('New user signup %s', $values['username']),
					'message' => strtr($message, $table),
				]);
			}

			// Track stats
			Stat::track([
				'action'	=> 'insert',
				'table'		=> 'users',
				'value'		=> '+1',
				'date_gmt'	=> $values['date_gmt']
			]);
      if(isset($_SESSION['guest_uploads']) and count((array) $_SESSION['guest_uploads']) > 0) {
        try {
          $db = DB::getInstance();
          $db->query('UPDATE ' . DB::getTable('images') . ' SET image_user_id=' . $user_id . ' WHERE image_id IN (' . implode(',', $_SESSION['guest_uploads']) . ')');
          $db->exec();
          // Get user actual image count
          $real_image_count = $db->queryFetchSingle('SELECT COUNT(*) as cnt FROM ' .DB::getTable('images').' WHERE image_user_id='. $user_id)['cnt'];
          if($real_image_count) {
            self::update($user_id, ['image_count' => $real_image_count]);
          }
        } catch(Exception $e) {} // Silence
        unset($_SESSION['guest_uploads']);
      }
      return $user_id;
		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}

	}

	public static function update($id, $values) {
		try {
			if(isset($values['name'])) {
				$values['name'] = self::sanitizeUserName($values['name']);
			}
			return DB::update('users', $values, ['id' => $id]);
		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}
	}

	public static function uploadPicture($user, $type, $source) {

		$type = strtolower($type);

		if(!in_array($type, ['background', 'avatar'])) {
			throw new UserException('unexpected upload value', 403);
		}

		if(!is_array($user)) {
			try {
				$user = self::getSingle($user, 'id', true);
			} catch(Exception $e) {
				throw new UserException($e->getMessage(), 400);
			}
		}

		if(!$user) {
			throw new UserException("target user doesn't exists", 403);
		}

		// Upload file
		try {

			$user_images_path = CHV_PATH_CONTENT_IMAGES_USERS . $user['id_encoded'];
			$image_upload = Image::upload($source, $user_images_path, ($type == 'avatar' ? 'av' : 'bkg').'_' . strtotime(G\datetimegmt()), ['max_size' => G\get_bytes(Settings::get('user_image_'.$type.'_max_filesize_mb') . ' MB')]);

			if($type == 'avatar') {
				$max_res = ['width' => 160, 'height' => 160];
				$must_resize = ($image_upload['uploaded']['fileinfo']['width'] !== $max_res['width'] and $image_upload['uploaded']['fileinfo']['height'] !== $max_res['height']);
			} else {
				$max_res = ['width' => 1920];
				$must_resize = $image_upload['uploaded']['fileinfo']['width'] > $max_res['width'];

				// Medium background
				Image::resize($image_upload['uploaded']['file'], NULL, $image_upload['uploaded']['name'] . '.md', ['width' => 500]);
			}

			if($must_resize) {
				$resized = Image::resize($image_upload['uploaded']['file'], NULL, NULL, $max_res);
			}

			$file_uploaded = $resized ? $resized : $image_upload['uploaded'];

		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}

		if($file_uploaded) {

			try {
				// Ok, now convert this image to compressed JPG
				$convert = new ImageConvert($file_uploaded['file'], 'jpg', $file_uploaded['file'], 90);
				$file_uploaded['file'] = $convert->out;

				// Edit user database

				$user_edit = self::update($user['id'], [$type.'_filename' => $file_uploaded['filename']]);
				if($user_edit) {
					// delete any old image (silent)
					if($user[$type]['url']) {
						$image_path = G\url_to_absolute($user[$type]['url']);
						if($type == 'background') {
							$pathinfo = pathinfo($image_path);
							@unlink(str_replace($pathinfo['basename'], $pathinfo['filename'].'.md.'.$pathinfo['extension'], $image_path));
						}
						@unlink($image_path);
					}
					// return the thing
					return $file_uploaded['fileinfo'];
				}
			} catch(Exception $e) {
				error_log($e);
			}

		}

	}

	public static function deletePicture($user, $deleting) {

		$deleting = strtolower($deleting);

		if(!in_array($deleting, ['background', 'avatar'])) {
			throw new UserException('Unexpected delete value', 100);
		}

		if(!is_array($user)) {
			$user = self::getSingle($user, 'id', true);
		}

		if(!$user) {
			throw new UserException("Target user doesn't exists", 101);
		}

		if(!$user[$deleting]) {
			throw new UserException('user '.$deleting." doesn't exists", 102);
		}
		$image_path = G\url_to_absolute($user[$deleting]['url']);

		if($deleting == 'background') {
			$pathinfo = pathinfo($image_path);
			@unlink(str_replace($pathinfo['basename'], $pathinfo['filename'].'.md.'.$pathinfo['extension'], $image_path));
		}

		@unlink($image_path);

		if(!file_exists($image_path)) {
			try {
				$edited_user = self::update($user['id'], [$deleting.'_filename' => NULL]);
			} catch(Exception $e) {} // Silence
		}

		if(!$edited_user) {
			throw new UserException("Can't delete ".$deleting." file", 200);
		}

		return true;
	}

	public static function delete($user) {
		try {
			if(!is_array($user)) {
				$user = self::getSingle($user, 'id', TRUE);
			}
			// Delete content user image folder
			$user_images_path = CHV_PATH_CONTENT_IMAGES_USERS . $user['id_encoded'];
			if(!@unlink($user_images_path)) {
				$files = glob($user_images_path.'/{,.}*', GLOB_BRACE);
				foreach($files as $file){
					if(is_file($file)) {
						@unlink($file);
					}
				}
			}

			// Delete images from disk
			$db = DB::getInstance();
			$db->query('SELECT image_id FROM '.DB::getTable('images').' WHERE image_user_id=:image_user_id');
			$db->bind(':image_user_id', $user['id']);
			$user_images = $db->fetchAll();

			foreach($user_images as $user_image) {
				Image::delete($user_image['image_id']);
			}

			// Remove related notifications tied to this user (follows)
			Notification::delete([
				'table'		=> 'users',
				'user_id'	=> $user['id'],
			]);

			// Track stats
			Stat::track([
				'action'	=> 'delete',
				'table'		=> 'users',
				'value'		=> '-1',
				'user_id'	=> $user['id'],
				'date_gmt'	=> $user['date_gmt']
			]);

			// Update affected user_likes count
			$sql = strtr('UPDATE `%table_users` SET user_likes = user_likes - COALESCE((SELECT COUNT(*) FROM `%table_likes` WHERE like_user_id = %user_id AND user_id = like_content_user_id AND like_user_id <> like_content_user_id GROUP BY like_content_user_id),"0");', [
				'%table_users'	=> DB::getTable('users'),
				'%table_likes'	=> DB::getTable('likes'),
				'%user_id'		=> $user['id'],
			]);
			DB::queryExec($sql);

			// Update affected user_liked count (users who liked content owner by this user)
			// --> Should happen in Image::delete()

			// Update affected user_followers count
			$sql = strtr('UPDATE `%table_users` SET user_followers = user_followers - COALESCE((SELECT 1 FROM `%table_follows` WHERE follow_user_id = %user_id AND user_id = follow_followed_user_id AND follow_user_id <> follow_followed_user_id GROUP BY follow_followed_user_id),"0");', [
				'%table_users'	=> DB::getTable('users'),
				'%table_follows'=> DB::getTable('follows'),
				'%user_id'		=> $user['id'],
			]);
			DB::queryExec($sql);

			// Update affected user_following count
			$sql = strtr('UPDATE `%table_users` SET user_following = user_following - COALESCE((SELECT 1 FROM `%table_follows` WHERE follow_followed_user_id = %user_id AND user_id = follow_user_id AND follow_user_id <> follow_followed_user_id GROUP BY follow_user_id),"0");', [
				'%table_users'	=> DB::getTable('users'),
				'%table_follows'=> DB::getTable('follows'),
				'%user_id'		=> $user['id'],
			]);

			DB::queryExec($sql);

			DB::delete('albums', ['user_id' => $user['id']]); // Delete albums DB
			DB::delete('images', ['user_id' => $user['id']]); // Delete images DB
			DB::delete('logins', ['user_id' => $user['id']]); // Delete logins
			DB::delete('likes', ['user_id' => $user['id']]); // Delete user likes
			DB::delete('follows', ['user_id' => $user['id'], 'followed_user_id' => $user['id']], 'OR'); // Delete user's followers and follows
			DB::delete('users', ['id' => $user['id']]); // Delete user DB

		} catch(Exception $e) {
			throw new UserException($e->getMessage(), $e->getCode());
		}
	}

	public static function statusRedirect($status) {
		if(isset($status) and $status != NULL and $status !== 'valid') {
			if($status == 'awaiting-email') $status = 'email-needed';
			G\redirect('account/'.$status);
		}
	}

	public static function isValidUsername($string) {
		$restricted = [
			'tag', 'tags',
			'categories',
			'profile',
			'messages',
			'map',
			'feed',
			'events',
			'notifications',
			'discover',
			'upload',
			'following','followers',
			'flow', 'trending', 'popular', 'fresh', 'upcoming', 'editors', 'profiles',
			'activity', 'upgrade', 'account',
			'affiliates', 'billing',
			'do', 'go', 'redirect',
			'api','sdk', 'plugin', 'plugins', 'tools',
			'external',
		];
		$virtual_routes = ['image', 'album'];
		foreach($virtual_routes as $k) {
			$restricted[] = getSetting('route_' .$k);
		}
		return preg_match('/'.getSetting('username_pattern').'/', $string) && !in_array($string, $restricted) && !G\is_route_available($string) && !file_exists(G_ROOT_PATH . $string);
	}

	public static function formatArray($object) {
		try {
			if($object) {
				$output = DB::formatRow($object);
				self::fill($output);
			}
			return $object ? $output : NULL;
		} catch(Exception $e) {
			throw new UserException($e->getMessage(), 400);
		}
	}

	public static function fill(&$user) {

		$user['id_encoded'] = encodeID($user['id']);

		// Abbreviated counts
		$user['image_count_display'] = G\abbreviate_number($user['image_count']);
		$user['album_count_display'] = G\abbreviate_number($user['album_count']);

		// Populate user URLs
		$user['url'] = self::getUrl($user);
		$user['url_albums'] = self::getUrlAlbums($user['url']);
		$user['url_liked'] = $user['url'] . '/liked';
		$user['url_following'] = $user['url'] . '/following';
		$user['url_followers'] = $user['url'] . '/followers';

		if(!filter_var($user['website'], FILTER_VALIDATE_URL)) {
			unset($user['website']);
		}

		// Do some safe cleaning
		if(isset($user['website'])) {
			$user['website_safe_html'] = G\safe_html($user['website']);
			$user['website_display'] = $user['is_admin'] ? $user['website_safe_html'] : get_redirect_url($user['website_safe_html']);
		}
		if(isset($user['bio'])) {
			$user['bio_safe_html'] = G\safe_html($user['bio']);
			$user['bio_linkify'] = $user['is_admin'] ? G\linkify($user['bio_safe_html'], ['attr' => ['target' => '_blank']]) : linkify_redirector($user['bio_safe_html']);
		}

		if(empty($user['name'])) {
			$user['name'] = ucfirst($user['username']);
		}

		foreach(['image_count', 'album_count'] as $v) {
			$single = $v == 'image_count' ? 'image' : 'album';
			$plural = $v == 'image_count' ? 'images' : 'albums';
			if(is_callable('_n')) {
				$user[$v.'_label'] = _n($single, $plural, $user[$v]);
			} else {
				$user[$v.'_label'] = $user[$v] == 1 ? $single : $plural;
			}
		}

		// Get first name like "Rodolfo" from "Rodolfo Berrios"
		$name_array = explode(' ', $user['name']);
		$user['firstname'] = mb_strlen($name_array[0]) > 20 ? trim(mb_substr($name_array[0], 0, 20, 'UTF-8')) : $name_array[0];
		$user['firstname_html'] = G\safe_html(strip_tags($user['firstname']));

		// Get short name like Rodolfoverylong Berr from "Rodolfoverylong Berrios"
		$user['name_short'] = mb_strlen($user['name']) > 20 ? $user['firstname'] : $user['name'];
		$user['name_short_html'] = G\safe_html(strip_tags($user['name_short']));

		if($user['avatar_filename']) {
			$avatar_file = $user['id_encoded'].'/'.$user['avatar_filename'];
			$avatar_path = CHV_PATH_CONTENT_IMAGES_USERS . $avatar_file;
			if(file_exists($avatar_path)) {
				$user['avatar'] = array(
					'filename'	=> $user['avatar_filename'],
					'url'		=> get_users_image_url($avatar_file)
				);
			}
		}
		unset($user['avatar_filename']);

		if($user['background_filename']) {
			$background_file = $user['id_encoded'].'/'.$user['background_filename'];
			$background_path = CHV_PATH_CONTENT_IMAGES_USERS . $background_file;

			$pathinfo = pathinfo($background_path);
			$background_md_file = $user['id_encoded'].'/'.$pathinfo['filename'].'.md.'.$pathinfo['extension'];

			if(file_exists($background_path)) {
				$user['background'] = array(
					'filename'	=> $user['background_filename'],
					'url'		=> get_users_image_url($user['id_encoded'].'/'.$user['background_filename']),
					'medium'	=> [
						'filename'	=> $pathinfo['basename'],
						'url' 		=> get_users_image_url($background_md_file)
					]
				);
			}
		}
		unset($user['background_filename']);
		unset($user['facebook_username']);

		if($user['twitter_username']) {
			$user['twitter'] = array(
				'username'	=> $user['twitter_username'],
				'url'		=> 'http://twitter.com/'.$user['twitter_username']
			);
		}
		unset($user['twitter_username']);

		$user['notifications_unread_display'] = $user['notifications_unread'] > 10 ? '+10' : $user['notifications_unread'];

	}

	public static function sanitizeUserName($name) {
		return preg_replace('#<|>#', '', $name);
	}

	// Clean unconfirmed accounts
	public static function cleanUnconfirmed($limit=NULL) {
		$db = DB::getInstance();
		$query = 'SELECT * FROM ' . DB::getTable('users') . ' WHERE user_status IN ("awaiting-confirmation", "awaiting-email") AND user_date_gmt <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY) ORDER BY user_id DESC';
		if(is_int($limit)) $query .= ' LIMIT ' . $limit;
		$db->query($query);
		$users = $db->fetchAll();
		foreach($users as $user) {
			$user = self::formatArray($user);
			self::delete($user);
		}
	}

}

class UserException extends Exception {}