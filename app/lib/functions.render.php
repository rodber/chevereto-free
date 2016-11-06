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

namespace CHV\Render;
use G, CHV;

if(!defined("access") or !access) die("This file cannot be directly accessed.");

/* ---------------------------------------------------------------------------------------------------------------------------------------- */
/*** STRING DATA FUNCTIONS ***/

function get_email_body_str($file) {
	ob_start();
	G\Render\include_theme_file($file);
	$mail_body = ob_get_contents();
	ob_end_clean();
	return $mail_body;
}

// For inline JS and CSS code from a given file
function get_theme_inline_code($file, $type=NULL) {
	if(!isset($type)) {
		$type = pathinfo(rtrim($file, '.php'), PATHINFO_EXTENSION);
	}
	if(!CHV\getSetting('minify_enable') or !in_array($type, ['js', 'css'])) {
		G\Render\include_theme_file($file);
	} else {
		$ob_start = ob_start();
		G\Render\include_theme_file($file);
		if($ob_start) {
			$code = ob_get_clean();
			ob_flush();
			if($code) {
				return get_cond_minified_code($code, $type);
			}
		}
	}
}
function show_theme_inline_code($file, $type=NULL) {
	G\Render\include_theme_file($file);
	// echo get_theme_inline_code($file, $type); // Don't minify this (saves execution time and is not needed)
}

/* ---------------------------------------------------------------------------------------------------------------------------------------- */
/*** THEME DATA FUNCTIONS ***/

function get_theme_file_url($file, $options=[]) {
	$filepath = G_APP_PATH_THEME . $file;
	$filepath_override = G_APP_PATH_THEME . 'overrides/' . $file;
	if(file_exists($filepath_override)) {
		$filepath = $filepath_override;
	}
	return get_static_url($filepath, $options);
}

function get_static_url($filepath, $options=[]) {
	$options = array_merge(['versionize' => true, 'minify' => NULL], $options);
	if($options['minify'] !== false) {
		$filepath = get_cond_minified_file($filepath, $options['forced']); // Handle the conditional minify
	}
	$return = G\absolute_to_url($filepath, defined('CHV_ROOT_URL_STATIC') ? CHV_ROOT_URL_STATIC : NULL);
	if($options['versionize']) {
		$return = versionize_src($return);
	}
	return $return;
}

function get_cond_minified_file($filepath, $forced=false) {
	// Check for theme override
	if(G\starts_with(G_APP_PATH_THEME, $filepath)) {
		$filepath_override = G\str_replace_first(G_APP_PATH_THEME, G_APP_PATH_THEME . 'overrides/', $filepath);
		if(file_exists($filepath_override)) {
			$filepath = $filepath_override;
		}
	}
	if(!CHV\getSetting('minify_enable')) return $filepath;
	return get_minified($filepath, ['forced' => $forced, 'output' => 'file']);
}
function get_cond_minified_code($code, $type='js') {
	if(!CHV\getSetting('minify_enable')) return $code;
	return get_minified($code, ['source_method' => 'inline', 'source_type' => $type, 'output' => 'inline']);
}

function get_minified($var, $options=[]) {
	$options = array_merge(['source_method' => 'file', 'forced' => false], (array) $options);
	try {
		$minify = new G\Minify(array_merge($options, [
			'source' => $var
		]));
		$minify->exec();
		$var = $minify->result;
	} catch(G\MinifyException $e) {
		error_log($e->getMessage());
	}
	return $var;
}

function theme_file_exists($var) {
	return file_exists(G_APP_PATH_THEME . $var);
}

/* ---------------------------------------------------------------------------------------------------------------------------------------- */
/*** HTML TAGS ***/

function get_html_tags() {
	$classes = 'tone-' . CHV\getSetting('theme_tone') . ' top-bar-' . CHV\getSetting('theme_top_bar_color') . ' unsafe-blur-' . (CHV\getSetting('theme_nsfw_blur') ? 'on' : 'off');
	return get_lang_html_tags() . ' class="' . $classes . '"';
}


/* ---------------------------------------------------------------------------------------------------------------------------------------- */
/*** LANGUAGE TAGS ***/

function get_lang_html_tags() {
	$lang = CHV\get_language_used();
	return 'xml:lang="'.$lang['base'].'" lang="'.$lang['base'].'" dir="'.$lang['dir'].'"';
}

/* ---------------------------------------------------------------------------------------------------------------------------------------- */
/*** FORM ASSETS ***/

function get_select_options_html($arr, $selected) {
	$html = '';
	foreach($arr as $k => $v) {
		$selected = is_bool($selected) ? ($selected ? 1 : 0) : $selected;
		$html .= '<option value="'.$k.'"'.($selected == $k ? ' selected' : '').'>'.$v.'</option>'."\n";
	}
	return $html;
}

