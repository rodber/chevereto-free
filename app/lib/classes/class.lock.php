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

class Lock {

	static $expire_timeout = 15; // t=seconds
	static $path;

	function __construct($lock=NULL) {
		self::$path = CHV_APP_PATH_CONTENT_LOCKS;
		if(strlen($lock) > 0) {
			$this->setLock($lock);
		}
		$this->expiration = self::$expire_timeout;
		$this->id = $this->getId();
	}

	function getAPI() {
		return 'default';
	}

	function getId() {
		if(!isset($this->id)) {
			$this->id = G\random_string(8) . md5(microtime(TRUE));
		}
		return $this->id;
	}

	function setID($id=NULL) {
		$this->id = $id;
	}

	function setExpiration($time) {
		$this->expiration = $time;
	}

	function setLock($lock) {
		$this->lock = $lock;
	}

	// Magic wand here (binds ->check(), ->create() and ->destroy())
	public function __call($name, $arguments) {
		if(in_array($name, ['check', 'create', 'destroy'])) {
			return $this->process($name, $arguments);
		}
	}

	private function process($action) {
		// Only accept things that look like a file name, not path or something else
		$callee = debug_backtrace()[0];
		$method = $callee['class'] . $callee['type'] . $callee['function'] . '()';
		if(preg_replace('/[^\.\w\d-]/i', '', $this->lock) !== $this->lock) {
			throw new LockException(sprintf('Invalid $lock argument \'%s\' passed in ' . $method, $this->lock));
		}
		$lock_folder = self::$path;
		$lock_file = self::$path . $this->lock . '.lock';
		clearstatcache(TRUE, $lock_file);
		switch($action) {
			case 'check':
				if(!file_exists($lock_file)) {
					return FALSE;
				}
				$contents = file_get_contents($lock_file);
				if(strpos($contents, 'expires') !== FALSE) {
					$contents = json_decode($contents);
				}
				if(isset($contents->expires)) {
					$is_locked = $contents->expires > microtime(true);
					if(!$is_locked) {
						self::destroy();
					}
					return $is_locked;
				}
			break;
			case 'create':
				$contents = [
					'id' => $this->id
				];
				$now = microtime(true);
				if($this->expiration) {
					$contents['expires'] = $now + (int)$this->expiration;
				}
				if(!is_dir(self::$path) && !@mkdir(self::$path)) {
					throw new LockException('Unable to create lock folder in ' . $method);
				}
				if(file_put_contents($lock_file, json_encode($contents), LOCK_EX) === FALSE) {
					throw new LockException('Unable to create ' . $this->lock . ' lock in ' . $method);
				}
			break;
			case 'destroy':
				if(file_exists($lock_file) && !@unlink($lock_file)) {
					throw new LockException('Unable to destroy ' . $this->lock . ' lock in ' . $method);
				}
			break;
		}
		return TRUE;
	}

}
class LockException extends Exception {}