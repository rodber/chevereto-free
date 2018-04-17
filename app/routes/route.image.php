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

		if($handler->isRequestLevel(3)) return $handler->issue404(); // Allow only 2 levels

		if(is_null($handler->request[0])) {
			return $handler->issue404();
		}

		$logged_user = CHV\Login::getUser();

		// User status override redirect
		CHV\User::statusRedirect($logged_user['status']);

		$id = CHV\decodeID($handler->request[0]);

		$tables = CHV\DB::getTables();

		if($id==0) {
			return $handler->issue404();
		}

		// Trail this view
		$_SESSION['last_viewed_image'] = CHV\encodeId($id);

		// Session stock viewed images
		if(!$_SESSION['image_view_stock']) {
			$_SESSION['image_view_stock'] = [];
		}

		// Get image DB
		$image = CHV\Image::getSingle($id, !in_array($id, $_SESSION['image_view_stock']), TRUE, $logged_user);

		// Stock this image view
		$_SESSION['image_view_stock'][] = $id;

		// No image or belongs to a banned user if exists?
		if(!$image || (!$logged_user['is_admin'] && $image['user']['status'] == 'banned')) {
			return $handler->issue404();
		}

		// Test local images
		if($image['file_resource']['type'] == 'path') {
			if(!$image['file_resource']['chain']['image'] || !file_exists($image['file_resource']['chain']['image'])) {
				return $handler->issue404();
			}
			// Update is_animated flag
			if($image['extension'] == 'gif' && !$image['is_animated'] && G\is_animated_image($image['file_resource']['chain']['image'])) {
				CHV\Image::update($id, ['is_animated' => 1]);
				$image['is_animated'] = 1;
			}
		}

		/*
			Note: Remote image testing was removed because of the HUGE number of websites running external containers unaccesible via HTTP.
			Remote image test works only if the website can fetch the image URI headers.
			Check Chevereto < 3.8.4 for the old remote image tester code
		*/

		$is_owner = $image['user']['id'] !== NULL ? ($image['user']['id'] == $logged_user['id']) : false;

		// Password protected content
		if(!($handler::getCond('admin') || $is_owner) && $image['album']['privacy'] == 'password' && !CHV\Album::checkSessionPassword($image['album'])) {
			G\redirect($image['album']['url']);
		}

		// Private profile
		if($image['user']['is_private'] && !$logged_user['is_admin'] && $image["user"]["id"] !== $logged_user['id']) {
			unset($image['user']);
			$image['user'] = CHV\User::getPrivate();
		}

		// Privacy
		if($handler::getCond('forced_private_mode')) {
			$image['album']['privacy'] = CHV\getSetting('website_content_privacy_mode');
		}
		if(!$handler::getCond('admin') and in_array($image['album']['privacy'], array('private', 'custom')) and !$is_owner) {
			return $handler->issue404();
		}

		$db = CHV\DB::getInstance();

		// User found
		if($image['user']['id'] !== NULL) {

			// Get user albums
			$name_array = explode(' ', $image['user']['name']);
			$user_name_short = $name_array[0];

			$image['user']['albums'] = [];

			// Lets fake the stream as an album
			$image['user']['albums']['stream'] = CHV\User::getStreamAlbum($image['user']);

			// Get user album list
			$image['user']['albums'] += CHV\DB::get('albums', ['user_id' => $image['user']['id']], 'AND', ['field' => 'name', 'order' => 'asc']);

			foreach($image['user']['albums'] as $k => $v) {
				$image['user']['albums'][$k] = CHV\DB::formatRow($v, 'album');
				CHV\Album::fill($image['user']['albums'][$k]);
			}

		}

		// Get the album slice
		if($image['album']['id'] !== NULL) {
			$get_album_slice = CHV\Image::getAlbumSlice($image['id'], $image['album']['id'], 2);
			$image_album_slice_db = $get_album_slice['db'];
			$image_album_slice = array_merge($image['album'], $get_album_slice['formatted']);
		}

		$image_safe_html =  G\safe_html($image);

		$pre_doctitle = $image_safe_html['title'] ?: ($image_safe_html['name'].'.'.$image_safe_html['extension']) . ' hosted at ' . CHV\getSetting('website_name');

		$tabs = [
			[
				"label"		=> _s('About'),
				"id"		=> "tab-about",
				"current"	=> true,
			]
		];
		if(CHV\getSetting('theme_show_embed_content')) {
			$tabs[] = [
				"label"		=> _s('Embed codes'),
				"id"		=> "tab-codes",
			];
		}

		if($handler::getCond('admin')) {
			$tabs[] = [
				"label"		=> _s('Full info'),
				"id"		=> "tab-full-info"
			];

			// Banned uploader IP?
			$banned_uploader_ip = CHV\Ip_ban::getSingle(['ip' => $image['uploader_ip']]);

			// Admin list values
			$image_admin_list_values = [
				[
					'label'		=> _s('Image ID'),
					'content'	=> $image['id'] . ' ('.$image['id_encoded'].')'
				],
				[
					'label'		=> _s('Uploader IP'),
					'content'	=> sprintf(str_replace('%IP','%1$s', '<a href="'.CHV\getSetting('ip_whois_url').'" target="_blank">%IP</a> · <a href="'.G\get_base_url('search/images/?q=ip:%IP').'">'._s('search content').'</a>  ·  ' . (!$banned_uploader_ip ? ('<a data-modal="form" data-args="%IP" data-target="modal-add-ip_ban" data-options=\'{"forced": true}\' data-content="ban_uploader_ip">' . _s('Ban IP') . '</a>') : NULL) . '<span class="'. ($banned_uploader_ip ? NULL : 'soft-hidden') .'" data-content="banned_uploader_ip">'._s('IP already banned').'</span>'), $image['uploader_ip'])
				],
				[
					'label' 	=> _s('Upload date'),
					'content'	=> $image['date']
				],
				[
					'label' 	=> '',
					'content' 	=> $image['date_gmt'] . ' (GMT)'
				]
			];

			$handler::setVar('image_admin_list_values', $image_admin_list_values);
			$handler::setCond('banned_uploader_ip', (bool)$banned_uploader_ip);
		}

		foreach($tabs as $tab) {
			if($tab['current'] === TRUE) {
				$handler::setVar('current_tab', G\str_replace_first('tab-', NULL, $tab['id']));
				break;
			}
		}
		$handler::setCond('owner', $is_owner);
		$handler::setVar('pre_doctitle', $pre_doctitle);
		$handler::setVar('image_album_slice_db', $image_album_slice_db);
		$handler::setVar('image', $image);
		$handler::setVar('image_safe_html', $image_safe_html);
		$handler::setVar('image_album_slice', G\safe_html($image_album_slice));
		$handler::setVar('tabs', $tabs);
		$handler::setVar('owner', $image['user']);

		// Populate the image meta description
		if($image['description']) {
			$meta_description = $image['description'];
		} else {
			$image_tr = [
				'%i' => $image[is_null($image['title']) ? 'filename' : 'title'],
				'%a' => $image['album']['name'],
				'%w' => CHV\getSetting('website_name'),
				'%c' => $image['category']['name']
			];
			if($image['album']['id'] || (!$image['user']['is_private'] && $image['album']['name'])) {
				$meta_description = _s('Image %i in %a album', $image_tr);
			} else if($image['category']['id']) {
				$meta_description = _s('Image %i in %c category', $image_tr);
			} else {
				$meta_description = _s('Image %i hosted in %w', $image_tr);
			}
		}
		$handler::setVar('meta_description', htmlspecialchars($meta_description));

		if($handler::getCond('admin') or $is_owner) {
			$handler::setVar('user_items_editor', [
				'user_albums'	=> $image['user']['albums'],
				'type'			=> 'image',
				'album'			=> $image['album'],
				'category_id'	=> $image['category_id']
			]);
		}

		// Share thing
		$share_element = [
			'referer'		=> G\get_base_url(),
			'url'			=> $image['url_viewer'],
			'image'			=> $image['url'],
			'title'			=> $handler::getVar('pre_doctitle')
		];
		$share_element['HTML'] = '<a href="'.$share_element["url"].'" title="'.$share_element["title"].'"><img src="'.$share_element["image"].'" /></a>';
		$share_links_array = CHV\render\get_share_links($share_element);
		$handler::setVar('share_links_array', $share_links_array);

		// Share modal
		$handler::setVar('share_modal', [
			'type'			=> 'image',
			'url'			=> $image['url_viewer'],
			'links_array'	=> $share_links_array,
			'privacy'		=> $image['album']['privacy'],
			'privacy_notes'	=> $image['album']['privacy_notes'],
		]);

		// Embed codes
		$embed = [];
		$embed['direct-links'] = [
			'label' => _s('Direct links'),
			'entries' => [
				[
					'label' => _s('Image link'),
					'value' => $image['url_viewer']
				],
				[
					'label' => _s('Image URL'),
					'value' => $image['url']
				],
				[
					'label' => _s('Thumbnail URL'),
					'value' => $image['thumb']['url']
				],
			]
		];
		if($image['medium']) {
			$embed['direct-links']['entries'][] = [
				'label' => _s('Medium URL'),
				'value' => $image['medium']['url']
			];
		}

		$image_full = [
			'html'		=> '<img src="'.$image['url'].'" alt="'.$image['filename'].'" border="0" />',
			'markdown'	=> '!['.$image['filename'].']('.$image['url'].')'
		];
		$image_full['bbcode'] = G\html_to_bbcode($image_full['html']);

		$embed['full-image'] = [
			'label' => _s('Full image'),
			'entries' => [
				[
					'label' => 'HTML',
					'value' => htmlentities($image_full['html'])
				],
				[
					'label' => 'BBCode',
					'value' => $image_full['bbcode']
				],
				[
					'label' => 'Markdown',
					'value' => $image_full['markdown']
				],
			]
		];

		$embed_full_linked['html'] = '<a href="'.$image['url_viewer'].'">'.$image_full['html'].'</a>';
		$embed_full_linked['bbcode'] = G\html_to_bbcode($embed_full_linked['html']);
		$embed_full_linked['markdown'] = '[!['.$image['filename'].']('.$image['url'].')]('.$image['url_viewer'].')';

		$embed['full-linked'] = [
			'label' => _s('Full image (linked)'),
			'entries' => [
				[
					'label' => 'HTML',
					'value' => htmlentities($embed_full_linked['html'])
				],
				[
					'label' => 'BBCode',
					'value' => $embed_full_linked['bbcode']
				],
				[
					'label' => 'Markdown',
					'value' => $embed_full_linked['markdown']
				],
			]
		];

		if($image['medium']) {
			$embed_medium_linked = array(
				"html" => '<a href="'.$image['url_viewer'].'"><img src="'.$image['medium']['url'].'" alt="'.$image['filename'].'" border="0" /></a>'
			);
			$embed_medium_linked['bbcode'] = G\html_to_bbcode($embed_medium_linked['html']);
			$embed_medium_linked['markdown'] = '[!['.$image['medium']['filename'].']('.$image['medium']['url'].')]('.$image['url_viewer'].')';
			$embed['medium-linked'] = [
				'label' => _s('Medium image (linked)'),
				'entries' => [
					[
						'label' => 'HTML',
						'value' => htmlentities($embed_medium_linked['html'])
					],
					[
						'label' => 'BBCode',
						'value' => $embed_medium_linked['bbcode']
					],
					[
						'label' => 'Markdown',
						'value' => $embed_medium_linked['markdown']
					],
				]
			];
		}

		$embed_thumb_linked = [
			'html' => '<a href="'.$image['url_viewer'].'"><img src="'.$image['thumb']['url'].'" alt="'.$image['filename'].'" border="0" /></a>'
		];
		$embed_thumb_linked['bbcode'] = G\html_to_bbcode($embed_thumb_linked['html']);
		$embed_thumb_linked['markdown'] = '[!['.$image['thumb']['filename'].']('.$image['thumb']['url'].')]('.$image['url_viewer'].')';

		$embed['thumb-linked'] = [
			'label' => _s('Thumbnail image (linked)'),
			'entries' => [
				[
					'label' => 'HTML',
					'value' => htmlentities($embed_thumb_linked['html'])
				],
				[
					'label' => 'BBCode',
					'value' => $embed_thumb_linked['bbcode']
				],
				[
					'label' => 'Markdown',
					'value' => $embed_thumb_linked['markdown']
				],
			]
		];

		// Insert an embed id for each entry (for the cliboard.js bind)
		$embed_id = 1;
		foreach($embed as &$v) {
			foreach($v['entries'] as &$entry) {
				$entry['id'] = 'embed-code-' . $embed_id;
				$embed_id++;
			}
		}

		$handler::setVar('embed', $embed);

	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};