function get_checkbox_html($options=[]) {
	
	if(!array_key_exists('name', $options)) {
		return 'ERR:CHECKBOX_NAME_MISSING';
	}
	
	$options = array_merge([
		'value_checked'		=> 1,
		'value_unchecked'	=> 0,
		'label'				=> $options['name'],
		'checked'			=> FALSE
	], $options);
	
	$tooltip = $options['tooltip'] ? (' rel="tooltip" title="'.$options['tooltip'].'"') : NULL;
	
	$html = '<div class="checkbox-label">' . "\n" .
			'	<label for="'.$options['name'].'"' . $tooltip  . '>'  . "\n" .
			'		<input type="hidden" name="'.$options['name'].'" value="'.$options['value_unchecked'].'">' . "\n" .
	        '		<input type="checkbox" name="'.$options['name'].'" id="'.$options['name'].'" ' . ((bool)$options['checked'] ? ' checked' : NULL) .' value="'.$options['value_checked'].'">' . $options['label'] . "\n" . 
			'	</label>' . "\n" .
			'</div>';
			
	return $html;
}

function get_recaptcha_html($theme="red", $key=NULL) {
	$public_key = CHV\getSetting('recaptcha_public_key');
	if($key) {
		$public_key = $key;
	}
	// Detect reCaptcha version
	if(preg_match('/[-_]+/', $public_key)) { // new one
		return '<script src="https://www.google.com/recaptcha/api.js"></script><div class="g-recaptcha" data-sitekey="'.$public_key.'" data-theme="'. ( CHV\getSetting('theme_tone') == 'light' ? 'light' : 'dark') . '"></div>';
	} else {
		require_once(CHV_APP_PATH_LIB_VENDOR . "recaptchalib.php");
		return '<script type="text/javascript">
			var RecaptchaOptions = {
				theme : "'.$theme.'"
			};
		</script>' . recaptcha_get_html($public_key, $theme);
	}
}


/**
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */

function get_share_links($share_element) {
	if(function_exists("get_share_links")) {
		return \get_share_links($share_element);
	}
	
	if(!$share_element["twitter"]) {
		$share_element["twitter"] = CHV\getSetting('twitter_account');
	}
	
	$share_element["urlencoded"] = array();
	
	foreach($share_element as $key => $value) {
		if($key == "urlencoded") continue;
		$share_element["urlencoded"][$key] = rawurlencode($value); 
	}

	global $share_links_networks;
	G\Render\include_theme_file('custom_hooks/share_links');
	
	if(!$share_links_networks) {
		$share_links_networks = array(
			'facebook'	=> array(
				'url'	=> 'http://www.facebook.com/share.php?u=%URL%',
				'label' => 'Facebook'
			),
			'twitter'	=> array(
				'url'	=> 'https://twitter.com/intent/tweet?original_referer=%URL%&url=%URL%&text=%TITLE%' . ($share_element['twitter'] ? '&via=%TWITTER%' : NULL),
				'label' => 'Twitter'
			),
			'google-plus' => array(
				'url'	=> 'https://plus.google.com/u/0/share?url=%URL%',
				'label'	=> 'Google+'
			),
			'whatsapp'	=> array(
				'url'	=> 'whatsapp://send?text=%TITLE% - ' . _s('view on %s', CHV\getSetting('website_name')) . ': %URL%',
				'label' => 'WhatsApp',
				'mobileonly' => TRUE,
			),
			'reddit'	=> array(
				'url'	=> 'http://reddit.com/submit?url=%URL%',
				'label' => 'reddit'
			),
			'vk'		=> array(
				'url'	=> 'http://vk.com/share.php?url=%URL%',
				'label' => 'VK'
			),
			'blogger'	=> array(
				'url'	=> 'http://www.blogger.com/blog-this.g?n=%TITLE%&source=&b=%HTML%',
				'label'	=> 'Blogger'
			),
			'tumblr'	=> array(
				'url'	=> 'http://www.tumblr.com/share/photo?source=%PHOTO_URL%&caption=%TITLE%&clickthru=%URL%&title=%TITLE%',
				'label'	=> 'Tumblr.'
			),
			'pinterest'	=> array(
				'url'	=> 'http://www.pinterest.com/pin/create/bookmarklet/?media=%PHOTO_URL%&url=%URL%&is_video=false&description=%DESCRIPTION%&title=%TITLE%',
				'label' => 'Pinterest'
			),
			'stumbleupon' => array(
				'url'	=> 'http://www.stumbleupon.com/submit?url=%URL%',
				'label'	=> 'StumbleUpon'
			),
			'mail'		=> array(
				'url' 	=> 'mailto:?subject=%TITLE%&body=%URL%',
				'label' => 'Email'
			),
		);
	}

	$return = array();
	
	foreach($share_links_networks as $key => $value) {
		$search = array("%URL%", "%TITLE%", "%DESCRIPTION%", "%HTML%", "%PHOTO_URL%", "%TWITTER%");
		$replace= array("url", "title", "description", "HTML", "image", "twitter");
		
		for($i=0; $i<count($replace); $i++) {
			if(array_key_exists($replace[$i], $share_element["urlencoded"])) {
				$replace[$i] = $share_element["urlencoded"][$replace[$i]];
			}
		}
		
		$value["url"] = str_replace($search, $replace, $value["url"]);
		
		$return[] = '<li'.($value['mobileonly'] ? ' class="hidden phone-show"' : NULL).'><a data-href="'.$value["url"].'" class="popup-link btn-32 btn-social btn-'.$key.'" rel="tooltip" data-tiptip="top" title="'.$value["label"].'"><span class="btn-icon icon-'.$key.'"></span></a></li>';
	}

	return $return;

}

