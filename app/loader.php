<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

// This file is used to load G and your G APP
// If you need to hook elements to this loader you can add them in loader-hook.php

namespace CHV;

use G;
use Exception;

if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
}

// settings.php workaround
if (!is_readable(dirname(__FILE__) . '/settings.php')) {
    if (!@fopen(dirname(__FILE__) . '/settings.php', 'w')) {
        die("Chevereto can't create the app/settings.php file. You must manually create this file.");
    }
}

// G thing
(file_exists(dirname(dirname(__FILE__)) . '/lib/G/G.php')) ? require_once(dirname(dirname(__FILE__)) . '/lib/G/G.php') : die("Can't find lib/G/G.php");

// Require at least X memory to do the thing
$min_memory = '256M';
$memory_limit = ini_get('memory_limit');
$memory_limit_bytes = $memory_limit ? G\get_ini_bytes($memory_limit) : 0;
if ($memory_limit_bytes < G\get_ini_bytes($min_memory)) {
    @ini_set('memory_limit', $min_memory); // Careful with that Axe, Eugene
}

// Cipher thing
if ($_SESSION['crypt'] == false) {
    $cipher = 'AES-128-CBC';
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $_SESSION['crypt'] = [
        'cipher' => $cipher,
        'ivlen' => $ivlen,
        'iv' => $iv,
    ];
}

// CHV\DB instance
// CHV\Settings Instance
try {
    if (G\settings_has_db_info()) {
        DB::getInstance();
    }
    Settings::getInstance();
} catch (Exception $e) {
    if (access !== 'install') {
        G\exception_to_error($e);
    }
}

if (Settings::get('cdn')) {
    define('CHV_ROOT_CDN_URL', Settings::get('cdn_url'));
}

