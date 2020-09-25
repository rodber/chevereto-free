<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

  /* API v1 : PLEASE NOTE

     This API v1 is currently just a bridge to port to Chevereto 3 the API from Chevereto 2.
     From now on Chevereto 2 API will be named API v1

     In future releases there will be an API v2 which will add methods like create user, create albums, etc.

  */

$route = function ($handler) {
    try {
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: application/json; charset=UTF-8');
        $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
        $params = [
            'secret'	=> CHV\getSetting('recaptcha_private_key'),
            'response'	=> $_GET['token'],
            'remoteip'	=> G\get_client_ip()
        ];
        $endpoint .= '?' . http_build_query($params);
        $fetch = G\fetch_url($endpoint);
        $json = json_decode($fetch);
        $_SESSION['isHuman'] = $json->success;
        $_SESSION['isBot'] = !$json->success;
        die($fetch);
    } catch (Exception $e) {
    }
};
