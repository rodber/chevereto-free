<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

namespace CHV;

use G;
use Exception;

class Lock
{
    public static $expire_timeout = 15; // t=seconds
    public static $path;

    public function __construct($lock=null)
    {
        self::$path = CHV_APP_PATH_CONTENT_LOCKS;
        if (strlen($lock) > 0) {
            $this->setLock($lock);
        }
        $this->expiration = self::$expire_timeout;
        $this->id = $this->getId();
    }

    public function getAPI()
    {
        return 'default';
    }

    public function getId()
    {
        if (!isset($this->id)) {
            $this->id = G\random_string(8) . md5(microtime(true));
        }
        return $this->id;
    }

    public function setID($id=null)
    {
        $this->id = $id;
    }

    public function setExpiration($time)
    {
        $this->expiration = $time;
    }

    public function setLock($lock)
    {
        $this->lock = $lock;
    }

    // Magic wand here (binds ->check(), ->create() and ->destroy())
    public function __call($name, $arguments)
    {
        if (in_array($name, ['check', 'create', 'destroy'])) {
            return $this->process($name, $arguments);
        }
    }

    private function process($action)
    {
        // Only accept things that look like a file name, not path or something else
        $callee = debug_backtrace()[0];
        $method = $callee['class'] . $callee['type'] . $callee['function'] . '()';
        if (preg_replace('/[^\.\w\d-]/i', '', $this->lock) !== $this->lock) {
            throw new LockException(sprintf('Invalid $lock argument \'%s\' passed in ' . $method, $this->lock));
        }
        $lock_folder = self::$path;
        $lock_file = self::$path . $this->lock . '.lock';
        clearstatcache(true, $lock_file);
        switch ($action) {
            case 'check':
                if (!file_exists($lock_file)) {
                    return false;
                }
                $contents = file_get_contents($lock_file);
                if (strpos($contents, 'expires') !== false) {
                    $contents = json_decode($contents);
                }
                if (isset($contents->expires)) {
                    $is_locked = $contents->expires > microtime(true);
                    if (!$is_locked) {
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
                if ($this->expiration) {
                    $contents['expires'] = $now + (int)$this->expiration;
                }
                if (!is_dir(self::$path) && !@mkdir(self::$path)) {
                    throw new LockException('Unable to create lock folder in ' . $method);
                }
                if (file_put_contents($lock_file, json_encode($contents), LOCK_EX) === false) {
                    throw new LockException('Unable to create ' . $this->lock . ' lock in ' . $method);
                }
            break;
            case 'destroy':
                if (file_exists($lock_file) && !@unlink($lock_file)) {
                    throw new LockException('Unable to destroy ' . $this->lock . ' lock in ' . $method);
                }
            break;
        }
        return true;
    }
}
class LockException extends Exception
{
}
