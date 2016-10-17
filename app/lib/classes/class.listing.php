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

namespace CHV;
use G, Exception;

class Listing {
	
	protected static $valid_types = ['images', 'albums', 'users'];
	protected static $valid_sort_types = ['date', 'size', 'views', 'id', 'image_count'];
	
	// Set the type of list
	public function setListing($listing) {
		$this->listing = $listing;
	}
	
	// Sets the type of resource being listed
	public function setType($type) {
		$this->type = $type;
	}
	
	// Sets the offset (sql> LIMIT offset,limit)
	public function setOffset($offset) {
		$this->offset = intval($offset);
	}
	
	// Sets the limit (sql> LIMIT offset,limit)
	public function setLimit($limit) {
		$this->limit = intval($limit);
	}
	
	public function setItemsPerPage($count) {
		$this->items_per_page = intval($count);
	}
	
	// Sets the sort type (sql> SORT BY sort_type)
	public function setSortType($sort_type) {
		$this->sort_type = $sort_type == 'date' ? 'id' : $sort_type;
	}
	
	// Sets the sort order (sql> DESC | ASC)
	public function setSortOrder($sort_order) {
		$this->sort_order = $sort_order;
	}
	
	// Sets the WHERE clause
	public function setWhere($where) {
		$this->where = !empty($where) ? $where : NULL;
	}
	
	// Sets the owner id of the content, usefull to add privacy
	public function setOwner($user_id) {
		$this->owner = $user_id;
	}
	
	// Sets the user id of the request, usefull to add privacy
	public function setRequester($user) {
		$this->requester = $user;
	}
	
	// Sets the category
	public function setCategory($category) {
		$this->category = (int)$category;
	}
	
	// Sets the privacy layer of this listing
	public function setPrivacy($privacy) {
		$this->privacy = $privacy;
	}
	
	// Sets the tools available for this listing (only if applies)
	public function setTools($tools=[]) {
		$this->tools = $tools;
	}
	
	public function bind($param, $value, $type = null) {
		$this->binds[] = array(
			'param' => $param,
			'value' => $value,
			'type'  => $type
		);
	}
	
	public function getTotals($bool) {
		$this->get_totals = $bool ? true : false;
	}
	
