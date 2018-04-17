<?php

/* --------------------------------------------------------------------

  G\ library
  http://gbackslash.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

/**
 * class.db.php
 * This class does all the DB handling of the G\ app
 */

namespace G;
use PDO, PDOException, Exception;

class DB {

	private static $instance;

	private $host = G_APP_DB_HOST;
	private $port = G_APP_DB_PORT;
	private $name = G_APP_DB_NAME;
	private $user = G_APP_DB_USER;
	private $pass = G_APP_DB_PASS;
	private $driver = G_APP_DB_DRIVER;
	private $pdo_attrs = G_APP_DB_PDO_ATTRS;

	static $dbh;
	public $query;

	/**
	 * Connect to the DB server
	 * Throws an Exception on error (tay weando? en serio?)
	 */
	public function __construct($conn=[]) {

		try {
			// PDO already connected
			if(empty($conn) and isset(self::$dbh) and get_class(self::$dbh) == 'PDO') {
				return TRUE;
			}

			if(!empty($conn)) {
				// Inject connection info
				foreach(['host', 'user', 'name', 'pass', 'port', 'driver', 'pdo_attrs'] as $k) {
					$this->{$k} = $conn[$k];
				}
			}

			$pdo_connect = $this->driver . ':host=' . $this->host . ';dbname=' . $this->name;
			if($this->port) {
				$pdo_connect .= ';port=' . $this->port;
			}

			$this->pdo_attrs = @unserialize($this->pdo_attrs) ?: $this->pdo_attrs;

			// PDO defaults
			$this->pdo_default_attrs = [
				PDO::ATTR_TIMEOUT		=> 30,
				//PDO::ATTR_PERSISTENT	=> FALSE
			];

			// Override PDO defaults ?
			$this->pdo_attrs = (is_array($this->pdo_attrs) ? $this->pdo_attrs : []) + $this->pdo_default_attrs;

			// PDO hard overrides
			$this->pdo_attrs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			$this->pdo_attrs[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";

			// Turn off PHP error reporting just for the connection here (invalid host names will trigger a PHP warning)
			$error_reporting = error_reporting();
			error_reporting(0);

			// Note that PDO::ERRMODE_SILENT has no effect on connection. Connections always throw an exception if it fails
			self::$dbh = new PDO($pdo_connect, $this->user, $this->pass, $this->pdo_attrs);

			// Re-enable the error_reporting level
			error_reporting($error_reporting);

			// PDO emulate prepares if needed
			if(version_compare(self::$dbh->getAttribute(PDO::ATTR_SERVER_VERSION), '5.1.17', '<')) {
				self::$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			}

			self::$instance = $this;

		} catch(Exception $e) {
			self::$dbh = NULL;
			throw new DBException($e->getMessage(), 400);
		}

	}

	/**
	 * Singleton instance handler
	 * Used for the static methods of this class
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Populates the class DB own PDO attributes array with an entire array
	 * Attribute list here: http://php.net/manual/en/pdo.setattribute.php
	 */
	public function setPDOAttrs($attributes) {
		$this->pdo_attrs = $attributes;
	}

	/**
	 * Populates the class DB own PDO attributes array with a single key
	 * Attributes list here: http://php.net/manual/en/pdo.setattribute.php
	 */
	public function setPDOAttr($key, $value) {
		$this->pdo_attrs[$key] = $value;
	}

	public function getAttr($attr) {
		return self::$dbh->getAttribute($attr);
	}

	/**
	 * Prepares an SQL statement to be executed by the PDOStatement::execute() method
	 * http://php.net/manual/en/pdo.prepare.php
	 */
	public function query($query) {
		$this->query = self::$dbh->prepare($query);
	}

	public function errorInfo() {
		return self::$dbh->errorInfo();
	}

	/**
	 * Binds a value to a corresponding named or question mark placeholder in the SQL statement that was used to prepare the statement
	 * http://php.net/manual/en/pdostatement.bindvalue.php
	 */
	public function bind($param, $value, $type = null) {
		if(is_null($type)) {
			switch(true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
				break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
				break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
				break;
				default:
					$type = PDO::PARAM_STR;
				break;
			}
		}
		$this->query->bindValue($param, $value, $type);
	}

	public function exec() {
		return $this->query->execute();
	}

	public function fetchColumn() {
		return $this->query->fetchColumn();
	}

	public function closeCursor() {
		return $this->query->closeCursor();
	}

	public function fetchAll($mode=PDO::FETCH_ASSOC) {
		$this->exec();
		return $this->query->fetchAll(is_int($mode) ? $mode : PDO::FETCH_ASSOC);
	}

	/**
	 * Execute and returns the single result from the prepared statement
	 * http://php.net/manual/en/pdostatement.fetch.php
	 */
	public function fetchSingle($mode=PDO::FETCH_ASSOC) {
		$this->exec();
		return $this->query->fetch(is_int($mode) ? $mode : PDO::FETCH_ASSOC);
	}

	/**
	 * Query and exec, return number of affected rows or FALSE
	 */
	public static function queryExec($query) {
		try {
			$db = self::getInstance();
			$db->query($query);
			return $db->exec() ? $db->rowCount() : FALSE;
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}
	}

	/**
	 * Query and fetch single record
	 */
	public static function queryFetchSingle($query, $fetch_style=NULL) {
		try {
			return self::queryFetch($query, 1, $fetch_style);
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}
	}

	/**
	 * Query and fetch all records
	 */
	public static function queryFetchAll($query, $fetch_style=NULL) {
		try {
			return self::queryFetch($query, NULL, $fetch_style);
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}
	}

	/**
	 * Query fetch (core version)
	 */
	public static function queryFetch($query, $limit=1, $fetch_style=NULL) {
		try {
			$db = self::getInstance();
			$db->query($query);
			return $limit == 1 ? $db->fetchSingle($fetch_style) : $db->fetchAll($fetch_style);
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}
	}

	/**
	 * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed
	 * http://php.net/manual/en/pdostatement.rowcount.php
	 */
	public function rowCount() {
		return $this->query->rowCount();
	}

	/**
	 * Returns the ID of the last inserted row, or the last value from a sequence object, depending on the underlying driver
	 * http://php.net/manual/en/pdo.lastinsertid.php
	 */
	public function lastInsertId() {
		return self::$dbh->lastInsertId();
	}

	/**
	 * Turns off autocommit mode
	 * http://php.net/manual/en/pdo.begintransaction.php
	 */
	public function beginTransaction(){
		return self::$dbh->beginTransaction();
	}

	/**
	 * Commits a transaction, returning the database connection to autocommit mode until the next call to PDO::beginTransaction() starts a new transaction
	 * http://php.net/manual/en/pdo.commit.php
	 */
	public function endTransaction(){
		return self::$dbh->commit();
	}

	/**
	 * Rolls back the current transaction, as initiated by PDO::beginTransaction()
	 * http://php.net/manual/en/pdo.rollback.php
	 */
	public function cancelTransaction(){
		return self::$dbh->rollBack();
	}

	/**
	 * Dumps the informations contained by a prepared statement directly on the output
	 * http://php.net/manual/en/pdostatement.debugdumpparams.php
	 */
	public function debugDumpParams(){
		return $this->query->debugDumpParams();
	}

	/* Now the G\ fast DB methods, presented by Chevereto */

	/**
	 * Get the table with its prefix
	 */
	public static function getTable($table) {
		return get_app_setting('db_table_prefix') . $table;
	}

	/**
	 * Get values from DB
	 */
	public static function get($table, $values, $clause='AND', $sort=[], $limit=NULL, $fetch_style=NULL) {

		if(!is_array($values) and $values !== 'all') {
			throw new DBException('Expecting array values, '.gettype($values).' given in ' . __METHOD__, 100);
		}

		self::validateClause($clause, __METHOD__);

		if(is_array($table)) {
			$join = $table['join'];
			$table = $table['table'];
		}

		$table = DB::getTable($table);

		$query = 'SELECT * FROM '.$table;

		if($join) {
			$query .= ' ' . $join . ' ';
		}

		if(is_array($values) and !empty($values)) {
			$query .= ' WHERE ';
			foreach($values as $k => $v) {
				if(is_null($v)) {
					$query .= '`'.$k.'` IS :'.$k.' '.$clause.' ';
				} else {
					$query .= '`'.$k.'`=:'.$k.' '.$clause.' ';
				}
			}
		}

		$query = rtrim($query, $clause . ' ');

		if(is_array($sort) and !empty($sort)) {
			if(!$sort['field']) {
				$sort['field'] = 'date';
			}
			if(!$sort['order']) {
				$sort['order'] = 'desc';
			}
			$query .= ' ORDER BY '.$sort['field'].' '.strtoupper($sort['order']).' ';
		}

		if($limit and is_int($limit)) {
			$query .= " LIMIT $limit";
		}

		try {
			$db = self::getInstance();
			$db->query($query);
			if(is_array($values)) {
				foreach($values as $k => $v) {
					$db->bind(':'.$k, $v);
				}
			}
			return $limit == 1 ? $db->fetchSingle($fetch_style) : $db->fetchAll($fetch_style);
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}
	}

	/**
	 * Update target table row(s)
	 * Returns the number of affected rows or false
	 */
	public static function update($table, $values, $wheres, $clause='AND') {

		if(!is_array($values)) {
			throw new DBException('Expecting array values, '.gettype($values).' given in '. __METHOD__, 100);
		}
		if(!is_array($wheres)) {
			throw new DBException('Expecting array values, '.gettype($wheres).' given in '. __METHOD__, 100);
		}

		self::validateClause($clause, __METHOD__);

		$table = DB::getTable($table);

		$query = 'UPDATE `'.$table.'` SET ';

		// Set the value pairs
		foreach($values as $k => $v) {
			$query .= '`' . $k . '`=:value_' . $k . ',';
		}
		$query = rtrim($query, ',') . ' WHERE ';

		// Set the where pairs
		foreach($wheres as $k => $v) {
			$query .= '`'.$k.'`=:where_'.$k.' '.$clause.' ';
		}
		$query = rtrim($query, $clause.' ');

		try {
			$db = self::getInstance();
			$db->query($query);

			// Bind the values
			foreach($values as $k => $v) {
				$db->bind(':value_'.$k, $v);
			}
			foreach($wheres as $k => $v) {
				$db->bind(':where_'.$k, $v);
			}

			return $db->exec() ? $db->rowCount() : FALSE;
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}

	}

	/**
	 * Insert single row to the table
	 */
	public static function insert($table, $values) {

		if(!is_array($values)) {
			throw new DBException('Expecting array values, '.gettype($values).' given in '. __METHOD__, 100);
		}

		$table = DB::getTable($table);

		$table_fields = [];
		foreach($values as $k => $v) {
			$table_fields[] = $k;
		}

		$query = 'INSERT INTO
					`'.$table.'` (`' . ltrim(implode('`,`', $table_fields), '`,`') . '`)
					VALUES (' . ':' . str_replace(':', ',:', implode(':', $table_fields)) . ')';

		try {
			$db = self::getInstance();
			$db->query($query);
			foreach($values as $k => $v) {
				$db->bind(':'.$k, $v);
			}
			return $db->exec() ? $db->lastInsertId() : FALSE;
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}

	}

	/**
	 * Update target numecic table row(s) with and increment (positive or negative)
	 * Returns the number of affected rows or false
	 * Note: Minimum value to be set is zero, no negative values here
	 */
	public static function increment($table, $values, $wheres, $clause='AND') {

		foreach(['values', 'wheres'] as $k) {
			if(!is_array(${$k})) {
				throw new DBException('Expecting array values, '.gettype(${$k}).' given in '. __METHOD__, 100);
			}
		}

		$table = DB::getTable($table);
		$query = 'UPDATE `'.$table.'` SET ';

		foreach($values as $k => $v) {
			if(preg_match('/^([+-]{1})\s*([\d]+)$/', $v, $matches)) { // 1-> op 2-> number
				$query .= '`' . $k . '`=';
				if($matches[1] == '+') {
					$query .= '`' . $k . '`' . $matches[1] . $matches[2] . ',';
				}
				if($matches[1] == '-') {
					$query .= 'GREATEST(cast(`'.$k.'` AS SIGNED) - '.$matches[2].', 0),';
				}
			}
		}

		$query = rtrim($query, ',') . ' WHERE ';

		// Set the where pairs
		foreach($wheres as $k => $v) {
			$query .= '`'.$k.'`=:where_'.$k.' '.$clause.' ';
		}
		$query = rtrim($query, $clause.' ');

		try {
			$db = self::getInstance();
			$db->query($query);
			foreach($wheres as $k => $v) {
				$db->bind(':where_'.$k, $v);
			}
			return $db->exec() ? $db->rowCount() : false;
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}

	}

	/**
	 * Delete row(s) from table
	 * Returns the number of affected rows or false
	 */
	public static function delete($table, $values, $clause='AND') {

		if(!is_array($values)) {
			throw new DBException('Expecting array values, '.gettype($values).' given in '. __METHOD__, 100);
		}

		self::validateClause($clause, __METHOD__);

		$table = DB::getTable($table);
		$query = 'DELETE FROM `'.$table.'` WHERE ';

		$table_fields = array();
		foreach($values as $k => $v) {
			$query .= '`'.$k.'`=:'.$k.' '.$clause.' ';
		}
		$query = rtrim($query, $clause.' ');

		try {
			$db = self::getInstance();
			$db->query($query);
			foreach($values as $k => $v) {
				$db->bind(':'.$k, $v);
			}
			return $db->exec() ? $db->rowCount() : FALSE;
		} catch(Exception $e) {
			throw new DBException($e->getMessage(), 400);
		}

	}

	/**
	 * Validate clause
	 */
	private static function validateClause($clause, $method=NULL) {
		if(!is_null($clause)) {
			$clause = strtoupper($clause);
			if(!in_array($clause, ['AND', 'OR'])) {
				throw new DBException('Expecting clause string \'AND\' or \'OR\' in ' . (!is_null($method) ? $method : __CLASS__), 100);
			}
		}
	}

}

// DB class own Exception
class DBException extends Exception {}