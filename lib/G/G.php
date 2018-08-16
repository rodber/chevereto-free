<?php

/* --------------------------------------------------------------------

  G\ library
  http://gbackslash.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

namespace G;

if(!defined('access') or !access) die("This file cannot be directly accessed.");

define('G_VERSION', '1.0.42');

// Error reporting setup
@ini_set('log_errors', TRUE);
error_reporting(E_ALL ^ E_NOTICE);

// Set default locale
setlocale(LC_ALL, 'en_US.UTF8');

// Set encoding to UTF-8
@ini_set('default_charset', 'utf-8');

// Set G\ paths and files
define('G_ROOT_PATH', rtrim(str_replace('\\','/', dirname(dirname(__DIR__))), '/') . '/');
define('G_ROOT_PATH_RELATIVE', rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/') . '/');
define('G_ROOT_LIB_PATH', G_ROOT_PATH . 'lib/');
define('G_PATH', G_ROOT_LIB_PATH . 'G/');
define('G_PATH_CLASSES', G_PATH . 'classes/');
define('G_FILE_FUNCTIONS', G_PATH . 'functions.php');
define('G_FILE_FUNCTIONS_RENDER', G_PATH . 'functions.render.php');

// Set app paths
define('G_APP_PATH', G_ROOT_PATH . 'app/');
define('G_APP_PATH_LIB', G_APP_PATH . 'lib/');
define('G_APP_PATH_ROUTES', G_APP_PATH . 'routes/');
define('G_APP_PATH_ROUTES_OVERRIDES', G_APP_PATH_ROUTES . 'overrides/');
define('G_APP_PATH_CLASSES', G_APP_PATH_LIB . 'classes/');
define('G_APP_FILE_FUNCTIONS', G_APP_PATH_LIB . 'functions.php');
define('G_APP_FILE_FUNCTIONS_RENDER', G_APP_PATH_LIB . 'functions.render.php');

define('G_APP_SETTINGS_FILE_ERROR', '<br />There are errors in the <strong>%%FILE%%</strong> file. Change the encodig to "UTF-8 without BOM" using Notepad++ or any similar code editor and remove any character before <span style="color: red;">&lt;?php</span>');

// Include the static app config file
(file_exists(G_APP_PATH . 'settings.php')) ? require_once(G_APP_PATH . 'settings.php') : die("G\: Can't find app/settings.php");
if(headers_sent()) die(str_replace('%%FILE%%', 'app/settings.php', G_APP_SETTINGS_FILE_ERROR)); // Stop on premature headers

// TZ failover
$tz = @date_default_timezone_get();
$dtz = @date_default_timezone_set($tz);
if(!$dtz && !@date_default_timezone_set('America/Santiago')) {
	die(strtr('Invalid timezone identifier: %i. Configure php.ini with a valid timezone identifier %l', ['%i' => $tz, '%l' => 'http://php.net/manual/en/timezones.php']));
}

// Session hack
if($settings['session.save_path']) {
	session_save_path($settings['session.save_path']);
}

// Can work with sessions?
if(!@session_start()) die("G\: Sessions are not working on this server (session_start).");

// Is session save path OK? (you won't believe how many people has session issues!)
$session_save_path = @realpath(session_save_path());
if($session_save_path) { // realpath on this needs pre-webroot directories access
	foreach(['write'] as $k) {
		$fn = 'is_' . $k . 'able';
		if(!$fn($session_save_path)) $session_errors[] = $k;
	}
	if(isset($session_errors)) die(strtr("G\: Sessions are not working on this server due to missing %s permission on session save path (%f session.save_path).", ['%s' => implode('/', $session_errors), '%f' => $settings['session.save_path'] ? 'app/settings.php' : 'php.ini']));
}

// Are sessions working properly?
$_SESSION['G'] = TRUE;
if(!$_SESSION['G']) die("G\: Sessions are not working properly. Check for any conflicting server setting.");

// Set the starting execution time
define('G_APP_TIME_EXECUTION_START', microtime(true));

// Include G\ core functions
(file_exists(__DIR__ . '/functions.php')) ? require_once(__DIR__ . '/functions.php') : die("G\: Can't find <strong>" . __DIR__ . '/functions.php' . '</strong>. Make sure that this file exists.');
if(file_exists(__DIR__ . '/functions.render.php')) {
	require_once(__DIR__ . '/functions.render.php');
}

if(isset($settings) && $settings['error_reporting'] === false) {
	error_reporting(0);
}

// Set the default timezone
if(isset($settings['default_timezone']) && is_valid_timezone($settings['default_timezone'])) {
	if(!@date_default_timezone_set($settings['default_timezone'])) {
		die(strtr("G\: Can't set %s timezone on line %l", ['%s' => $settings['default_timezone'], '%l' => __LINE__ - 1]));
	}
}

// Set the system environment
if(isset($settings['environment'])) {
	define('G_APP_ENV', $settings['environment']);
}

// Set the HTTP definitions
define('G_HTTP_HOST', $_SERVER['HTTP_HOST']);
define('G_HTTP_PROTOCOL', 'http' . ((((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ||  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || $settings['https']) ? 's' : NULL));

// La cumbia me divierte y mesita

// Fix some $_SERVER vars
$_SERVER['SCRIPT_FILENAME'] = forward_slash($_SERVER['SCRIPT_FILENAME']);
$_SERVER['SCRIPT_NAME'] = forward_slash($_SERVER['SCRIPT_NAME']);
// Fix CloudFlare REMOTE_ADDR
if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

// Inherit application definitions
if(file_exists(G_APP_PATH . 'app.php')) {
	require_once(G_APP_PATH . 'app.php');
}

// Set the DB constants
foreach(['host', 'port', 'name', 'user', 'pass', 'driver', 'pdo_attrs'] as $k) {
	define('G_APP_DB_' . strtoupper($k), isset($settings['db_' . $k]) ? (is_array($settings['db_' . $k]) ? serialize($settings['db_' . $k]) : $settings['db_' . $k]) : NULL);
}

// Include app functions
(file_exists(G_APP_FILE_FUNCTIONS)) ? require_once(G_APP_FILE_FUNCTIONS) : die("G\: Can't find <strong>" . G_APP_FILE_FUNCTIONS . '</strong>. Make sure that this file exists.');
if(file_exists(G_APP_FILE_FUNCTIONS_RENDER)) {
	require_once(G_APP_FILE_FUNCTIONS_RENDER);
}

// Set the URLs
define("G_ROOT_URL", G_HTTP_PROTOCOL . "://".G_HTTP_HOST . G_ROOT_PATH_RELATIVE); // http(s)://www.mysite.com/chevereto/
define("G_ROOT_LIB_URL", absolute_to_url(G_ROOT_LIB_PATH));
define("G_APP_LIB_URL", absolute_to_url(G_APP_PATH_LIB));

// Define the app theme
define('G_APP_PATH_THEMES', G_APP_PATH . 'themes/');
if(!file_exists(G_APP_PATH_THEMES)) {
	die("G\: Theme path doesn't exists!");
}

if(isset($settings['theme']) and file_exists(G_APP_PATH_THEMES . $settings['theme'])) {
	define('G_APP_PATH_THEME', G_APP_PATH_THEMES . $settings['theme'].'/');
	define('BASE_URL_THEME', absolute_to_url(G_APP_PATH_THEME));
}