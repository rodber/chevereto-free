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

class Stat {
	public static function getTotals() {
		$totals = DB::queryFetchSingle('SELECT * FROM '.DB::getTable('stats').' WHERE stat_type = "total"');
		unset($totals['stat_id'], $totals['stat_type'], $totals['date_gmt']);
		return DB::formatRow($totals);
	}
	public static function track($args=[]) {
		if(!in_array($args['action'], ['insert', 'update', 'delete'])) {
			throw new StatException(sprintf('Invalid stat action "%s" in ', $args['action']) . __METHOD__, 100);
		}
		$tables = DB::getTables();
		// Restrict unknown tables
		if(!array_key_exists($args['table'], $tables)) {
			throw new StatException(sprintf('Unknown table "%s" in ' . __METHOD__, $args['table']), 101);
		}
		// Restrict tables by stat action
		switch($args['action']) {
			case 'insert':
				if(!in_array($args['table'], ['albums', 'images', 'likes', 'users'])) {
					throw new StatException(sprintf('Table "%s" does not bind an stat procedure in ' . __METHOD__, $args['table']), 101);
				}
			break;
		}
		// Check missing values
		if($args['table'] == 'images' and in_array($args['action'], ['insert', 'delete'])) {
			if(!isset($args['disk_sum'])) {
				$disk_sum_value = 0;
			} else {
				if(preg_match('/^([+-]{1})?\s*([\d]+)$/', $args['disk_sum'], $matches)) {
					$disk_sum_value = $matches[2];
				} else {
					throw new StatException(sprintf('Invalid disk_sum value "%s" in ' . __METHOD__, $args['disk_sum']), 104);
				}
			}
		}
		// Fix values
		if(!isset($args['value'])) {
			$value = 1;
		} else {
			if(preg_match('/^([+-]{1})?\s*([\d]+)$/', $args['value'], $matches)) {
				$value = $matches[2];
			} else {
				throw new StatException(sprintf('Invalid value "%s" in ' . __METHOD__, $args['value']), 102);
			}
		}
		if(!isset($args['date_gmt'])) {
			switch($args['action']) {
				case 'insert':
				case 'update':
					$args['date_gmt'] = G\datetimegmt();
				break;
				case 'delete':
					throw new StatException('Missing date_gmt value in ' . __METHOD__, 105);
				break;
			}
		} else {
			try {
				$date = new \DateTime($args['date_gmt']);
				$args['date_gmt'] = $date->format('Y-m-d');
			} catch(Exception $e) {
				//throw new StatException($e);
				throw new StatException('Invalid date_gmt value in ' . __METHOD__, 106);
			}
		}
		
		// Get to the choppa
		switch($args['action']) {
			
			case 'insert':
				switch($args['table']) {
					case 'images':
						if(!isset($args['disk_sum'])) {
							throw new StatException('Missing disk_sum value in ' . __METHOD__, 103);
						}
						$sql_tpl = 
							'UPDATE `%table_stats` SET stat_images = stat_images + %value, stat_disk_used = stat_disk_used + %disk_sum WHERE stat_type = "total";' . "\n" . 
							'INSERT INTO `%table_stats` (stat_type, stat_date_gmt, stat_images, stat_disk_used) VALUES ("date",DATE("%date_gmt"),"%value", "%disk_sum") ON DUPLICATE KEY UPDATE stat_images = stat_images + %value, stat_disk_used = stat_disk_used + %disk_sum;';
					break;
					default: // albums, likes, users
						$sql_tpl = 
							'UPDATE `%table_stats` SET stat_%related_table = stat_%related_table + %value WHERE stat_type = "total";' . "\n" . 
							'INSERT `%table_stats` (stat_type, stat_date_gmt, stat_%related_table) VALUES ("date",DATE("%date_gmt"),"%value") ON DUPLICATE KEY UPDATE stat_%related_table = stat_%related_table + %value;';
					break;
				}
			break;
			
			case 'update':
				switch($args['table']) {
					case 'images':
					case 'albums':
						// Track image | album | user views
						$sql_tpl = 
							'UPDATE `%table_stats` SET stat_%aux_views = stat_%aux_views + %value WHERE stat_type = "total";' . "\n" . 
							'INSERT INTO `%table_stats` (stat_type, stat_date_gmt, stat_%aux_views) VALUES ("date",DATE("%date_gmt"),"%value") ON DUPLICATE KEY UPDATE stat_%aux_views = stat_%aux_views + %value;';
						if(isset($args['user_id'])) {
							$sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_content_views = user_content_views + %value WHERE user_id = %user_id;';
						}
						$sql_tpl = strtr($sql_tpl, ['%aux' => DB::getFieldPrefix($args['table'])]);
					break;
				}
			break;
			
			case 'delete':
				switch($args['table']) {
					case 'images':
						$sql_tpl = 
							'UPDATE `%table_stats` SET stat_images = GREATEST(stat_images - %value, 0) WHERE stat_type = "total";' . "\n" .
							'UPDATE `%table_stats` SET stat_images = GREATEST(stat_images - %value, 0) WHERE stat_type = "date" AND stat_date_gmt = DATE("%date_gmt");' . "\n" .
							'UPDATE `%table_stats` SET stat_image_likes = GREATEST(stat_image_likes - %likes, 0) WHERE stat_type = "total";' . "\n" .
							'UPDATE `%table_stats` SET stat_image_likes = GREATEST(stat_image_likes - %likes, 0) WHERE stat_type = "date" AND stat_date_gmt = DATE("%date_gmt");' . "\n" .
							'UPDATE `%table_stats` SET stat_disk_used = GREATEST(stat_disk_used - %disk_sum, 0) WHERE stat_type = "total";' . "\n" .
							'UPDATE `%table_stats` SET stat_disk_used = GREATEST(stat_disk_used - %disk_sum, 0) WHERE stat_type = "date" AND stat_date_gmt = DATE("%date_gmt");';
					break;
					default:  // albums, likes, users
						$sql_tpl = 
							'UPDATE `%table_stats` SET stat_%related_table = GREATEST(stat_%related_table - %value, 0) WHERE stat_type = "total";' . "\n" .
							'UPDATE `%table_stats` SET stat_%related_table = GREATEST(stat_%related_table - %value, 0) WHERE stat_type = "date" AND stat_date_gmt = DATE("%date_gmt");';
						if($args['table'] == 'users') {
							$sql_tpl .=
								// Update likes stats related to this deleted user
								'UPDATE IGNORE `%table_stats` AS S
									INNER JOIN (
										SELECT DATE(like_date_gmt) AS like_date_gmt, COUNT(*) AS cnt
										FROM `%table_likes`
											WHERE like_user_id = %user_id
										GROUP BY DATE(like_date_gmt)
								  ) AS L ON S.stat_date_gmt = L.like_date_gmt
								SET S.stat_image_likes = GREATEST(S.stat_image_likes - COALESCE(L.cnt, "0"), 0) WHERE stat_type = "date";
								UPDATE IGNORE `%table_stats` SET stat_image_likes = GREATEST(stat_image_likes - COALESCE((SELECT COUNT(*) FROM `%table_likes` WHERE like_user_id = %user_id), "0"), 0) WHERE stat_type = "total";' . "\n" .
								// Update album stats related to this deleted user
								'UPDATE IGNORE `%table_stats` AS S
									INNER JOIN (
										SELECT DATE(album_date_gmt) AS album_date_gmt, COUNT(*) AS cnt
										FROM `%table_albums`
											WHERE album_user_id = %user_id
										GROUP BY DATE(album_date_gmt)
								  ) AS A ON S.stat_date_gmt = A.album_date_gmt
								SET S.stat_albums = GREATEST(S.stat_albums - COALESCE(A.cnt, "0"), 0) WHERE stat_type = "date";
								UPDATE IGNORE `%table_stats` SET stat_albums = GREATEST(stat_albums - COALESCE((SELECT COUNT(*) FROM `%table_albums` WHERE album_user_id = %user_id), "0"), 0) WHERE stat_type = "total";';
						}
					break;
				}
			break;
		}
		
		$sql = strtr($sql_tpl, [
			'%table_stats'		=> $tables['stats'],
			'%table_users'		=> $tables['users'],
			'%table_likes'		=> $tables['likes'],
			'%table_albums'		=> $tables['albums'],
			'%related_table'	=> (isset($args['content_type']) ? ($args['content_type'] . '_') : NULL) . $args['table'],
			'%value'			=> $value,
			'%date_gmt'			=> $args['date_gmt'],
			'%user_id'			=> $args['user_id'],
			'%disk_sum'			=> $disk_sum_value,
			'%likes'			=> $args['likes'],
		]);
		
		try {
			DB::queryExec($sql);
		} catch(Exception $e) {
			throw new StatException($e->getMessage(), 400);
		}
		
	}
}

class StatException extends Exception {}
