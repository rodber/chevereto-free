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
