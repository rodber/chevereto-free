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

class Page {

	static $table_fields = [
			'url_key',
			'type',
			'file_path',
			'link_url',
			'icon',
			'title',
			'description',
			'keywords',
			'is_active',
			'is_link_visible',
			'attr_target',
			'attr_rel',
			'sort_display'
		];

	public static function getSingle($var, $by='url_key') {
		try {
			$page = self::get([$by => $var], NULL, 1);
			return $page ?: NULL;
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}

	public static function getAll($args=[], $sort=[]) {
		try {
			$page = self::get($args, $sort, NULL);
			return $page ?: NULL;
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}

	public static function get($values, $sort=[], $limit=NULL) {
		try {
			$get = DB::get('pages', $values, 'AND', $sort, $limit);
			if($get[0]) {
				foreach($get as $k => $v) {
					self::formatRowValues($get[$k], $v);
				}
			} else {
				if($get) {
					self::formatRowValues($get);
				}
			}
			return $get;
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}

	public static function getPath($var=NULL) {
		return CHV_PATH_CONTENT_PAGES . (is_string($var) ? $var : NULL);
	}

	public static function getFields() {
		return self::$table_fields;
	}

	public static function update($id, $values) {
		try {
			return DB::update('pages', $values, array('id'=>$id));
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}

	public static function writePage($args=[]) {

		if(!$args['file_path']) {
			throw new PageException("Missing file_path argument", 100);
		}

		$file_path = self::getPath($args['file_path']);
		$file_dirname = dirname($file_path);
		$contents = !empty($args['contents']) ? $args['contents'] : NULL;

		if(!is_dir($file_dirname)) {
			$base_perms = fileperms(self::getPath());
			$old_umask = umask(0);
			if(mkdir($file_dirname, $base_perms, TRUE)) {
				chmod($file_dirname, $base_perms);
				umask($old_umask);
			} else {
				throw new PageException(_s("Can't create %s destination dir", $file_dirname), 500);
			}
		}

		// Skip empting an empty file
		if(file_exists($file_path) and $contents == NULL and filesize($file_path) == 0) {
			return TRUE;
		}

		$fh = @fopen($file_path, 'w');
		if(!$fh or fwrite($fh, $contents) === FALSE) { // Ternary if the write is zero (null)
			$st = FALSE;
		} else {
			$st = TRUE;
		}
		@fclose($fh);

		if(!$st) {
			throw new PageException(_s("Can't open %s for writing", $file_path), 501);
		}

		return TRUE;

	}

	public static function fill(&$page) {
		$page['title_html'] = $page['title'];
		$type_tr = [
			'internal'	=> _s('Internal'),
			'link'		=> _s('Link')
		];
		$page['type_tr'] = $type_tr[$page['type']];
		switch($page['type']) {
			case 'internal':
				$page['url'] = G\get_base_url('page/'. $page['url_key']);
				// Workaround default file_path
				if(empty($page['file_path'])) {
					$filepaths = [
						'default'	=> 'default/',
						'user'		=> NULL // base
					];
					$file_basename = $page['url_key'] . '.php';
					foreach($filepaths as $k => $v) {
						if($k == 'default') {
							//$file_basename =  $page['url_key'] . '.php';
						} else {

						}
						if(is_readable(self::getPath($v) . $file_basename)) {
							$page['file_path'] = $v . $file_basename;
						}
					}
				} else {
					$page_extension = G\get_file_extension($page['file_path']);
					// Disable PHP pages here
					if(G\get_app_setting('disable_php_pages') and $page_extension == 'php') {
						$page['file_path'] = G\str_replace_last($page_extension, 'html', $page['file_path']);
					}
				}
				$page['file_path_absolute'] = self::getPath($page['file_path']);
				if(!file_exists($page['file_path_absolute'])) {
					try {
						self::writePage(['file_path' => $page['file_path'], 'contents' => NULL]);
					} catch(Exception $e) {} // Silence
				}
			break;
			case 'link':
				$page['url'] = G\is_url($page['link_url']) ? $page['link_url'] : NULL;
			break;
		}
		$page['link_attr'] = 'href="' . $page['url'] . '"';
		if($page['attr_target'] !== '_self') {
			$page['link_attr'] .= ' target="' . $page['attr_target'] . '"';
		}
		if(!empty($page['attr_rel'])) {
			$page['link_attr'] .= ' rel="' . $page['attr_rel'] . '"';
		}
		if(!empty($page['icon'])) {
			$page['title_html'] = '<span class="btn-icon ' . $page['icon'] . '"></span> ' . $page['title_html'];
		}
	}

	// Format get row return
	protected static function formatRowValues(&$values, $row=[]) {
		$values = DB::formatRow(count($row) > 0 ? $row : $values);
		self::fill($values);
	}

	public static function insert($values=[]) {
		if(!is_array($values)) {
			throw new PageException('Expecting array, '.gettype($values).' given in ' . __METHOD__, 100);
		}
		try {
			return DB::insert('pages', $values);
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}

	public static function delete($page) {
		try {
			if(!is_array($page)) {
				$page = self::getSingle($page, 'id');
			}
			// Delete the page file for internal type
			if($page['type'] == 'internal' and file_exists($page['file_path_absolute'])) {
				@unlink($page['file_path_absolute']);
			}
			return DB::delete('pages', ['id' => $page['id']]);
		} catch(Exception $e) {
			throw new PageException($e->getMessage(), 400);
		}
	}
}

class PageException extends Exception {}