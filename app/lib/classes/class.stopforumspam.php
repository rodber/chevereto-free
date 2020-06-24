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

namespace CHV;

use Exception;
use G;

use function GuzzleHttp\json_decode;

class StopForumSpam
{
    const THRESHOLD = 3;
    private $ip;
    private $email;
    private $username;

    final public function __construct($ip, $email, $username)
    {
        $this->ip = $ip;
        $this->email = $email;
        $this->username = $username;
    }

    final public function isSpam()
    {
        $json = $this->fetch();
        return $json->ip->frequency >= static::THRESHOLD || $json->email->frequency >= static::THRESHOLD || $json->username->frequency >= static::THRESHOLD;
    }

    private function fetch()
    {
        $url = 'http://api.stopforumspam.org/api?ip=' . $this->ip . '&email=' . $this->email . '&username=' . $this->username . '&json';
        $json = G\fetch_url($url);
        return json_decode($json);
    }
}