define('G_HTTP_HOST', $_SERVER['HTTP_HOST']);
switch (Settings::get('website_https')) {
    default:
    case 'auto':
        $http_protocol = 'http' . ((((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || $settings['https']) ? 's' : null);
        break;
    case 'forced':
        $http_protocol = 'https';
        break;
    case 'disabled':
        $http_protocol = 'http';
        break;
}
define('G_HTTP_PROTOCOL', $http_protocol);
// Set the URLs
define('G_ROOT_URL', G_HTTP_PROTOCOL . '://' . G_HTTP_HOST . G_ROOT_PATH_RELATIVE); // http(s)://www.mysite.com/chevereto/
define('G_ROOT_LIB_URL', G\absolute_to_url(G_ROOT_LIB_PATH)); // not used
define('G_APP_LIB_URL', G\absolute_to_url(G_APP_PATH_LIB));

// Folders definitions
define('CHV_FOLDER_IMAGES', !is_null(Settings::get('chevereto_version_installed')) ? Settings::get('upload_image_path') : 'images');

// CHV APP path definitions
define('CHV_APP_PATH_INSTALL', G_APP_PATH . 'install/');
define('CHV_APP_PATH_CONTENT', G_APP_PATH . 'content/');
define('CHV_APP_PATH_LIB_VENDOR', G_APP_PATH . 'vendor/');

require_once CHV_APP_PATH_LIB_VENDOR . 'autoload.php';

define('CHV_APP_PATH_CONTENT_SYSTEM', CHV_APP_PATH_CONTENT . 'system/');
define('CHV_APP_PATH_CONTENT_LANGUAGES', CHV_APP_PATH_CONTENT . 'languages/');
define('CHV_APP_PATH_CONTENT_LOCKS', CHV_APP_PATH_CONTENT . 'locks/');

// CHV paths
define('CHV_PATH_IMAGES', G_ROOT_PATH . CHV_FOLDER_IMAGES . '/');
define('CHV_PATH_CONTENT', G_ROOT_PATH . 'content/');
define('CHV_PATH_CONTENT_IMAGES_SYSTEM', CHV_PATH_CONTENT . 'images/system/');
define('CHV_PATH_CONTENT_IMAGES_USERS', CHV_PATH_CONTENT . 'images/users/');
define('CHV_PATH_CONTENT_PAGES', CHV_PATH_CONTENT . 'pages/');
define('CHV_PATH_PEAFOWL', G_ROOT_LIB_PATH . 'Peafowl/');

// subdomain wildcards here
$hostnameSetting = G\get_app_setting('hostname') ?: Settings::get('hostname') ?: G\get_domain(G_HTTP_HOST);
$isIP = filter_var(G_HTTP_HOST, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var(G_HTTP_HOST, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
$isWildcardEnabled = Settings::get('lang_subdomain_wildcard') || Settings::get('user_subdomain_wildcard');

if ($isIP == false && $isWildcardEnabled && ($hostnameSetting != G_HTTP_HOST)) {
    $hostExplode = explode('.', $hostnameSetting);
    if ($hostWildcard = $hostExplode[2] ? $hostExplode[0] : null) {
        define('CHV_HOST_WILDCARD', $hostWildcard);
    }

    array_shift($hostExplode);
    $app_g_http_host = implode('.', $hostExplode);
    $cookieDomain = '.' . (defined('CHV_HOST_WILDCARD') ? $app_g_http_host : G_HTTP_HOST);
    // Destroy non wilcard domain cookies
    // No way to avoid this structure
    if (ini_get('session.cookie_domain') != $cookieDomain) {
        ini_set('session.cookie_domain', $cookieDomain);
        if (ini_get('session.cookie_domain') == $cookieDomain) {
            $cookieWilcard = true;
        }
    }
    $cookie_params = session_get_cookie_params();
    if (defined('CHV_HOST_WILDCARD') && $cookieWilcard) {
        define('APP_G_HTTP_HOST', $app_g_http_host);
        // APP_G_ROOT_URL, APP_G_ROOT_LIB_URL, APP_G_APP_LIB_URL
        foreach (['G_ROOT_URL', 'G_ROOT_LIB_URL', 'G_APP_LIB_URL'] as $v) {
            define('APP_' . $v, G\str_replace_first(G_HTTP_HOST, APP_G_HTTP_HOST, constant($v)));
        }
        if (getSetting('lang_subdomain_wildcard') && array_key_exists(CHV_HOST_WILDCARD, get_enabled_languages())) {
            $hostWildcardType = 'lang';
            L10n::setCookieLang(CHV_HOST_WILDCARD);
        } elseif (getSetting('user_subdomain_wildcard')) {
            if ($user = User::getSingle(CHV_HOST_WILDCARD, 'username', false)) {
                define('CHV_HOST_WILDCARD_USER_ID', $user['user_id']);
                $hostWildcardType = 'user';
            }
        }
    }
    if ($hostWildcardType) {
        define('CHV_HOST_WILDCARD_TYPE', $hostWildcardType);
    }
}

define('CHV_ROOT_URL', defined('APP_G_ROOT_URL') ? APP_G_ROOT_URL : G_ROOT_URL);
define('CHV_HTTP_HOST', defined('APP_G_HTTP_HOST') ? APP_G_HTTP_HOST : G_HTTP_HOST);
define('CHV_ROOT_URL_STATIC', defined('CHV_ROOT_CDN_URL') ? CHV_ROOT_CDN_URL : (defined('APP_G_ROOT_URL') ? APP_G_ROOT_URL : G_ROOT_URL));

// Define the app theme
define('G_APP_PATH_THEMES', G_APP_PATH . 'themes/');
if (!file_exists(G_APP_PATH_THEMES)) {
    die("G\: Theme path doesn't exists!");
}

if (isset($settings['theme']) and file_exists(G_APP_PATH_THEMES . $settings['theme'])) {
    define('G_APP_PATH_THEME', G_APP_PATH_THEMES . $settings['theme'] . '/');
    define('BASE_URL_THEME', G\absolute_to_url(G_APP_PATH_THEME));
}

// Set some hard constants
define('CHV_MAX_INVALID_REQUESTS_PER_DAY', 25);

if (isset($_REQUEST['session_id'])) {
    session_id($_REQUEST['session_id']);
}

// Can work with sessions?
if (!@session_start()) {
    die("G\: Sessions are not working on this server (session_start).");
}

// Define the app theme
if (!defined('G_APP_PATH_THEME')) {
    $theme_path = G_APP_PATH_THEMES;
    if (Settings::get('chevereto_version_installed')) {
        $theme_path .= Settings::get('theme') . '/';
    }
    if (is_dir($theme_path)) {
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

if (access !== 'install' and Settings::get('chevereto_version_installed')) {
    // Error reporting by DB config
    if (Settings::get('error_reporting') === false) {
        error_reporting(0);
    }
    // Set the default timezone by DB config
    if (G\is_valid_timezone(Settings::get('default_timezone'))) {
        date_default_timezone_set(Settings::get('default_timezone'));
    }

    if (access !== 'cli') {
        $upload_max_filesize_mb_db = Settings::get('upload_max_filesize_mb');
        $upload_max_filesize_mb_bytes = G\get_bytes($upload_max_filesize_mb_db  . 'MB');
        // Fix upload_max_filesize_mb if needed
        $ini_upload_max_filesize = G\get_ini_bytes(ini_get('upload_max_filesize'));
        $ini_post_max_size = ini_get('post_max_size') == 0 ? $ini_upload_max_filesize : G\get_ini_bytes(ini_get('post_max_size'));
        Settings::setValue('true_upload_max_filesize', min($ini_upload_max_filesize, $ini_post_max_size));
        if (Settings::get('true_upload_max_filesize') < $upload_max_filesize_mb_bytes) {
            Settings::update([
                'upload_max_filesize_mb' => G\bytes_to_mb(Settings::get('true_upload_max_filesize')),
            ]);
        }
    }
}

(file_exists(G_APP_PATH_LIB . 'l10n.php')) ? require_once(G_APP_PATH_LIB . 'l10n.php') : die("Can't find app/lib/l10n.php");

if (access !== 'cli') {
    require 'web.php';
}
