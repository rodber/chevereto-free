<?php
/** Use this file to customize the order and the behaviour of the share links used by the share button **/
global $share_links_networks;
$share_links_networks = array(
	'mail'		=> array(
		'url' 	=> 'mailto:?subject=%TITLE%&body=%URL%',
		'label' => 'Email'
	),
	'facebook'	=> array(
		'url'	=> 'http://www.facebook.com/share.php?u=%URL%',
		'label' => 'Facebook'
	),
	'twitter'	=> array(
		'url'	=> 'https://twitter.com/intent/tweet?original_referer=%URL%&url=%URL%&via=%TWITTER%&text=%TITLE%',
		'label' => 'Twitter'
	),
	'google-plus' => array(
		'url'	=> 'https://plus.google.com/u/0/share?url=%URL%',
		'label'	=> 'Google+'
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
	/*
	'stumbleupon' => array(
		'url'	=> 'http://www.stumbleupon.com/submit?url=%URL%',
		'label'	=> 'StumbleUpon'
	),
	*/
	'reddit'	=> array(
		'url'	=> 'http://reddit.com/submit?url=%URL%',
		'label' => 'reddit'
	),
	'vk'		=> array(
		'url'	=> 'http://vk.com/share.php?url=%URL%',
		'label' => 'VK'
	)
);