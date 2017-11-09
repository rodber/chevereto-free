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
	
	static $expire_timeout = 120; // t=seconds
	static $path;
	
	protected $api = 'default';
	
	function __construct($lock=NULL) {
		self::$path = CHV_APP_PATH_CONTENT . 'locks/';
		if(strlen($lock) > 0) {
			$this->setLock($lock);
		}
		$this->expiration = self::$expire_timeout;
		$this->id = $this->getId();
	}
	
	function getAPI() {
		return $this->api;
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
		switch($action) {
			case 'check':
				switch($this->api) {
					case 'shmop':
						$Shmop = new Shmop();
						$memory = $Shmop->read();
						$Shmop->close();
						if(!$memory) {
							return FALSE;
						}
						$memory = json_decode($memory, TRUE);
						if(!is_array($memory) || !array_key_exists($this->lock, $memory)) {
							return FALSE;
						}
						$contents = $memory[$this->lock];
						if(!array_key_exists('expires', $contents)) {
							return TRUE;
						}
					break;
					default:
						if(!file_exists($lock_file)) {
							return FALSE;
						}
						$contents = file_get_contents($lock_file);
						if(strpos($contents, 'expires') !== FALSE) {
							$contents = json_decode($lock, TRUE);
						}
					break;
				}
				if(isset($contents['expires'])) {
					return $contents['expires'] > microtime(true); // id + expiration
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
				switch($this->api) {
					case 'shmop':
						$contents['time'] = $now;
						$Shmop = new Shmop();
						$memory = $Shmop->read();
						if(!$memory) {
							return FALSE;
						}
						$memory = json_decode($memory, TRUE);
						$memory[$this->lock] = $contents;
						$memory = json_encode($memory);
						$Shmop->write($memory);
						$Shmop->close();
					break;
					default:
						if(!is_dir(self::$path) && !@mkdir(self::$path)) {
							throw new LockException('Unable to create lock folder in ' . $method);
						}
						if(file_put_contents($lock_file, json_encode($contents), LOCK_EX) === FALSE) {
							throw new LockException('Unable to create ' . $this->lock . ' lock in ' . $method);
						}
					break;
				}				
			break;
			case 'destroy':
				switch($this->api) {
					case 'shmop':
						$Shmop = new Shmop();
						$memory = $Shmop->read();
						if($memory) $memory = json_decode($memory, TRUE);
						if(is_array($memory) && array_key_exists($this->lock, $memory)) {
							unset($memory[$this->lock]);
							$memory = json_encode($memory);
							$Shmop->write($memory);
							$Shmop->close();
						}
					break;
					default:
						if(!@unlink($lock_file)) {
							throw new LockException('Unable to destroy ' . $this->lock . ' lock in ' . $method);
						}
					break;
				}
			break;
		}
		return TRUE;
	}
	
}
class LockException extends Exception {}