/**
 * PEAFOWL FRAMEWORK
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */
function include_peafowl_head() {
	$peafowl_css = get_static_url(CHV_PATH_PEAFOWL . 'peafowl.css');
	$opensans_css = get_static_url(CHV_PATH_PEAFOWL . 'fonts/opensans/opensans.css');
	echo	'<meta name="generator" content="' . G_APP_NAME . ' ' . CHV\get_chevereto_version() . '">' . "\n" . 
			'<link rel="stylesheet" href="' . $peafowl_css . '">' . "\n" .
			'<link rel="stylesheet" href="' . $opensans_css . '">' . "\n\n" .
			'<script>document.documentElement.className += " js";(function(w,d,u){w.readyQ=[];w.bindReadyQ=[];function p(x,y){if(x=="ready"){w.bindReadyQ.push(y);}else{w.readyQ.push(x);}};var a={ready:p,bind:p};w.$=w.jQuery=function(f){if(f===d||f===u){return a}else{p(f)}}})(window,document);var devices=["phone","phablet","tablet","laptop","desktop","largescreen"];window_to_device=function(){for(var e=[480,768,992,1200,1880,2180],n=[],t="",d=document.documentElement.clientWidth||document.getElementsByTagName("body")[0].clientWidth||window.innerWidth,o=0;o<devices.length;++o)d>=e[o]&&n.push(devices[o]);0==n.length&&n.push(devices[0]),t=n[n.length-1];for(var o=0;o<devices.length;++o)document.documentElement.className=document.documentElement.className.replace(devices[o],""),o==devices.length-1&&(document.documentElement.className+=" "+t),document.documentElement.className=document.documentElement.className.replace(/\s+/g," ");if("laptop"==t||"desktop"==t){var c=document.getElementById("pop-box-mask");null!==c&&c.parentNode.removeChild(c)}},window_to_device(),window.onresize=window_to_device,$(document).ready(function(){PF.obj.devices=window.devices,PF.fn.window_to_device=window.window_to_device});</script>' . "\n\n";
			
	// We don't need no language localization
	/*
	foreach(CHV\get_enabled_languages() as $k => $v) {
		if(CHV\get_language_used()['code'] == $k) continue;
		echo '<link rel="alternate" hreflang="'.str_replace('_', '-', $k).'" href="'.G\get_base_url('?lang=' . $k).'">'. "\n";
	}
	*/
}

// Get cookie law banner
function get_cookie_law_banner() {
	return '<div id="cookie-law-banner" data-cookie="CHV_COOKIE_LAW_DISPLAY"><div class="c24 center-box position-relative"><p class="">' . _s('We use our own and third party cookies to improve your browsing experience and our services. If you continue using our website is understood that you accept this cookie policy.') . '</p><a data-action="cookie-law-close" class="cookie-law-close"><span class="icon icon-close"></span></a></div></div>' . "\n\n";
}

// Sensitive Cookie law display
function display_cookie_law_banner() {
	if(!CHV\getSetting('enable_cookie_law') or CHV\Login::getUser()) return;
	// No user logged in and cookie law has not been accepted
	if(!isset($_COOKIE['CHV_COOKIE_LAW_DISPLAY']) or (bool)$_COOKIE['CHV_COOKIE_LAW_DISPLAY'] !== FALSE) {
		echo get_cookie_law_banner();
	}
}

function include_peafowl_foot() {
	display_cookie_law_banner();
	$resources = [
		'peafowl'	=> CHV_PATH_PEAFOWL . 'peafowl.js',
		'chevereto' => G_APP_PATH_LIB . 'chevereto.js'
	];
	foreach($resources as $k => &$v) {
		$v = get_static_url($v);
	}
	$resources['scripts'] = get_static_url(CHV_PATH_PEAFOWL . 'js/scripts.js');
	$echo =
		 '<script src="' . $resources['scripts'] . '"></script>' . "\n" .
		 '<script>(function($,d){$.each(readyQ,function(i,f){$(f)});$.each(bindReadyQ,function(i,f){$(d).bind("ready",f)})})(jQuery,document)</script>' . "\n" .
		 '<script src="' . $resources['peafowl'] . '"></script>' . "\n" .
		 '<script src="' . $resources['chevereto'] . '"></script>' . "\n\n";
	if(method_exists('CHV\Settings','getChevereto')) {
		$echo .= '<script>var CHEVERETO = ' . json_encode(CHV\Settings::getChevereto()) . '</script>';
	}
	echo $echo;
}
 
