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

$route = function($handler) {
	try {
		
		if(!CHV\getSetting('website_explore_page')) {
			return $handler->issue404();
		}
		
		$category = NULL;
		$categories = $handler::getVar('categories');
		$category_url_key = $handler->request[0];
		
		if(!$category_url_key) {
			G\redirect('explore');
		}
		
		if($category_url_key) {
			foreach($categories as $k => $v) {
				// Set category info
				if($v['url_key'] == $category_url_key) {
					$category = $v;
					break;
				}
			}
			if(!$category) {
				return $handler->issue404();
			}
			$handler::setVar('pre_doctitle', $category['name']);
		}

		// Tabs
		$tabs = [
			[
				'list'		=> true,
				'tools'		=> true,
				'label'		=> _s('Most recent'),
				'id'		=> 'list-most-recent',
				'params'	=> 'list=images&sort=date_desc&page=1',
				'current'	=> $_REQUEST['sort'] == 'date_desc' or !$_REQUEST['sort'] ? true : false,
			],
			[
				'list'		=> true,
				'tools'		=> true,
				'label'		=> _s('Oldest'),
				'id'		=> 'list-most-oldest',
				'params'	=> 'list=images&sort=date_asc&page=1',
				'current'	=> $_REQUEST['sort'] == 'date_asc',
			],
			[
				'list'		=> true,
				'tools'		=> true,
				'label'		=> _s('Most viewed'),
				'id'		=> 'list-most-viewed',
				'params'	=> 'list=images&sort=views_desc&page=1',
				'current'	=> $_REQUEST['sort'] == 'views_desc',
			],
		];
		$current = false;
		foreach($tabs as $k => $v) {
			$tabs[$k]['params_hidden'] .= 'category_id=' . $category['id'];
			if($v['current']) {
				$current = true;
			}
			$tabs[$k]['type'] = 'images';
			$route_path = G\get_route_name();
			$route_path .= '/' . $category['url_key'];
			$tabs[$k]['url'] = G\get_base_url($route_path . '/?' . $tabs[$k]['params']);
		}
		if(!$current) {
			$tabs[0]['current'] = true;
		}
		
		// List
		$list_params = CHV\Listing::getParams(); // Use CHV magic params
		$list = new CHV\Listing;
		$list->setType('images');
		$list->setOffset($list_params['offset']);
		$list->setLimit($list_params['limit']); // how many results?
		$list->setItemsPerPage($list_params['items_per_page']); // must
		$list->setSortType($list_params['sort'][0]); // date | size | views
		$list->setSortOrder($list_params['sort'][1]); // asc | desc
		$list->setCategory($category['id']);
		$list->setRequester(CHV\Login::getUser());
		$list->exec();

		$meta_description = $category['description'] ? $category['description'] : NULL;
		
		$handler::setVar('meta_description', htmlspecialchars($meta_description));
		$handler::setVar('meta_keywords', $category['name']);		
		$handler::setVar('category', $category);
		$handler::setVar('tabs', $tabs);
		$handler::setVar('list', $list);
		
		$handler->template = 'explore';
		
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};