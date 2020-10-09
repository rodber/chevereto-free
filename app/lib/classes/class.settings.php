<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

namespace CHV;

use G;
use Exception;

class Settings
{
    protected static $instance;

    public static $settings;
    public static $defaults;

    public function __construct()
    {
        try {
            $settings = [];
            try {
                $db_settings = DB::get('settings', 'all', null, ['field' => 'name', 'order' => 'asc']);
                foreach ($db_settings as $k => $v) {
                    $v = DB::formatRow($v);
                    $value = $v['value'];
                    $default = $v['default'];
                    if ($v['typeset'] == 'bool') {
                        $value = (bool) $value == 1;
                        $default = (bool) $default == 1;
                    }
                    if ($v['typeset'] == 'string') {
                        $value = (string) $value;
                        $default = (string) $default;
                    }
                    $settings[$v['name']] = $value;
                    $defaults[$v['name']] = $default;
                }
                foreach (Login::getSocialServices(['get' => 'all']) as $k => $v) { // Must get all to avoid endless nesting
                    if ($settings[$k]) {
                        $settings['social_signin'] = true;
                        break;
                    }
                }
            } catch (Exception $e) {
                $settings = [];
                $defaults = [];
            }

            if (!$db_settings) {
                //throw new Exception("Can't find any DB setting. Table seems to be empty.", 400);
            }

            // Inject the missing settings
            $injected = [
                // 3.2.0
                'theme_download_button' => true,
                'enable_signups' => true,
                'website_mode' => 'community',
                // 3.3.0
                'listing_pagination_mode' => 'classic',
                'website_content_privacy_mode' => 'default',
                'website_privacy_mode' => 'public',
                // 3.3.1
                'website_explore_page' => true,
                // 3.4.4
                'website_search' => true,
                'website_random' => true,
                'theme_show_social_share' => true,
                // 'theme_show_embed_content' => true, // deprecated @3.14.2
                'theme_show_embed_uploader' => true,
                // 3.4.5
                'user_routing' => true,
                'require_user_email_confirmation' => true,
                'require_user_email_social_signup' => true,
                // 3.5.15
                'homepage_style' => 'landing',
                //'logged_user_logo_link'	=> 'homepage', // Removed in 3.7.0
                // 3.5.19
                'user_image_avatar_max_filesize_mb' => '1',
                'user_image_background_max_filesize_mb' => '2',
                // 3.5.20
                'theme_image_right_click' => false,
                // 3.6.0
                'theme_show_exif_data' => true,
                'homepage_cta_color' => 'green',
                'homepage_cta_outline' => false,
                'watermark_enable_guest' => true,
                'watermark_enable_user' => true,
                'watermark_enable_admin' => true,
                // 3.6.1
                'language_chooser_enable' => true,
                'languages_disable' => null,
                'homepage_cta_fn' => 'cta-upload',
                // 3.6.5
                'watermark_target_min_width' => 100,
                'watermark_target_min_height' => 100,
                'watermark_percentage' => 4,
                'watermark_enable_file_gif' => false,
                // 3.7.2
                'upload_medium_fixed_dimension' => 'width',
                'upload_medium_size' => 500,
                // 3.7.3
                'enable_followers' => true,
                'enable_likes' => true,
                'enable_consent_screen' => false,
                'user_minimum_age' => null,
                // 3.7.5
                'route_image' => 'image',
                'route_album' => 'album',
                // 3.7.6
                'enable_duplicate_uploads' => false,
                // 3.8.4
                'upload_enabled_image_formats' => 'jpg,png,bmp,gif',
                'upload_threads' => '2',
                'enable_automatic_updates_check' => true,
                'comments_api' => 'js',
                // 3.8.9
                'image_load_max_filesize_mb' => '3',
                // 3.8.12
                'upload_max_image_width' => '0',
                'upload_max_image_height' => '0',
                // 3.9.0
                'enable_expirable_uploads' => null,
                // 3.10.2
                'enable_user_content_delete' => false,
                // 3.10.3
                'enable_plugin_route' => true,
                'sdk_pup_url' => null,
                // 3.10.6
                'website_explore_page_guest' => true,
                'explore_albums_min_image_count' => 5,
                'upload_max_filesize_mb_guest' => 0.5,
                'notify_user_signups' => false,
                'listing_viewer' => true,
                // 3.11.1
                'seo_image_urls' => true,
                'seo_album_urls' => true,
                // 3.12.0
                'lang_subdomain_wildcard' => false,
                'user_subdomain_wildcard' => false,
                'website_https' => 'auto',
                // 3.12.4
                'upload_gui' => 'js',
                'recaptcha_version' => '2',
                // 3.12.8
                'force_recaptcha_contact_page' => true,
                // 3.13.0
                'dump_update_query' => false,
                // 3.13.4
                'enable_powered_by' => true,
                'akismet' => false,
                'stopforumspam' => false,
                // 3.14.0
                'upload_enabled_image_formats' => 'jpg,png,bmp,gif,webp',
                // 3.15.0
                'hostname' => null,
                'theme_show_embed_content_for' => 'all', // none,users,all
                // 3.16.0
                'moderatecontent' => 0,
                'moderatecontent_key' => '',
                'moderatecontent_block_rating' => 'a',
                'moderatecontent_flag_nsfw' => 'a',
                'moderate_uploads' => '', // ,
            ];

            $device_to_columns = [
                'phone' => 1,
                'phablet' => 3,
                'tablet' => 4,
                'laptop' => 5,
                'desktop' => 6,
            ];
            foreach ($device_to_columns as $k => $v) {
                $injected['listing_columns_' . $k] = $v;
            }

            foreach ($injected as $k => $v) {
                if (!array_key_exists($k, $settings)) {
                    $settings[$k] = $v;
                    $defaults[$k] = $v;
                }
            }

            // Fixed settings
            if ($settings['email_mode'] == 'phpmail') {
                $settings['email_mode'] = 'mail';
            }
            if (!in_array($settings['upload_medium_fixed_dimension'], ['width', 'height'])) {
                $settings['upload_medium_fixed_dimension'] = 'width';
            }

            // Virtual settings
            $settings['listing_device_to_columns'] = [];
            foreach ($device_to_columns as $k => $v) {
                $settings['listing_device_to_columns'][$k] = $settings['listing_columns_' . $k];
            }
            $settings['listing_device_to_columns']['largescreen'] = $settings['listing_columns_desktop'];

            // Chevereto demo only
            if (!in_array($_SERVER['SERVER_NAME'], ['demo.chevereto.com'])) {
                if ($settings['twitter_account'] == 'chevereto') {
                    $settings['twitter_account'] = null;
                }
            }

            // Internal settings
            $settings = array_merge($settings, [
                // Free tier
                'enable_followers'			=> 0,
                'enable_likes'				=> 0,
                'social_signin'				=> 0,
                'require_user_email_social_signup' => 0,
                // HArdc0D3, so haxxor that it hurts!
                'username_min_length' => 3,
                'username_max_length' => 16,
                'username_pattern' => '^[\w]{3,16}$',
                'user_password_min_length' => 6,
                'user_password_max_length' => 128,
                'user_password_pattern' => '^.{6,128}$',
                'maintenance_image' => 'default/maintenance_cover.jpg',
                'ip_whois_url' => 'https://ipinfo.io/%IP',
                'available_button_colors' => ['blue', 'green', 'orange', 'red', 'grey', 'black', 'white', 'default'],
                'routing_regex' => '([\w_-]+)',
                'routing_regex_path' => '([\w\/_-]+)',
                'single_user_mode_on_disables' => ['enable_signups', 'guest_uploads', 'user_routing'],
                'listing_safe_count' => 100,
                // 3.6.5
                'image_title_max_length' => 100,
                'album_name_max_length' => 100,
                // 3.8.4
                'upload_available_image_formats' => 'jpg,png,bmp,gif,webp',
            ]);

            if (!$settings['active_storage']) {
                $settings['active_storage'] = null;
            }

            // '' -> NULL
            foreach ($settings as $k => &$v) {
                G\nullify_string($v);
            }
            unset($v); // break reference
            foreach ($defaults as $k => &$v) {
                G\nullify_string($v);
            }
            unset($v);

            if ($settings['theme_logo_height'] > 0) {
                $settings['theme_logo_height'] = (int) $settings['theme_logo_height'];
            }

            // Injected things due to single user mode on
            if ($settings['website_mode'] == 'personal') {
                if (array_key_exists('website_mode_personal_routing', $settings)) { // Single user routing workaround
                    if (is_null($settings['website_mode_personal_routing']) or $settings['website_mode_personal_routing'] == '/') {
                        $settings['website_mode_personal_routing'] = '/';
                    } else {
                        $settings['website_mode_personal_routing'] = G\get_regex_match($settings['routing_regex'], '#', $settings['website_mode_personal_routing'], 1);
                    }
                }

                if (G\is_integer($settings['website_mode_personal_uid'], ['min' => 0])) {
                    foreach ($settings['single_user_mode_on_disables'] as $k) {
                        $settings[$k] = false;
                    }
                } else {
                    $settings['website_mode'] = 'community';
                }

                $settings['enable_likes'] = false;
                $settings['enable_followers'] = false;
            }

            // CTA fixings
            if (is_null($settings['homepage_cta_fn'])) {
                $settings['homepage_cta_fn'] = 'cta-upload';
            }
            if ($settings['homepage_cta_fn'] == 'cta-link' and !G\is_url($settings['homepage_cta_fn_extra'])) {
                $settings['homepage_cta_fn_extra'] = G\get_regex_match($settings['routing_regex_path'], '#', $settings['homepage_cta_fn_extra'], 1);
            }

            // Disabled languages handle
            if (!is_null($settings['languages_disable'])) {
                $languages_disable = (array) explode(',', $settings['languages_disable']);
                $languages_disable = array_filter(array_unique($languages_disable));
            } else {
                $languages_disable = [];
            }
            $settings['languages_disable'] = $languages_disable;

            self::$settings = $settings;
            self::$defaults = $defaults;
        } catch (Exception $e) {
            throw new SettingsException($e->getMessage(), 400);
        }
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getStatic($var)
    {
        $instance = self::getInstance();

        return $instance::$$var;
    }

    public static function get($key = null)
    {
        $settings = self::getStatic('settings');
        if (!is_null($key)) {
            return $settings[$key];
        } else {
            return $settings;
        }
    }

    public static function getType($val)
    {
        return ($val === 0 || $val === 1) ? 'bool' : 'string';
    }

    public static function getDefaults($key = null)
    {
        $defaults = self::getStatic('defaults');
        if (!is_null($key)) {
            return $defaults[$key];
        } else {
            return $defaults;
        }
    }

    public static function getDefault($key)
    {
        return self::getDefaults($key);
    }

    public static function setValues($values)
    {
        self::$settings = $values;
    }

    public static function setValue($key, $value)
    {
        $settings = self::getStatic('settings');
        self::$settings[$key] = $value ?: null;
    }

    /* Multi settings update [name => value]*/
    public static function update($name_values)
    {
        try {
            $query = '';
            $binds = [];
            $query_tpl = 'UPDATE `' . DB::getTable('settings') . '` SET `setting_value` = %v WHERE `setting_name` = %k;' . "\n";
            $i = 0;
            foreach ($name_values as $k => $v) {
                $query .= strtr($query_tpl, ['%v' => ':v_' . $i, '%k' => ':n_' . $i]);
                $binds[':v_' . $i] = $v;
                $binds[':n_' . $i] = $k;
                ++$i;
            }
            unset($i);
            $db = DB::getInstance();
            $db->query($query);
            foreach ($binds as $k => $v) {
                $db->bind($k, $v);
            }
            $db->exec();
            foreach ($name_values as $k => $v) {
                self::setValue($k, $v);
            }

            return true;
        } catch (Exception $e) {
            throw new SettingsException($e->getMessage(), 400);
        }
    }

    public static function getChevereto()
    {
        $api = 'https://chevereto.com/api/';
        include_once G_APP_PATH . 'license/key.php';
        $id = explode(':', $license)[0];
        $info = $api . 'get/info/';
        if (!defined('G_APP_GITHUB_REPO_URL')) {
            $info .= '?id=' . $id;
            $label = 'chevereto.com/panel/downloads';
            $url = 'https://chevereto.com/panel/downloads';
        } else {
            $info .= 'free';
            $label = G_APP_GITHUB_OWNER . '/' . G_APP_GITHUB_REPO;
            $url = G_APP_GITHUB_REPO_URL;
        }

        return [
            'id' => $id,
            'edition' => G_APP_NAME,
            'version' => G_APP_VERSION,
            'source' => [
                'label' => $label,
                'url' => $url,
            ],
            'api' => [
                'download' => $api . 'download',
                'get' => [
                    'info' => $info,
                ],
            ],
        ];
    }
}

class SettingsException extends Exception
{
}
