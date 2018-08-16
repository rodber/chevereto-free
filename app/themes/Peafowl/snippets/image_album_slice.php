<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<?php
$image_album_slice = G\get_global('image_album_slice') ?: (function_exists('get_image_album_slice') ? get_image_album_slice() : NULL);
$image_id = function_exists('get_image') ? get_image()['id'] : G\get_global("image_id");

if(is_array($image_album_slice["images"]) && count($image_album_slice["images"]) > 0) {
	foreach($image_album_slice["images"] as $album_image) {
?>
<li<?php if($album_image["id"] == $image_id) echo ' class="current"'; ?>><a href="<?php echo $album_image["url_viewer"]; ?>"><img src="<?php echo $album_image["thumb"]["url"]; ?>" alt="<?php echo $album_image["name"]; ?>"></a></li>
<?php
	}
?>
<li class="more-link"><a href="<?php echo $image_album_slice["url"]; ?>" title=""><?php _se('view more'); ?></a></li>
<?php
}
?>