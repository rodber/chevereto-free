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

class Login {

	static $logged_user;
	
	public static function get($values, $sort=array(), $limit=NULL) {
		try {
			return DB::get('logins', $values, 'AND', $sort, $limit);
		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function login($id, $by='session', $keep=FALSE) {

		$user = User::getSingle($id, 'id');
		
		if(!in_array(strtolower($by), ['session', 'password', 'cookie'])) {
			$by = 'session';
		}

		try {

			// Destroy any remaining session login
			if($by !== 'session') {
				self::delete(['type' => 'session', 'user_id' => $id]);
			}


			// User has logins?
			if($user['login']) {

				unset($_SESSION['signup']);

				// Check with the current PHP session
				if($_SESSION['login']) {
					if($user['login'][$by]['date_gmt'] !== $_SESSION['login']['datetime']) {
						return NULL;
					}
				}

				// Set the login session
				$_SESSION['login'] = [
					'id'		=> $id,
					'datetime'	=> $user['login'][$by]['date_gmt'],
					'type'		=> $by
				];

				// Session login was already saved to the DB
				if($by == 'session' and $user['login'][$by]) {
					$by = false;
				}

				// Keep login and session insert the new login in DB
				if($keep or $by == 'session') {
					$values = ['type' => $keep ? 'cookie' : 'session', 'user_id' => $id];
					if($by == 'session') {
						$values['date'] = G\datetime();
						$values['date_gmt'] = G\datetimegmt();
						$_SESSION['login']['datetime'] = $values['date_gmt'];
					}
					self::insert($values);
				}

			}

			// Set the timezone for the logged user
			if(self::getUser()['timezone'] !== Settings::get('default_timezone') and G\is_valid_timezone($user['timezone'])) {
				date_default_timezone_set($user['timezone']);
			}

			self::$logged_user = $user;

			return self::$logged_user;

		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function getSession() {
		return $_SESSION['login'];
	}

	public static function getUser() {
		return self::isLoggedUser() ? self::$logged_user : NULL;
	}

	public static function setUser($key, $value) {
		if(self::$logged_user) {
			self::$logged_user[$key] = $value;
		}
	}

	public static function isLoggedUser() {
		return !is_null(self::$logged_user);
	}

	public static function logout() {

		try {

			self::$logged_user = NULL;

			// Unset the cookie from client and DB
			$cookies = ['KEEP_LOGIN', 'KEEP_LOGIN_SOCIAL'];
			foreach($cookies as $cookie_name) {
				$cookie = $_COOKIE[$cookie_name];
				setcookie($cookie_name, '',  time() - 3600, G_ROOT_PATH_RELATIVE);
				if($cookie_name == 'KEEP_LOGIN_SOCIAL') continue;
				$explode = array_filter(explode(':', $cookie));
				if(count($explode) == 4) {
					foreach($explode as $exp) {
						if($exp == NULL) {
							return false;
						}
					}
					$user_id = decodeID($explode[0]);
					self::delete([
						'user_id'	=> $user_id,
						'type'		=> 'cookie',
						'date_gmt'	=> date('Y-m-d H:i:s', $explode[3])
					]);
				}
			}

			$doing = $_SESSION['login']['type'];

			if($doing == 'session') {
				self::delete([
					'user_id'	=> $_SESSION['login']['id'],
					'type'		=> 'session',
					'date_gmt'	=> $_SESSION['login']['datetime']
				]);
			}

			// On logout reset all content passwords
			unset($_SESSION['password']);

			// On logout reset all the session things (access tokens)
			unset($_SESSION['login']);
			

		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}

	}

	public static function checkPassword($id, $password) {
		try {
			$login_db = self::get(['user_id' => $id, 'type' => 'password'], NULL, 1);
			return password_verify($password, $login_db['login_secret']);
		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function loginCookie($type='internal') {

		if(!in_array($type, ['internal', 'social'])) {
			return;
		}

		try {

			$request_log = Requestlog::getCounts('login', 'fail');

			if(is_max_invalid_request($request_log['day'])) {
				return;
			}

			$cookie =  $_COOKIE[$type == 'internal' ? 'KEEP_LOGIN' : 'KEEP_LOGIN_SOCIAL'];

			$explode = array_filter(explode(':', $cookie));
			// CHV: 0->id | 1:token | 2:timestamp
			// SOC: 0->id | 1:type | 2:hash | 3:timestamp

			$count = $type == 'social' ? 4 : 3;

			if(count($explode) !== $count) {
				return false;
			}
			foreach($explode as $exp) {
				if($exp == NULL) {
					return false;
				}
			}

			$user_id = decodeID($explode[0]);
			$login_db_arr = [
				'user_id'	=> $user_id,
				'type'		=> $type == 'internal' ? 'cookie' : $explode[1],
				'date_gmt'	=> date('Y-m-d H:i:s', end($explode))
			];

			$login_db = self::get($login_db_arr, NULL, 1);

			$is_valid_token = $type == 'internal' ? check_hashed_token($login_db['login_secret'], $_COOKIE['KEEP_LOGIN']) : password_verify($login_db['login_secret'].$login_db['login_token_hash'], $explode[2]);

			if($is_valid_token) {
				return self::login($login_db['login_user_id'], $type == 'internal' ? 'cookie' : $explode[1]);
			} else {
				Requestlog::insert(array('result' => 'fail', 'type' => 'login', 'user_id' => $user_id));
				self::logout();
				return NULL;
			}

		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}

	}

	public static function update($id, $values) {
		try {
			return DB::update('logins', $values, ['id' => $id]);
		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function insert($values, $update_session=TRUE) {

		if(!is_array($values)) {
			throw new LoginException('Expecting array values, '.gettype($values).' given in ' . __METHOD__, 100);
		}

		if(!$values['ip']) $values['ip'] = G\get_client_ip();
		if(!$values['hostname']) {
			$values['hostname'] = json_encode(array_merge(G\parse_user_agent($_SERVER['HTTP_USER_AGENT'])));
		}
		if(!$values['date']) $values['date'] = G\datetime();
		if(!$values['date_gmt']) $values['date_gmt'] = G\datetimegmt();

		try {

			if($values['type'] == 'cookie') {
				$tokenize = generate_hashed_token($values['user_id']);
				$values['secret'] = $tokenize['hash'];
				$insert = DB::insert('logins', $values);
				if($insert and $update_session) {
					$cookie = $tokenize['public_token_format'] . ':' . strtotime($values['date_gmt']);
					setcookie('KEEP_LOGIN', $cookie, time()+(60*60*24*30), G_ROOT_PATH_RELATIVE);
				}
				return $insert;
			} else {
				return DB::insert('logins', $values);
			}

		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function addPassword($id, $password, $update_session=true) {
		return self::passwordDB('insert', $id, $password, $update_session);
	}

	public static function changePassword($id, $password, $update_session=true) {
		return self::passwordDB('update', $id, $password, $update_session);
	}

	protected static function passwordDB($action, $id, $password, $update_session) {

		$action = strtoupper($action);

		if(!in_array($action, array('UPDATE', 'INSERT'))) {
			throw new LoginException('Expecting UPDATE or INSERT statements in ' . __METHOD__, 200);
		}

		$hash = password_hash($password, PASSWORD_BCRYPT);

		$array_values = array(
			'ip'		=> G\get_client_ip(),
			'date'		=> G\datetime(),
			'date_gmt'	=> G\datetimegmt(),
			'secret'	=> $hash
		);

		try {
			if($action == 'UPDATE') {
				$dbase = DB::update('logins', $array_values, array('type' => 'password', 'user_id' => $id));
			} else {
				$array_values['user_id'] = $id;
				$array_values['type'] = 'password';
				$dbase = DB::insert('logins', $array_values);
			}

			// Update logged user?
			if(self::getUser()['id'] == $id and $_SESSION['login'] and $update_session) {
				$_SESSION['login'] = [
					'id'		=> $id,
					'datetime'	=> $array_values['date_gmt'],
					'type'		=> 'password'
				];
			}

			return $dbase;

		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}

	public static function delete($values, $clause='AND') {
		try {
			return DB::delete('logins', $values, $clause);
		} catch(Exception $e) {
			throw new LoginException($e->getMessage(), 400);
		}
	}
	
	public static function isAdmin() {
		return (bool) self::$logged_user['is_admin'];
	}

}

class LoginException extends Exception {}
