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

if(!defined('access') or !access) die('This file cannot be directly accessed.');

/*** Set some functions in the global namespace ***/

// Gettext with parsed arguments
function _s($msg, $args=NULL) {
	$msg = CHV\L10n::gettext($msg);
	if($msg && !is_null($args)) {
		$fn = is_array($args) ? 'strtr' : 'sprintf';
		$msg = $fn($msg, $args);
	}
	return $msg;
}
// Same as _s but with echo
function _se($msg, $args=NULL) {
	echo _s($msg, $args);
}

// Plural version of _s
function _n($msg, $msg_plural, $count) {
	return CHV\L10n::ngettext($msg, $msg_plural, $count);
}
// Same as _n but with echo
function _ne($msg, $msg_plural, $count) {
	echo _n($msg, $msg_plural, $count);
}