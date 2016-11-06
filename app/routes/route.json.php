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

		// CSRF protection
		if(!$handler::checkAuthToken($_REQUEST['auth_token'])) {
			throw new Exception(_s('Request denied'), 400);
		}
        
		$logged_user = CHV\Login::getUser();

		$doing = $_REQUEST['action'];
		if($logged_user and $logged_user['status'] !== 'valid') {
			$doing = 'deny';
		}

		switch($doing) {

			case 'deny':
				throw new Exception(_s('Request denied'), 403);
			break;

			case 'upload': // EX 100

				// Deny guest uploads if setting is set to false and no user is logged in
				if(!CHV\getSetting('guest_uploads') and !$logged_user) {
					throw new Exception(_s('Login needed'), 403);
				}

				// Deny all uploads if setting is set to true and logged user isn't admin
				if(!CHV\getSetting('enable_uploads') and $logged_user['is_admin'] == false) {
					throw new Exception(_s('Request denied'), 403);
				}

				$source = $_REQUEST['type'] == 'file' ? $_FILES['source'] : $_REQUEST['source'];
				$type = $_REQUEST['type'];
				$owner_id = !empty($_REQUEST['owner']) ? CHV\decodeID($_REQUEST['owner']) : $logged_user['id'];

				if(in_array($_REQUEST['what'], ['avatar', 'background'])) {

					if(!$logged_user) {
						throw new Exception(_s('Login needed'), 403);
					}

					if(!$logged_user['is_admin'] and $owner_id !== $logged_user['id']) {
						throw new Exception('Invalid content owner request', 403);
					}

					try {
						$user_picture_upload = CHV\User::uploadPicture($owner_id == $logged_user['id'] ? $logged_user : $owner_id, $_REQUEST['what'], $source);
						$json_array['success'] = ['image' => $user_picture_upload, 'message' => sprintf('%s picture uploaded', ucfirst($type)), 'code' => 200];
                        // image inside success??
					} catch(Exception $e) {
						throw new Exception($e->getMessage(), $e->getCode());
					}
					break;

				}

				// Inject the system level privacy override
				if($handler::getCond('forced_private_mode')) {
					$_REQUEST['privacy'] = CHV\getSetting('website_content_privacy_mode');
				}
				
				// Fix some values (due to js)
				if(intval($_REQUEST['category_id']) == 0) {
					$_REQUEST['category_id'] = NULL;
				}
                
				// Upload to website
				$uploaded_id = CHV\Image::uploadToWebsite($source, $logged_user, $_REQUEST);
				
				$json_array['status_code'] = 200;
				$json_array['success'] = array('message' => 'image uploaded', 'code' => 200);
				$json_array['image'] = CHV\Image::formatArray(CHV\Image::getSingle($uploaded_id, false, false), true);

			break;

			case 'get-album-contents':
			case 'list': // EX 200
				
				if($doing == 'get-album-contents') {
					if(!$logged_user) {
						throw new Exception(_s('Login needed'), 403);
					}
					$list_request = 'images';
					$aux = $_REQUEST['albumid'];
					$_REQUEST = NULL; // We don't need anything else
					$_REQUEST['albumid'] = $aux;
				} else {
					$list_request = $_REQUEST["list"];
				}
				
				if(!in_array($list_request, array('images', 'albums', 'users'))) {
					throw new Exception('Invalid list request', 100);
				}

				$output_tpl = $list_request;

				switch($list_request) {

					case 'images':

						$binds = array();
						$where = '';
						
						if(!empty($_REQUEST['like_user_id'])) {
							$where .= ($where == '' ? 'WHERE' : ' AND') . ' like_user_id=:image_user_id';
							$binds[] = [
								'param' => ':image_user_id',
								'value'	=> CHV\decodeID($_REQUEST['like_user_id'])
							];
						}
						
						if(!empty($_REQUEST['follow_user_id'])) {
							$where .= ($where == '' ? 'WHERE' : ' AND') . ' follow_user_id=:image_user_id';
							$binds[] = [
								'param' => ':image_user_id',
								'value'	=> CHV\decodeID($_REQUEST['follow_user_id'])
							];
						}
						
						if(!empty($_REQUEST['userid'])) {
							$owner_id = CHV\decodeID($_REQUEST['userid']);
							$where .= ($where == '' ? 'WHERE' : ' AND') . ' image_user_id=:image_user_id';
							$binds[] = [
								'param' => ':image_user_id',
								'value'	=> $owner_id
							];
						}

						if(!empty($_REQUEST['albumid'])) {
							$album_id = CHV\decodeID($_REQUEST['albumid']);
							$where .= ($where == '' ? 'WHERE' : ' AND') . ' image_album_id=:image_album_id';
							$binds[] = array(
								'param' => ':image_album_id',
								'value'	=> $album_id
							);
							$album = CHV\Album::getSingle($album_id);
							if($album['user']['id']) {
								$owner_id = $album['user']['id'];
							}
							if((!$handler::getCond('admin') || $owner_id !== $logged_user['id']) && $album['privacy'] == 'password' && !CHV\Album::checkSessionPassword($album)) {
								throw new Exception(_s('Request denied'), 403);
							}
						}


						if(!empty($_REQUEST['category_id']) and is_numeric($_REQUEST['category_id'])) {
							$category = $_REQUEST['category_id'];
						}

						switch($_REQUEST['from']) {
							case 'user':
								$output_tpl = 'user/images';
							break;
							case 'album':
								$output_tpl = 'album/images';
							break;
						}

					break;

					case 'albums':

						$binds = array();
						$where = '';

						if(!empty($_REQUEST['userid'])) {
							$where .= ($where == '' ? 'WHERE' : ' AND') . ' album_user_id=:album_user_id';
							$binds[] = array(
								'param' => ':album_user_id',
								'value'	=> CHV\decodeID($_REQUEST['userid'])
							);
						}

						switch($_REQUEST['from']) {
							case 'user':
								$output_tpl = 'user/albums';
							break;
							case 'album':
								$output_tpl = 'album';
							break;
						}

					break;

					case 'users':
						$where = '';
					break;
				}
				
				if(!empty($_REQUEST['q'])) {
					
					// Build search params
					$search = new CHV\Search;
					$search->q = $_REQUEST['q'];
					$search->type = $list_request;
					$search->request = $_REQUEST;
					$search->requester = CHV\Login::getUser();
					$search->build();
					
					if(!G\check_value($search->q)) {
						throw new Exception('Missing search term', 400);
					}
					
					$where .= $where == '' ? $search->wheres : preg_replace('/WHERE /', ' AND ', $search->wheres, 1);
					$binds = array_merge((array)$binds, $search->binds);
					
				}
				
				$list_params = CHV\Listing::getParams(TRUE);
				
				if($list_params['sort'][0] == 'likes') {
					throw new Exception(_s('Request denied'), 403);
				}
				
				if($doing == 'get-album-contents') {
					$album_fetch = min(1000, $album['image_count']);
					$list_params = [
						'items_per_page' => $album_fetch,
						'page'		=> 0,
						'limit'		=> $album_fetch,
						'offset'	=> 0,
						'sort'		=> ['date', 'desc'],
					];
				}
				
				$list = new CHV\Listing;
				$list->setType($list_request); // images | users | albums
				$list->setOffset($list_params['offset']);
				$list->setLimit($list_params['limit']); // how many results?
				$list->setSortType($list_params['sort'][0]); // date | size | views | likes
				$list->setSortOrder($list_params['sort'][1]); // asc | desc
				if($category) {
					$list->setCategory($category);
				}
				$list->setWhere($where);
				$list->setOwner($owner_id);
				$list->setRequester($logged_user);
				
				if($list_request == 'images' && !empty($_REQUEST['albumid'])) {
					if($handler::getCond('forced_private_mode')) { // Remeber this override...
						$album['privacy'] = CHV\getSetting('website_content_privacy_mode');
					}
					$list->setPrivacy($album['privacy']);
				}

				if(is_array($binds)) {
					foreach($binds as $bind) {
						$list->bind($bind['param'], $bind['value']);
					}
				}
				$list->exec();
				
				$json_array['status_code'] = 200;

				if($doing == 'get-album-contents') {
					$json_array['album'] = G\array_filter_array($album, ['id', 'creation_ip', 'password', 'user', 'privacy_extra', 'privacy_notes'], 'rest');
					$contents = [];
					foreach($list->output_assoc as $v) {
						$contents[] = G\array_filter_array($v, ['id_encoded', 'url', 'url_viewer', 'filename', 'medium', 'thumb'], 'exclusion');
					}
					$json_array['is_output_truncated'] = $album['image_count'] > $album_fetch ? 1 : 0;
					$json_array['contents'] = $contents;
					
				} else {
					$json_array['html'] = $list->htmlOutput($output_tpl);
				}

			break;

			case 'edit': // EX 3X

				if(!$logged_user) {
					throw new Exception(_s('Login needed'), 403);
				}

				$editing = $_REQUEST['editing'];
				$type = $_REQUEST['edit'];
				$owner_id = !empty($_REQUEST['owner']) ? CHV\decodeID($_REQUEST['owner']) : $logged_user['id'];

				if(!in_array($type, ['image', 'album', 'images', 'albums', 'category', 'ip_ban'])) {
					throw new Exception('Invalid edit request', 100);
				}

				if(is_null($editing['id'])) {
					throw new Exception('Missing edit target id', 100);
				} else {
					$id = CHV\decodeID($editing['id']);
				}

				$editing['new_album'] = $editing['new_album'] == 'true';

				$allowed_to_edit = [
					'image'		=> ['name', 'category_id', 'title', 'description', 'album_id', 'nsfw'],
					'album'		=> ['name', 'privacy', 'album_id', 'description', 'password'],
					'category'	=> ['name', 'description', 'url_key'],
					'ip_ban'	=> ['ip', 'expires', 'message']
				];
				$allowed_to_edit['images'] = $allowed_to_edit['image'];
				$allowed_to_edit['albums'] = $allowed_to_edit['album'];

				if($editing['new_album']) {
					$new_album = ['new_album', 'album_name', 'album_privacy', 'album_password', 'album_description'];
					$allowed_to_edit['image'] = array_merge($allowed_to_edit['image'], $new_album);
					$allowed_to_edit['album'] = array_merge($allowed_to_edit['album'], $new_album);
				}

				$editing = G\array_filter_array($editing, $allowed_to_edit[$type], 'exclusion');

				// Inject the system level privacy override
				if($handler::getCond('forced_private_mode') and in_array($type, ['album', 'image'])) {
					$editing[$type == 'album' ? 'privacy' : 'album_privacy'] = CHV\getSetting('website_content_privacy_mode');
				}

				if(count($editing) == 0) {
					throw new Exception('Invalid edit request', 403);
				}

				if(!is_null($editing['album_id'])) {
					$editing['album_id'] = CHV\decodeID($editing['album_id']);
				}

				switch($type) {

					// Single image/album edit
					case 'image':

						$source_image_db = CHV\Image::getSingle($id, false, false);

						if(!$source_image_db) {
							throw new Exception("Image doesn't exists", 100);
						}

						if(!$logged_user['is_admin'] and $source_image_db['image_user_id'] !== $logged_user['id']) {
							throw new Exception('Invalid content owner request', 101);
						}

						if($editing['new_album']) {
							$inserted_album = CHV\Album::insert($editing['album_name'], $source_image_db['image_user_id'], $editing['album_privacy'], $editing['album_description'], $editing['album_password']);
							$editing['album_id'] = $inserted_album;
						}

						// Validate category
						if(!empty($editing['category_id']) and !array_key_exists($editing['category_id'], $handler::getVar('categories'))) {
							throw new Exception("Invalid category", 102);
						}

						unset($editing['album_privacy'], $editing['new_album'], $editing['album_name']);

						// Submit image DB edit
						CHV\Image::update($id, $editing);

						// Get the edited image
						$image_edit_db = CHV\Image::getSingle($id, false, false);

						// Changed album, get the slice
						if($source_image_db['image_album_id'] !== $image_edit_db['image_album_id'] && $image_edit_db['image_album_id']) {
							global $image_album_slice, $image_id;
							$image_album_slice = CHV\Image::getAlbumSlice($id, $image_edit_db['image_album_id'], 2)['formatted'];
							$image_id = $image_edit_db['image_id'];
						}

						$album_id = $image_edit_db['image_album_id'];

						$json_array['status_code'] = 200;
						$json_array['success'] = ['message' => 'Image edited', 'code' => 200];

						// Editing response
						$json_array['editing'] = $editing;
						$json_array['image'] = CHV\Image::formatArray($image_edit_db, true); // Safe formatted image

						// Append the HTML slice
						if($image_album_slice) {

							// Add the album URL to the slice
							$image_album_slice['url'] = CHV\Album::getUrl(CHV\encodeID($album_id));

							ob_start();
							G\Render\include_theme_file('snippets/image_album_slice');
							$html = ob_get_contents();
							ob_end_clean();

							$json_array['image']['album']['slice'] = array(
								'next' => $image_album_slice['next']['url_viewer'],
								'prev' => $image_album_slice['prev']['url_viewer'],
								'html' => $html
							);

						} else {
							$json_array['image']['album']['slice'] = null;
						}

					break;

					case 'album':

						if($id) {
							$source_album_db = CHV\Album::getSingle($id, false);
							if(!$source_album_db) {
								throw new Exception("Album doesn't exists", 100);
							}
							if(!$logged_user['is_admin'] and $source_album_db['album_user_id'] !== $logged_user['id']) {
								throw new Exception('Invalid content owner request', 101);
							}
						}

						// We want to move contents or edit?
						if(isset($editing['album_id']) or $editing['new_album']) {
							$album_move = true;
							if($editing['new_album']) {
								$editing['album_id'] = CHV\Album::insert($editing['album_name'],  $source_album_db['album_user_id'], $editing['album_privacy'], $editing['album_description'], $editing['album_password']);
							}
							$album_move_contents = CHV\Album::moveContents($id, $editing['album_id']);
						} else {
							unset($editing['album_privacy'], $editing['new_album'], $editing['album_name']);
							$album_edit = CHV\Album::update($id, $editing);
						}

						// Get the edited album
						$album_edited = CHV\Album::getSingle($editing['album_id'] ? $editing['album_id'] : $id);

						if(!$album_edited) {
							throw new Exception("Edited album doesn't exists", 100);
						}

						$json_array['status_code'] = 200;
						$json_array['success'] = ['message' => 'Album edited', 'code' => 200];

						// New moved album or current edited album
						$json_array['album'] = $album_edited;

						if($album_move) {
							$json_array['old_album'] = CHV\Album::formatArray(CHV\Album::getSingle($id, false), true); // Safe formatted album
							$json_array['album']['html'] = CHV\Listing::getAlbumHtml($album_edited['id']);
							$json_array['old_album']['html'] = CHV\Listing::getAlbumHtml($id);
						}

					break;

					case 'category':
						if(!$logged_user['is_admin']) {
							throw new Exception('Invalid content owner request', 403);
						}
						// Validate ID
						$id = $_REQUEST['editing']['id'];
						if(!array_key_exists($id, $handler::getVar('categories'))) {
							throw new Exception('Invalid target category', 100);
						}
						// Validate name
						if(!$editing['name']) {
							throw new Exception('Invalid category name', 101);
						}
						// Validate URL key
						if(!preg_match('/^[-\w]+$/', $editing['url_key'])) {
							throw new Exception('Invalid category URL key', 102);
						}

						//  Category URL key being used?
						if($handler::getVar('categories')) {
							foreach($handler::getVar('categories') as $v) {
								if($v['id'] == $id) continue;
								if($v['url_key'] == $editing['url_key']) {
									$category_error = true;
									break;
								}
							}
						}
						if($category_error) {
							throw new Exception('Category URL key already being used.', 103);
						}

						G\nullify_string($editing['description']);

						$update_category = CHV\DB::update('categories', $editing, ['id' => $id]);

						if(!$update_category) {
							throw new Exception('Failed to edit category', 400);
						}

						$category = CHV\DB::get('categories', ['id' => $id])[0];
						$category['category_url'] = G\get_base_url('category/' . $category['category_url_key']);
						$category = CHV\DB::formatRow($category);

						$json_array['status_code'] = 200;
						$json_array['success'] = ['message' => 'Category edited', 'code' => 200];
						$json_array['category'] = $category;

					break;
					
					case 'ip_ban':
						if(!CHV\Login::isAdmin()) {
							throw new Exception('Invalid content owner request', 403);
						}
						
						$id = $_REQUEST['editing']['id'];
						
						// Validate IP
						if(!G\is_valid_ip($editing['ip'])) {
							throw new Exception('Invalid IP address', 101);
						}
						
						// Validate expiration
						if(!empty($editing['expires']) and !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $editing['expires'])) {
							throw new Exception('Invalid expiration date format', 102);
						}
						
						try {
							// Already banned?
							$ip_already_banned = CHV\Ip_ban::getSingle(['ip' => $editing['ip']]);
							
							if($ip_already_banned and $ip_already_banned['id'] !== $id) {
								throw new Exception(_s('IP address already banned'), 103);
							}
							
							// Fix expiration
							if(empty($editing['expires'])) {
								$editing['expires'] = NULL;
							}
							
							// OK to go
							$editing = array_merge($editing, ['expires_gmt' => is_null($editing['expires']) ? NULL : gmdate('Y-m-d H:i:s', strtotime($editing['expires']) )]);

							if(!CHV\Ip_ban::update(['id' => $id], $editing)) {
								throw new Exception('Failed to edit IP ban', 400);
							}

							$json_array['status_code'] = 200;
							$json_array['success'] = array('message' => 'IP ban edited', 'code' => 200);
							$json_array['ip_ban'] = CHV\Ip_ban::getSingle(['id' => $id]);
							
						} catch(Exception $e) {
							$json_array = [
								'status_code' => 403,
								'error' => ['message' => $e->getMessage(), $e->getCode()]
							];
							break;
						}
						
					break;
					
				}

			break;

			case 'add-user':

				// Must be admin
				if(!$logged_user['is_admin']) {
					throw new Exception(_s('Request denied'), 403);
				}

				$user = $_REQUEST['user'];

				foreach(['username', 'email', 'password', 'role'] as $v) {
					if($user[$v] == '') {
						throw new Exception(_s('Missing values'), 100);
					}
				}
				
				// Validate username
				if(!CHV\User::isValidUsername($user['username'])) {
					throw new Exception(_s('Invalid username'), 101);
				}
				
				// Validate email
				if(!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
					throw new Exception(_s('Invalid email'), 102);
				}
				
				// Validate password
				if(!preg_match('/'.CHV\getSetting('user_password_pattern').'/', $user['password'])) {
					throw new Exception(_s('Invalid password'), 103);;
				}

				// Validate role
				if(!in_array($user['role'], ['admin', 'user'])) {
					throw new Exception(_s('Invalid role'), 104);
				}

				// Username already being used?
				if(CHV\DB::get('users', ['username' => $user['username']])) {
					throw new Exception(_s('Username already being used'), 200);
				}

				// Email already being used?
				if(CHV\DB::get('users', ['email' => $user['email']])) {
					throw new Exception(_s('Email already being used'), 200);
				}

				// Ok to create user

				$add_user = CHV\User::insert([
					'username'	=> $user['username'],
					'email'		=> $user['email'],
					'is_admin'	=> $user['role'] == 'admin' ? 1 : 0
				]);

				// Add the password
				if($add_user) {
					CHV\Login::addPassword($add_user, $user['password'], false);
				}

				$json_array['status_code'] = 200;
				$json_array['success'] = ['message' => 'User added', 'code' => 200];

			break;

			case 'add-category':
				// Must be admin
				if(!$logged_user['is_admin']) {
					throw new Exception(_s('Request denied'), 403);
				}

				$category = $_REQUEST['category'];

				foreach(['name', 'url_key'] as $v) {
					if($category[$v] == '') {
						throw new Exception(_s('Missing values'), 100);
					}
				}

				// Validate URL key
				if(!preg_match('/^[-\w]+$/', $category['url_key'])) {
					throw new Exception('Invalid category URL key', 102);
				}
				
				// Category URL key being used?
				if($handler::getVar('categories')) {
					foreach($handler::getVar('categories') as $v) {
						if($v['url_key'] == $category['url_key']) {
							$category_error = true;
							break;
						}
					}
				}
				if($category_error) {
					throw new Exception('Category URL key already being used.', 103);
				}

				G\nullify_string($category['description']);

				$category = G\array_filter_array($category, ['name', 'url_key', 'description'], 'exclusion');

				// Ok to add category
				$add_category = CHV\DB::insert('categories', $category);

				$category = CHV\DB::get('categories', ['id' => $add_category])[0];
				$category['category_url'] = G\get_base_url('category/' . $category['category_url_key']);
				$category = CHV\DB::formatRow($category);

				$json_array['status_code'] = 200;
				$json_array['success'] = ['message' => 'Category added', 'code' => 200];
				$json_array['category'] = $category;

			break;
			
			case 'add-ip_ban':
				// Must be admin
				if(!CHV\Login::isAdmin()) {
					throw new Exception(_s('Request denied'), 403);
				}
				
				$ip_ban = G\array_filter_array($_REQUEST['ip_ban'], ['ip', 'expires', 'message'], 'exclusion');
				
				// Validate IP
				if(!G\is_valid_ip($ip_ban['ip'])) {
					throw new Exception('Invalid IP address', 101);
				}
				
				// Validate expiration
				if(!empty($ip_ban['expires']) and !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ip_ban['expires'])) {
					throw new Exception('Invalid expiration date format', 102);
				}
				
				try {
					// Already banned?
					if(CHV\Ip_ban::getSingle(['ip' => $ip_ban['ip']])) {
						throw new Exception(_s('IP address already banned'), 103);
					}
					
					// Fix expiration
					if(empty($ip_ban['expires'])) {
						$ip_ban['expires'] = NULL;
					}
					
					// OK to go
					$ip_ban = array_merge($ip_ban, ['date' => G\datetime(), 'date_gmt' => G\datetimegmt(), 'expires_gmt' => is_null($ip_ban['expires']) ? NULL : gmdate('Y-m-d H:i:s', strtotime($ip_ban['expires']) )]);
					$add_ip_ban = CHV\Ip_ban::insert($ip_ban);
					
				} catch(Exception $e) {
					$json_array = [
						'status_code' => 403,
						'error' => ['message' => $e->getMessage(), $e->getCode()]
					];
					break;
				}
				
				$json_array['status_code'] = 200;
				$json_array['success'] = ['message' => 'IP ban added', 'code' => 200];
				$json_array['ip_ban'] = CHV\Ip_ban::getSingle(['id' => $add_ip_ban]);

			break;
			
			case 'edit-category':
			case 'flag-safe':
			case 'flag-unsafe':

				if(!$logged_user) {
					throw new Exception(_s('Login needed'), 403);
				}
				
				$editing = $_REQUEST['editing'];
				$owner_id = $logged_user['id'];

				// Admin
				if(!$logged_user['is_admin'] and $owner_id !== $logged_user['id']) {
					throw new Exception('Invalid content owner request', 403);
				}

				$ids = array();
				foreach($editing['ids'] as $id) {
					$ids[] = CHV\decodeID($id);
				}

				$images = CHV\Image::getMultiple($ids);
				$images_ids = [];

				foreach($images as $image) {
					if(!$logged_user['is_admin'] and $image['image_user_id'] != $logged_user['id']) {
						continue;
					}
					$images_ids[] = $image['image_id'];
				}

				if(!$images_ids) {
					throw new Exception('Invalid content owner request', 101);
				}

				// There is no CHV\Image::editMultiple, so we must cast manually the editing
				
				switch($doing) {
					case 'flag-safe':
					case 'flag-unsafe':
						$query_field = 'nsfw';
						$prop = $editing['nsfw'] == 1 ? 1 : 0;
						$msg = 'Content flag changed';
					break;
					case 'edit-category':
						$query_field = 'category_id';
						$prop = $editing['category_id'] ?: NULL;
						$msg = 'Content category edited';
					break;
				}

				$db = CHV\DB::getInstance();
				$db->query('UPDATE `' . CHV\DB::getTable('images') . '` SET `image_'.$query_field.'`=:prop WHERE `image_id` IN ('.implode(',', $images_ids).')');
				$db->bind(':prop', $prop);
				$db->exec();

				$json_array['status_code'] = 200;
				$json_array['success'] = ['message' => $msg, 'code' => 200];
				
				if($query_field == 'category_id') {
					$json_array['category_id'] = $prop;
				}

			break;

			case 'move':
			case 'create-album':

				if(!$logged_user) {
					throw new Exception(_s('Login needed'), 403);
				}

				$type = $_REQUEST['type'];

				if(!in_array($type, array('images', 'album', 'albums'))) {
					throw new Exception('Invalid album ' . ($_REQUEST['action'] == 'move' ? 'move' : 'create') . ' request', 100);
				}

				$album = $_REQUEST['album'];
				$album['new'] = $album['new'] == 'true';
				$owner_id = !empty($_REQUEST['owner']) ? CHV\decodeID($_REQUEST['owner']) : $logged_user['id'];

				if(!$logged_user['is_admin'] and $owner_id !== $logged_user['id']) {
					throw new Exception('Invalid content owner request', 403);
				}

				// Inject the system level privacy override
				if($handler::getCond('forced_private_mode')) {
					$album['privacy'] = CHV\getSetting('website_content_privacy_mode');
				}

				// Had to create an album ?
				$album_id = $album['new'] ? CHV\Album::insert($album['name'], $owner_id, $album['privacy'], $album['description'], $album['password']) : CHV\decodeID($album['id']);
				$album_db = CHV\Album::getSingle($album_id, false);

				if(!is_array($album['ids']) && count($album['ids']) == 0) {
					throw new Exception('Invalid source album ids' . ($_REQUEST['action'] == 'move' ? 'move' : 'create') . ' request', 100);
				}

				$ids = array();
				foreach($album['ids'] as $id) {
					$ids[] = CHV\decodeID($id);
				}

				// Move by type
				if($type == 'images') {

					$images = CHV\Image::getMultiple($ids);
					$images_ids = [];

					foreach($images as $image) {
						if(!$logged_user['is_admin'] and $image['image_user_id'] != $logged_user['id']) {
							continue;
						}
						$images_ids[] = $image['image_id'];
					}

					if(!$images_ids) {
						throw new Exception('Invalid content owner request', 101);
					}

					$album_add = CHV\Album::addImages($album_db['album_id'], $images_ids);

				} else {
					$album_move = true;

					$albums = CHV\Album::getMultiple($ids);
					$albums_ids = array();

					foreach($albums as $album) {
						if(!$logged_user['is_admin'] and $album['album_user_id'] != $logged_user['id']) {
							continue;
						}
						$albums_ids[] = $album['album_id'];
					}

					if(!$albums_ids) {
						throw new Exception('Invalid content owner request', 101);
					}

					$album_to_album = CHV\Album::moveContents($albums_ids, $album_id);
				}

				$album_move_db = $album_db['album_id'] ? CHV\Album::getSingle($album_db['album_id'], false) : CHV\User::getStreamAlbum($owner_id);

				$json_array['status_code'] = 200;
				$json_array['success'] = ['message' => 'Content added to album', 'code' => 200];

				// Moving to album
				if($album_move_db) {
					$json_array['album'] = CHV\Album::formatArray($album_move_db, true);
					$json_array['album']['html'] = CHV\Listing::getAlbumHtml($album_move_db['album_id']);
				}

				// Add the old albums to the object
				if($type == 'albums') {
					$json_array['albums_old'] = [];
					foreach($ids as $album_id) {
						$album_item = CHV\Album::formatArray(CHV\Album::getSingle($album_id, false), true);
						$album_item['html'] = CHV\Listing::getAlbumHtml($album_id);
						$json_array['albums_old'][] = $album_item;

					}
				}

			break;

			case 'delete':

				if(!$logged_user) {
					throw new Exception(_s('Login needed'), 403);
				}

				$deleting = $_REQUEST['deleting'];
				$type = $_REQUEST['delete'];
				$owner_id = $_REQUEST['owner'] != NULL ? CHV\decodeID($_REQUEST['owner']) : $logged_user['id'];

				$multiple = $_REQUEST['multiple'] == 'true';
				$single = $_REQUEST['single'] == 'true';
				if(!$multiple) $single = TRUE;
				
				// Admin
				if(in_array($type, ['avatar', 'background', 'user', 'category', 'ip_ban']) and !CHV\Login::isAdmin() and $owner_id !== $logged_user['id']) {
					throw new Exception('Invalid content owner request', 403);
				}

				if(in_array($type, ['avatar', 'background'])) {
					try {
						CHV\User::deletePicture($owner_id == $logged_user['id'] ? $logged_user : $owner_id, $type);
                        $json_array['status_code'] = 200;
						$json_array['success'] = ['message' => 'Profile background deleted', 'code' => 200];
					} catch(Exception $e) {
						throw new Exception($e->getMessage(), $e->getCode());
					}
					break;
				}

				if($type == 'user') {
					CHV\User::delete($owner_id == $logged_user['id'] ? $logged_user : $owner_id);
					break;
				}

				if($single) {
					if(is_null($deleting['id'])) {
						throw new Exception('Missing delete target id', 100);
					}
				} else {
					if(count($deleting['ids']) == 0) {
						throw new Exception('Missing delete target ids', 100);
					}
				}

				if($type == 'category') {
					if(!array_key_exists($deleting['id'], $handler::getVar('categories'))) {
						throw new Exception('Invalid target category', 100);
					}
					$delete_category = CHV\DB::delete('categories', ['id' => $deleting['id']]);
					if($delete_category) {
						$update_images = CHV\DB::update('images', ['category_id' => NULL], ['category_id' => $deleting['id']]);
					} else {
						throw new Exception('Error deleting category', 400);
					}
					break;
				}
				
				if($type == 'ip_ban') {
					if(!CHV\Ip_ban::delete(['id' => $deleting['id']])) {
						throw new Exception('Error deleting IP ban', 400);
					}
					break;
				}

				if(!in_array($type, ['image', 'album', 'images', 'albums'])) {
					throw new Exception('Invalid delete request', 100);
				}

				$db_field_prefix = in_array($type, array('image', 'images')) ? 'image' : 'album';

				switch($type) {
					case 'image':
					case 'images':
						$Class_fn = 'CHV\\Image';
					break;

					case 'album':
					case 'albums':
						$Class_fn = 'CHV\\Album';
					break;
				}

				if($single) {

					if(is_null($deleting['id'])) {
						throw new Exception('Missing delete target id', 100);
					} else {
						$id = CHV\decodeID($deleting['id']);
					}

					$content_db = $Class_fn::getSingle($id, false, false);

					if($content_db) {
						if(!$logged_user['is_admin'] and $content_db[$db_field_prefix . '_user_id'] !== $logged_user['id']) {
							throw new Exception('Invalid content owner request', 403);
						}
						$delete = $Class_fn::delete($id);
					} else {
						throw new Exception("Content doesn't exists", 100);
					}

					$affected = $delete;

				} else {

					if(count($deleting['ids']) > 0) {
						$ids = array();
						foreach($deleting['ids'] as $id) {
							$ids[] = CHV\decodeID($id);
						}
					}

					if(!is_array($deleting['ids'])) {
						throw new Exception('Expecting ids array values, '.gettype($deleting['ids']).' given', 100);
					}

					$contents_db = $Class_fn::getMultiple($ids);
					$owned_ids = [];

					foreach($contents_db as $content_db) {
						if(!$logged_user['is_admin'] and $content_db[$db_field_prefix . '_user_id'] != $logged_user['id']) {
							continue;
						}
						$owned_ids[] = $content_db[$db_field_prefix . '_id'];
					}

					if(!$owned_ids) {
						throw new Exception('Invalid content owner request', 101);
					}
					
					$delete = $Class_fn::deleteMultiple($owned_ids);

					$affected = $delete;
				}
                
				$json_array['success'] = [
					'message' => ucfirst($type) . ' deleted',
					'code' 	  => 200,
					'affected' => $affected
				];

			break;
                
            case 'test':
                if(!$logged_user['is_admin']) {
					throw new Exception('Invalid request', 403);
				}
                switch($_REQUEST['test']['object']) {
                    case 'email':
                        // Validate email
                        if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception(_s('Invalid email'), 100);
                        }
                        CHV\send_mail($_REQUEST['email'], _s('Test email from %s @ %t', ['%s' => CHV\getSetting('website_name'), '%t' => G\datetime()]), _s('This is just a test'));
                        $json_array['success'] = ['message' => _s('Test email sent to %s.', $_REQUEST['email']), 'code' => 200];
                    break;
                }
            break;
			
			case 'notifications':
				if(!$logged_user) {
					throw new Exception('Invalid request', 403);
				}
				$notification_array = [
					'user_id'	=> $logged_user['id']
				];
				$notifications = CHV\Notification::get($notification_array);
				CHV\Notification::markAsRead($notification_array);
				$json_array['status_code'] = 200;
				if($notifications) {
					$json_array['html'] = '';
					$template = '<li%class>%avatar<span class="notification-text">%message</span><span class="how-long-ago">%how_long_ago</span></li>';
					$avatar_src_tpl = [
						0 => '<span class="user-image default-user-image"><span class="icon icon-user"></span></span>',
						1 => '<img class="user-image" src="%user_avatar_url" alt="%user_name_short_html">'
					];
					$avatar_tpl = [
						0 => $avatar_src_tpl[0],
						1 => '<a href="%user_url">%user_avatar</a>'
					];
					foreach($notifications as $k => $v) {
						switch($v['type']) {
							case 'like':
								$message = _s('%u liked your %t %c', [
									'%t' => _s($v['content_type']),
									'%c' => '<a href="'.$v['image']['url_viewer'].'">'.$v['image']['title_truncated_html'].'</a>'
								]);
							break;
							case 'follow':
								$message = _s('%u is now following you');
							break;
						}
						$v['message'] = strtr($message, [
							'%u' => $v['user']['is_private'] ? _s('A private user') : ('<a href="'.$v['user']['url'].'">'.$v['user']['name_short_html'].'</a>'),
						]);
						if($v['user']['is_private']) {
							$avatar = $avatar_tpl[0];
						} else {
							$avatar = strtr($avatar_tpl[1], [
								'%user_url'		=> $v['user']['url'],
								'%user_avatar'	=> strtr($avatar_src_tpl[isset($v['user']['avatar']) ? 1 : 0], [
									'%user_avatar_url' 		=> $v['user']['avatar']['url'],
									'%user_name_short_html' => $v['user']['name_short_html'],
								]),
							]);
						}
						$json_array['html'] .= strtr($template, [
							'%class'		=> !$v['is_read'] ? ' class="new"' : NULL,
							'%avatar'		=> $avatar,
							'%user_url'		=> $v['user']['url'],
							'%message'		=> $v['message'],
							'%how_long_ago'	=> CHV\time_elapsed_string($v['date_gmt']),
						]);
					}
				} else {
					$json_array['html'] = NULL;
				}
			break;

			default: // EX X
				throw new Exception(!G\check_value($_REQUEST['action']) ? 'empty action' : 'invalid action', !G\check_value($_REQUEST['action']) ? 0 : 1);
			break;
		}
        // Inject any missing status_code
        if(isset($json_array['success']) and !isset($json_array['status_code'])) {
            $json_array['status_code'] = 200;
        }
		$json_array['request'] = $_REQUEST;
		G\Render\json_output($json_array);
	} catch(Exception $e) {
		if($_REQUEST['action'] == 'upload') {
			@unlink($image_upload['uploaded']['file']);
			@unlink($image_medium['file']);
			@unlink($image_thumb['file']);
		}
		$json_array = G\json_error($e);
		$json_array['request'] = $_REQUEST;
		G\Render\json_output($json_array);
	}

};