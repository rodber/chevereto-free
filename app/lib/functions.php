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
use G, DirectoryIterator, Exception;

if(!defined("access") or !access) die("This file cannot be directly accessed.");

// Inspired from http://stackoverflow.com/a/18602474
// L10n version of G\time_elapsed_string
function time_elapsed_string($datetime, $full=false) {

	$now = new \DateTime(G\datetimegmt());
	$ago = new \DateTime($datetime);
	$diff = $now->diff($ago);

	$diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;

	$string = [
		'y' => _s('year'),
		'm' => _s('month'),
		'w' => _s('week'),
		'd' => _s('day'),
		'h' => _s('hour'),
		'i' => _s('minute'),
		's' => _s('second'),
	];
	foreach ($string as $k => &$v) {
		if ($diff->$k) {

			$times = [
				'y' => _n('year', 'years', $diff->$k),
				'm' => _n('month', 'months', $diff->$k),
				'w' => _n('week', 'weeks', $diff->$k),
				'd' => _n('day', 'days', $diff->$k),
				'h' => _n('hour', 'hours', $diff->$k),
				'i' => _n('minute', 'minutes', $diff->$k),
				's' => _n('second', 'seconds', $diff->$k),
			];

			$v = $diff->$k . ' ' . $times[$k];

		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);

	return count($string) > 0 ? _s('%s ago', implode(', ', $string)) : _s('moments ago');

}

function missing_values_to_exception($object, $exception=Exception, $values_array, $code=100) {
	if(!is_object($object)) return;
	for($i=0; $i<count((array) $values_array); $i++) {
		if(!G\check_value($object->{$values_array[$i]})) {
			throw new $exception('Missing $' . $values_array[$i], ($code+$i));
			break;
		}
	}
}

function system_notification_email($args=[]) {
	try {
		$subject = 'System notification: ' . $args['subject'] . ' [' . G\get_base_url() . ']';
		$report = $args['message'];
		send_mail(getSetting('email_incoming_email'), $subject, $report);
	} catch(Exception $e) {
		error_log($e);
	}
}

// Work-in-progress 2.0 baby!
function send_mail($to, $subject, $body) {
	$own_name = __FUNCTION__ . '()';
	$args = ['to', 'subject', 'body'];
	foreach(func_get_args() as $k => $v) {
		if(!$v) {
			throw new Exception('Missing $'.$args[$k].' in '. $own_name);
		}
	}

	// Bridge implementation
	if(is_array($to)) {
		$aux = $to;
		$to = $aux['to'];
		$from = $aux['from'];
		$reply_to = $aux['reply-to'];
	} else {
		$from = [getSettings()['email_from_email'], getSettings()['email_from_name']];
		$reply_to = NULL;
	}

	if(!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		throw new Exception('Invalid email in ' . $own_name);
	}
	foreach(['email_from_email', 'email_from_name'] as $v) {
		if(!getSettings()[$v]) {
			throw new Exception('Invalid $'.$v.' setting in ' . $own_name);
		}
	}
	$body = trim($body);
	try {
		$mail = new \Mailer();
		$alt_body = $mail->html2text($body);
		$mail->CharSet = 'UTF-8';
		$mail->Mailer = getSettings()['email_mode'];
		if($mail->Mailer == 'smtp') {
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = getSettings()['email_smtp_server_security'];
			$mail->SMTPAutoTLS = in_array(getSettings()['email_smtp_server_security'], ['ssl', 'tls']);
			$mail->Port	= getSettings()['email_smtp_server_port'];
			$mail->Host = getSettings()['email_smtp_server'];
			$mail->Username = getSettings()['email_smtp_server_username'];
			$mail->Password = getSettings()['email_smtp_server_password'];
		}
		$mail->Timeout = 30;
		$mail->Subject = $subject;
		if($body != $alt_body) {
			$mail->IsHTML(true);
			$mail->Body = $mail->normalizeBreaks($body);
			$mail->AltBody = $mail->normalizeBreaks($alt_body);
		} else {
			$mail->Body = $body;
		}
		$mail->addAddress($to);
		if($reply_to and is_array($reply_to)) {
			foreach($reply_to as $v) {
				$mail->addReplyTo($v);
			}
		}
		$mail->setFrom($from[0], $from[1]);
		if($mail->Send()) {
			return true;
		} else {
			throw new Exception($mail->DbgOut, 300);
		}
	} catch (Exception $e) {
		throw new Exception($e->getMessage(), $e->getCode());
	}

}

/**
 * GET AND FETCH SOME DATA
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */

function get_chevereto_version($full=true) {
	return G\get_app_version($full);
}

// Move to render?
function getSettings($safe=false) {
	$settings = Settings::get();
	return $safe ? G\safe_html($settings) : $settings;
}
// Move to render?
function get_chv_default_settings($safe=false) {
	$defaults = Settings::getDefaults();
	return $safe ? G\safe_html($defaults) : $defaults;
}
// Move to render?
function getSetting($value='', $safe=false) {
	$return = getSettings()[$value];
	return $safe ? G\safe_html($return) : $return;
}
// Move to render?
function get_chv_default_setting($value='', $safe=false) {
	$return = get_chv_default_settings()[$value];
	return $safe ? G\safe_html($return) : $return;
}

function getStorages() {
	$storages = DB::get('storages', 'all');
	if($storages) {
		foreach($storages as $k => $v) {
			$storages[$k] = DB::formatRow($v);
		}
		$return = $storages;
	} else {
		$return = false;
	}
	return $return;
}

function get_banner_code($banner, $safe_html=true) {
	if(strpos($banner, 'banner_') !== 0) {
		$banner = 'banner_' . $banner;
	};
	$banner_code = Settings::get($banner);
	if($safe_html) {
		$banner_code = G\safe_html($banner_code);
	}
	if($banner_code) {
		return $banner_code;
	}
}

function getSystemNotices() {
	$system_notices = [];
	// Don't notify if system files are newer or match notified release
	if(getSetting('update_check_display_notification') && (version_compare(getSetting('update_check_notified_release'), getSetting('chevereto_version_installed'), '>') && version_compare(getSetting('update_check_notified_release'), G_APP_VERSION, '>'))) {
		$system_notices[] = _s('There is an update available for your system. Go to %s to download and install this update.', '<a href="'.G\get_base_url('dashboard?checkUpdates').'">'._s('Dashboard').'</a>');
	}
	if(version_compare(G_APP_VERSION, getSetting('chevereto_version_installed'), '>')) {
		$system_notices[] = _s('System database is outdated. You need to run the <a href="%s">update</a> tool.', G\get_base_url('install'));
	}
	if(getSetting('maintenance')) {
		$system_notices[] = _s('Website is in maintenance mode. To revert this setting go to <a href="%s">Dashboard > Settings</a>.', G\get_base_url('dashboard/settings/system'));
	}
	// Just for production + demo
	if(!in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']) && !in_array($_SERVER['SERVER_NAME'], ['demo.chevereto.com'])) {
		if(getSetting('error_reporting')) {
			$system_notices[] = _s("You should disable PHP error reporting for production enviroment. Go to <a href='%s'>System settings</a> to revert this setting.",  G\get_base_url('dashboard/settings/system'));
		}
		if(preg_match('/@chevereto\.com$/', getSetting('email_from_email')) || preg_match('/@chevereto\.com$/', getSetting('email_incoming_email'))) {
			$system_notices[] = _s("You haven't changed the default email settings. Go to <a href='%s'>Email settings</a> to fix this.", G\get_base_url('dashboard/settings/email'));
		}
	}
	return $system_notices;
}

function hashed_token_info($public_token_format) {
	$explode = explode(":", $public_token_format);
	return array(
		"id"		 => decodeID($explode[0]),
		"id_encoded" => $explode[0],
		"token"		 => $explode[1]
	);
}

function generate_hashed_token($id, $token="") {
	$token = G\random_string(rand(128, 256));
	$hash = password_hash($token, PASSWORD_BCRYPT);
	return array(
		"token"					=> $token,
		"hash"					=> $hash,
		"public_token_format"	=> encodeID($id) . ':' . $token
	);
}

function check_hashed_token($hash, $public_token_format) {
	$public_token = hashed_token_info($public_token_format);
	return password_verify($public_token["token"], $hash);
}

function recaptcha_check() {
	// V2 ONLY
	$endpoint = 'https://www.google.com/recaptcha/api/siteverify';
	$params = [
		'secret'	=> getSetting('recaptcha_private_key'),
		'response'	=> $_POST['g-recaptcha-response'],
		'remoteip'	=> G\get_client_ip()
	];

	$endpoint .= '?' . http_build_query($params);
	$re_api = json_decode(G\fetch_url($endpoint));
	// Mimic old reCaptcha API return
	return (object)['is_valid' => (bool)$re_api->success];
}

function must_use_recaptcha($val, $max="") {
	if($max == "" || !is_int($max)) {
		$db_max = getSetting('recaptcha_threshold');
		$max = isset($db_max) ? $db_max : 5;
	}
	return $val >= $max;
}

function is_max_invalid_request($val, $max='') {
	if($max == '' || !is_int($max)) {
		$max = CHV_MAX_INVALID_REQUESTS_PER_DAY;
	}
	return $val > $max;
}

// BCMath workaroud
if (!function_exists('bcdiv')) {
	function bcdiv( $dividend, $divisor ) {
	   $quotient = floor( $dividend/$divisor );
	   return $quotient;
	}
	function bcmod( $dividend, $modulo ) {
	   $remainder = $dividend%$modulo;
	   return $remainder;
	}
	function bcmul( $left, $right ) {
	   return $left * $right;
	}
	function bcadd( $left, $right ) {
	   return $left + $right;
	}
	function bcpow( $base, $power ) {
	   return pow( $base, $power );
	}
}

/**
 * LANGUAGE
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */

function get_translation_table() {
	return L10n::getTranslation();
}

function get_language_used() {
	return get_available_languages()[L10n::getStatic('locale')];
}

function get_available_languages() {
	return L10n::getAvailableLanguages();
}

function get_enabled_languages() {
	return L10n::getEnabledLanguages();
}

function get_disabled_languages() {
	return L10n::getDisabledLanguages();
}

/**
 * CRYPT
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */

/*
 * cheveretoID
 * Encode/decode an id
 *
 * @author   Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author   Simon Franz
 * @author   Deadfish
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link   http://kevin.vanzonneveld.net/
 *
 * http://kvz.io/blog/2009/06/10/create-short-ids-with-php-like-youtube-or-tinyurl/
 *
 */

function cheveretoID($in, $action="encode") {
	global $cheveretoID;
	$index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$salt = getSetting('crypt_salt');
	$id_padding = intval(getSetting('id_padding'));

	// Use a stock version of the hashed values (faster execution)
	if(isset($cheveretoID)) {
		$passhash = $cheveretoID['passhash'];
		$p = $cheveretoID['p'];
		$i = $cheveretoID['i'];
	} else {

		for($n = 0; $n<strlen($index); $n++) {
			$i[] = substr($index,$n ,1);
		}

		$passhash = hash('sha256',$salt);
		$passhash = (strlen($passhash) < strlen($index)) ? hash('sha512',$salt) : $passhash;

		for($n=0; $n < strlen($index); $n++) {
			$p[] =  substr($passhash, $n ,1);
		}

		// Stock the crypting thing to don't do it every time
		$cheveretoID = [
			'passhash'	=> $passhash,
			'p'			=> $p,
			'i'			=> $i
		];
	}

	array_multisort($p, SORT_DESC, $i);
	$index = implode($i);

	$base  = strlen($index);

	if($action == 'decode') {
		// Digital number  <<--  alphabet letter code
		$out = 0;
		$len = strlen($in) - 1;
		for ($t = 0; $t <= $len; $t++) {
		  $bcpow = bcpow($base, $len - $t);
		  $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
		}
		$out = sprintf("%d", $out);
		// $out = substr($out, 0, strpos($out, '.'));
		if($id_padding > 0) {
			$out = (string) ($out / $id_padding); // Always return as string
		}
	} else {
		// Digital number  -->>  alphabet letter code
		if($id_padding > 0) {
			$in = $in * $id_padding;
		}
		$out = '';
		for ($t = floor(log((float)$in, $base)); $t >= 0; $t--) {
			$bcp = bcpow($base, $t);
			$a   = floor($in / $bcp) % $base;
			$out = $out . substr($index, $a, 1);
			$in  = $in - ($a * $bcp);
		}
	}

	return $out;
}

// Shorthand for cheveretoID encode
function encodeID($var) {
	return cheveretoID($var, "encode");
}

// Shorthand for cheveretoID decode
function decodeID($var) {
	return cheveretoID($var, "decode");
}

// Linkify stuff to the internal redirector
function linkify_redirector($text) {
	return G\linkify_safe($text, ['callback' => function($url, $caption, $options) {
		return '<a href="' . get_redirect_url($url) . '" '. $options['attr']. '>' . $caption . '</a>';
	}]);
}

// A simple base64 encode, perfect to avoid SEO bitches
function get_redirect_url($url) {
	return G\get_base_url('redirect/' . base64_encode($url));
}

/**
 * Get some URLs
 */
function get_content_url($sub) {
	return G\absolute_to_url(CHV_PATH_CONTENT . $sub, CHV_ROOT_URL_STATIC);
}

function get_system_image_url($filename) {
	return get_content_url('images/system/'.$filename);
}

function get_users_image_url($filename) {
	return get_content_url('images/users/'.$filename);
}

/**
 * Some G\ overrides
 */
function get_image_fileinfo($file) {
	$extension = G\get_file_extension($file);
	$return = [
		'filename'	=> basename($file), // image.jpg
		'name'		=> basename($file, '.' . $extension), // image
		'mime'		=> G\extension_to_mime($extension),
		'extension'	=> $extension,
		'url' 		=> G\is_url($file) ? $file : G\absolute_to_url($file)
	];
	if(!G\is_url($file) && defined('CHV_ROOT_URL_STATIC')) {
		$return['url'] = preg_replace('#'.G_ROOT_URL.'#', CHV_ROOT_URL_STATIC, $return['url'], 1);
	}
	return $return;
}

/**
 * Internal uploads
 */
function upload_to_content_images($source, $what) {
	try {

		if(!defined('CHV_PATH_CONTENT_IMAGES_SYSTEM')) {
			throw new Exception('Outdated app/loader.php', 100);
		}

		if(!file_exists(CHV_PATH_CONTENT_IMAGES_SYSTEM) && !@mkdir(CHV_PATH_CONTENT_IMAGES_SYSTEM, 0755, true)) {
			throw new Exception(sprinf("Target upload directory %s doesn't exists.", G\absolute_to_relative(CHV_PATH_CONTENT_IMAGES_SYSTEM)), 101);
		}

		if(!is_writable(CHV_PATH_CONTENT_IMAGES_SYSTEM)) {
			throw new Exception(sprintf("No write permission in %s", G\absolute_to_relative(CHV_PATH_CONTENT_IMAGES_SYSTEM)), 102);
		}

		$typeArr = [
			'favicon_image'	=> [
				'name' => 'favicon',
				'type' => 'image'
			],
			'logo_vector'	=> [
				'name' => 'logo',
				'type' => 'file'
			],
			'logo_image'	=> [
				'name' => 'logo',
				'type' => 'image'
			],
			'watermark_image' => [
				'name' => 'watermark',
				'type' => 'image'
			],
			'consent_screen_cover_image' => [
				'name'	=> 'consent-screen_cover',
				'type'	=> 'image'
			],
			'homepage_cover_image' => [
				'name'	=> 'home_cover',
				'type'	=> 'image'
			]
		];

		if(G\starts_with('homepage_cover_image_', $what)) {
			$cover_handle = str_replace('homepage_cover_image_', NULL, $what);
			if($cover_handle == 'add') {
				$remove_old = FALSE;
			} else {
				$change_cover = TRUE;
				$db_filename = getSetting('homepage_cover_images')[$cover_handle]['basename'];
			}
			$typeArr[$what] = $typeArr['homepage_cover_image'];
		}

		foreach(['logo_vector', 'logo_image'] as $k) {
			$typeArr[$k . '_homepage'] = array_merge($typeArr[$k], ['name' => 'logo_homepage']);
		}
		foreach($typeArr as $k => &$v) {
			$v['name'] .= '_' . number_format(round(microtime(TRUE) * 1000), 0, '', '') . '_' . G\random_string(6); // prevent hard cache issues
		}

		$name = $typeArr[$what]['name'];

		if($typeArr[$what]['type'] == 'image') {

			$fileinfo = @G\get_image_fileinfo($source['tmp_name']);

			// Pre-validations
			switch($what) {
				case 'favicon_image':
					if(!$fileinfo['ratio']) {
						throw new Exception('Invalid favicon image.', 200);
					}
					if($fileinfo['ratio'] != 1) {
						throw new Exception('You need to use a square image for the favicon.', 210);
					}
				break;
				case 'watermark_image':
					if($fileinfo['extension'] !== 'png') {
						throw new Exception('Invalid watermark image.', 200);
					}
				break;
			}

			$upload = new Upload;
			$upload->setSource($source);
			$upload->setDestination(CHV_PATH_CONTENT_IMAGES_SYSTEM);
			$upload->setFilename($name);
			if(in_array($what, ['homepage_cover_image_add', 'homepage_cover_image', 'consent_screen_cover_image'])) {
				$upload->setOption('max_size', Settings::get('true_upload_max_filesize'));
			}
			$upload->exec();
			$uploaded = $upload->uploaded;

		} else {

			// Check file error
			 switch ($source['error']) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new Exception('No file sent.', 500);
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new Exception('Exceeded filesize limit.', 501);
				default:
					throw new Exception('Unknown errors.', 502);
			}

			$file_contents = @file_get_contents($source['tmp_name']);
			if(!$file_contents) {
				throw new Exception("Can't read uploaded file content.", 500);
			}

			if(strpos($file_contents, '<!DOCTYPE svg PUBLIC') == false and strpos($file_contents, '<svg') == false) {
				throw new Exception("Uploaded file isn't an SVG.", 300);
			}

			$filename = $name . G\random_string(8) . '.svg';
			$destination = CHV_PATH_CONTENT_IMAGES_SYSTEM . $filename;

			if(!@move_uploaded_file($source['tmp_name'], $destination)) {
				throw new Exception("Can't move uploaded file to its destination.", 500);
			}

			$uploaded = [
				'file' => $destination,
				'filename' => $filename,
				'fileinfo' => [
					'extension' => 'svg',
					'filename' => $filename
				]
			];
		}

		$filename = $name . '.' . $uploaded['fileinfo']['extension'];
		$file = str_replace($uploaded['fileinfo']['filename'], $filename, $uploaded['file']);

		if(!@rename($uploaded['file'], $file)) {
			throw new Exception("Can't rename uploaded " . $name . " file", 500);
		}

		$remove_old = isset($remove_old) ? $remove_old : TRUE;

		if(!isset($db_filename) || empty($db_filename)) {
			$db_filename = getSetting($what);
		}
		$db_file = CHV_PATH_CONTENT_IMAGES_SYSTEM . $db_filename;

		if(in_array($what, ['logo_vector_homepage', 'logo_image_homepage']) && !G\starts_with('logo_homepage', $db_filename)) {
			$remove_old = FALSE;
		}

		if($remove_old && !G\starts_with('default/', $db_filename) && $db_filename != $filename && is_readable($db_file) && !@unlink($db_file)) {
			throw new Exception("Can't remove old ".$name." file", 500);
		}

		if(isset($cover_handle)) {
			$what = 'homepage_cover_image';
			$homepage_cover_image = getSetting($what);
			if($cover_handle == 'add') {
				$filename = (isset($homepage_cover_image) ? $homepage_cover_image : getSetting('homepage_cover_images')[0]['basename']) . ',' . $filename;
			} else {
				$filename = isset($homepage_cover_image) ? str_replace($db_filename, $filename, getSetting('homepage_cover_image')) : $filename;
			}
			$filename = trim($filename, ',');
			$homecovers = [];
			foreach(explode(',', $filename) as $v) {
				$homecovers[] = [
					'basename'	=> $v,
					'url'		=> get_system_image_url($v)
				];
			}
		}

		Settings::update([$what => $filename]);

		if(isset($cover_handle)) {
			Settings::setValue('homepage_cover_images', $homecovers);
		}

	} catch(Exception $e) {
		throw new Exception($e->getMessage(), $e->getCode());
	}
}

