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
        if (!$handler::getCond('explore_enabled')) {
            return $handler->issue404();
        }

        $category = null;
        $categories = $handler::getVar('categories');
        $category_url_key = $handler->request[0];

        if (!$category_url_key) {
            G\redirect('explore');
        }

        if ($category_url_key) {
            foreach ($categories as $k => $v) {
                // Set category info
                if ($v['url_key'] == $category_url_key) {
                    $category = $v;
                    break;
                }
            }
            if (!$category) {
                return $handler->issue404();
            }
            $handler::setVar('pre_doctitle', $category['name']);
        }

        // Tabs
        $tabs = CHV\Listing::getTabs([
            'listing'	=> 'images',
            'basename'	=> G\get_route_name() . '/' . $category['url_key'],
            'params_hidden' => ['category_id' => $category['id'], 'hide_banned' => 1],
        ]);

        // List
        $list_params = CHV\Listing::getParams(); // Use CHV magic params
        $handler::setVar('list_params', $list_params);
        $list = new CHV\Listing;
        $list->setType('images');
        $list->setReverse($list_params['reverse']);
        $list->setSeek($list_params['seek']);
        $list->setOffset($list_params['offset']);
        $list->setLimit($list_params['limit']); // how many results?
        $list->setItemsPerPage($list_params['items_per_page']); // must
        $list->setSortType($list_params['sort'][0]); // date | size | views
        $list->setSortOrder($list_params['sort'][1]); // asc | desc
        $list->setCategory($category['id']);
        $list->setRequester(CHV\Login::getUser());
        $list->exec();

        $meta_description = $category['description'] ?: null;

        $handler::setVar('meta_description', htmlspecialchars($meta_description));
        $handler::setVar('category', $category);
        $handler::setVar('tabs', $tabs);
        $handler::setVar('list', $list);

        $handler->template = 'explore';
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