function get_peafowl_item_list($tpl="image", $item, $template, $requester=NULL, $tools) {
	
	// todo: pass requester permissions
	if(empty($requester)) {
		$requester = CHV\Login::getUser();
	} else if(!is_array($requester) and !is_null($requester)) {
		$requester = CHV\User::getSingle($requester, 'id');
	}
	
	// Default
	$stock_tpl = 'IMAGE';
	
	if($tpl == 'album' || $tpl == 'user/album') {
		$stock_tpl = "ALBUM";
	}
	if($tpl == 'user' || $tpl == 'user/user') {
		$stock_tpl = 'USER';
		if($item['is_private']) {
			if($requester['is_admin']) {
				$item['name'] = '🔒 ' . $item['name'];
			} else {
				unset($item);
				$item = CHV\User::getPrivate();
			}
		}
	} else {
		if(array_key_exists('user', $item)) {
			CHV\User::fill($item["user"]);
		}
	}
	
	if($stock_tpl == 'IMAGE') {
		$item['liked'] = is_null($item['like']['user_id']) ? 0 : ($requester['id'] == $item['like']['user_id'] ? 1 : 0);
		if(!$item['is_animated'] || !isset($item['file_resource']['chain']['image'])) {
			$conditional_replaces['tpl_list_item/item_image_play_gif'] = NULL;
		}
	} else {
		if(!isset($item['images_slice'][0]['is_animated']) || $item['images_slice'][0]['is_animated'] == FALSE) {
			$conditional_replaces['tpl_list_item/item_image_play_gif'] = NULL;
		}
	}
	
	$filled_template = $template["tpl_list_item/$tpl"]; // Stock the unfilled template
	
	// Missing template file cause uncaught error
	$tpl_replacements = $template;
	
	if(!CHV\getSetting('enable_likes') || $requester['is_private'] || $item['user']['is_private']) {
		$conditional_replaces['tpl_list_item/item_like'] = NULL;
	}
	
	if($item['user']['is_private'] && !$requester['is_admin'] && $item["user"]["id"] !== $requester['id']) {
		unset($item['user']);
		$item['user'] = CHV\User::getPrivate();
		$conditional_replaces['tpl_list_item/image_description_user'] = NULL;
		$conditional_replaces['tpl_list_item/image_description_guest'] = NULL;
	} else {
		$conditional_replaces['tpl_list_item/image_description_private'] = NULL;
	}
	
	if($item['user']['is_private'] && $requester['is_admin']) {
		$item['user']['name'] = '🔒 ' . $item['user']['name'];
	}
	
	$conditional_replaces[$item["user"]["id"] == NULL ? "tpl_list_item/image_description_user" : "tpl_list_item/image_description_guest"] = NULL;	
	$conditional_replaces[$item["user"]["avatar"] == NULL ? "tpl_list_item/image_description_user_avatar" : "tpl_list_item/image_description_user_no_avatar"] = NULL;
	
	if($stock_tpl == "IMAGE") {
		$conditional_replaces['tpl_list_item/' . (!$item['file_resource']['chain']['image'] ? 'image_cover_image' : 'image_cover_empty')] = NULL;
	}
	
	if($stock_tpl == "ALBUM") {
		
		if($item['privacy'] !== 'password' || (!$requester['is_admin'] || $item["user"]["id"] !== $requester['id'])) {
			$item['password'] = NULL;
		}
		
		$conditional_replaces['tpl_list_item/' . (($item['image_count'] == 0 or !$item['images_slice'][0]['file_resource']) ? 'album_cover_image' : 'album_cover_empty')] = NULL;
		for($i=1; $i<count($item["images_slice"]); $i++) {
			if(!$item['images_slice'][$i]['file_resource']['chain']['thumb']) {
				continue;
			}
			$template["tpl_list_item/album_thumbs"] = str_replace("%$i", "", $template["tpl_list_item/album_thumbs"]);
		}
		$template["tpl_list_item/album_thumbs"] = preg_replace("/%[0-9]+(.*)%[0-9]+/", "", $template["tpl_list_item/album_thumbs"]);
	}
	
	if($stock_tpl == "USER") {
		$conditional_replaces[$item["avatar"] ? "tpl_list_item/user_no_avatar" : "tpl_list_item/user_avatar"] = NULL;
		foreach(array("twitter", "facebook", "website") as $social) {
			if(!$item[$social]) {
				$conditional_replaces["tpl_list_item/user_" . $social] = NULL;
			}
		}
		$conditional_replaces[empty($item["avatar"]['url']) ? "tpl_list_item/user_cover_image" : "tpl_list_item/user_cover_empty"] = NULL;
		$conditional_replaces[empty($item["background"]['url']) ? "tpl_list_item/user_background_image" : "tpl_list_item/user_background_empty"] = NULL;
	}
	
	if(is_null($requester)) {
		$show_item_edit_tools = FALSE;
		$show_item_public_tools = FALSE;
	} else {

		//$show_item_edit_tools = $item["user"]["id"] == $requester['id'];
		if(!is_null($tools)) {
			$show_item_edit_tools = !is_array($tools);
			$show_item_public_tools = is_array($tools);
		} else {
			$show_item_edit_tools = FALSE;
			$show_item_public_tools = FALSE;
		}
		
		if($requester['is_admin']) {
			$show_item_edit_tools = TRUE;
			$show_item_public_tools = FALSE;
		}
	}	
	
	if(!$show_item_public_tools) {
		$template['tpl_list_item/item_'.strtolower($stock_tpl).'_public_tools'] = NULL;
	}
	
	if(!$show_item_edit_tools) {
		$template['tpl_list_item/item_'.strtolower($stock_tpl).'_edit_tools'] = NULL;
	}
	
	if(!$requester['is_admin']) {
		$template['tpl_list_item/item_'.strtolower($stock_tpl).'_admin_tools'] = NULL;
	}
	
	foreach($conditional_replaces as $k => $v) {
		$template[$k] = $v;
	}
	
	preg_match_all("#%(tpl_list_item/.*)%#", $filled_template, $matches);
	
	if(is_array($matches[1])) {
		foreach($matches[1] as $k => $v) {
			$filled_template = replace_tpl_string($v, $template[$v], $filled_template);
		}
	}
	
	foreach($template as $k => $v) {
		$filled_template = replace_tpl_string($k, $v, $filled_template);
	}
	
	// Get rid of the useless keys
	unset($item['original_exifdata']);
	
	// Get rid of any empty property
	//$item = G\array_remove_empty($item);
	
	// Sensitive utf8 encode
	$utf8_encodes = [
		'image'	=> ['title', 'title_truncated', 'original_filename'],
		'album' => ['name', 'name_truncated', 'description'],
		'user'	=> ['name', 'bio'],
	];
	
	foreach($utf8_encodes as $k => $v) {
		if($k == strtolower($stock_tpl)) {
			foreach($v as $encode) {
				//$item[$encode] = mb_detect_encoding($item[$encode]);
			}
		} else {
			foreach($v as $encode) {
				//$item[$k][$encode] = mb_convert_encoding($item[$encode], 'UTF-8', "Windows-1252");
			}
		}
	}
	
	// Now stock the item values
	$replacements = array_change_key_case(flatten_array($item, $stock_tpl."_"), CASE_UPPER);
	
	unset($replacements['IMAGE_ORIGINAL_EXIFDATA']);
	
	if($stock_tpl == "IMAGE" or $stock_tpl == "ALBUM") {
		$replacements["ITEM_URL_EDIT"] = ($stock_tpl == "IMAGE" ? $item["url_viewer"] : $item["url"]) . "#edit";
	}
	
	// Public for the guest
	if(!array_key_exists('user', $item)) {
		$replacements['IMAGE_ALBUM_PRIVACY'] = 'public';
	}
	
	if($stock_tpl == 'IMAGE') {
		$replacements['IMAGE_FLAG'] = $item['nsfw'] ? 'unsafe' : 'safe';
	}
	
	$show_object = ($show_item_edit_tools || $show_item_public_tools) || ($requester['is_admin'] || (!is_null($requester) AND $item["user"]["id"] == $requester['id']));
	
	if($show_object) {
		$object = G\array_filter_array($item, ['image', 'medium', 'thumb', 'name', 'extension', 'size_formatted', 'display_url', 'how_long_ago', 'url', 'url_viewer', 'filename']);
		$replacements['DATA_OBJECT'] = "data-object='" . rawurlencode(json_encode(G\array_utf8encode($object))) . "'";
	} else {
		$replacements['DATA_OBJECT'] = NULL;
	}
	
	if($stock_tpl == 'IMAGE') {
		$replacements['SIZE_TYPE'] = CHV\getSetting('theme_image_listing_sizing') . '-size';
	}
	
	foreach($replacements as $k => $v) {
		$filled_template = replace_tpl_string($k, $v, $filled_template);
	}
	
	$column_sizes = array(
		"image"	=> 8,
		"album"	=> 8,
		"user"	=> 8
	);
	
	foreach($column_sizes as $k => $v) {
		$filled_template = replace_tpl_string("COLUMN_SIZE_".strtoupper($k), $v, $filled_template);
	}
	
	return $filled_template;
	
}

