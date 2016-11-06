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
  
  # This file is used to load G and your G APP
  # If you need to hook elements to this loader you can add them in loader-hook.php

namespace CHV;
use G, Exception;

if(!defined('access') or !access) die('This file cannot be directly accessed.');

setlocale(LC_ALL, 'en_US.UTF8');

// settings.php workaround
if(!is_readable(dirname(__FILE__) . '/settings.php')) {
	if(!@fopen(dirname(__FILE__) . '/settings.php', 'w')) {
		die("Chevereto can't create the app/settings.php file. You must manually create this file.");
	}
}

// G thing
(file_exists(dirname(dirname(__FILE__)) . '/lib/G/G.php')) ? require_once(dirname(dirname(__FILE__)) . '/lib/G/G.php') : die("Can't find lib/G/G.php");

// CHV\DB instance
// CHV\Settings Instance
try {
	if(G\settings_has_db_info()) {
		DB::getInstance();
	}
	Settings::getInstance();
} catch(Exception $e) {
	if(access !== 'install') {
		G\exception_to_error($e);
	}
}

// Set some hard constants
define('CHV_MAX_INVALID_REQUESTS_PER_DAY', 25);

// Folders definitions
define("CHV_FOLDER_IMAGES", !is_null(Settings::get('chevereto_version_installed')) ? Settings::get('upload_image_path') : 'images');

// CHV APP path definitions
define('CHV_APP_PATH_INSTALL', G_APP_PATH . 'install/');
define('CHV_APP_PATH_CONTENT', G_APP_PATH . 'content/');
define('CHV_APP_PATH_LIB_VENDOR', G_APP_PATH . 'vendor/');
define('CHV_APP_PATH_SYSTEM', CHV_APP_PATH_CONTENT . 'system/');
define('CHV_APP_PATH_LANGUAGES', CHV_APP_PATH_CONTENT . 'languages/');

// CHV paths
define('CHV_PATH_IMAGES', G_ROOT_PATH . CHV_FOLDER_IMAGES . '/');
define('CHV_PATH_CONTENT', G_ROOT_PATH . 'content/');
define('CHV_PATH_CONTENT_IMAGES_SYSTEM', CHV_PATH_CONTENT . 'images/system/');
define('CHV_PATH_CONTENT_IMAGES_USERS', CHV_PATH_CONTENT . 'images/users/');
define('CHV_PATH_CONTENT_PAGES', CHV_PATH_CONTENT . 'pages/');
define('CHV_PATH_PEAFOWL', G_ROOT_LIB_PATH . 'Peafowl/');

if(Settings::get('cdn')) {
	define('CHV_ROOT_CDN_URL', Settings::get('cdn_url'));
}
define('CHV_ROOT_URL_STATIC', defined('CHV_ROOT_CDN_URL') ? CHV_ROOT_CDN_URL : G_ROOT_URL);

// Define the app theme
if(!defined('G_APP_PATH_THEME')) {
	$theme_path = G_APP_PATH_THEMES;
	if(Settings::get('chevereto_version_installed')) {
		$theme_path .= Settings::get('theme') . '/';
	}
	if(is_dir($theme_path)) {
		define('G_APP_PATH_THEME', $theme_path);
		define('BASE_URL_THEME', G\absolute_to_url(G_APP_PATH_THEME, CHV_ROOT_URL_STATIC));
	} else {
		die(sprintf("Theme path %s doesn't exists.", G\absolute_to_relative($theme_path)));
	}
}

// Set some url paths
define('CHV_URL_PEAFOWL', G\absolute_to_url(CHV_PATH_PEAFOWL, CHV_ROOT_URL_STATIC));

// Always test the current installation
(file_exists(G_APP_PATH_LIB . 'integrity-check.php')) ? require_once G_APP_PATH_LIB . 'integrity-check.php' : die("Can't find app/lib/integrity-check.php");
check_system_integrity();

