<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

use function G\str_replace_first;

$route = function ($handler) {
    try {
        // Parse the current query string
        parse_str($_SERVER['QUERY_STRING'], $querystr);

        if (!in_array(key($querystr), ['random']) and G\starts_with('route_', CHV\Settings::get('homepage_style'))) {
            $route = str_replace_first('route_', '', CHV\Settings::get('homepage_style'));
            $handler->mapRoute($route);
            include G_APP_PATH_ROUTES . 'route.'.$route.'.php';

            return $route($handler);
        }

        $logged_user = CHV\Login::getUser();

        // User status override redirect
        CHV\User::statusRedirect($logged_user['status']);

        // Process the index query string requests like "?lang=en"
        if ($_SERVER['QUERY_STRING']) {
            switch (key($querystr)) {
                    // Party Boy
                case 'random':
                    if (!CHV\getSetting('website_random')) {
                        break;
                    }
                    $tables = CHV\DB::getTables();
                    $db = CHV\DB::getInstance();
                    $db->query('SELECT MIN(image_id) as min, MAX(image_id) as max FROM ' . $tables['images']);
                    $limit = $db->fetchSingle();
                    // Try to get the right image
                    $random_ids = G\random_values($limit['min'], $limit['max'], 100);
                    if (is_null($random_ids)) {
                        G\redirect();
                    } else {
                        if (count($random_ids) == 1) {
                            $random_ids[] = $random_ids[0];
                        }
                    }
                    if ($limit['min'] !== $limit['max']) {
                        // Do NOT show the last viewed image
                        $last_viewed_image = CHV\decodeID($_SESSION['last_viewed_image']);
                        if (($key = array_search($last_viewed_image, $random_ids)) !== false) {
                            unset($random_ids[$key]);
                        }
                    }
                    $query = 'SELECT image_id FROM ' . $tables['images'] . ' LEFT JOIN ' . $tables['albums'] . ' ON ' . $tables['images'] . '.image_album_id = ' . $tables['albums'] . '.album_id WHERE image_id IN (' . join(',', $random_ids) . ") AND (album_privacy = 'public' OR album_privacy IS NULL) ";
                    // Don't show NSFW in random mode
                    if (!CHV\getSetting('show_nsfw_in_random_mode')) {
                        if ($logged_user) {
                            $query .= 'AND (' . $tables['images'] . '.image_nsfw = 0 OR ' . $tables['images'] . '.image_user_id = ' . $logged_user['id'] . ') ';
                        } else {
                            $query .= 'AND ' . $tables['images'] . '.image_nsfw = 0 ';
                        }
                    }
                    if ($handler::getCond('forced_private_mode')) {
                        $query .= 'AND ' . $tables['images'] . '.image_user_id = ' . $logged_user['id'] . ' ';
                    }
                    $query .= 'ORDER BY RAND() LIMIT 1';
                    $db->query($query);
                    $imageID = $db->fetchSingle()['image_id'];
                    $image = CHV\Image::getSingle($imageID, false, true);
                    // Does exists in the disk?
                    if ($image['file_resource']['chain']['image'] == null) {
                        $image = false;
                    }
                    if (!$image) {
                        if ($_SESSION['random_failure'] > 3) {
                            G\redirect();
                        } else {
                            ++$_SESSION['random_failure'];
                        }
                    } else {
                        unset($_SESSION['random_failure']);
                    }

                    return G\redirect($image ? CHV\Image::getUrlViewer(CHV\encodeID($imageID)) : '?random');
                    break;

                    // Legacy 1.X viewer request (?v=file.ext)
                case 'v':
                    if (preg_match('{^\w*\.jpg|png|gif$}', $_GET['v'])) {
                        $explode = array_filter(explode('.', $_GET['v']));
                        if (count($explode) == 2) {
                            $image = CHV\DB::get('images', ['name' => $explode[0], 'extension' => $explode[1]], 'AND', [], 1);
                            if ($image) {
                                $image = CHV\Image::formatArray($image);
                                G\redirect($image['url_viewer']);
                            }
                        }
                    }
                    $handler->issue404();
                    break;

                    // Allow any /?list=
                case 'list':
                    $handler->template = 'index';
                    break;

                    // For everything else, check for redirects
                default:
                    $handler->checkIndexRedirect = true;
                    break;
            }
        }

        if (CHV\Settings::get('homepage_style') == 'split') {
            // Tabs
            $tabs = [
                [
                    'tools' => true,
                    'current' => true,
                    'id' => 'home-pics',
                    'type' => 'image',
                ],
            ];

            // Handle the home uids
            $home_uids = CHV\getSetting('homepage_uids');
            $home_uid_is_null = ($home_uids == '' or $home_uids == '0' ? true : false);
            $home_uid_arr = !$home_uid_is_null ? explode(',', $home_uids) : false;

            if (is_array($home_uid_arr)) {
                $home_uid_bind = [];
                foreach ($home_uid_arr as $k => $v) {
                    $home_uid_bind[] = ':user_id_' . $k;
                    if ($v == 0) {
                        $home_uid_is_null = true;
                    }
                }
                $home_uid_bind = implode(',', $home_uid_bind);
            }

            $doing = is_array($home_uid_arr) ? 'recent' : 'trending';

            $explore_semantics = $handler::getVar('explore_semantics');
            $listing = $explore_semantics[$doing];
            $listing['list'] = is_null($doing) ? G\get_route_name() : $doing;

            $list_params = CHV\Listing::getParams(); // Use CHV magic params

            $list = new CHV\Listing();

            $listingParams = [
                'listing' => $doing,
                'basename' => '/',
                'params_hidden' => [
                    'hide_empty' => 1,
                    'hide_banned' => 1,
                    'album_min_image_count' => CHV\getSetting('explore_albums_min_image_count'),
                    'route' => 'index',
                ],
            ];

            $tabs = CHV\Listing::getTabs($listingParams, true);
            $currentKey = $tabs['currentKey'];
            $currentTab = $tabs['tabs'][$currentKey];
            $type = $currentTab['type'];
            $tabs = $tabs['tabs'];
            parse_str($tabs[$currentKey]['params'], $tabs_params);
            $list_params['sort'] = explode('_', $tabs_params['sort']);

            $handler::setVar('list_params', $list_params);

            $list->setParamsHidden($listingParams['params_hidden']);

            if (is_array($home_uid_arr)) {
                foreach ($tabs as $k => &$v) {
                    if ($v['type'] == 'users') {
                        unset($tabs[$k]);
                    }
                }
                $prefix = CHV\DB::getFieldPrefix($type);
                $where = 'WHERE ' . $prefix . '_user_id IN(' . $home_uid_bind . ')';
                if ($home_uid_is_null) {
                    $where .= ' OR ' . $prefix . '_user_id IS NULL';
                }
                $list->setWhere($where);
                foreach ($home_uid_arr as $k => $v) {
                    $list->bind(':user_id_' . $k, $v);
                }
            } else {
                $canonical = G\str_replace_first(G\get_base_url(), G\get_base_url('explore/trending'), G\get_current_url());
                $handler::setVar('canonical', $canonical);
            }

            $list->setType($type);
            $list->setReverse($list_params['reverse']);
            $list->setSeek($list_params['seek']);
            $list->setOffset($list_params['offset']);
            $list->setLimit($list_params['limit']); // how many results?
            $list->setItemsPerPage($list_params['items_per_page']); // must
            $list->setSortType($list_params['sort'][0]); // date | size | views | likes
            $list->setSortOrder($list_params['sort'][1]); // asc | desc
            $list->setRequester(CHV\Login::getUser());
            $list->exec();

            $handler::setVar('listing', $listing);

            $handler::setVar('tabs', $tabs);
            $handler::setVar('list', $list);
        }

        $handler::setVar('doctitle', CHV\Settings::get('website_doctitle'));
        $handler::setVar('pre_doctitle', CHV\Settings::get('website_name'));

        if ($logged_user['is_content_manager']) {
            $handler::setVar('user_items_editor', false);
        }
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