function replace_tpl_string($search, $replace, $subject) {
	return str_replace("%".$search."%", is_null($replace) ? "" : $replace, $subject);
}

// http://stackoverflow.com/a/9546215
function flatten_array($array, $prefix = '') {
    $result = array();
    foreach($array as $key => $value) {
        if(is_array($value)) {
            $result = $result + flatten_array($value, $prefix . $key . '_');
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

// This function is sort of an alias of php die() but with html error display
function chevereto_die($error_msg, $paragraph=NULL, $title=NULL) {
	if(!is_array($error_msg) && G\check_value($error_msg)) $error_msg = array($error_msg);
	
	if(is_null($paragraph)) {
		$paragraph = "The system has encountered errors that must be fixed to allow proper Chevereto functionality. Chevereto won't work until the following issues are solved:";
	}
	$solution = "Need help or questions about this? Go to <a href='http://chevereto.com/support' target='_blank'>Chevereto support<a/>.";
	$title = (!is_null($title)) ? $title : 'System error';
	$doctitle = $title . " - Chevereto";
	
	$handled_request = G_ROOT_PATH == '/' ? sanitize_path_slashes($_SERVER["REQUEST_URI"]) : str_ireplace(G_ROOT_PATH_RELATIVE, "", G\add_trailing_slashes($_SERVER["REQUEST_URI"]));
	$base_request = explode('/', rtrim(str_replace("//", "/", str_replace("?", "/", $handled_request)), '/'))[0];
	
	if($base_request == 'json' || $base_request == 'api'){
		$output = array(
			'status_code' => 500,
			'status_txt' => G\get_set_status_header_desc(500),
			'error' => $title,
			'errors' => $error_msg
		);
		G\set_status_header(500);
		G\json_prepare();
		die(G\Render\json_output($output));
	}
	
	$html = [
		'<h1>'.$title.'</h1>',
		'<p>'.$paragraph.'</p>'
	];
	
	if(is_array($error_msg)) {
		$html[] = '<ul class="errors">';
		foreach($error_msg as $error) {
			$html[] = '<li>'.$error.'</li>';
		}
		$html[] = '</ul>';
	}

	$html[] = '<p>'.$solution.'</p>';
	$html = join("", $html);
	$template = CHV_APP_PATH_SYSTEM . 'template.php';
	
	if(!require_once($template)) {
		die("Can't find " . G\absolute_to_relative($system_template));
	}
	
	die();
}

function getFriendlyExif($Exif) {
	
	if(gettype($Exif) == 'string') {
		$Exif = json_decode($Exif);
	}

	if($Exif->Make) {
		$exif_one_line = [];
		if($Exif->ExposureTime) {
			$Exposure = $Exif->ExposureTime . 's';
			$exif_one_line[] = $Exposure;
		}
		if($Exif->FNumber or $Exif->COMPUTED->ApertureFNumber) {
			$Aperture = 'ƒ/' . ($Exif->FNumber ? G\fraction_to_decimal($Exif->FNumber) : explode('/', $Exif->COMPUTED->ApertureFNumber)[1]);
			$exif_one_line[] = $Aperture; 
		}
		if($Exif->ISOSpeedRatings) {
			$ISO = 'ISO' . $Exif->ISOSpeedRatings;
			$exif_one_line[] = $ISO;
		}
		if($Exif->FocalLength) {
			$FocalLength = G\fraction_to_decimal($Exif->FocalLength) . 'mm';
			$exif_one_line[] = $FocalLength;
		}
		
		$exif_relevant = [
			'XResolution',
			'YResolution',
			'ResolutionUnit',
			'ColorSpace',
			'Orientation',
			'Software',
			'BrightnessValue',
			'SensingMethod',
			'SceneCaptureType',
			'GainControl',
			'ExposureBiasValue',
			'MaxApertureValue',
			'ExposureProgram',
			'ExposureMode',
			'MeteringMode',
			'LightSource',
			'Flash',
			'WhiteBalance',
			'DigitalZoomRatio',
			'Contrast',
			'Saturation',
			'Sharpness',
			'ExifVersion',
			'DateTimeModified',
			'DateTimeOriginal',
			'DateTimeDigitized'
		];
		$ExifRelevant = [];
		foreach($exif_relevant as $v) {
			$ExifRelevant[$v] = exifReadableValue($Exif, $v);
		}
		$return = (object) [
			'Simple'	=> (object) [
				'Camera'			=> $Exif->Make . ' ' . $Exif->Model,
				'Capture'			=> implode(' ', $exif_one_line)
			],
			'Full'		=> (object) array_merge([
				'Manufacturer'		=> $Exif->Make,
				'Model'				=> $Exif->Model,
				'ExposureTime'		=> $Exposure,
				'Aperture'			=> $Aperture,
				'ISO'				=> preg_replace('/iso/i', '', $ISO),
				'FocalLength' 		=> $FocalLength
			], $ExifRelevant)
		];
		foreach($return->Full as $k => $v) {
			if(!$v) unset($return->Full->{$k});
		}
		return $return;
	}
	return null;
}

function exifReadableValue($Exif, $key) {
	$table = [
		'PhotometricInterpretation'	=> [
			0 => 'WhiteIsZero',
			1 => 'BlackIsZero',
			2 => 'RGB',
			3 => 'RGB Palette',
			4 => 'Transparency Mask',
			5 => 'CMYK',
			6 => 'YCbCr',
			8 => 'CIELab',
			9 => 'ICCLab',
			10 => 'ITULab',
			32803 => 'Color Filter Array',
			32844 => 'Pixar LogL',
			32845 => 'Pixar LogLuv',
			34892 => 'Linear Raw'
		],
		'ColorSpace' => [
			1 => 'sRGB',
			2 => 'Adobe RGB',
			65533 => 'Wide Gamut RGB',
			65534 => 'ICC Profile',
			65535 => 'Uncalibrated'
		],
		'Orientation' => [
			1 => 'Horizontal (normal)',
			2 => 'Mirror horizontal',
			3 => 'Rotate 180',
			4 => 'Mirror vertical',
			5 => 'Mirror horizontal and rotate 270 CW',
			6 => 'Rotate 90 CW',
			7 => 'Mirror horizontal and rotate 90 CW',
			8 => 'Rotate 270 CW'
		],
		'ResolutionUnit' => [
			1 => 'None',
			2 => 'inches', 
			3 => 'cm'
		],
		'ExposureProgram' => [
			0 => 'Not Defined',
			1 => 'Manual',
			2 => 'Program AE',
			3 => 'Aperture-priority AE',
			4 => 'Shutter speed priority AE',
			5 => 'Creative (Slow speed)',
			6 => 'Action (High speed)',
			7 => 'Portrait',
			8 => 'Landscape',
			9 => 'Bulb'
		],
		'MeteringMode' => [
			0 => 'Unknown',
			1 => 'Average',
			2 => 'Center-weighted average',
			3 => 'Spot',
			4 => 'Multi-spot',
			5 => 'Multi-segment',
			6 => 'Partial',
			255 => 'Other'
		],
		'ExposureMode' => [
			0 => 'Auto',
			1 => 'Manual',
			2 => 'Auto bracket'
		],
		'SensingMethod' => [
			1 => 'Monochrome area',
			2 => 'One-chip color area',
			3 => 'Two-chip color area',
			4 => 'Three-chip color area',
			5 => 'Color sequential area',
			6 => 'Monochrome linear',
			7 => 'Trilinear',
			8 => 'Color sequential linear'
		],
		'SceneCaptureType' => [
			0 => 'Standard',
			1 => 'Landscape',
			2 => 'Portrait',
			3 => 'Night'
		],
		'GainControl' => [
			0 => 'None',
			1 => 'Low gain up',
			2 => 'High gain up',
			3 => 'Low gain down',
			4 => 'High gain down'
		],
		'Saturation' => [
			0 => 'Normal',
			1 => 'Low',
			2 => 'High'
		],
		'Sharpness'	=>  [
			0 => 'Normal',
			1 => 'Soft',
			2 => 'Hard'
		],
		'Flash' => [
			0	=> 'No Flash',
			1	=> 'Fired',
			5	=> 'Fired, Return not detected',
			7	=> 'Fired, Return detected',
			8	=> 'On, Did not fire',
			9	=> 'On, Fired',
			13	=> 'On, Return not detected',
			15	=> 'On, Return detected',
			16	=> 'Off, Did not fire',
			20	=> 'Off, Did not fire, Return not detected',
			24	=> 'Auto, Did not fire',
			25	=> 'Auto, Fired',
			29	=> 'Auto, Fired, Return not detected',
			31	=> 'Auto, Fired, Return detected',
			32	=> 'No flash function',
			48	=> 'Off, No flash function',
			65	=> 'Fired, Red-eye reduction',
			69	=> 'Fired, Red-eye reduction, Return not detected',
			71	=> 'Fired, Red-eye reduction, Return detected',
			73	=> 'On, Red-eye reduction',
			77	=> 'On, Red-eye reduction, Return not detected',
			79	=> 'On, Red-eye reduction, Return detected',
			80	=> 'Off, Red-eye reduction',
			88	=> 'Auto, Did not fire, Red-eye reduction',
			89	=> 'Auto, Fired, Red-eye reduction',
			93	=> 'Auto, Fired, Red-eye reduction, Return not detected',
			95	=> 'Auto, Fired, Red-eye reduction, Return detected'
		],
		'LightSource' => [
			0	=> 'Unknown',
			1	=> 'Daylight',
			2	=> 'Fluorescent',
			3	=> 'Tungsten (Incandescent)',
			4	=> 'Flash',
			9	=> 'Fine Weather',
			10	=> 'Cloudy',
			11	=> 'Shade',
			12	=> 'Daylight Fluorescent',	
			13	=> 'Day White Fluorescent',
			14	=> 'Cool White Fluorescent',
			15	=> 'White Fluorescent',
			16	=> 'Warm White Fluorescent',
			17	=> 'Standard Light A',
			18	=> 'Standard Light B',
			19	=> 'Standard Light C',
			20	=> 'D55',
			21	=> 'D65',
			22	=> 'D75',
			23	=> 'D50',
			24	=> 'ISO Studio Tungsten',
			255	=> 'Other'
		]
	];
	$table['Contrast'] = $table['Saturation'];

    if(is_object($Exif) and is_array($Exif->$key)) {
        $value_arr = [];
        foreach($Exif->$key as $k) {
            $value_arr[] = $table[$key][$k];
        }
        $value = implode(', ', $value_arr);
    } else {
         $value = $table[$key][$Exif->$key];
    }
	
	switch($key) {
		case 'DateTime':
		case 'DateTimeOriginal':
		case 'DateTimeDigitized':
			$value =  preg_replace('/(\d{4})(:)(\d{2})(:)(\d{2})/', '$1-$3-$5', $value);	
		break;
		case 'WhiteBalance':
			$value = $value == 0 ? 'Auto' : $value;
		break;
		case 'BrightnessValue':
		case 'MaxApertureValue':
			$value = $value ? G\fraction_to_decimal($value) : NULL;
		break;
		case 'XResolution':
		case 'YResolution':
			$value = $value ? (floor(G\fraction_to_decimal($value)) . ' dpi') : NULL;
		break;
	}

	return $value ?: NULL;
}

function arr_printer($arr, $tpl='', $wrap=[]) {
	ksort($arr);
	$rtn = '';
	$rtn .= $wrap[0];
	foreach($arr as $k => $v) {
		if(is_array($v)) {
			$rtn .= strtr($tpl, ['%K' => $k, '%V' => arr_printer($v, $tpl, $wrap)]);
		} else {
			$rtn .= strtr($tpl, ['%K' => $k, '%V' => $v]);
		}
	}
	$rtn .= $wrap[1];
	return $rtn;
}

function versionize_src($src) {
	return $src.'?'.md5(CHV\get_chevereto_version());
}

function show_banner($banner, $sfw=TRUE) {
	return NULL;
}

function getPixel($type) {
	if(!in_array($type, ['queue', 'ping'])) return;
	return '<img data-content="'.$type.'-pixel" src="'. G\get_base_url('?'.$type.'&r=' . md5(G\datetimegmt())) .'" width="1" height="1" alt="" style="display: none;">';
}

function showQueuePixel() {
	if(version_compare(CHV\getSetting('chevereto_version_installed'), '3.5.5', '<') || CHV\DB::queryFetchSingle('SELECT EXISTS(SELECT 1 FROM '.CHV\DB::getTable('queues').' WHERE queue_status = "pending") as has')['has'] == 0) {
		return;
	};
	echo getPixel('queue');
}

function show_queue_img() {
	return showQueuePixel();
}

function showPingPixel() {
	if(CHV\getSetting('enable_automatic_updates_check') && (is_null(CHV\getSetting('update_check_datetimegmt')) || G\datetime_add(CHV\getSetting('update_check_datetimegmt'), 'P1D') < G\datetimegmt())) {
		echo getPixel('ping');
	}
}

function displayEmptyPixel() {
	header('Content-Type: image/gif');
	Header('Content-Length: 43');
	echo base64_decode('R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	die();
}

function showComments() {
	switch(CHV\getSetting('comments_api')) {
		case 'js':
			$html = CHV\getSetting('comment_code');
		break;
		case 'disqus':
			$disqus_secret = CHV\getSetting('disqus_secret_key');
			$disqus_public = CHV\getSetting('disqus_public_key');
			if(!empty($disqus_secret) && !empty($disqus_public)) {
				$logged_user = CHV\Login::getuser();
				$data = [
					'id'		=> $logged_user['id_encoded'],
					'username'	=> $logged_user['name'],
					'email'		=> $logged_user['email'],
					'avatar'	=> $logged_user['avatar']['url'],
					'url'		=> $logged_user['url']
				];
				function dsq_hmacsha1($data, $key) {
					$blocksize = 64;
					$hashfunc = 'sha1';
					if (strlen($key)>$blocksize) {
						$key = pack('H*', $hashfunc($key));
					}
					$key = str_pad($key, $blocksize,chr(0x00));
					$ipad = str_repeat(chr(0x36), $blocksize);
					$opad = str_repeat(chr(0x5c), $blocksize);
					$hmac = pack('H*', $hashfunc(($key^$opad).pack('H*', $hashfunc(($key^$ipad).$data))));
					return bin2hex($hmac);
				}
				$message = base64_encode(json_encode($data));
				$timestamp = time();
				$hmac = dsq_hmacsha1($message . ' ' . $timestamp, $disqus_secret);
				$auth = $message . ' ' . $hmac . ' ' . $timestamp;
			}
			$html = strtr('<div id="disqus_thread"></div>
<script>
var disqus_config = function() {
	this.page.url = "%page_url";
	this.page.identifier = "%page_id";
};
(function() {
	var d = document, s = d.createElement("script");
	s.src = "//%shortname.disqus.com/embed.js";
	s.setAttribute("data-timestamp", +new Date());
	(d.head || d.body).appendChild(s);
})();
var disqus_config = function () { 
	this.language = "%language_code";
	this.page.remote_auth_s3 = "%auth";
	this.page.api_key = "%api_key";
};
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>', [
	'%page_url'		=> G\get_current_url(),
	'%page_id'		=> G\str_replace_first(G\get_route_path(), G\get_route_name(), G\get_route_path(TRUE)), // image.ID
	'%shortname'	=> CHV\getSetting('disqus_shortname'),
	'%language_code'=> CHV\get_language_used()['base'],
	'%auth'			=> isset($auth) ? $auth : NULL,
	'%api_key'		=> $disqus_public,
]);
		break;
	}
	echo $html;
}