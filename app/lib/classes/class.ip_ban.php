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

class Ip_ban {
	public static function getSingle($args=[]) {
		try {
			$args = array_merge([
				'ip' => G\get_client_ip()
			], $args);
			
			$db = DB::getInstance();
			
			if($args['id']) {
				$query = 'SELECT * FROM ' . DB::getTable('ip_bans') . ' WHERE ip_ban_id = :id';
			} else {
				$query = 'SELECT * FROM ' . DB::getTable('ip_bans') . ' WHERE ip_ban_ip = :ip AND (ip_ban_expires_gmt > :now OR ip_ban_expires_gmt IS NULL) ORDER BY ip_ban_id DESC;';
			}
			
			$db->query($query);
			
			if($args['id']) {
				$db->bind(':id', $args['id']);
			} else {
				$db->bind(':ip', $args['ip']);
				$db->bind(':now', G\datetimegmt());
			}
			
			$banned_ip = $db->fetchSingle();
			if($banned_ip) {
				$banned_ip = DB::formatRow($banned_ip, 'ip_ban');
				return $banned_ip;
			} else {
				return false;
			}
		} catch(Exception $e) {
			throw new Ip_banException($e->getMessage(), 400);
		}
	}
	
	public static function getAll() {
		try {
			$ip_bans_raw = DB::get('ip_bans', 'all');
			$ip_bans = [];
			if($ip_bans_raw) {
				foreach($ip_bans_raw as $ip_ban) {
					$ip_bans[$ip_ban['ip_ban_id']] = DB::formatRow($ip_ban, 'ip_ban');
				}
			}
			return $ip_bans;
		} catch(Exception $e) {
			throw new Ip_banException($e->getMessage(), 400);
		}
	}
	
	public static function delete($args=[]) {
		try {
			return DB::delete('ip_bans', $args);
		} catch(Exception $e) {
			throw new Ip_banException($e->getMessage(), 400);
		}
	}
	
	public static function update($where=[], $values=[]) {
		try {
			return DB::update('ip_bans', $values, $where);
		} catch(Exception $e) {
			throw new Ip_banException($e->getMessage(), 400);
		}
	}
	
	public static function insert($args=[]) {
		try {
			return DB::insert('ip_bans', $args);
		} catch(Exception $e) {
			throw new Ip_banException($e->getMessage(), 400);
		}
	}
	
}

class Ip_banException extends Exception {}