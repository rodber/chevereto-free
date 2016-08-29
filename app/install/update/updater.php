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
use G, Exception;

if(!defined('access') or !access) die('This file cannot be directly accessed.');

try {
	if(is_null(getSetting('chevereto_version_installed'))) {
		G\set_status_header(403);
        die('403');
	}
    if(!isset($_REQUEST['action'])) {
        Render\chevereto_die(['Download the <a href="https://github.com/Chevereto/Chevereto-Free/releases/latest" target="_blank">latest release</a> from our GitHub repo', 'Upload this files to your server website folder', 'Go to /install to proceed with the installation of the update package'], strtr('This website is ready to update to Chevereto v%s but to get this update and all future updates of our main releases channel, please consider upgrading to our <a href="https://chevereto.com/pricing" target="_blank">paid version</a>. This paid version has more functionalities, updates, comes with tech support and you can update it with just one click from your dashboard panel.<br><br>You can manually update this website to the latest available <a href="https://github.com/Chevereto/Chevereto-Free" target="_blank">Chevereto Free</a> release by doing the following:', ['%s' => getSetting('update_check_notified_release')]), "Upgrade for one-click updates");
    }
	die(); // Terminate any remaining execution (if any)
} catch (Exception $e) {
    if(!isset($_REQUEST['action'])) {
        Render\chevereto_die($e->getMessage(), "This installation can't use the automatic update functionality because this server is missing some crucial elements to allow Chevereto to perform the automatic update:", "Can't perform automatic update");
    }
}