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
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function create()
    {
        $lock = DB::get('locks', ['name' => $this->name]);
        $lock = isset($lock[0]) ? $lock[0] : false;
        if ($lock) {
            $diff = G\datetime_diff($lock['expires_gmt']);
            if ($diff > 0) {
                return false;
            }
            $this->destroy();
        }
        $datetime = G\datetimegmt();
        try {
            $insert = DB::insert('locks', [
                'name' => $this->name,
                'date_gmt' => $datetime,
                'expires_gmt' => G\datetime_add($datetime, 'PT15S'),
            ]);
            return $insert !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function destroy()
    {
        if (DB::delete('locks', ['name' => $this->name]) === false) {
            throw new LockException('Unable to destroy lock ' . $this->name);
        }

        return true;
    }
}
class LockException extends Exception
{
}
