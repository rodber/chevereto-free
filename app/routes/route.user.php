<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

$route = function ($handler) {
    try {
        // 0 -> username
        // 1 -> ?QS | albums | search
        // 2 -> albums/?QS | search/?QS

        if ($handler->isRequestLevel($handler::getCond('mapped_route') ? 4 : 5)) {
            return $handler->issue404();
        } // Allow only N levels

        // Handle the request for /user and /username routing
        $request_handle = $handler::getCond('mapped_route') ? $handler->request_array : $handler->request;

        // Handle the request for /album or /search (personal mode ON + '/' routing)
        if (CHV\getSetting('website_mode') == 'personal' and CHV\getSetting('website_mode_personal_routing') == '/' and in_array($request_handle[0], ['albums', 'search', 'following', 'followers'])) {
            $request_handle = [1 => $request_handle[0]];
        }

        // Username handle
        $username = $request_handle[0];

        // Detect mapped args
        if ($handler::getCond('mapped_route') and $handler::$mapped_args) {
            $mapped_args = $handler::$mapped_args;
        }

        if ($mapped_args['id']) {
            $id = $handler::$mapped_args['id'];
        }

        if (is_null($username) and is_null($id)) {
            return $handler->issue404();
        }

        // User routing redirect
        if (CHV\getSetting('user_routing') and $handler->request_array[0] == 'user') {
            G\redirect(preg_replace('#/user/#', '/', G\get_current_url(), 1));
        }

        $logged_user = CHV\Login::getUser();

        // Logged user status override redirect
        CHV\User::statusRedirect($logged_user['status']);

        $userhandle = is_null($id) ? 'username' : 'id';
        $user = CHV\User::getSingle($$userhandle, $userhandle);
        $is_owner = $user['id'] == $logged_user['id'];

        // No user or invalid status
        if (!$user or $user['status'] !== 'valid' and (!$logged_user or !$handler::getCond('content_manager'))) {
            return $handler->issue404();
        }

        // Single user mode redirect
        if (CHV\getSetting('website_mode') == 'personal' and $user['id'] == CHV\getSetting('website_mode_personal_uid') and $handler->request_array[0] == 'user') {
            G\redirect(CHV\getSetting('website_mode_personal_routing'));
        }

        // Private gate
        if (!($is_owner || $handler::getCond('content_manager')) && $user['is_private']) {
            return $handler->issue404();
        }

        $user_routes = [];
        $user_views = [
            'images'	=> [
                'title' 		=> _s("%s's Images"),
                'title_short'	=> _s("Images"),
                'doctitle'		=> '',
            ],
            'albums'	=> [
                'title'			=> _s("%s's Albums"),
                'title_short'	=> _s("Albums"),
                'doctitle'		=> '',
            ],
            'search'	=> [
                'title'			=> _s('Results for'),
                'title_short'	=> '',
                'doctitle'		=> '',
            ],
        ];

        foreach ($user_views as $k => $v) { // Need to use $k => $v to fetch array key easily
            array_push($user_routes, $k == 'images' ? $username : $k);
        }
        
        foreach ($user_views as $k => $v) {
            $user_views[$k]['current'] = false;
        }

        if ($request_handle[1]) {

            // Sub path user
            if ($request_handle[1] !== $_SERVER['QUERY_STRING']) {
                if (!in_array($request_handle[1], $user_routes)) {
                    return $handler->issue404();
                }
            }

            // Search in user
            if ($request_handle[1] == 'search') {
                if (!$_SERVER['QUERY_STRING']) {
                    return $handler->issue404();
                }
                // Invalid list request?
                if (!empty($_REQUEST['list']) and !in_array($_REQUEST['list'], ['images', 'albums', 'users'])) {
                    return $handler->issue404();
                }
            }
        } else {
            $user_views['images']['current'] = true;
        }
        
        $user['followed'] = false;
        $show_follow_button = false;
        
        $handler::setCond('show_follow_button', $show_follow_button);

        // $albums = CHV\User::getAlbums($user);

        $pre_doctitle = $user['name'];
        if (CHV\getSetting('website_mode') == 'community' or $user['id'] !== CHV\getSetting('website_mode_personal_uid')) {
            $pre_doctitle .= ' ('.$user['username'].')';
        }
        $handler::setVar('pre_doctitle', $pre_doctitle);

        if (array_key_exists($request_handle[1], $user_views)) {
            $user_views[$request_handle[1]]['current'] = true;
        }

        if ($request_handle[1] == 'search') {
            if (!$_REQUEST['q']) {
                G\redirect($user['url']);
            }
            $user['search'] = [
                'type'	=> empty($_REQUEST['list']) ? 'images' : $_REQUEST['list'],
                'q'		=> $_REQUEST['q'],
                'd'		=> strlen($_REQUEST['q']) >= 25 ? (substr($_REQUEST['q'], 0, 22) . '...') : $_REQUEST['q']
            ];
        }

        // Tabs

        $base_user_url = $user['url'];

        foreach ($user_views as $k => $v) {
            $handler::setCond('user_' . $k, $v['current']);
            if ($v['current']) {
                $current_view = $k;
                if ($current_view !== 'images') {
                    $base_user_url .= '/' . $k;
                }
            }
        }

        $safe_html_user = G\safe_html($user);

        switch ($current_view) {

            case 'images':
            case 'liked':
                $type = "images";
                $tools = $is_owner || $handler::getCond('content_manager');
                if ($current_view == 'liked') {
                    $tools_available = $handler::getCond('content_manager') ? ['delete', 'category', 'flag'] : ['embed'];
                }
            break;

            case 'following':
            case 'followers':
                $type = 'users';
                $tools = false;
                $params_hidden = [$current_view . '_user_id' => $user['id_encoded']];
                $params_remove_keys = ['list'];
            break;

            case 'albums':
                $type = "albums";
                $tools = true;
            break;

            case 'search':
                $type = $user['search']['type'];
                $tabs = [
                    [
                        'type'		=> 'images',
                        'label'		=> _n('Image', 'Images', 2),
                        'id'		=> 'list-user-images',
                        'current'	=> $_REQUEST['list'] == 'images' || !$_REQUEST['list'],
                    ],
                    [
                        'type'		=> 'albums',
                        'label'		=> _n('Album', 'Albums', 2),
                        'id'		=> 'list-user-albums',
                        'current'	=> $_REQUEST['list'] == 'albums',
                    ]
                ];
                foreach ($tabs as $k => $v) {
                    $params = [
                        'list'	=> $v['type'],
                        'q'		=> $safe_html_user['search']['q'],
                        'sort'	=> 'date_desc',
                        'page'	=> '1',
                    ];
                    $tabs[$k]['params'] = http_build_query($params);
                    $tabs[$k]['url'] = $base_user_url . '/?' . $tabs[$k]['params'];
                }
            break;
        }

        if ($user_views['albums']['current']) {
            $params_hidden['list'] = 'albums';
        }
        $params_hidden[$current_view == 'liked' ? 'like_user_id' : 'userid'] = $user['id_encoded'];
        $params_hidden['from'] = 'user';

        if (!$tabs) {
            $tabs = CHV\Listing::getTabs([
                'listing'	=> $type,
                'basename'	=> $base_user_url,
                'tools'		=> $tools,
                'tools_available'	=> $tools_available,
                'params_hidden'		=> $params_hidden,
                'params_remove_keys'=> $params_remove_keys,
            ]);
        }
        foreach ($tabs as $k => &$v) {
            if ($params_hidden && !array_key_exists('params_hidden', $tabs)) {
                $tabs[$k]['params_hidden'] = http_build_query($params_hidden);
            }
            $v['disabled'] = $user[($user_views['images']['current'] ? 'image' : 'album') . '_count'] == 0 ? !$v['current'] : false;
        }

        // Listings
        if ($user["image_count"] > 0 or $user["album_count"] > 0 or in_array($current_view, ['liked', 'following', 'followers'])) {
            $list_params = CHV\Listing::getParams(); // Use CHV magic params
            $handler::setVar('list_params', $list_params);

            if ($list_params['sort'][0] == 'likes' and !CHV\getSetting('enable_likes')) {
                $handler->issue404();
            }

            $tpl = $type;

            switch ($current_view) {
                case 'liked':
                    $where = 'WHERE like_user_id=:user_id';
                    $tpl = 'liked';
                break;
                case 'following':
                    $where = 'WHERE follow_user_id=:user_id';
                break;
                case 'followers':
                    $where = 'WHERE follow_followed_user_id=:user_id';
                break;
                default:
                    $where = $type == 'images' ? 'WHERE image_user_id=:user_id' : 'WHERE album_user_id=:user_id';
                break;
            }

            $output_tpl = 'user/' . $tpl;

            if ($user_views['search']['current']) {
                $type = $user["search"]["type"];
                $where = $user["search"]["type"] == "images" ? "WHERE image_user_id=:user_id AND MATCH(image_name, image_title, image_description, image_original_filename) AGAINST(:q)" : "WHERE album_user_id=:user_id AND MATCH(album_name, album_description) AGAINST(:q)";
            }

            $show_user_items_editor = CHV\Login::getUser() ? true : false;

            if ($type == 'albums') {
                $show_user_items_editor = false;
            }
            try {
                $list = new CHV\Listing;
                $list->setType($type); // images | users | albums
                $list->setReverse($list_params['reverse']);
                $list->setSeek($list_params['seek']);
                $list->setOffset($list_params['offset']);
                $list->setLimit($list_params['limit']); // how many results?
                $list->setItemsPerPage($list_params['items_per_page']); // must
                $list->setSortType($list_params['sort'][0]); // date | size | views | likes
                $list->setSortOrder($list_params['sort'][1]); // asc | desc
                $list->setWhere($where);
                $list->setOwner($user["id"]);
                $list->setRequester(CHV\Login::getUser());
                if ($is_owner or $handler::getCond('content_manager')) {
                    if ($type == 'users') {
                        $list->setTools(false);
                        $show_user_items_editor = false;
                    } else {
                        if ($current_view == 'liked') {
                            $list->setTools($user['id'] == $logged_user['id'] ? ['embed'] : []);
                        } else {
                            $list->setTools(true);
                        }
                    }
                }
                $list->bind(":user_id", $user["id"]);
                if ($user_views['search']['current'] and !empty($user['search']['q'])) {
                    $list->bind(':q', $q ?: $user['search']['q']);
                }
                $list->output_tpl = $output_tpl;
                $list->exec();
            } catch (Exception $e) {
            } // Silence to avoid wrong input queries
        }

        $title = sprintf($user_views[$current_view]['title'], $user['firstname_html']);
        $title_short = sprintf($user_views[$current_view]['title_short'], $user['firstname_html']);

        $handler::setCond('owner', $is_owner);
        $handler::setCond('show_user_items_editor', $show_user_items_editor);
        $handler::setVar('user', $user);
        $handler::setVar('safe_html_user', $safe_html_user);
        $handler::setVar('title', $title);
        $handler::setVar('title_short', $title_short);
        $handler::setVar('tabs', $tabs);
        $handler::setVar('list', $list);

        // Note, _s must be call like this to bind the PO crawler
        if ($user_views['albums']['current']) {
            $meta_description = _s('%n (%u) albums on %w');
        } else {
            if ($user['bio']) {
                $meta_description = $safe_html_user['bio'];
            } else {
                $meta_description = _s('%n (%u) on %w');
            }
        }
        $handler::setVar('meta_description', strtr($meta_description, ['%n' => $user['name'], '%u' => $user['username'], '%w' => CHV\getSetting('website_name')]));

        if ($handler::getCond('content_manager') or $is_owner) {
            $handler::setVar('user_items_editor', [
                "user_albums"	=> CHV\User::getAlbums($user),
                "type"			=> $user_views['albums']['current'] ? "albums": "images"
            ]);
        }
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
