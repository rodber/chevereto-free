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
		
    // Parse the current query string
		parse_str($_SERVER['QUERY_STRING'], $querystr);

		if(!in_array(key($querystr), ['random', 'lang']) and CHV\Settings::get('homepage_style') == 'route_explore') {
			$handler->mapRoute('explore');
			include G_APP_PATH_ROUTES . 'route.explore.php';
			return $route($handler);
		}

		$logged_user = CHV\Login::getUser();

		// User status override redirect
		CHV\User::statusRedirect($logged_user['status']);

		// Process the index query string requests like "?lang=en"
		if($_SERVER['QUERY_STRING']) {

			switch(key($querystr)){

				// Party Boy
				case 'random':

					if(!CHV\getSetting('website_random')) {
						break;
					}

					$tables = CHV\DB::getTables();
					$db = CHV\DB::getInstance();

					$db->query('SELECT MIN(image_id) as min, MAX(image_id) as max FROM '.$tables['images']);
					$limit = $db->fetchSingle();

					// Try to get the right image
					$random_ids = G\random_values($limit['min'], $limit['max'], 100);

					if(is_null($random_ids)) {
						G\redirect();
					} else {
						if(count($random_ids) == 1) {
							$random_ids[] = $random_ids[0];
						}
					}

					if($limit['min'] !== $limit['max']) {
						// Do NOT show the last viewed image
						$last_viewed_image = CHV\decodeID($_SESSION['last_viewed_image']);
						if(($key = array_search($last_viewed_image, $random_ids)) !== false) {
							unset($random_ids[$key]);
						}
					}

					$query = 'SELECT image_id FROM '.$tables['images'].' LEFT JOIN '.$tables['albums'].' ON '.$tables['images'].'.image_album_id = '.$tables['albums'].'.album_id WHERE image_id IN ('.join(',', $random_ids).") AND (album_privacy = 'public' OR album_privacy IS NULL) ";

					// Don't show NSFW in random mode
					if(!CHV\getSetting('show_nsfw_in_random_mode')) {
						if($logged_user) {
							$query .= 'AND ('.$tables['images'].'.image_nsfw = 0 OR '.$tables['images'].'.image_user_id = '.$logged_user['id'].') ';
						} else {
							$query .= 'AND '.$tables['images'].'.image_nsfw = 0 ';
						}
					}

					if($handler::getCond('forced_private_mode')) {
						$query .= 'AND '.$tables['images'].'.image_user_id = '.$logged_user['id'].' ';
					}

					$query .= 'ORDER BY RAND() LIMIT 1';

					$db->query($query);

					$imageID = $db->fetchSingle()['image_id'];
					$image = CHV\Image::getSingle($imageID, false, true);;

					// Does exists in the disk?
					if($image['file_resource']['chain']['image'] == NULL) $image = false;

					if(!$image) {
						if($_SESSION['random_failure'] > 3) {
							G\redirect();
						} else {
							$_SESSION['random_failure'] += 1;
						}
					} else {
						unset($_SESSION['random_failure']);
					}

					return G\redirect($image ? CHV\Image::getUrlViewer(CHV\encodeID($imageID)) : '?random');

				break;

				// Set the language by cookie then redirect to the original referer
				case 'lang':

					if(!CHV\getSetting('language_chooser_enable')) {
						return G\redirect();
					}

					// Valid lang?
					if(!array_key_exists($querystr['lang'], CHV\get_available_languages())) {
						return G\redirect();
					}

					$logged_user = CHV\Login::getUser();

					if($logged_user and $logged_user['language'] !== $querystr['lang']) {
						CHV\User::update($logged_user['id'], ['language' => $querystr['lang']]);
					}

					// Store selected language in cookie
					setcookie('USER_SELECTED_LANG', $querystr['lang'], time()+(60*60*24*30), G_ROOT_PATH_RELATIVE);

					// Final redir
					G\redirect($_SESSION['REQUEST_REFERER']);

				break;

				// Legacy 1.X viewer request (?v=file.ext)
				case 'v':
					if(preg_match('{^\w*\.jpg|png|gif$}', $_GET['v'])) {
						$explode = array_filter(explode('.', $_GET['v']));
						if(count($explode) == 2) {
							$image = CHV\DB::get('images', ['name' => $explode[0], 'extension' => $explode[1]], 'AND', [], 1);
							if($image) {
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

			}
		}

		if (CHV\Settings::get('homepage_style') == 'split') {
			// Tabs
			$tabs = [
				[
					'tools'		=> true,
					'current'	=> true,
					'id'		=> 'home-pics',
					'type'		=> 'image'
				]
			];

			// Handle the home uids
			$home_uids = CHV\getSetting('homepage_uids');
			$home_uid_is_null = ($home_uids == '' or $home_uids == '0' ? true : false);
			$home_uid_arr = !$home_uid_is_null ? explode(',', $home_uids) : false;

			if(is_array($home_uid_arr)) {
				$home_uid_bind = [];
				foreach($home_uid_arr as $k => $v) {
					$home_uid_bind[] = ':user_id_' . $k;
					if($v == 0) {
						$home_uid_is_null = true;
					}
				}
				$home_uid_bind = implode(',', $home_uid_bind);
			}

			$list = new CHV\Listing;
			$list->setType('images');
			$list->setOffset(0);
			$list->setLimit(24);
			$list->setItemsPerPage(24);
			$list->setSortType('date');
			$list->setSortOrder('desc');
			$list->output_tpl = 'image_plain';
			if(is_array($home_uid_arr)) {
				$where = 'WHERE image_user_id IN('.$home_uid_bind.')';
				if($home_uid_is_null) {
					$where .= ' OR image_user_id IS NULL';
				}
				$list->setWhere($where);
				foreach($home_uid_arr as $k => $v) {
					$list->bind(':user_id_' . $k, $v);
				}
			}
			$list->exec();
			$list->pagination = false;

			$handler::setVar('tabs', $tabs);
			$handler::setVar('list', $list);

		}

		$handler::setVar('doctitle', CHV\Settings::get('website_doctitle'));
		$handler::setVar('pre_doctitle', CHV\Settings::get('website_name'));

		if($logged_user['is_admin']) {
			$handler::setVar('user_items_editor', false);
		}

	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};
