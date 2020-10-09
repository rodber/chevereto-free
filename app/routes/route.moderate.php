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

$route = function ($handler) {
    try {
        $logged_user = CHV\Login::getUser();

        if (!$logged_user['is_content_manager']) {
            return $handler->issue404();
        }
        $listing = ['label' => _s('Moderate'), 'icon' => 'icon-download2'];
        $listing['list'] = G\get_route_name();
        $listingParams = [
            'listing'    => $listing['list'],
            'basename'    => G\get_route_name(),
            'params_hidden' => [
                'approved' => 0,
                'hide_empty' => 0,
                'hide_banned' => 0,
                'album_min_image_count' => 0,
            ],
            'exclude_criterias' => ['most-viewed', 'most-liked']
        ];
        $tabs = CHV\Listing::getTabs($listingParams, true);
        $currentKey = $tabs['currentKey'];
        $type = $tabs['tabs'][$currentKey]['type'];
        $tabs = $tabs['tabs'];
        parse_str($tabs[$currentKey]['params'], $tabs_params);
        $list_params = CHV\Listing::getParams(); // Use CHV magic params
        $list_params['sort'] = explode('_', $tabs_params['sort']); // Hack this stuff
        $handler::setVar('list_params', $list_params);
        // List
        $list = new CHV\Listing;
        $list->setApproved(0);
        $list->setType($type);
        $list->setReverse($list_params['reverse']);
        $list->setSeek($list_params['seek']);
        $list->setOffset($list_params['offset']);
        $list->setLimit($list_params['limit']); // how many results?
        $list->setItemsPerPage($list_params['items_per_page']); // must
        $list->setSortType($list_params['sort'][0]); // date | size | views | likes
        $list->setSortOrder($list_params['sort'][1]); // asc | desc
        $list->setRequester(CHV\Login::getUser());
        $list->setParamsHidden($listingParams['params_hidden']);
        $list->exec();
        $handler::setVar('listing', $listing);
        $handler::setVar('pre_doctitle', _s('Moderate'));
        $handler::setVar('category', null);
        $handler::setVar('tabs', $tabs);
        $handler::setVar('list', $list);
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
