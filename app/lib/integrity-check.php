<?php

/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
			<inbox@rodolfoberrios.com>

  Copyright (C) 2013 Rodolfo Berrios A. All rights reserved.
  
  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */

namespace CHV;
use G;
  
if(!defined('access') or !access) die('This file cannot be directly accessed.');

/**
 * SYSTEM INTEGRITY CHECK
 * Welcome to the jungle of non-standard PHP setups
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */

function check_system_integrity() {

	$settings = Settings::get();
	
	/*** Check server requirements ***/
	
	// Try to fix sessions in crap setups (OVH)
	@ini_set('session.gc_divisor', 100);
	@ini_set('session.gc_probability', TRUE);
	@ini_set('session.use_trans_sid', FALSE);
	@ini_set('session.use_only_cookies', TRUE);
	@ini_set('session.hash_bits_per_character', 4);
	
	$missng_fn_tpl = '%n (<a href="http://php.net/manual/en/function.%u.php">%f</a>) function is disabled in this server. This function must be enabled in your PHP configuration (php.ini) and/or you must add this missing function.';
	
	if(version_compare(PHP_VERSION, '5.4.0', '<')) {
		$install_errors[] = 'This server is currently running PHP version '.PHP_VERSION.' and Chevereto needs at least PHP 5.5.0 to run. You need to update PHP in this server.';
	}
	if(ini_get('allow_url_fopen') !== 1 && !function_exists('curl_init')) {
		$install_errors[] = "cURL isn't installed and allow_url_fopen is disabled. Chevereto needs one of these to perform HTTP requests to remote servers.";
	}
		
	if(preg_match('/apache/i', $_SERVER['SERVER_SOFTWARE']) && function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
		$install_errors[] = 'Apache <a href="http://httpd.apache.org/docs/2.1/rewrite/rewrite_intro.html" target="_blank">mod_rewrite</a> is not enabled in this server. This must be enabled to run Chevereto.';
	}
	
	if(!extension_loaded('gd') && !function_exists('gd_info')) {
		$install_errors[] = '<a href="http://www.libgd.org" target="_blank">GD Library</a> is not enabled in this server. GD is needed to perform image handling.';
	} else {
		$imagetype_fail = 'image support is not enabled in your current PHP setup (GD Library).';
		if(!imagetypes() & IMG_PNG)  $install_errors[] = 'PNG ' . $imagetype_fail;
		if(!imagetypes() & IMG_GIF)  $install_errors[] = 'GIF ' . $imagetype_fail;
		if(!imagetypes() & IMG_JPG)  $install_errors[] = 'JPG ' . $imagetype_fail;
		if(!imagetypes() & IMG_WBMP) $install_errors[] = 'BMP ' . $imagetype_fail;
	}
	
	if(!extension_loaded('pdo')) {
		$install_errors[] = 'PHP Data Objects (<a href="http://www.php.net/manual/book.pdo.php">PDO</a>) is not loaded in this server. PDO is needed to perform database operations.';
	}
	if(!extension_loaded('pdo_mysql')) {
		$install_errors[] = 'PDO MySQL Functions (<a href="http://www.php.net/manual/ref.pdo-mysql.php" target="_blank">PDO_MYSQL</a>) is not loaded in this server. PDO_MYSQL is needed to work with a MySQL database.';
	}
    if(!extension_loaded('mbstring')) {
		$install_errors[] = 'Multibyte string (<a href="http://php.net/manual/en/book.mbstring.php" target="_blank">mbstring</a>) is not loaded in this server. mbstring is needed to handle multibyte strings.';
	}
	if(!function_exists('utf8_encode')) {
		$install_errors[] = strtr($missng_fn_tpl, ['%n' => 'UTF-8 encode', '%f' => 'utf8_encode', '%u' => 'utf8-encode']);
	}
	if(!function_exists('utf8_decode')) {
		$install_errors[] = strtr($missng_fn_tpl, ['%n' => 'UTF-8 decode', '%f' => 'utf8_decode', '%u' => 'utf8-decode']);
	}
	
	/*** Folders check ***/
	
	// Check the writting folders
	$writting_paths = array(CHV_PATH_IMAGES, CHV_PATH_CONTENT);
	foreach($writting_paths as $v) {
		if(!file_exists($v)) { // Exists?
			if(!@mkdir($v)) {
				$install_errors[] = "<code>".G\absolute_to_relative($v)."</code> doesn't exists. Make sure to upload this.";
			}
		} else { // Can write?
			if(!is_writable($v)) {
				$install_errors[] = 'No write permission in <code>'.G\absolute_to_relative($v).'</code> directory. Chevereto needs to be able to write in this directory.';
			}
		}
	}
	
	/*** System template file check ***/
	$system_template = CHV_APP_PATH_SYSTEM . 'template.php';
	if(!file_exists($system_template)) {
		$install_errors[] = "<code>".G\absolute_to_relative($system_template)."</code> doesn't exists. Make sure to upload this.";
	}
	
	/*** License file ***/
	$license_file = G_APP_PATH . 'license/check.php';
	if(!file_exists($license_file)) {
		$install_errors[] = "Can't find <code>".G\absolute_to_relative($license_file)."</code> file. Make sure to upload the <code>app/license</code> folder.";
	} else {
		require_once($license_file);
	}
	
	/*** .htaccess checks (only for Apache) ***/

	if(G\is_apache()) {
		// Check for the root .htaccess file
		if(!file_exists(G_ROOT_PATH . '.htaccess')) {
			$install_errors[] = "Can't find root <code>.htaccess</code> file. Re-upload this file and take note that in some computers this file could be hidden in your local folder.";
		}
		// Check for the other .htaccess files
		$htaccess_files = array(CHV_PATH_IMAGES, G_APP_PATH);
		foreach($htaccess_files as $dir) {
			if(file_exists($dir . '.htaccess')) continue;
			switch($dir) {
				case CHV_PATH_IMAGES:
					$rules = 'static';
				break;
				case G_APP_PATH:
					$rules = 'deny_php';
				break;
			}
			$htaccess_file = G\generate_htaccess($rules, $dir, NULL, true);
			if(!$htaccess_file and $dir == G_APP_PATH) {
				$install_errors[] = "Can't create " . G\absolute_to_relative($dir) . '.htaccess file. The file must be uploaded manually to this path.<br>
				Alternatively you can create the file yourself with this contents:<br><br>
				<pre><code>'.htmlspecialchars($htaccess_file).'</code></pre>';
			}
		}
	}

	if(count($install_errors) > 0){
		Render\chevereto_die($install_errors);
	}
}