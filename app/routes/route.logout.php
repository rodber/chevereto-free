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
		if(!$handler::checkAuthToken($_REQUEST['auth_token'])) {
			$handler->template = 'request-denied';
			return;
		}
		if(CHV\Login::isLoggedUser()) {
			CHV\Login::logout();
			unset($_SESSION['last_url']);
			G\redirect(G\get_current_url());
		} else {
			$handler::setVar('pre_doctitle', _s('Logged out'));
		}
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};