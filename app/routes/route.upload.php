<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

$route = function ($handler) {
    try {
        if (!$handler::getCond('upload_allowed')) {
            if (CHV\Login::getUser()) {
                G\set_status_header(403);
                $handler->template = 'request-denied';
                return;
            } else {
                G\redirect('login');
            }
        }
        $logged_user = CHV\Login::getUser();
        // User status override redirect
        CHV\User::statusRedirect($logged_user['status']);
        $album = null;
        if ($_GET['toAlbum']) {
            $toAlbumId = CHV\decodeID($_GET['toAlbum']);
            $album = CHV\Album::getSingle($toAlbumId, false, true, $logged_user);
            $is_owner = $album['user']['id'] && $album['user']['id'] == $logged_user['id'];
            if (!$is_owner) {
                $album = null;
            }
        }
        $handler::setVar('album', $album);
        if (CHV\getSetting('homepage_style') == 'route_upload' && $handler->request_array[0] !== 'upload') {
            $handler::setVar('doctitle', CHV\Settings::get('website_doctitle'));
            $handler::setVar('pre_doctitle', CHV\Settings::get('website_name'));
        } else {
            $handler::setVar('pre_doctitle', _s('Upload'));
        }
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
