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
