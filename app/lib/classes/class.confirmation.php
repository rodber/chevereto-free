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

class Confirmation {
	
	public static function get($values, $sort=array(), $limit=1) {
		try {
			return DB::get('confirmations', $values, 'AND', $sort, $limit);
		} catch(Exception $e) {
			throw new ConfirmationException($e->getMessage(), 400);
		}
	}
	
	public static function insert($values) {
	
		if(!is_array($values)) {
			throw new ConfirmationException('Expecting array values, '.gettype($values).' given in ' . __METHOD__, 100);
		}
		
		if(!$values['status']) {
			$values['status'] = 'active';
		}
		
		$values['date'] = G\datetime();
		$values['date_gmt'] = G\datetimegmt();
		
		try {
			return DB::insert('confirmations', $values);
		} catch(Exception $e) {
			throw new ConfirmationException($e->getMessage(), 400);
		}

	}
	
	public static function update($id, $values) {
		try {
			return DB::update('confirmations', $values, ['id' => $id]);
		} catch(Exception $e) {
			throw new ConfirmationException($e->getMessage(), 400);
		}
	}
	
	public static function delete($values, $clause='AND') {
		try {
			return DB::delete('confirmations', $values, $clause);
		} catch(Exception $e) {
			throw new ConfirmationException($e->getMessage(), 400);
		}
	}
}

class ConfirmationException extends Exception {}