	/**
	 * Do the thing
	 * @Exeption 4xx
	 */
	public function exec($get_total_count=false) {

		$this->validateInput();

		$tables = DB::getTables();
		
		if(empty($this->requester)) {
			$this->requester = Login::getUser();
		} else if(!is_array($this->requester)) {
			$this->requester = User::getSingle($this->requester, 'id');
		}
		
		$joins = [
			// Get image + storage + parent album + user uploader
			'images' => [
				'storages'	=> 'LEFT JOIN '.$tables['storages'].' ON '.$tables['images'].'.image_storage_id = '.$tables['storages'].'.storage_id',
				'users'		=> 'LEFT JOIN '.$tables['users'].' ON '.$tables['images'].'.image_user_id = '.$tables['users'].'.user_id',
				'albums'	=> 'LEFT JOIN '.$tables['albums'].' ON '.$tables['images'].'.image_album_id = '.$tables['albums'].'.album_id',
				'categories'=> 'LEFT JOIN '.$tables['categories'].' ON '.$tables['images'].'.image_category_id = '.$tables['categories'].'.category_id',
			],
			'users' => [],
			'albums' => [
				'user'		=> 'LEFT JOIN '.$tables['users'].' ON '.$tables['albums'].'.album_user_id = '.$tables['users'].'.user_id'
			]
		];
		
		// Attempt to add explicit clauses
		if(!empty($this->where)) {
			$where_clauses = explode(' ', str_ireplace('WHERE ', NULL, $this->where));
			$where_arr = [];
			foreach($where_clauses as $clause) {
				if(!preg_match('/\./', $clause)) {
					$field_prefix = explode('_', $clause, 2)[0]; // field prefix (singular)
					$table = DB::getTableFromFieldPrefix($field_prefix); // image -> chv_images
					$table_prefix = G\get_app_setting('db_table_prefix');
					$table_key = !empty($table_prefix) ? G\str_replace_first($table_prefix, NULL, $table) : $table;					
					if(array_key_exists($table_key, $tables)) {
						$where_arr[] = $table . '.' . $clause;
					} else {
						$where_arr[] = $clause;
					}
				} else {
					$where_arr[] = $clause; // Let it be
				}
			}
			$this->where = 'WHERE ' . implode(' ', $where_arr);
		}

		// Add category clause
		if($this->type == 'images' && $this->category) {
			$category_qry = $tables['images'] . '.image_category_id = ' . $this->category;
			if(empty($this->where)) {
				$this->where = 'WHERE ' . $category_qry;
			} else {
				$this->where .= ' AND ' . $category_qry;
			}
		}

		// Privacy layer
		if(!$this->requester['is_admin'] && in_array($this->type, array('images', 'albums')) && ((!$this->owner || !$this->requester) || $this->owner !== $this->requester['id'])) {
			
			if(empty($this->where)) {
				$this->where = 'WHERE ';
			} else {
				$this->where .= ' AND ';
			}
			
			$nsfw_off = $this->requester ? !$this->requester['show_nsfw_listings'] : !getSetting('show_nsfw_in_listings');
			
			if($this->type == 'images' && $nsfw_off) {
				$this->where .= $tables['images'].'.image_nsfw = 0 AND ';
			}
			
			if(getSetting('website_privacy_mode') == 'public' || $this->privacy == 'private_but_link' || getSetting('website_content_privacy_mode') == 'default') {
				$this->where .= '(' . $tables['albums'].'.album_privacy NOT IN';
				$privacy_modes = ['private', 'private_but_link', 'password', 'custom'];
				if(in_array($this->privacy, $privacy_modes)) {
					unset($privacy_modes[array_search($this->privacy, $privacy_modes)]);
				}
				$this->where .= " (" . "'" . implode("','", $privacy_modes) . "'" . ") ";
				$this->where .=  "OR ".$tables['albums'].'.album_privacy IS NULL OR '.$tables['albums'].'.album_user_id';
				$this->where .= (!$this->requester ? ' IS NULL' : '='.$this->requester['id']) . ')';
			} else {
				$injected_requester = !$this->requester['id'] ? 0 : $this->requester['id'];
				$this->where .= '(' . $tables['albums'].'.album_user_id = '.$injected_requester;				
				$this->where .= $this->type == 'albums' ? ')' : (' OR ' . $tables['images'].'.image_user_id = '.$injected_requester . ')');
			}
		}
		
		if(!empty($this->where)) {
			$this->where = "\n" . $this->where;
		}
		
		$type_singular = substr($this->type, 0, -1);		
		$order_by = "\n" . 'ORDER BY ' . DB::getTableFromFieldPrefix($type_singular) . '.' . $type_singular . '_' . $this->sort_type . ' ' . strtoupper($this->sort_order);
		$limit = "\n" . 'LIMIT ' . $this->offset . ',' . ($this->limit + 1);

		$base_table = $tables[$this->type];
		
		// Normal query
		if(empty($joins[$this->type])) {
			$query = 'SELECT * FROM ' . $base_table;
			$query .= $this->where . $order_by;
			if($this->offset < 0 || $this->limit < 0) {
				throw new ListingException('Limit integrity violation', 400);
			}
			$query .= $limit;
		// Alternative query
		} else {
			if(!empty($this->where)) {
				preg_match_all('/' . G\get_app_setting('db_table_prefix') . '([\w_]+)\./', $this->where, $where_tables);
				$where_tables = array_values(array_diff(array_unique($where_tables[1]), [$this->type]));
			} else {
				$where_tables = FALSE;
			}
			if($where_tables) {
				$join_tables = $where_tables;
			} else {
				reset($joins);
				$join_tables = [key($joins)];
			}
			$join = NULL;
			foreach($join_tables as $join_table) {
				if(!empty($joins[$this->type][$join_table])) {
					$join .= "\n" . $joins[$this->type][$join_table];
					unset($joins[$this->type][$join_table]);
				}
			}
			
			// Get rid of the original Exif data (for listings)
			$null_db = $this->type == 'images' ? ', NULL as image_original_exifdata ' : NULL;
			
			$query = 'SELECT *' . $null_db . 'FROM (SELECT * FROM ' . $base_table . $join . $this->where . $order_by . $limit . ') ' . $base_table . ' ' . "\n";
			$query .=  implode("\n", $joins[$this->type]);
			$query .= $order_by;
		}
		
		try {
			$db = DB::getInstance();
			$db->query($query);

			if(is_array($this->binds)) {
				foreach($this->binds as $bind) {
					$db->bind($bind['param'], $bind['value'], $bind['type']);
				}
			}

			$this->output = $db->fetchAll();
			$this->has_page_next = $db->rowCount() > $this->limit;
			if($db->rowCount() > $this->limit) {
				array_pop($this->output);
			}
			$this->output = G\safe_html($this->output);
			$this->nsfw = FALSE;
			
			$this->output_assoc = [];
			$formatfn = 'CHV\\' . ucfirst(substr($this->type, 0, -1));
			foreach($this->output as $k => $v) {
				$val = $formatfn::formatArray($v);
				$this->output_assoc[] = $val;
				if(!$this->nsfw and $val['nsfw']) {
					$this->nsfw = TRUE;
				}
			}
			
			$this->sfw = !$this->nsfw;
			
			$this->has_page_prev = $this->offset > 0;
			
		} catch(Exception $e) {
			throw new ListingException($e->getMessage(), 400);
		}
		
		// Get album slices and stuff
		if($this->type == 'albums' and $this->output) {
			
			$album_slice_qry_tpl = 'SELECT * FROM ' . $tables['images'] . ' LEFT JOIN ' . $tables['storages'] . ' ON ' . $tables['images'] . '.image_storage_id = ' . $tables['storages'] . '.storage_id WHERE ' . $tables['images'] . '.image_album_id=%ALBUM_ID% ORDER BY ' . $tables['images'] . '.image_id ASC LIMIT 0,5';
			$albums_slice_qry_arr = [];
			$albums_mapping = [];
			foreach($this->output as $k => &$album) {
				// Album count
				if($album['album_image_count'] < 0) {
					$album['album_image_count'] = 0;
				}
				$album['album_image_count_label'] = _n('image', 'images', $album['album_image_count']);
				// Album slice
				$albums_slice_qry_arr[] = str_replace('%ALBUM_ID%', $album['album_id'], $album_slice_qry_tpl);
				// Album mapping
				$albums_mapping[$album['album_id']] = $k;
			}
			
			$albums_slice_qry = '(' . implode(') ' . "\n" . 'UNION ALL ' . "\n" . '(', $albums_slice_qry_arr) . ')';
			
			try {
				$db->query($albums_slice_qry);
				$albums_slice = $db->fetchAll();
				if($albums_slice) {
					foreach($albums_slice as $slice) {
						$album_key = $albums_mapping[$slice['image_album_id']];
						if(!array_key_exists('album_images_slice', $this->output[$album_key])) {
							$this->output[$album_key]['album_images_slice'] = [];
						}
						$this->output[$album_key]['album_images_slice'][] = $slice;
					}
				}
			} catch(Exception $e) {
				throw new ListingException($e->getMessage(), 400);
			}

		}
		
		// Get user counts
		/*if($this->type == 'users') {
			if($this->sort_type == 'image_count') {
				G\key_asort($this->output, 'user_image_count');
				$this->output = $this->sort_order == 'desc' ? array_reverse($this->output) : $this->output;
			}
		}*/
		
	}
	