if(access !== 'install' and Settings::get('chevereto_version_installed')) {
	// Error reporting by DB config
	if(Settings::get('error_reporting') === false) {
		error_reporting(0);
	}
	// Set the default timezone by DB config
	if(G\is_valid_timezone(Settings::get('default_timezone'))) {
		date_default_timezone_set(Settings::get('default_timezone'));
	}
	// Cloudflare REMOTE_ADDR workaround 
	if(Settings::get('cloudflare') or isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		// Inject CF setting // not safe to rely in this
		/*if(Settings::get('cloudflare') !== (bool) $cloudflare) {
			DB::update('settings', ['value' => $cloudflare], ['name' => 'cloudflare']);
		}*/
	}
}

// Proccess queues
if(array_key_exists('queue', $_REQUEST) && $_REQUEST['r']) {
	Queue::process(['type' => 'storage-delete']);
}

// User login handle
if(Settings::get('chevereto_version_installed')) {
	try {
		if($_SESSION['login']) {
			Login::login($_SESSION['login']['id'], $_SESSION['login']['type']);
		} else if($_COOKIE['KEEP_LOGIN']) {
			Login::loginCookie('internal');
		} else if($_COOKIE['KEEP_LOGIN_SOCIAL']) {
			Login::loginCookie('social');
		}
		if(Login::isLoggedUser()) {
			// Set the timezone for the logged user
			if(Login::getUser()['timezone'] !== Settings::get('default_timezone') and G\is_valid_timezone(Login::getUser()['timezone'])) {
				date_default_timezone_set(Login::getUser()['timezone']);
			}
		}
	} catch(Exception $e) {
		Login::logout();
		G\exception_to_error($e);
	}
}

// Language localization
(file_exists(G_APP_PATH_LIB . 'l10n.php')) ? require_once(G_APP_PATH_LIB . 'l10n.php') : die("Can't find app/lib/l10n.php");

// Not installed
if(!Settings::get('chevereto_version_installed')) {
	new G\Handler([
		'before' => function($handler) {
			if($handler->request_array[0] !== 'install') {
				G\redirect('install');
			}
		}
	]);
}

// Process ping update
if(Settings::get('enable_automatic_updates_check') && array_key_exists('ping', $_REQUEST) && $_REQUEST['r']) {
	L10n::setLocale(Settings::get('default_language')); // Force system language
	checkUpdates();
}

// Delete expired images
if(method_exists('CHV\Image','deleteExpired')) {
    try {
		Image::deleteExpired();
	} catch(Exception $e) {}
}

// Translate logged user count labels
if(Login::isLoggedUser()) {
	foreach(['image_count_label', 'album_count_label'] as $v) {
		Login::$logged_user[$v] = _s(Login::$logged_user[$v]);
	}
}

// Handle banned IP address
if(method_exists('CHV\Ip_ban','getSingle')) {
	$banned_ip = Ip_ban::getSingle();
	if($banned_ip) {
		if(G\is_url($banned_ip['message'])) {
			G\redirect($banned_ip['message']);
		} else {
			die(empty($banned_ip['message']) ? _s('You have been forbidden to use this website.') : $banned_ip['message']);
		}
	}
}

// Handle invalid user accounts
if(method_exists('CHV\User','cleanup')) {
	try {
		User::cleanup();
	} catch(Exception $e) {}
}

// Append any app loader hook (user own hooks)
if(is_readable(G_APP_PATH . 'chevereto-hook.php')) {
	require_once(G_APP_PATH . 'chevereto-hook.php');
}

