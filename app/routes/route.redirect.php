<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

/**
 * This is an internal redirector intented to avoid spammers trying to get some
 * SEO juice by putting links all over your Chevereto website.
 *
 * It also avoids spammers to use this redirector to hang spam and whatnot in
 * third-party websites (auth_token stuff).
 *
 * The redirection is only issued if the URL was generedated by CHV\crypt().
 */
$route = function ($handler) {
    try {
        $encrypted = $_GET['to'];
        $url = CHV\decryptString($encrypted);
        $is_admin = CHV\Login::getUser()['is_admin'] == true;
        $validations = [
            filter_var($url, FILTER_VALIDATE_URL) != false,
            $handler::checkAuthToken($_GET['auth_token']) != false
            // $is_admin ?:  --> if admin doesn't need the auth token
        ];
        if (in_array(false, $validations)) {
            return $handler->issue404();
        }
        G\redirect($url, 302);
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
