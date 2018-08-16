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

// Set some hard constants
define('CHV_MAX_INVALID_REQUESTS_PER_DAY', 25);

// Folders definitions
define('CHV_FOLDER_IMAGES', !is_null(Settings::get('chevereto_version_installed')) ? Settings::get('upload_image_path') : 'images');

// CHV APP path definitions
define('CHV_APP_PATH_INSTALL', G_APP_PATH . 'install/');
define('CHV_APP_PATH_CONTENT', G_APP_PATH . 'content/');
define('CHV_APP_PATH_LIB_VENDOR', G_APP_PATH . 'vendor/');
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

if (Settings::get('cdn')) {
    define('CHV_ROOT_CDN_URL', Settings::get('cdn_url'));
}
define('CHV_ROOT_URL_STATIC', defined('CHV_ROOT_CDN_URL') ? CHV_ROOT_CDN_URL : G_ROOT_URL);

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
    // Cloudflare REMOTE_ADDR workaround
    if (Settings::get('cloudflare') or isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
    }

    // Fix upload_max_filesize_mb if needed
    $ini_upload_max_filesize = G\get_ini_bytes(ini_get('upload_max_filesize'));
    $ini_post_max_size = ini_get('post_max_size') == 0 ? $ini_upload_max_filesize : G\get_ini_bytes(ini_get('post_max_size'));

    Settings::setValue('true_upload_max_filesize', min($ini_upload_max_filesize, $ini_post_max_size));

    if (Settings::get('true_upload_max_filesize') < G\get_bytes(Settings::get('upload_max_filesize_mb') . 'MB')) {
        Settings::update([
            'upload_max_filesize_mb' => G\bytes_to_mb(Settings::get('true_upload_max_filesize'))
        ]);
    }
}

// Proccess queues
if (class_exists('CHV\Lock') && array_key_exists('queue', $_REQUEST) && $_REQUEST['r']) {
    try {
        $lock = new Lock('storage-delete');
        if (!$lock->check() && $lock->create()) {
            Queue::process(['type' => 'storage-delete']);
            $lock->destroy();
        }
        Render\displayEmptyPixel();
    } catch (Exception $e) {
        error_log($e);
    }
}

// User login handle
if (Settings::get('chevereto_version_installed')) {
    try {
        if ($_SESSION['login']) {
            Login::login($_SESSION['login']['id'], $_SESSION['login']['type']);
        } elseif ($_COOKIE['KEEP_LOGIN']) {
            Login::loginCookie('internal');
        } elseif ($_COOKIE['KEEP_LOGIN_SOCIAL']) {
            Login::loginCookie('social');
        }
        if (Login::isLoggedUser()) {
            // Set the timezone for the logged user
            if (Login::getUser()['timezone'] !== Settings::get('default_timezone') and G\is_valid_timezone(Login::getUser()['timezone'])) {
                date_default_timezone_set(Login::getUser()['timezone']);
            }
        }
    } catch (Exception $e) {
        Login::logout();
        G\exception_to_error($e);
    }
}

// Language localization
(file_exists(G_APP_PATH_LIB . 'l10n.php')) ? require_once(G_APP_PATH_LIB . 'l10n.php') : die("Can't find app/lib/l10n.php");

// Not installed
if (!Settings::get('chevereto_version_installed')) {
    new G\Handler([
        'before' => function ($handler) {
            if ($handler->request_array[0] !== 'install') {
                G\redirect('install');
            }
        }
    ]);
}

// Process showPingPixel (automatic updates check)
if (class_exists('CHV\Lock') && Settings::get('enable_automatic_updates_check') && array_key_exists('ping', $_REQUEST) && $_REQUEST['r']) {
    if (is_null(Settings::get('update_check_datetimegmt')) || G\datetime_add(Settings::get('update_check_datetimegmt'), 'P1D') < G\datetimegmt()) {
        try {
            L10n::setLocale(Settings::get('default_language')); // Force system language
            $lock = new Lock('check-updates');
            if (!$lock->check() && $lock->create()) {
                checkUpdates();
                $lock->destroy();
            }
        } catch (Exception $e) {
            error_log($e);
        }
    }
    Render\displayEmptyPixel();
}

// Translate logged user count labels
if (Login::isLoggedUser()) {
    foreach (['image_count_label', 'album_count_label'] as $v) {
        Login::$logged_user[$v] = _s(Login::$logged_user[$v]);
    }
}