// Fix the default system images (must be done here because CHV_PATH_CONTENT_IMAGES_SYSTEM)
foreach([
	'favicon_image'			=> 'favicon.png',
	'logo_vector'			=> 'logo.svg',
	'logo_image'			=> 'logo.png',
	'watermark_image'		=> 'watermark.png',
	'consent_screen_cover_image'	=> 'consent-screen_cover.jpg',
	'homepage_cover_image'	=> 'home_cover.jpg',
	'logo_vector_homepage'	=> 'logo_homepage.svg',
	'logo_image_homepage'	=> 'logo_homepage.png'
] as $k => $v) {
	if($k == 'homepage_cover_image') {
		$homepage_cover_image = getSetting('homepage_cover_image');
		$homepage_cover_image_default = 'default/' . $v;
		$homecovers = [];
		if(!is_null($homepage_cover_image)) {
			foreach(explode(',', $homepage_cover_image) as $vv) {
				if(file_exists(CHV_PATH_CONTENT_IMAGES_SYSTEM . $vv)) {
					$homecovers[] = [
						'basename'	=> $vv,
						'url'		=> get_system_image_url($vv)
					];
				} else {
					$homepage_cover_image = rtrim(preg_replace('/,+/', ',', str_replace($vv, NULL, $homepage_cover_image)), ',');
				}
			}
		}
		
		
		if($homepage_cover_image !== getSetting('homepage_cover_image')) {
			Settings::update(['homepage_cover_image' => $homepage_cover_image]);
		}
		if(empty($homecovers) || is_null($homepage_cover_image)) {
			$homecovers[] = [
				'basename'	=> $homepage_cover_image_default,
				'url'		=> get_system_image_url($homepage_cover_image_default)
			];
		}
		Settings::setValue('homepage_cover_images', $homecovers);
		shuffle($homecovers);
		Settings::setValue('homepage_cover_images_shuffled', $homecovers);
		continue;
	}
	if(!G\check_value(Settings::get($k)) || !is_readable(CHV_PATH_CONTENT_IMAGES_SYSTEM . Settings::get($k))) {
		$value = 'default/' . $v;
		if(in_array($k, ['logo_vector_homepage', 'logo_image_homepage'])) {
			$no_homepage_value = Settings::get(G\str_replace_last('_homepage', NULL, $k));
			if(!G\starts_with('default/', $no_homepage_value)) {
				$value = $no_homepage_value;
			}
		}
		Settings::setValue($k, $value);
	}
}

