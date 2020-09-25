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

class Redirect
{
    public static function get($from)
    {
        $db = DB::getInstance();
        $db->query('SELECT * FROM ' . DB::getTable('redirects') . ' WHERE redirect_from=:from;');
        $db->bind(':from', $from);
        $redirect = $db->fetchSingle();
        return static::formatArray($redirect);
    }
    public static function getUrl($from)
    {
        if ($redirect = static::get($from)) {
            switch ($redirect['content_type']) {
                case 'album':
                    $content = Album::getSingle($redirect['content_id'], false, true);
                    $url = $content['url'];
                break;
                case 'image':
                    $content = Image::getSingle($redirect['content_id'], false, true);
                    $url = $content['url_viewer'];
                break;
                case 'user':
                    $content = User::getSingle($redirect['content_id']);
                    $url = $content['url'];
                break;
            }
            return $url;
        } else {
            return null;
        }
    }
    public static function handle($from)
    {
        if ($url = static::getUrl($from)) {
            G\redirect($url, 301);
        }
    }
    public static function insert($from, $content_id, $content_type)
    {
        DB::insert('redirects', [
            'from' => $from,
            'content_id' => $content_id,
            'content_type' => $content_type,
        ]);
    }
    public static function formatArray($object)
    {
        try {
            if ($object) {
                $output = DB::formatRow($object);
            }
            return $object ? $output : null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 400);
        }
    }
}
