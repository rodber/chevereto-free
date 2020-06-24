<?php
$route = function ($handler) {
	try {
		// Detect if Chevereto is installed
		if (CHV\Settings::get('chevereto_version_installed')) {
			// Must be an admin
			if (!CHV\Login::getUser()['is_admin']) {
				G\redirect();
			}
		}
		$install_script = CHV_APP_PATH_INSTALL . 'installer.php';
		if (!file_exists($install_script)) {
			throw new Exception('Missing ' . G\absolute_to_relative($install_script), 100);
		}
		if (!@require_once($install_script)) {
			throw new Exception("Can't include " . G\absolute_to_relative($install_script), 101);
		}
	} catch (Exception $e) {
		G\exception_to_error($e);
	}
};