	/**
	 * validate_input aka "first stage validation"
	 * This checks for valid input source data before exec
	 * @Exception 1XX
	 */
	protected function validateInput() {
		
		if($this->limit == 1) {
			$this->sort_type = 'date';
			$this->sort_order = 'desc'; 
		}
		
		if(empty($this->offset)) {
			$this->offset = 0;
		}
		
		// Missing values
		$check_missing = ['type', 'offset', 'limit', 'sort_type', 'sort_order'];
		missing_values_to_exception($this, 'CHV\ListingException', $check_missing, 100);
		
		// Validate type
		if(!in_array($this->type, self::$valid_types)) {
			throw new ListingException('Invalid $type "'.$this->type.'"', 110);
		}
		
		// Validate limits
		if($this->offset == 0 && $this->limit == 0) {
			throw new ListingException('$offset and $limit are equal to 0 (zero)', 120);
		}
		
		// Validate sort type
		if(!in_array($this->sort_type, self::$valid_sort_types)) {
			throw new ListingException('Invalid $sort_type "'.$this->sort_type.'"', 130);
		}
		
		// Validate sort order
		if(!preg_match('/^(asc|desc)$/', $this->sort_order)) {
			throw new ListingException('Invalid $sort_order "'.$this->sort_order.'"', 140);
		}
		
	}
	
