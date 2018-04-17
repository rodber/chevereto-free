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

class DB extends G\DB {
	
	public static function getTable($table) {
		return G\get_app_setting('db_table_prefix') . $table;
	}
	
	public static function getTables() {
		$tables = ['images', 'users', 'albums', 'logins', 'queue', 'requests', 'confirmations', 'settings', 'storages', 'storage_apis', 'categories', 'ip_bans', 'pages', 'likes', 'stats', 'deletions', 'follows', 'notifications'];
		$return = [];
		foreach($tables as $table) {
			$return[$table] = G\get_app_setting('db_table_prefix') . $table;
		}
		return $return;
	}
	
	// G\DB::get wrapper
	public static function get($table, $values, $clause='AND', $sort=[], $limit=NULL, $fetch_style=NULL) {
		$prefix = self::getFieldPrefix($table);
		$values = self::getPrefixedValues($prefix, $values);
		$sort = self::getPrefixedSort($prefix, $sort);
		return G\DB::get($table, $values, $clause, $sort, $limit, $fetch_style);
	}
	
	// G\DB::update wrapper
	public static function update($table, $values, $wheres, $clause='AND') {
		$prefix = self::getFieldPrefix($table);
		$values = self::getPrefixedValues($prefix, $values);
		$wheres = self::getPrefixedValues($prefix, $wheres);
		return G\DB::update($table, $values, $wheres, $clause);
	}
	
	// G\DB::insert wrapper
	public static function insert($table, $values) {
		$prefix = self::getFieldPrefix($table);
		$values = self::getPrefixedValues($prefix, $values);
		return G\DB::insert($table, $values);
	}
	
	// G\DB::increment wrapper
	public static function increment($table, $values, $wheres, $clause='AND') {
		$prefix = self::getFieldPrefix($table);
		$values = self::getPrefixedValues($prefix, $values);
		$wheres = self::getPrefixedValues($prefix, $wheres);
		return G\DB::increment($table, $values, $wheres, $clause);
	}
	
	// G\DB::delete wrapper
	public static function delete($table, $values, $clause='AND') {
		$prefix = self::getFieldPrefix($table);
		$values = self::getPrefixedValues($prefix, $values);
		return G\DB::delete($table, $values, $clause);
	}
	
	// Format a single row. Converts prefix_something to [prefix] = something
	public static function formatRow($dbrow, $field_prefix='') {
		
		if(!is_array($dbrow)) return $dbrow;
		
		if($field_prefix == '') {
			$array = $dbrow;
			reset($array);
			$first_key = preg_match('/^([a-z0-9]+)_{1}/', key($array), $match);
			$field_prefix = $match[1];
		}
		
		$output = [];
		
		foreach($dbrow as $k => $v) {
			if(!G\starts_with($field_prefix, $k)) {
				$new_key = preg_match('/^([a-z0-9]+)_/i', $k, $new_key_match);
				$new_key = $new_key_match[1];
				$output[$new_key][str_replace($new_key . '_', '', $k)] = $v;
				unset($output[$k]);
			} else {
				$output[str_replace($field_prefix.'_', '', $k)] = $v;
			}
		}
		
		return $output;
	}
	
	// Format universal row resulset
	public static function formatRows($get) {
		if($get[0]) {
			foreach($get as $k => $v) {
				self::formatRowValues($get[$k], $v);
			}
		} else {
			if($get) {
				self::formatRowValues($get);
			}
		}
		return $get;
	}
	
	// Format row handle
	public static function formatRowValues(&$values, $row=[]) {
		$values = self::formatRow(count($row) > 0 ? $row : $values);
	}
	
	public static function getTableFromFieldPrefix($prefix, $db_table_prefix=TRUE) {
		$prefix_to_table = [
			'category'	=> 'categories',
			'deleted'	=> 'deletions',
		];
		if(array_key_exists($prefix, $prefix_to_table)) {
			$table = $prefix_to_table[$prefix];
		} else {
			$table = $prefix . 's';
		}
		return $db_table_prefix ? self::getTable($table) : $table;
	}
	
	public static function getFieldPrefix($table) {
		$tables_to_prefix = [
			'categories'	=> 'category',
			'deletions'		=> 'deleted', /* fix this duplicate */
		];
		if(is_array($table)) {
			$array = $table;
			$table = $array['table'];
		}
		if(array_key_exists($table, $tables_to_prefix)) {
			return $tables_to_prefix[$table];
		} else {
			return rtrim($table, 's');
		}
	}
	
	protected static function getPrefixedValues($prefix, $values) {
		if(!is_array($values)) return $values;
		$values_prefix = [];
		if(is_array($values)) {
			foreach($values as $k => $v) {
				$values_prefix[$prefix . '_' . $k] = $v;
			}
		}
		return $values_prefix;
	}
	
	protected static function getPrefixedSort($prefix, $sort) {
		if(is_array($sort) and !empty($sort['field'])) {
			$sort['field'] = $prefix.'_'.$sort['field'];
		}
		return $sort;
	}
	
}

class DBException extends Exception {}
