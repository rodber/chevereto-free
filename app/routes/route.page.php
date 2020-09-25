<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

$route = function($handler) {

	$request_url_key = implode('/', $handler->request);

	// Get this page
	$page = CHV\Page::getSingle($request_url_key);

	// Exists or is active or is type default?
	if(!$page or !$page['is_active'] or $page['type'] !== 'internal') {
		return $handler->issue404();
	}

	// No file path set
	if(!$page['file_path_absolute']) {
		return $handler->issue404();
	}

	// File path doesn't exists
	if(!file_exists($page['file_path_absolute'])) {
		return $handler->issue404();
	}

	$pathinfo = pathinfo($page['file_path_absolute']);
	$page_extension = G\get_file_extension($page['file_path_absolute']);

	// Inject theme based path
	$handler->path_theme = G\add_ending_slash($pathinfo['dirname']);
	$handler->template = $pathinfo['filename'] . '.' . $page_extension;

	// Add page meta data
	$page_metas = [
		'pre_doctitle'		=> $page['title'],
		'meta_description'	=> htmlspecialchars($page['description']),
		'meta_keywords'		=> htmlspecialchars($page['keywords'])
	];
	foreach($page_metas as $k => $v) {
		if($v == NULL) continue;
		$handler->setVar($k, $v);
	}
};