	public function htmlOutput($tpl_list='images') {
		
		if(!is_array($this->output)) {
			return;
		}
		
		if(is_null($tpl_list)) {
			$tpl_list = 'images';
		}
		
		$directory = new \RecursiveDirectoryIterator(G_APP_PATH_THEME . 'tpl_list_item/');
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex  = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
		
		$filelist = array();
		foreach($regex as $file) {
			$filelist = array_merge($filelist, $file);
		}
		
		$list_item_template = array();
		foreach($filelist as $file) {
			$file = G\forward_slash($file);
			$key = preg_replace('/\\.[^.\\s]{3,4}$/', '', str_replace(G_APP_PATH_THEME, "", $file));
			ob_start();
			require($file);
			$file_get_contents = ob_get_contents();
			ob_end_clean();
			$list_item_template[$key] = $file_get_contents;
		}
		
		$html_output = '';
		$tpl_list = preg_replace('/s$/', '', $tpl_list);
		
		foreach($this->output as $row) {
			switch($tpl_list) {
				case 'image':
				case 'user/image':
				case 'album/image':
				default: // key thing here...
					$Class = 'CHV\Image';
				break;
				case 'album':
				case 'user/album':
					$Class = 'CHV\Album';
				break;
				case 'user':
				case 'user/user':
					$Class = 'CHV\User';
				break;
			}
			
			$item = $Class::formatArray($row);
			
			$tools = $this->tools ?: NULL;
			
			if(function_exists('get_peafowl_item_list')) {
				$render = 'get_peafowl_item_list';
			} else {
				$render = 'CHV\Render\get_peafowl_item_list';
			}
			
			$html_output .= $render($tpl_list, $item, $list_item_template, Login::getUser()['id'], $tools);

		}
		
		return $html_output;
	}
	
	public static function getAlbumHtml($album_id, $template='user/albums') {
		try {
			$album = new Listing;
			$album->setType('albums');
			$album->setOffset(0);
			$album->setLimit(1);
			$album->setWhere('WHERE album_id=:album_id');
			$album->bind(':album_id', $album_id);
			$album->exec();
			return $album->htmlOutput($template);
		} catch(Exception $e) {
			throw new ListingException($e->getMessage(), 400);
		}
	}
	
	public static function getParams($json_call=false) {
		
		$items_per_page = getSetting('listing_items_per_page');
		$listing_pagination_mode = getSetting('listing_pagination_mode');
		
		$params = [];
		$params['items_per_page'] = $items_per_page;
		
		if(!$json_call and $listing_pagination_mode == 'endless') {
			$params['page'] = max(intval($_REQUEST['page']), 1);
			$params['limit'] = $params['items_per_page'] * $params['page'];
			$params['offset'] = 0;	
			
			// Switch endless to classic if we are dealing with large listings (from GET)
			if($params['limit'] > getSetting('listing_safe_count')) {
				$listing_pagination_mode = 'classic';
				Settings::setValue('listing_pagination_mode', $listing_pagination_mode );
			}
		}
		
		if(isset($_REQUEST['pagination']) or $listing_pagination_mode == 'classic') { // Static single page display
			$params['page'] = $_REQUEST['page'] ? intval($_REQUEST['page']) - 1 : 0;
			$params['limit'] = $params['items_per_page'];
			$params['offset'] = $params['page']*$params['limit'];
		}
		
		if($json_call) {
			$params = array_merge($params, [
				'page'	=> $_REQUEST['page'] ? $_REQUEST['page'] - 1 : 0,
				'limit'	=> $items_per_page
			]);
			$params['offset'] = $params['page'] * $params['limit'] + ($_REQUEST['offset'] ? $_REQUEST['offset'] : 0);
		}
		
		$default_sort = [
			0 => 'date',
			1 => 'desc'
		];
		
		preg_match('/(.*)_(asc|desc)/', $_REQUEST['sort'], $sort_matches);
		$params['sort'] = array_slice($sort_matches, 1);
		
		// Empty sort
		if(count($params['sort']) !== 2) {
			$params['sort'] = $default_sort;
		}
		
		// Check sort type
		if(!in_array($params['sort'][0], self::$valid_sort_types)) {
			$params['sort'][0] = $default_sort[0];
		}
		// Check sort order
		if(!in_array($params['sort'][1], ['asc', 'desc'])) {
			$params['sort'][1] = $default_sort[1];
		}
		
		return $params;
		
	}
	
}

class ListingException extends Exception {}