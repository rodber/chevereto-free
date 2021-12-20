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
use Mobile_Detect;

if (G\ends_with('/lib/Peafowl/js/hammer.min.js.map', $_SERVER['REQUEST_URI'])) {
    G\Handler::issue404();
    die();
}
$isGt3160 = version_compare(Settings::get('chevereto_version_installed'), '1.2.0', '>=');
// Not installed
if (!Settings::get('chevereto_version_installed')) {
    new G\Handler([
        'before' => function ($handler) {
            if ($handler->request_array[0] !== 'install') {
                G\redirect('install');
            }
        },
    ]);
}

// Process showPingPixel (automatic updates check)
if (
    $isGt3160
    && Settings::get('enable_automatic_updates_check')
    && array_key_exists('ping', $_REQUEST)
    && $_REQUEST['r']
) {
    if (
        is_null(Settings::get('update_check_datetimegmt'))
        || G\datetime_add(Settings::get('update_check_datetimegmt'), 'P1D') < G\datetimegmt()
) {
        try {
            $lock = new Lock('check-updates');
            if ($lock->create()) {
                checkUpdates();
                updateCheveretoNews();
                $lock->destroy();
            }
        } catch (Exception $e) {
            error_log($e);
        }
    }
    Render\displayEmptyPixel();
}

// Handle banned IP address
$banned_ip = Ip_ban::getSingle();
if ($banned_ip) {
    if (G\is_url($banned_ip['message'])) {
        G\redirect($banned_ip['message']);
    } else {
        die(empty($banned_ip['message']) ? _s('You have been forbidden to use this website.') : $banned_ip['message']);
    }
}

// Append any app loader hook (user own hooks)
if (is_readable(G_APP_PATH . 'chevereto-hook.php')) {
    require_once G_APP_PATH . 'chevereto-hook.php';
}