// Handle banned IP address
if(method_exists('CHV\Ip_ban', 'getSingle')) {
    $banned_ip = Ip_ban::getSingle();
    if ($banned_ip) {
        if (G\is_url($banned_ip['message'])) {
            G\redirect($banned_ip['message']);
        } else {
            die(empty($banned_ip['message']) ? _s('You have been forbidden to use this website.') : $banned_ip['message']);
        }
    }
}

// Append any app loader hook (user own hooks)
if (is_readable(G_APP_PATH . 'chevereto-hook.php')) {
    require_once(G_APP_PATH . 'chevereto-hook.php');
}

// Fix the default system images (must be done here because CHV_PATH_CONTENT_IMAGES_SYSTEM)
foreach ([
    'favicon_image'		=> 'favicon.png',
    'logo_vector'			=> 'logo.svg',
    'logo_image'			=> 'logo.png',
    'watermark_image'		=> 'watermark.png',
    'consent_screen_cover_image'	=> 'consent-screen_cover.jpg',
    'homepage_cover_image'	=> 'home_cover.jpg',
    'logo_vector_homepage'	=> 'logo_homepage.svg',
    'logo_image_homepage'	=> 'logo_homepage.png'
] as $k => $v) {
    if ($k == 'homepage_cover_image') {
        $homepage_cover_image = getSetting('homepage_cover_image');
        $homepage_cover_image_default = 'default/' . $v;
        $homecovers = [];
        if (!is_null($homepage_cover_image)) {
            foreach (explode(',', $homepage_cover_image) as $vv) {
                if (file_exists(CHV_PATH_CONTENT_IMAGES_SYSTEM . $vv)) {
                    $homecovers[] = [
                        'basename'	=> $vv,
                        'url'		=> get_system_image_url($vv)
                    ];
                } else {
                    $homepage_cover_image = rtrim(preg_replace('/,+/', ',', str_replace($vv, null, $homepage_cover_image)), ',');
                }
            }
        }
        if ($homepage_cover_image !== getSetting('homepage_cover_image')) {
            Settings::update(['homepage_cover_image' => $homepage_cover_image]);
        }
        if (empty($homecovers) || is_null($homepage_cover_image)) {
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
    if (!G\check_value(Settings::get($k)) || !is_readable(CHV_PATH_CONTENT_IMAGES_SYSTEM . Settings::get($k))) {
        $value = 'default/' . $v;
        if (in_array($k, ['logo_vector_homepage', 'logo_image_homepage'])) {
            $no_homepage_value = Settings::get(G\str_replace_last('_homepage', null, $k));
            if (!G\starts_with('default/', $no_homepage_value)) {
                $value = $no_homepage_value;
            }
        }
        Settings::setValue($k, $value);
    }
}

// Let's try this one out... Why not?
register_shutdown_function(function () {

	if(class_exists('CHV\Lock')) {

        // Delete expired images
        if (method_exists('CHV\Image', 'deleteExpired')) {
            try {
                $lock = new Lock('delete-expired-images');
                if (!$lock->check() && $lock->create()) {
                    Image::deleteExpired(50);
                    $lock->destroy();
                }
            } catch (Exception $e) {
                error_log($e);
            }
        }

        // Handle invalid user accounts
        if (method_exists('CHV\User', 'cleanUnconfirmed')) {
            try {
                $lock = new Lock('clean-unconfirmed-users');
                if (!$lock->check() && $lock->create()) {
                    User::cleanUnconfirmed(5);
                    $lock->destroy();
                }
            } catch (Exception $e) {
                error_log($e);
            }
        }

        if (array_key_exists('deletions', DB::getTables())) {
            try {
                $lock = new Lock('remove-delete-log');
                if (!$lock->check() && $lock->create()) {
                    $db = DB::getInstance();
                    $db->query('DELETE FROM ' . DB::getTable('deletions') . ' WHERE deleted_date_gmt <= :time;');
                    $db->bind(':time', G\datetime_sub(G\datetimegmt(), 'P3M'));
                    $db->exec();
                    $lock->destroy();
                }
            } catch (Exception $e) {
                error_log($e);
            }
        }
    }
});

// We're getting fancy
try {
    if (!isset($hook_before)) {
        $hook_before = function ($handler) {
            $time = G\get_execution_time();
            // Handle agree consent stuff
            if (array_key_exists('agree-consent', $_GET)) {
                setcookie('AGREE_CONSENT', 1, time()+(60*60*24*30), G_ROOT_PATH_RELATIVE); // 30-day cookie
                $_SESSION['agree-consent'] = true;
                G\redirect(preg_replace('/([&\?]agree-consent)/', null, G\get_current_url()));
            }

            $base = $handler::$base_request;

            // Parse this sh*t right away
            parse_str($_SERVER['QUERY_STRING'], $querystr);

            // Inject some global stuff
            $handler::setVar('auth_token', $handler::getAuthToken());
            $handler::setVar('doctitle', getSetting('website_name'));
            $handler::setVar('meta_description', getSetting('website_description'));
            $handler::setVar('logged_user', Login::getUser());
            $handler::setVar('failed_access_requests', 0); // Init
            $handler::setVar('header_logo_link', G\get_base_url());
            $handler::setCond('admin', Login::isAdmin());
            $handler::setCond('maintenance', getSetting('maintenance') and !Login::isAdmin());
            $handler::setCond('show_consent_screen', $base !== 'api' && (getSetting('enable_consent_screen') ? !(Login::getUser() or isset($_SESSION['agree-consent']) or isset($_COOKIE['AGREE_CONSENT'])) : false));
            $handler::setCond('captcha_needed', getSetting('recaptcha') and getSetting('recaptcha_threshold') == 0);
            $handler::setCond('show_header', !($handler::getCond('maintenance') or $handler::getCond('show_consent_screen')));
			$handler::setCond('show_notifications', FALSE);
            $handler::setCond('allowed_to_delete_content', Login::isAdmin() || getSetting('enable_user_content_delete'));

            // Login if maintenance /dashboard
            if ($handler::getCond('maintenance') && $handler->request_array[0] == 'dashboard') {
                G\redirect('login');
            }

            // Consent screen "accept" URL
            if ($handler::getCond('show_consent_screen')) {
                $handler::setVar('consent_accept_url', G\get_current_url() . (parse_url(G\get_current_url(), PHP_URL_QUERY) ? '&' : '/?')  . 'agree-consent');
            }

            if (!Login::getUser()) {
                $failed_access_requests = Requestlog::getCounts(['login', 'signup'], 'fail');
                // reCaptcha thing (only non logged users)
                if (getSetting('recaptcha') && $failed_access_requests['day'] > getSetting('recaptcha_threshold')) {
                    $handler::setCond('captcha_needed', true);
                }
                $handler::setVar('failed_access_requests', $failed_access_requests);
            }

            if ($handler::getCond('captcha_needed')) {
                $handler::setVar('recaptcha_html', Render\get_recaptcha_html());
            }

            // Personal mode
            if (getSetting('website_mode') == 'personal') {

                // Disable some stuff for the rest of the mortals
                if (!$handler::getVar('logged_user')['is_admin']) {
                    //Settings::setValue('website_explore_page', FALSE);
                    //Settings::setValue('website_search', FALSE);
                }

                // Keep ?random & ?lang when route is /
                if ($handler->request_array[0] == '/' and getSetting('website_mode_personal_routing') == '/' and in_array(key($querystr), ['random', 'lang'])) {
                    $handler->mapRoute('index');
                // Keep /search/something (global search) when route is /
                } elseif ($handler->request_array[0] == 'search' and in_array($handler->request_array[1], ['images', 'albums', 'users'])) {
                    $handler->mapRoute('search');
                // Map user for base routing + sub-routes
                } elseif ($handler->request_array[0] == getSetting('website_mode_personal_routing') or (getSetting('website_mode_personal_routing') == '/' and in_array($handler->request_array[0], ['albums', 'search']))) {
                    $handler->mapRoute('user', [
                        'id' => getSetting('website_mode_personal_uid')
                    ]);
                }

                // Inject some stuff for the index page
                if ($handler->request_array[0] == '/' and !in_array(key($querystr), ['random', 'lang']) and !$handler::getCond('mapped_route')) {
                    $personal_mode_user = User::getSingle(getSetting('website_mode_personal_uid'));
                    if (Settings::get('homepage_title_html') == null) {
                        Settings::setValue('homepage_title_html', $personal_mode_user['name']);
                    }
                    if (Settings::get('homepage_paragraph_html') == null) {
                        Settings::setValue('homepage_paragraph_html', _s('Feel free to browse and discover all my shared images and albums.'));
                    }
                    if (Settings::get('homepage_cta_html') == null) {
                        Settings::setValue('homepage_cta_html', _s('View all my images'));
                    }
                    if (Settings::get('homepage_cta_fn') !== 'cta-link') {
                        Settings::setValue('homepage_cta_fn', 'cta-link');
                        Settings::setValue('homepage_cta_fn_extra', $personal_mode_user['url']);
                    }
                    if ($personal_mode_user['background']['url']) {
                        Settings::setValue('homepage_cover_image', $personal_mode_user['background']['url']);
                    }
                }
            } else { // Community mode

                if ($base !== 'index' and !G\is_route_available($handler->request_array[0])) {
                    if (getSetting('user_routing')) {
                        $handler->mapRoute('user');
                    } else {
                        $image_id = decodeID($base);
                        $image = Image::getSingle($image_id, false, true);
                        if ($image) {
                            G\redirect($image['url_viewer'], 301);
                        }
                    }
                }
            }

            // Virtual routes galore
            $virtualizable_routes = ['image', 'album'];

            // Redirect from real route to virtual route (only if needed)
            if (in_array($handler->request_array[0], $virtualizable_routes)) {
                $virtual_route = getSetting('route_' . $handler->request_array[0]);
                if ($handler->request_array[0] !== $virtual_route) {
                    $virtualized_url = str_replace(G\get_base_url($handler->request_array[0]), G\get_base_url($virtual_route), G\get_current_url());
                    return G\redirect($virtualized_url);
                }
            }

            // Virtual route mapping
            if ($base !== 'index' && !G\is_route_available($handler->request_array[0])) {
                foreach ($virtualizable_routes as $k) {
                    if ($handler->request_array[0] == getSetting('route_' . $k)) {
                        $handler->mapRoute($k);
                    }
                }
            }

            // Website privacy mode
            if (getSetting('website_privacy_mode') == 'private' && !Login::getUser()) {
                $allowed_requests = ['api', 'login', 'logout', 'image', 'album', 'page', 'account', 'connect', 'json']; // json allows endless scrolling for privacy link
                if (getSetting('enable_signups')) {
                    $allowed_requests[] = 'signup';
                }
                if (!in_array($handler->request_array[0], $allowed_requests)) {
                    G\redirect('login');
                }
            }

            // Private gate
            $handler::setCond('private_gate', getSetting('website_privacy_mode') == 'private' and !Login::getUser());

            // Forced privacy
            $handler::setCond('forced_private_mode', (getSetting('website_privacy_mode') == 'private' and getSetting('website_content_privacy_mode') !== 'default'));

            // show explorer?
            $handler::setCond('explore_enabled', Login::isAdmin() ?: getSetting('website_explore_page') ? (Login::getUser() ?: getSetting('website_explore_page_guest')) : false);

            // Categories
            $categories = [];
            if ($handler::getCond('explore_enabled') || $base == 'dashboard') {
                try {
                    $categories_db = DB::queryFetchAll('SELECT * FROM ' . DB::getTable('categories') . ' ORDER BY category_name ASC;');
                    if (count($categories_db) > 0) {
                        foreach ($categories_db as $k => $v) {
                            $key = $v['category_id'];
                            $categories[$key] = $v;
                            $categories[$key]['category_url'] = G\get_base_url('category/' . $v['category_url_key']);
                            $categories[$key] = DB::formatRow($categories[$key]);
                        }
                    }
                } catch (Exception $e) {
                }
            }
            $handler::setVar('categories', $categories);

            $explore_semantics = [
                'recent' => [
                    'label' => _s('Recent'),
                    'icon'	=> 'icon-ccw',
                ],
                'trending' => [
                    'label' => _s('Trending'),
                    'icon'	=> 'icon-fire',
                ],
                'animated' => [
                    'label' => _s('Animated'),
                    'icon'	=> 'icon-play4',
                ],
            ];
            if (!getSetting('enable_likes')) {
                unset($explore_semantics['popular']);
            }
            if (!in_array('gif', Image::getEnabledImageFormats())) {
                unset($explore_semantics['animated']);
            }
            foreach ($explore_semantics as $k => &$v) {
                $v['url'] = G\get_base_url('explore/' . $k);
            }
            unset($v);

            $handler::setVar('explore_semantics', $explore_semantics);

            // Get active AND visible pages
            $pages_visible_db = Page::getAll(['is_active' => 1, 'is_link_visible' => 1], ['field' => 'sort_display', 'order' => 'ASC']);
            $handler::setVar('page_tos', Page::getSingle('tos'));
            $handler::setVar('page_privacy', Page::getSingle('privacy'));
            $pages_visible = [];
            if ($pages_visible_db) {
                foreach ($pages_visible_db as $k => $v) {
                    if (!$v['is_active'] && !$v['is_link_visible']) {
                        continue;
                    }
                    $pages_visible[$v['id']] = $v;
                }
            }
            if (getSetting('enable_plugin_route')) {
                $plugin_page = [
                    'type' => 'link',
                    'link_url' => G\get_base_url('plugin'),
                    'icon' => 'icon-code2',
                    'title' => _s('Plugin'),
                    'is_active' => 1,
                    'is_link_visible' => 1,
                ];
                Page::fill($plugin_page);
                array_unshift($pages_visible, $plugin_page);
            }

            $handler::setVar('pages_link_visible', $pages_visible);

            // Allowed/Enabled upload conditional
            $upload_enabled = Login::isAdmin() ? true : getSetting('enable_uploads');
            $upload_allowed = $upload_enabled;

            if (!Login::getUser()) {
                if (!getSetting('guest_uploads') || getSetting('website_privacy_mode') == 'private' || $handler::getCond('maintenance')) {
                    $upload_allowed = false;
                }
            } elseif (!Login::isAdmin() && getSetting('website_mode') == 'personal' && getSetting('website_mode_personal_uid') !== Login::getUser()['id']) {
                $upload_allowed = false;
            }
            // Guest upload limit, sets local value
            if (!Login::getUser() && $upload_allowed && getSetting('upload_max_filesize_mb_guest')) {
                Settings::setValue('upload_max_filesize_mb_bak', getSetting('upload_max_filesize_mb'));
                Settings::setValue('upload_max_filesize_mb', getSetting('upload_max_filesize_mb_guest'));
            }

            $handler::setCond('upload_enabled', $upload_enabled); // System allows to upload?
            $handler::setCond('upload_allowed', $upload_allowed); // Target peer can upload?

            // Maintenance mode + Consent screen
            if ($handler::getCond('maintenance') || $handler::getCond('show_consent_screen')) {
                $handler::setCond('private_gate', true);
                $allowed_requests = ['login', 'account', 'connect'];
                if (!in_array($handler->request_array[0], $allowed_requests)) {
                    $handler->preventRoute($handler::getCond('show_consent_screen') ? 'consent-screen' : 'maintenance');
                }
            }

            // Inject system notices
            $handler::setVar('system_notices', Login::isAdmin() ? getSystemNotices() : null);

            if (!in_array($handler->request_array[0], ['login', 'signup', 'account', 'connect', 'logout', 'json', 'api'])) {
                $_SESSION['last_url'] = G\get_current_url();
            }
            if (!isset($_SESSION['is_mobile_device'])) {
                $_SESSION['is_mobile_device'] = false;
                if (@require_once CHV_APP_PATH_LIB_VENDOR . '/serbanghita/Mobile_Detect.php') {
                    $detect = new \Mobile_Detect;
                    $_SESSION['is_mobile_device'] = $detect->isMobile();
                }
            }
            $handler::setCond('mobile_device', isset($_SESSION['is_mobile_device']) ? $_SESSION['is_mobile_device'] : null);
            $handler::setCond('show_viewer_zero', false);
        }; // hook
    } // hook before?
    if (!isset($hook_after)) {
        $hook_after = function ($handler) {
            if ($handler->template == 404) {
                unset($_SESSION['last_url']);
                $handler::setVar('doctitle', _s("That page doesn't exist") . ' (404) - ' . getSetting('website_name'));
            }
        };
    }
    new G\Handler(['before' => $hook_before, 'after' => $hook_after]);
    $_SESSION['REQUEST_REFERER'] = G\get_current_url(); // Save in session the current internal request
} catch (Exception $e) {
    G\exception_to_error($e);
}
