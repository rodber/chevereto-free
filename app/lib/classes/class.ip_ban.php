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

class Ip_ban
{
    public static function getSingle($args=[])
    {
        try {
            $args = array_merge([
                'ip' => G\get_client_ip()
            ], $args);

            $db = DB::getInstance();

            $query = 'SELECT * FROM ' . DB::getTable('ip_bans') . ' WHERE ';

            if ($args['id']) {
                $query .= 'ip_ban_id = :id;';
            } else {
                $query .= ':ip LIKE ip_ban_ip AND (ip_ban_expires_gmt > :now OR ip_ban_expires_gmt IS NULL) ORDER BY ip_ban_id DESC;'; // wilcard are stored as % but displayed as *
            }

            $db->query($query);

            if ($args['id']) {
                $db->bind(':id', $args['id']);
            } else {
                $db->bind(':ip', $args['ip']);
                $db->bind(':now', G\datetimegmt());
            }

            $ip_ban = $db->fetchSingle();
            if ($ip_ban) {
                $ip_ban = DB::formatRow($ip_ban, 'ip_ban');
                self::fill($ip_ban);
                return $ip_ban;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new Ip_banException($e->getMessage(), 400);
        }
    }

    public static function getAll()
    {
        try {
            $ip_bans_raw = DB::get('ip_bans', 'all');
            $ip_bans = [];
            if ($ip_bans_raw) {
                foreach ($ip_bans_raw as $ip_ban) {
                    $idx = $ip_ban['ip_ban_id'];
                    $ip_bans[$idx] = DB::formatRow($ip_ban, 'ip_ban');
                    self::fill($ip_bans[$idx]);
                }
            }
            return $ip_bans;
        } catch (Exception $e) {
            throw new Ip_banException($e->getMessage(), 400);
        }
    }

    public static function delete($args=[])
    {
        try {
            return DB::delete('ip_bans', $args);
        } catch (Exception $e) {
            throw new Ip_banException($e->getMessage(), 400);
        }
    }

    public static function update($where=[], $values=[])
    {
        try {
            if ($values['ip']) {
                $values['ip'] = str_replace('*', '%', $values['ip']);
            }
            return DB::update('ip_bans', $values, $where);
        } catch (Exception $e) {
            throw new Ip_banException($e->getMessage(), 400);
        }
    }

    public static function insert($args=[])
    {
        try {
            $args['ip'] = str_replace('*', '%', $args['ip']);
            return DB::insert('ip_bans', $args);
        } catch (Exception $e) {
            throw new Ip_banException($e->getMessage(), 400);
        }
    }

    public static function fill(&$ip_ban)
    {
        $ip_ban['ip'] = str_replace('%', '*', $ip_ban['ip']);
    }

    public static function validateIP($ip, $wilcards=true)
    {
        $validate = true;
        if ($wilcards) {
            $base_ip = str_replace('*', '0', $ip);
            if (!G\is_valid_ip($ip) && !G\is_valid_ip($base_ip)) {
                $validate = false;
            }
        } else {
            if (!G\is_valid_ip($ip)) {
                $validate = false;
            }
        }
        if (!$validate) {
            throw new Ip_banException('Invalid IP address');
        }
        return true;
    }
}

class Ip_banException extends Exception
{
}