// We're getting fancy
try {
	if(!isset($hook_before)) {
		$hook_before = function($handler) {
			
			// Handle agree consent stuff
			if(array_key_exists('agree-consent', $_GET)) {
				setcookie('AGREE_CONSENT', 1, time()+(60*60*24*30), G_ROOT_PATH_RELATIVE); // 30-day cookie
				$_SESSION['agree-consent'] = TRUE;
				G\redirect(preg_replace('/([&\?]agree-consent)/', NULL, G\get_current_url()));
			}
			
			// ACE OF BASE
			$base = $handler::$base_request;
			
			// Magic
			$is_admin = (bool) Login::getUser()['is_admin'];
			
			// Parse this sh*t right away
			parse_str($_SERVER['QUERY_STRING'], $querystr);
			
			// Inject some global stuff
			$handler::setVar('auth_token', $handler::getAuthToken());
			$handler::setVar('doctitle', getSetting('website_name'));
			$handler::setVar('meta_description', getSetting('website_description'));
			$handler::setVar('meta_keywords', getSetting('website_keywords'));
			$handler::setVar('logged_user', Login::getUser());
			$handler::setVar('failed_access_requests', 0); // Init
			$handler::setVar('header_logo_link', G\get_base_url());
			$handler::setCond('admin', $is_admin);
			$handler::setCond('maintenance', getSetting('maintenance') AND !Login::getUser()['is_admin']);
			$handler::setCond('show_consent_screen', $base !== 'api' && (getSetting('enable_consent_screen') ? !(Login::getUser() OR isset($_SESSION['agree-consent']) OR isset($_COOKIE['AGREE_CONSENT'])) : FALSE));
			$handler::setCond('captcha_needed', getSetting('recaptcha') AND getSetting('recaptcha_threshold') == 0);
			$handler::setCond('show_header', !($handler::getCond('maintenance') OR $handler::getCond('show_consent_screen')));
			$handler::setCond('show_notifications', FALSE);
			
			// Login if maintenance /dashboard
			if($handler::getCond('maintenance') && $handler->request_array[0] == 'dashboard') {
				G\redirect('login');
			}
			
			// Consent screen "accept" URL
			if($handler::getCond('show_consent_screen')) {
				$handler::setVar('consent_accept_url', G\get_current_url() . (parse_url(G\get_current_url(), PHP_URL_QUERY) ? '&' : '/?')  . 'agree-consent');
			}

			if(!Login::getUser()) {
				$failed_access_requests = Requestlog::getCounts(['login', 'signup'], 'fail');
				// reCaptcha thing (only non logged users)
				if(getSetting('recaptcha') && $failed_access_requests['day'] > getSetting('recaptcha_threshold')) {
					$handler::setCond('captcha_needed', TRUE);
				}
				$handler::setVar('failed_access_requests', $failed_access_requests);
			}
			
			if($handler::getCond('captcha_needed')) {
				$handler::setVar('recaptcha_html', Render\get_recaptcha_html('clean'));
			}
			
			// Personal mode
			if(getSetting('website_mode') == 'personal') {
				
				// Disable some stuff for the rest of the mortals
				if(!$handler::getVar('logged_user')['is_admin']) {
					//Settings::setValue('website_explore_page', FALSE);
					//Settings::setValue('website_search', FALSE);
				}
				
				// Keep ?random & ?lang when route is /
				if($handler->request_array[0] == '/' and getSetting('website_mode_personal_routing') == '/' and in_array(key($querystr), ['random', 'lang'])) {
					$handler->mapRoute('index');
				// Keep /search/something (global search) when route is /
				} else if($handler->request_array[0] == 'search' and in_array($handler->request_array[1], ['images', 'albums', 'users'])) {
					$handler->mapRoute('search');
				// Map user for base routing + sub-routes
				} else if($handler->request_array[0] == getSetting('website_mode_personal_routing') or (getSetting('website_mode_personal_routing') == '/' and in_array($handler->request_array[0], ['albums', 'search']))) {
					$handler->mapRoute('user', [
						'id' => getSetting('website_mode_personal_uid')
					]);
				}
				
				// Inject some stuff for the index page
				if($handler->request_array[0] == '/' and !in_array(key($querystr), ['random', 'lang']) and !$handler::getCond('mapped_route')) {
					$personal_mode_user = User::getSingle(getSetting('website_mode_personal_uid'));
					if(Settings::get('homepage_title_html') == NULL) {
						Settings::setValue('homepage_title_html', $personal_mode_user['name']);
					}
					if(Settings::get('homepage_paragraph_html') == NULL) {
						Settings::setValue('homepage_paragraph_html', _s('Feel free to browse and discover all my shared images and albums.'));
					}
					if(Settings::get('homepage_cta_html') == NULL) {
						Settings::setValue('homepage_cta_html', _s('View all my images'));
					}
					if(Settings::get('homepage_cta_fn') !== 'cta-link') {
						Settings::setValue('homepage_cta_fn', 'cta-link');
						Settings::setValue('homepage_cta_fn_extra', $personal_mode_user['url']);
					}
					if($personal_mode_user['background']['url']) {
						Settings::setValue('homepage_cover_image', $personal_mode_user['background']['url']);
					}
				}
				
			} else { // Community mode
				
				if($base !== 'index' and !G\is_route_available($handler->request_array[0])) {
					if(getSetting('user_routing')) {
						$handler->mapRoute('user');
					} else {
						$image_id = decodeID($base);
						$image = Image::getSingle($image_id, false, true);
						if($image) {
							G\redirect($image['url_viewer'], 301);
						}
					}
				}
			}
			
			// Virtual routes galore
			$virtualizable_routes = ['image', 'album'];
			
			// Redirect from real route to virtual route (only if needed)
			if(in_array($handler->request_array[0], $virtualizable_routes)) {
				$virtual_route = getSetting('route_' . $handler->request_array[0]);
				if($handler->request_array[0] !== $virtual_route) {
					$virtualized_url = str_replace(G\get_base_url($handler->request_array[0]), G\get_base_url($virtual_route), G\get_current_url());
					return G\redirect($virtualized_url);
				}
			}
			
			// Virtual route mapping
			if($base !== 'index' && !G\is_route_available($handler->request_array[0])) {
				foreach($virtualizable_routes as $k) {
					if($handler->request_array[0] == getSetting('route_' . $k)) {
						$handler->mapRoute($k);
					}
				}
			}		
			
			// Website privacy mode
			if(getSetting('website_privacy_mode') == 'private' and !Login::getUser()) {
				$allowed_requests = ['api', 'login', 'logout', 'image', 'album', 'page', 'account', 'connect', 'json']; // json allows endless scrolling for privacy link
				if(getSetting('enable_signups')) {
					$allowed_requests[] = 'signup';
				}
				if(!in_array($handler->request_array[0], $allowed_requests)) {
					G\redirect('login');
				}
			}
			
			// Private gate
			$handler::setCond('private_gate', getSetting('website_privacy_mode') == 'private' and !Login::getUser());
			
			// Forced privacy
			$handler::setCond('forced_private_mode', (getSetting('website_privacy_mode') == 'private' and getSetting('website_content_privacy_mode') !== 'default'));
			
			// Categories
			$categories = [];
			if(getSetting('website_explore_page') || $base == 'dashboard') {
				try {
					$categories_db = DB::queryFetchAll('SELECT * FROM ' . DB::getTable('categories') . ' ORDER BY category_name ASC;');
					if(count($categories_db) > 0) {
						foreach($categories_db as $k => $v) {
							$key = $v['category_id'];
							$categories[$key] = $v;
							$categories[$key]['category_url'] = G\get_base_url('category/' . $v['category_url_key']);
							$categories[$key] = DB::formatRow($categories[$key]);
						}
					}
				} catch (Exception $e) {}
			}
			$handler::setVar('categories', $categories);

			// Get active AND visible pages
			$pages_visible_db = Page::getAll(['is_active' => 1, 'is_link_visible' => 1], ['field' => 'sort_display', 'order' => 'ASC']);
			$pages_visible = [];
			if($pages_visible_db) {
				foreach($pages_visible_db as $k => $v) {
					if(!$v['is_active'] and !$v['is_link_visible']) {
						continue;
					}
					$pages_visible[$v['id']] = $v;
				}
			}
			$handler::setVar('pages_link_visible', $pages_visible);
			
			// Allowed upload conditional
			$upload_allowed = getSetting('enable_uploads');
			if(!Login::getUser()) {
				if(!getSetting('guest_uploads') || getSetting('website_privacy_mode') == 'private' || $handler::getCond('maintenance')) {
					$upload_allowed = false;
				}
			} else {
				if(getSetting('website_mode') == 'personal' && getSetting('website_mode_personal_uid') !== Login::getUser()['id']) {
					$upload_allowed = false;
				}
			}
			if(Login::getUser()['is_admin']) {
				$upload_allowed = true;
			}
			$handler::setCond('upload_allowed', $upload_allowed);
			
			// Maintenance mode + Consent screen
			if($handler::getCond('maintenance') || $handler::getCond('show_consent_screen')) {
				$handler::setCond('private_gate', TRUE);
				$allowed_requests = ['login', 'account', 'connect'];
				if(!in_array($handler->request_array[0], $allowed_requests)) {
					$handler->preventRoute($handler::getCond('show_consent_screen') ? 'consent-screen' : 'maintenance');
				}
			}
			
			// Inject the system notices
			if($is_admin) {
				$system_notices = getSystemNotices();
			}
			$handler::setVar('system_notices', $system_notices);
			
			if(!in_array($handler->request_array[0], ['login', 'signup', 'account', 'connect', 'logout', 'json', 'api'])) {
				$_SESSION['last_url'] = G\get_current_url();
			}

		};
	}
	if(!isset($hook_after)) {
		$hook_after = function($handler) {
			if($handler->template == 404) {
				unset($_SESSION['last_url']);
				$handler::setVar('doctitle', _s("That page doesn't exist") . ' (404) - ' . getSetting('website_name'));
			}
		};
	}
	new G\Handler(['before' => $hook_before, 'after' => $hook_after]);
	$_SESSION['REQUEST_REFERER'] = G\get_current_url(); // Save in session the current internal request
} catch(Exception $e) {
	G\exception_to_error($e);
}