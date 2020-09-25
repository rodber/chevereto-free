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
        $logged_user = CHV\Login::getUser();

        if ($handler::getCond('search_enabled') == false) {
            return $handler->issue404();
        }

        if ($_POST and !$handler::checkAuthToken($_REQUEST['auth_token'])) {
            $handler->template = 'request-denied';
            return;
        }

        if ($handler->isRequestLevel(4)) {
            return $handler->issue404();
        } // Allow only 3 levels

        if (empty($handler->request[0])) {
            return $handler->issue404();
        }

        // User status override redirect
        CHV\User::statusRedirect($logged_user['status']);

        // Valid search type
        if (!in_array($handler->request[0], ['images', 'albums', 'users'])) {
            return $handler->issue404();
        }

        // Build search params
        $search = new CHV\Search;
        $search->q = $_REQUEST['q'];
        $search->type = $handler->request[0];
        $search->request = $_REQUEST;
        $search->requester = CHV\Login::getUser();
        $search->build();

        if (!G\check_value($search->q)) {
            return G\redirect();
        }

        $safe_html_search = G\safe_html($search->display);

        try {
            /*** Listing ***/
            $list_params = CHV\Listing::getParams(); // Use CHV magic params
            $handler::setVar('list_params', $list_params);
            $list = new CHV\Listing;
            $list->setType($search->type);
            $list->setReverse($list_params['reverse']);
            $list->setSeek($list_params['seek']);
            $list->setOffset($list_params['offset']);
            $list->setLimit($list_params['limit']); // how many results?
            $list->setItemsPerPage($list_params['items_per_page']); // must
            $list->setSortType($list_params['sort'][0]); // date | size | views
            $list->setSortOrder($list_params['sort'][1]); // asc | desc
            $list->setWhere($search->wheres);
            $list->setRequester(CHV\Login::getUser());
            foreach ($search->binds as $k => $v) {
                $list->bind($v['param'], $v['value']);
            }
            $list->output_tpl = $search->type;
            $list->exec();
        } catch (Exception $e) {
        } // Silence to avoid wrong input queries

        $tabs = CHV\Listing::getTabs([
            'listing'	=> 'search',
            'basename'	=> 'search',
            'params'	=> ['q' => $safe_html_search['q'], 'page' => '1'],
            'params_remove_keys' => ['sort'],
        ]);
        foreach ($tabs as $k => &$v) {
            $v['current'] = $v['type'] == $search->type;
        }

        // _s() must be bind in this way for the PO grabber
        switch ($search->type) {
            case 'images':
                $meta_description = _s('Image search results for %s');
            break;
            case 'albums':
                $meta_description = _s('Album search results for %s');
            break;
            case 'users':
                $meta_description = _s('User search results for %s');
            break;
        }

        $handler::setVar('pre_doctitle', _s('Search'));
        $handler::setVar('meta_description', sprintf($meta_description, $safe_html_search['q']));
        $handler::setVar('search', $search->display);
        $handler::setVar('safe_html_search', $safe_html_search);
        $handler::setVar('tabs', $tabs);
        $handler::setVar('list', $list);

        if ($handler::getCond('content_manager')) {
            $handler::setVar('user_items_editor', false);
        }
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
