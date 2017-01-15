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

$route = function($handler) {
	try {
		$base64 = $handler->request[0];
		$url = base64_decode($base64);
		if(!filter_var($url, FILTER_VALIDATE_URL)) {
			return $handler->issue404();
		}
		G\redirect($url, 302);
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};