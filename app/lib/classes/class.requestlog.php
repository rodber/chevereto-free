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

class Requestlog {
	
	public static function get($values, $sort=[], $limit=NULL) {
		try {
			return DB::get('requests', $values, 'AND', $sort, $limit);
		} catch(Exception $e) {
			throw new RequestlogException($e->getMessage(), 400);
		}
	}
	
	public static function insert($values) {
	
		if(!is_array($values)) {
			throw new RequestlogException('Expecting array, '.gettype($values).' given in ' . __METHOD__, 100);
		}
		
		if(!$values['ip']) {
			$values['ip'] = G\get_client_ip();
		}
		
		$values['date'] = G\datetime();
		$values['date_gmt'] = G\datetimegmt();
		
		try {
			return DB::insert('requests', $values);
		} catch(Exception $e) {
			throw new RequestlogException($e->getMessage(), 400);
		}
	}
	
	public static function getCounts($type, $result, $ip=NULL) {
		
		if(is_array($type)) {
			$type_qry = 'request_type IN(';
			$binds = [];
			for($i = 0; $i<count($type); $i++) {
				$type_qry .= ':rt' . $i . ',';
				$binds[':rt' . $i] = $type[$i];
			}
			$type_qry = rtrim($type_qry, ',') . ')';
		} else {
			$type_qry = 'request_type=:request_type';
			$binds = [
				':request_type' => $type
			];
		}
		
		try {
			$db = DB::getInstance();
			$db->query('SELECT
							COUNT(IF(request_date_gmt >= DATE_SUB(:now, INTERVAL 1 MINUTE), 1, NULL)) AS minute,
							COUNT(IF(request_date_gmt >= DATE_SUB(:now, INTERVAL 1 HOUR), 1, NULL)) AS hour,
							COUNT(IF(request_date_gmt >= DATE_SUB(:now, INTERVAL 1 DAY), 1, NULL)) AS day,
							COUNT(IF(request_date_gmt >= DATE_SUB(:now, INTERVAL 1 WEEK), 1, NULL)) AS week,
							COUNT(IF(request_date_gmt >= DATE_SUB(:now, INTERVAL 1 MONTH), 1, NULL)) AS month
						FROM '.DB::getTable('requests').' WHERE ' . $type_qry . ' AND request_result=:request_result AND request_ip=:request_ip AND request_date_gmt >= DATE_SUB(:now, INTERVAL 1 MONTH)');
			
			$db->bind(':now', G\datetimegmt());
			foreach($binds as $k => $v) {
				$db->bind($k, $v);
			}
			$db->bind(':request_result', $result);
			$db->bind(':request_ip', $ip ?: G\get_client_ip());
			return $db->fetchSingle();
		} catch(Exception $e) {
			throw new RequestlogException($e->getMessage(), 400);
		}
		
	}
	
	public static function delete($values, $clause='AND') {
		try {
			return DB::delete('requests', $values, $clause);
		} catch(Exception $e) {
			throw new RequestlogException($e->getMessage(), 400);
		}
	}
}

class RequestlogException extends Exception {}