function isSafeToExecute($max_execution_time=NULL, $options=[]) {
	if(is_null($max_execution_time)) {
		$max_execution_time = ini_get('max_execution_time');
	}
	$executed_time = pow(10, -5) * (microtime(true) - G_APP_TIME_EXECUTION_START);
	$options = array_merge(['safe_time' => 5], $options);
	if(($max_execution_time - $executed_time) > $options['safe_time']) {
		return TRUE;
	}
	return FALSE;
}

/* Update ping */
function checkUpdates() {
	try {
		$safe_time = 5;
		$max_execution_time = ini_get('max_execution_time'); // Store the limit
		$CHEVERETO = Settings::getChevereto();
		$update = G\fetch_url($CHEVERETO['api']['get']['info']);
		if(isSafeToExecute() && $update) {
			$json = json_decode($update);
			$release_notes = $json->software->release_notes;
			$latest_release = $json->software->current_version;
			// Notify only if not notified OR if latest release is newer and not being notified AND is not installed (files)
			if(is_null(getSetting('update_check_notified_release')) || (version_compare($latest_release, G_APP_VERSION, '>') && version_compare($latest_release, getSetting('update_check_notified_release'), '>'))) {
				error_log('se fue un email');
				// Email notify
				$message = _s('There is an update available for your Chevereto based website.') . ' ' . _s('The release notes for this update are:') ;
				$message .= "\n\n";
				$message .= $release_notes . "\n\n";
				$message .= _s('You can apply this update directly from your %a or download it from %s and then manually install it.', ['%a' => '<a href="' . G\get_base_url('dashboard?checkUpdates') . '" target="_blank">'._s('admin dashboard').'</a>', '%s' => '<a href="' . $CHEVERETO['source']['url'] . '" target="_blank">' . $CHEVERETO['source']['label'] . '</a>']) . "\n\n";
				$message .= '--' . "\n" . 'Chevereto' . "\n" . G\get_base_url();
				$message = nl2br($message);
				system_notification_email(['subject' => sprintf('Chevereto update available (v%s)', $latest_release), $latest_release, 'message' => $message]);
				$settings_update = [
					'update_check_notified_release'	=> $latest_release,
					'update_check_datetimegmt'		=> G\datetimegmt(),
					'update_check_latest_release'	=> $latest_release,
				];
			} else {
				error_log("no email");
				$settings_update = ['update_check_datetimegmt' => G\datetimegmt()];
			}
			Settings::update($settings_update);
		}
	} catch(Exception $e) {
		error_log($e);
	} // Silence
}

function getJsModLangL10n() {
	foreach (new DirectoryIterator(CHV_APP_PATH_CONTENT_LANGUAGES . 'cache/') as $fileInfo) {
		if($fileInfo->isDot() || $fileInfo->isDir()) continue;
		$lang_code = str_replace('.po.cache.php', NULL, $fileInfo->getFilename());
		include($fileInfo->getPathname());
		if(!$translation_table['Upload images']) continue;
		$l10n[$lang_code] = $translation_table['Upload images'][0];
	}
	unset($translation_table);
	return json_encode($l10n);
}
