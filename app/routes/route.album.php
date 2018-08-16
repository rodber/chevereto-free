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

		if($handler->isRequestLevel(4)) return $handler->issue404(); // Allow only 3 levels

		if(is_null($handler->request[0])) {
			return $handler->issue404();
		}

		$logged_user = CHV\Login::getUser();

		// User status override redirect
		CHV\User::statusRedirect($logged_user['status']);

		$id = CHV\decodeID($handler->request[0]);
		$tables = CHV\DB::getTables();

		// Session stock viewed albums
		if(!$_SESSION['album_view_stock']) {
			$_SESSION['album_view_stock'] = [];
		}

		$album = CHV\Album::getSingle($id, !in_array($id, $_SESSION['album_view_stock']), TRUE, $logged_user);

		// Stock this album view
		$_SESSION['album_view_stock'][] = $id;

		// No album or belogns to a banned user?
		if(!$album || (!$logged_user['is_admin'] and $album['user']['status'] !== 'valid')) {
			return $handler->issue404();
		}

		$is_owner = $album['user']['id'] == $logged_user['id'];

		// Password protected content
		if(!($handler::getCond('admin') || $is_owner) && $album['privacy'] == 'password' && isset($album['password'])) {

			$is_error = FALSE;
			$error_message = NULL;

			$failed_access_requests = CHV\Requestlog::getCounts('content-password', 'fail');

			// GTFO
			if(CHV\is_max_invalid_request($failed_access_requests['day'])) {
				G\set_status_header(403);
				$handler->template = 'request-denied';
				return;
			}

			$captcha_needed = $handler::getCond('captcha_needed');
			if($captcha_needed && $_POST['content-password']) {
				$captcha = CHV\recaptcha_check();
				if(!$captcha->is_valid) {
					$is_error = TRUE;
					$error_message = _s("The reCAPTCHA wasn't entered correctly");
				}
			}

			if(!$is_error) {
				if(isset($_POST['content-password']) && CHV\Album::checkPassword($album['password'], $_POST['content-password'])) {
					CHV\Album::storeUserPasswordHash($album['id'], $_POST['content-password']);
				} else {
					if(!CHV\Album::checkSessionPassword($album)) {
						$is_error = TRUE;
						if($_POST['content-password']) {
							CHV\Requestlog::insert(['type' => 'content-password', 'user_id' => ($logged_user ? $logged_user['id'] : NULL), 'content_id' => $album['id'], 'result' => 'fail']);
							$error_message = _s('Invalid password');
						}
					}
				}
			}

			$handler::setCond('error', $is_error);
			$handler::setVar('error', $error_message);

			if($is_error) {
				if(CHV\getSettings()['recaptcha'] && CHV\must_use_recaptcha($failed_access_requests['day'] + 1)) {
					$captcha_needed = TRUE;
				}
				$handler::setCond('captcha_needed', $captcha_needed);
				if($captcha_needed && !$handler::getVar('recaptcha_html')) {
					$handler::setVar('recaptcha_html', CHV\Render\get_recaptcha_html());
				}
				$handler->template = 'password-gate';
				$handler::setVar('pre_doctitle', _s('Password required'));
				return;
			}

		}

		// Private profile
		if($album['user']['is_private'] && !$logged_user['is_admin'] && $album["user"]["id"] !== $logged_user['id']) {
			unset($album['user']);
			$album['user'] = CHV\User::getPrivate();
		}

		// Privacy
		if($handler::getCond('forced_private_mode')) {
			$album['privacy'] = CHV\getSetting('website_content_privacy_mode');
		}
		if(!$handler::getCond('admin') && in_array($album['privacy'], array('private', 'custom')) and !$is_owner) {
			return $handler->issue404();
		}

		$safe_html_album = G\safe_html($album);
		$safe_html_album['description'] = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $safe_html_album['description']));

		// List
		$list_params = CHV\Listing::getParams(); // Use CHV magic params

		$type = 'images';
		$where = 'WHERE image_album_id=:image_album_id';

		$list = new CHV\Listing;
		$list->setType($type); // images | users | albums
		$list->setOffset($list_params['offset']);
		$list->setLimit($list_params['limit']); // how many results?
		$list->setItemsPerPage($list_params['items_per_page']); // must
		$list->setSortType($list_params['sort'][0]); // date | size | views
		$list->setSortOrder($list_params['sort'][1]); // asc | desc
		$list->setOwner($album["user"]["id"]);
		$list->setRequester(CHV\Login::getUser());
		$list->setWhere($where);
		$list->setPrivacy($album["privacy"]);
		$list->bind(":image_album_id", $album["id"]);
		$list->output_tpl = 'album/image';
		if($is_owner or $logged_user['is_admin']) {
			$list->setTools(TRUE);
		}
		$list->exec();

		// Tabs
		$tabs = CHV\Listing::getTabs([
			'listing'	=> 'images',
			'basename'	=> G\get_route_name() . '/' . $album['id_encoded'],
			'params_hidden' => ['list' => 'images', 'from' => 'album', 'albumid' => $album['id_encoded']],
		]);

		if(CHV\getSetting('theme_show_social_share')) {
			$tabs[] = [
				'list'		=> FALSE,
				'tools'		=> FALSE,
				'label'		=> _s('Share'),
				'id'		=> 'tab-share',
			];
		}

		if(CHV\getSetting('theme_show_embed_content')) {
			$tabs[] = [
				'list'		=> FALSE,
				'tools'		=> FALSE,
				'label'		=> _s('Embed codes'),
				'id'		=> 'tab-codes',
			];
		}

		if($logged_user['is_admin']) {
			$tabs[] = [
				'list'		=> FALSE,
				'tools'		=> FALSE,
				'label'		=> _s('Full info'),
				'id'		=> 'tab-full-info',
			];
		}

		foreach($tabs as $k => &$v) {
			if(!isset($v['params'])) continue;
			$class_tabs[$k]['disabled'] = $album['image_count'] == 0 ? !$v['current'] : FALSE;
		}

		$handler::setCond('owner', $is_owner);
		$handler::setVars([
			'pre_doctitle'		=> $safe_html_album['name'],
			'album'				=> $album,
			'album_safe_html'	=> $safe_html_album,
			'tabs'				=> $tabs,
			'list'				=> $list,
			'owner'				=> $album['user']
		]);

		// Populate the album meta description
		if($album['description']) {
			$meta_description = $album['description'];
		} else {
			$meta_description = _s('%a album hosted in %w', ['%a' => $album['name'], '%w' => CHV\getSetting('website_name')]);
		}
		$handler::setVar('meta_description', htmlspecialchars($meta_description));

		// Items editor
		if($handler::getCond('admin') or $is_owner) {
			$handler::setVar('user_items_editor', [
				"user_albums"	=> CHV\User::getAlbums($album["user"]["id"]),
				"type"			=> "images"
			]);
		}

		// Sharing
		$share_element = array(
			"referer"		=> G\get_base_url(),
			"url"			=> $album["url"],
			"title"			=> $safe_html_album["name"]
		);
		$share_element["HTML"] = '<a href="'.$share_element["url"].'" title="'.$share_element["title"].'">'.$safe_html_album["name"].' ('.$album['image_count'].' '._n('image', 'images', $album['user']['image_count']).')</a>';
		$share_links_array = CHV\render\get_share_links($share_element);

		$handler::setVar('share_links_array', $share_links_array);

		// Share modal
		$handler::setVar('share_modal', [
			'type'			=> 'album',
			'url'			=> $album['url'],
			'links_array'	=> $share_links_array,
			'privacy'		=> $album['privacy'],
			'privacy_notes'	=> $album['privacy_notes'],
		]);

	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};