// Fix the default system images (must be done here because CHV_PATH_CONTENT_IMAGES_SYSTEM)
foreach ([
    'favicon_image' => 'favicon.png',
    'logo_vector' => 'logo.svg',
    'logo_image' => 'logo.png',
    'watermark_image' => 'watermark.png',
    'consent_screen_cover_image' => 'consent-screen_cover.jpg',
    'homepage_cover_image' => 'home_cover.jpg',
    'logo_vector_homepage' => 'logo_homepage.svg',
    'logo_image_homepage' => 'logo_homepage.png',
] as $k => $v) {
    if ($k == 'homepage_cover_image') {
        $homepage_cover_image = getSetting('homepage_cover_image');
        $homepage_cover_image_default = 'default/' . $v;
        $homecovers = [];
        if (!is_null($homepage_cover_image)) {
            foreach (explode(',', $homepage_cover_image) as $vv) {
                if (file_exists(CHV_PATH_CONTENT_IMAGES_SYSTEM . $vv)) {
                    $homecovers[] = [
                        'basename' => $vv,
                        'url' => get_system_image_url($vv),
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
                'basename' => $homepage_cover_image_default,
                'url' => get_system_image_url($homepage_cover_image_default),
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

if ($isGt3160) {
    register_shutdown_function(function () {
        try {
            $lock = new Lock('delete-expired-images');
            if ($lock->create()) {
                Image::deleteExpired(50);
                $lock->destroy();
            }
        } catch (Exception $e) {
            error_log($e);
        }
    });
}

// We're getting fancy
try {
    if (!isset($hook_before)) {
        $hook_before = function ($handler) {
            header("Permissions-Policy: interest-cohort=()");
            header("Content-Security-Policy: frame-ancestors 'none'");
            $failed_access_requests = Requestlog::getCounts(['login', 'signup'], 'fail');
            if (is_max_invalid_request($failed_access_requests['day'])) {
                G\set_status_header(403);
                $handler->template = 'request-denied';
            } else {
                try {
                    Login::tryLogin();
                } catch (Exception $e) {
                    G\exception_to_error($e);
                }
            }

            // Handle agree consent stuff
            if (array_key_exists('agree-consent', $_GET)) {
                setcookie('AGREE_CONSENT', 1, time() + (60 * 60 * 24 * 30), G_ROOT_PATH_RELATIVE); // 30-day cookie
                $_SESSION['agree-consent'] = true;
                G\redirect(preg_replace('/([&\?]agree-consent)/', null, G\get_current_url()));
            }

            $base = $handler::$base_request;

            parse_str($_SERVER['QUERY_STRING'], $querystr);

            $handler::setVar('auth_token', $handler::getAuthToken());
            $handler::setVar('doctitle', getSetting('website_name'));
            $handler::setVar('meta_description', getSetting('website_description'));
            $handler::setVar('logged_user', Login::getUser());
            $handler::setVar('failed_access_requests', $failed_access_requests);
            $handler::setVar('header_logo_link', G\get_base_url());
            $handler::setCond('admin', Login::isAdmin());
            $handler::setCond('manager', Login::isManager());
            $handler::setCond('content_manager', Login::isAdmin() || Login::isManager());
            $allowed_nsfw_flagging = !getSetting('image_lock_nsfw_editing');
            if ($handler::getCond('content_manager')) {
                $allowed_nsfw_flagging = true;
            }
            $handler::setCond('allowed_nsfw_flagging', $allowed_nsfw_flagging);
            $handler::setCond('maintenance', getSetting('maintenance') and !Login::isAdmin());
            $handler::setCond('show_consent_screen', $base !== 'api' && (getSetting('enable_consent_screen') ? !(Login::getUser() or isset($_SESSION['agree-consent']) or isset($_COOKIE['AGREE_CONSENT'])) : false));
            $handler::setCond('captcha_needed', getSetting('recaptcha') and getSetting('recaptcha_threshold') == 0);
            $handler::setCond('captcha_show', false); // this must be enabled on each route, applies to reCaptcha V3
            $handler::setCond('show_header', !($handler::getCond('maintenance') or $handler::getCond('show_consent_screen')));
            $handler::setCond('show_notifications', getSetting('website_mode') == 'community' && (getSetting('enable_followers') || getSetting('enable_likes')));
            $handler::setCond('allowed_to_delete_content', Login::isAdmin() || getSetting('enable_user_content_delete'));
            $handler::setVar('canonical', null);
            if (Login::getUser()) {
                $theme_tone = Login::getUser()['is_dark_mode'] ? 'dark' : 'light';
            } else {
                $theme_tone = Settings::get('theme_tone');
            }
            $handler::setVar('theme_tone', $theme_tone);
            $is_dark_mode = $theme_tone == 'dark';
            $handler::setCond('dark_mode', $is_dark_mode);
            $handler::setVar('theme_top_bar_color', $is_dark_mode ? 'black' : 'white');
            // Login if maintenance /dashboard
            if ($handler::getCond('maintenance') && $handler->request_array[0] == 'dashboard') {
                G\redirect('login');
            }

            // Consent screen "accept" URL
            if ($handler::getCond('show_consent_screen')) {
                $handler::setVar('consent_accept_url', G\get_current_url() . (parse_url(G\get_current_url(), PHP_URL_QUERY) ? '&' : '/?') . 'agree-consent');
            }

            // Recaptcha limit on multiple failed attempts
            if (!Login::getUser()) {
                // reCaptcha thing (only non logged users)
                if (getSetting('recaptcha') && $failed_access_requests['day'] >= getSetting('recaptcha_threshold')) {
                    $handler::setCond('captcha_needed', true);
                }
            }

            // Personal mode
            if (getSetting('website_mode') == 'personal') {
                // Disable some stuff for the rest of the mortals
                if (!$handler::getVar('logged_user')['is_admin']) {
                    //Settings::setValue('website_explore_page', FALSE);
                    //Settings::setValue('website_search', FALSE);
                }

                if ($handler->request_array[0] == '/' and getSetting('website_mode_personal_routing') == '/' and in_array(key($querystr), ['random'])) {
                    $handler->mapRoute('index');
                // Keep /search/something (global search) when route is /
                } elseif ($handler->request_array[0] == 'search' and in_array($handler->request_array[1], ['images', 'albums', 'users'])) {
                    $handler->mapRoute('search');
                // Map user for base routing + sub-routes
                } elseif ($handler->request_array[0] == getSetting('website_mode_personal_routing') or (getSetting('website_mode_personal_routing') == '/' and in_array($handler->request_array[0], ['albums', 'search']))) {
                    $handler->mapRoute('user', [
                        'id' => getSetting('website_mode_personal_uid'),
                    ]);
                }

                // Inject some stuff for the index page
                if ($handler->request_array[0] == '/' and !in_array(key($querystr), ['random']) and !$handler::getCond('mapped_route')) {
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

            // Virtual route mapping, is_route_available check route.name.php
            if ($base !== 'index' && !G\is_route_available($handler->request_array[0])) {
                foreach ($virtualizable_routes as $k) {
                    if ($handler->request_array[0] == getSetting('route_' . $k)) {
                        $handler->mapRoute($k);
                    }
                }
            }

            // Website privacy mode (require login to use the website)
            if (getSetting('website_privacy_mode') == 'private' && !Login::getUser()) {
                $allowed_requests = ['api', 'login', 'logout', 'page', 'account', 'connect', 'json', 'recaptcha-verify'];
                // Add content routes here (detect content privacy handling on route)
                foreach ($virtualizable_routes as $v) {
                    $v = getSetting('route_' . $v);
                    if (isset($v)) {
                        $allowed_requests[] = $v;
                    }
                }
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

            $handler::setCond('explore_enabled', $handler::getCond('content_manager') ?: (getSetting('website_explore_page') ? (Login::getUser() ?: getSetting('website_explore_page_guest')) : false));

            $handler::setCond('search_enabled', $handler::getCond('content_manager') ?: getSetting('website_search'));

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
                    'icon' => 'icon-ccw',
                ],
                'trending' => [
                    'label' => _s('Trending'),
                    'icon' => 'icon-fire',
                ],
                'popular' => [
                    'label' => _s('Popular'),
                    'icon' => 'icon-heart3',
                ],
                'animated' => [
                    'label' => _s('Animated'),
                    'icon' => 'icon-play4',
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
            $pageHandle = version_compare(Settings::get('chevereto_version_installed'), '1.2.0', '>=') ? 'internal' : 'url_key';
            $handler::setVar('page_tos', Page::getSingle('tos', $pageHandle));
            $handler::setVar('page_privacy', Page::getSingle('privacy', $pageHandle));
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
                    'attr_target' => '_self'
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
                $allowed_requests = ['login', 'account', 'connect', 'recaptcha-verify', 'oembed'];
                if (!in_array($handler->request_array[0], $allowed_requests)) {
                    $handler->preventRoute($handler::getCond('show_consent_screen') ? 'consent-screen' : 'maintenance');
                }
            }
            if($handler->request_array[0] == getSetting('route_image')) {
                $id = getIdFromURLComponent($handler->request[0]);
                if ($id !== false) {
                    $image = Image::getSingle($id, false, true, $handler::getVar('logged_user'));
                    $userNotBanned = isset($image['user']['status'])
                        ? $image['user']['status'] != 'banned'
                        : true;
                    if ($image && $image['is_approved'] && $userNotBanned && !in_array($image['album']['privacy'], array('private', 'custom'))) {
                        $image_safe_html = G\safe_html($image);
                        $handler::setVar('oembed', [
                            'title' => ($image_safe_html['title'] ?? ($image_safe_html['name'] . '.' . $image['extension'])) . ' hosted at ' . getSetting('website_name'),
                            'url' => $image['url_viewer']
                        ]);
                    }
                }
            }
            // Inject system notices
            $handler::setVar('system_notices', Login::isAdmin() ? getSystemNotices() : null);

            if (!in_array($handler->request_array[0], ['login', 'signup', 'account', 'connect', 'logout', 'json', 'api', 'recaptcha-verify'])) {
                $_SESSION['last_url'] = G\get_current_url();
            }
            if (!isset($_SESSION['is_mobile_device'])) {
                $_SESSION['is_mobile_device'] = false;
                $detect = new Mobile_Detect();
                $_SESSION['is_mobile_device'] = $detect->isMobile();
            }
            $handler::setCond('mobile_device', isset($_SESSION['is_mobile_device']) ? $_SESSION['is_mobile_device'] : null);
            $handler::setCond('show_viewer_zero', false);

            if ($handler->template == 'request-denied') {
                $handler::setVar('doctitle', _s("Request denied") . ' (403) - ' . getSetting('website_name'));
                $handler->preventRoute('request-denied');
            }
        }; // hook
    } // hook before?
    if (!isset($hook_after)) {
        $hook_after = function ($handler) {
            // Not found, check redirectors
            if ($handler->is404 || $handler->checkIndexRedirect) {
                $from = $handler->is404 ? $handler->handled_request : ('/?' . $_SERVER['QUERY_STRING']);
                Redirect::handle($from);
            }
            if ($handler->template == 404) {
                unset($_SESSION['last_url']);
                $handler::setVar('doctitle', _s("That page doesn't exist") . ' (404) - ' . getSetting('website_name'));
            }
            $list_params = $handler::getVar('list_params');
            if (isset($list_params) && $list_params['page_show']) {
                $handler::setVar('doctitle', $handler::getVar('doctitle') . ' | ' . _s('Page %s', $list_params['page_show']));
            }
            $handler::setVar('safe_html_website_name', G\safe_html(getSetting('website_name')));
            $handler::setVar('safe_html_doctitle', G\safe_html($handler::getVar('doctitle')));
            if ($handler::getVar('pre_doctitle')) {
                $handler::setVar('safe_html_pre_doctitle', G\safe_html($handler::getVar('pre_doctitle')));
            }
            $handler::setVar('safe_html_meta_description', G\safe_html($handler::getVar('meta_description')));
        };
    }
    new G\Handler(['before' => $hook_before, 'after' => $hook_after]);
    $_SESSION['REQUEST_REFERER'] = G\get_current_url(); // Save in session the current internal request
} catch (Exception $e) {
    G\exception_to_error($e);
}
