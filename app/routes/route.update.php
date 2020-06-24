<?php
$route = function ($handler) {
	try {
		// Detect if Chevereto is installed
		if (
			!CHV\Settings::get('chevereto_version_installed') ||
			!CHV\Login::getUser()['is_admin'] ||
			!$handler::checkAuthToken($_REQUEST['auth_token'])
		) {
			G\set_status_header(403);
			$handler->template = 'request-denied';
			return;
		}
		$update_script = CHV_APP_PATH_INSTALL . 'update/updater.php';
		if (!file_exists($update_script)) {
			throw new Exception('Missing ' . G\absolute_to_relative($update_script), 100);
		}
		if (!@require_once($update_script)) {
			throw new Exception("Can't include " . G\absolute_to_relative($update_script), 101);
		}
	} catch (Exception $e) {
		G\exception_to_error($e);
	}
};
