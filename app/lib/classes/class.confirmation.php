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

class Confirmation
{
    public static function get($values, $sort=array(), $limit=1)
    {
        try {
            return DB::get('confirmations', $values, 'AND', $sort, $limit);
        } catch (Exception $e) {
            throw new ConfirmationException($e->getMessage(), 400);
        }
    }
    
    public static function insert($values)
    {
        if (!is_array($values)) {
            throw new ConfirmationException('Expecting array values, '.gettype($values).' given in ' . __METHOD__, 100);
        }
        
        if (!$values['status']) {
            $values['status'] = 'active';
        }
        
        $values['date'] = G\datetime();
        $values['date_gmt'] = G\datetimegmt();
        
        try {
            return DB::insert('confirmations', $values);
        } catch (Exception $e) {
            throw new ConfirmationException($e->getMessage(), 400);
        }
    }
    
    public static function update($id, $values)
    {
        try {
            return DB::update('confirmations', $values, ['id' => $id]);
        } catch (Exception $e) {
            throw new ConfirmationException($e->getMessage(), 400);
        }
    }
    
    public static function delete($values, $clause='AND')
    {
        try {
            return DB::delete('confirmations', $values, $clause);
        } catch (Exception $e) {
            throw new ConfirmationException($e->getMessage(), 400);
        }
    }
}

class ConfirmationException extends Exception
{
}
