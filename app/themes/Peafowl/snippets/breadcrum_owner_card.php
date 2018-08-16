<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<?php
$owner = function_exists('get_owner') ? get_owner() : G\get_global("owner");
?>
<div class="breadcrum-item pop-btn pop-btn-auto pop-keep-click pop-btn-desktop">
	<a href="<?php echo $owner['url']; ?>" class="user-image">
		<?php if($owner['avatar']['url']) { ?>
		<img class="user-image" src="<?php echo $owner['avatar']['url']; ?>" alt="<?php echo $owner['username']; ?>">
		<?php } else { ?>
		<span class="user-image default-user-image"><span class="icon icon-user"></span></span>
		<?php } ?>
	</a>
	<span class="breadcrum-text float-left"><a class="user-link" href="<?php echo $owner['url']; ?>"><?php if($owner['is_private']) { ?><span class="user-meta font-size-small"><span class="icon icon-lock"></span></span><?php } ?><?php echo $owner['name_short_html']; ?><span class="arrow arrow-down"></span></a></span>
	<div class="pop-box pop-box-userdetails" style="display: none;">
		<div class="pop-box-inner">
			<div class="user-card no-avatar">
				<div class="user-card-header">
					<h2><a href="<?php echo $owner['url']; ?>" rel="author"><?php echo $owner['name']; ?></a></h2>
					<?php if($owner['is_private']) { ?><span class="user-meta font-size-small"><span class="icon icon-lock"></span><?php _se('Private profile'); ?></span><?php } ?>
				</div>
				<div><a class="user-link" href="<?php echo $owner['url']; ?>" rel="author"><?php echo $owner['username']; ?></a></div>
				<div class="or-separator"></div>
				<div class="user-card-footer">
					<a class="number-figures float-left" href="<?php echo $owner['url']; ?>"><b data-text="total-image-count"><?php echo $owner['image_count_display']; ?></b> <span data-text="total-image-count-label" data-label-single="<?php _ne('image', 'images', 1); ?>" data-label-plural="<?php _ne('image', 'images', 2); ?>"><?php _ne('image', 'images', $owner['image_count']); ?></span></a>
					<a class="number-figures float-left" href="<?php echo $owner['url_albums']; ?>"><b data-text="album-count"><?php echo $owner['album_count_display']; ?></b> <span data-text="album-label" data-label-single="<?php _ne('album', 'albums', 1); ?>" data-label-plural="<?php _ne('album', 'albums', 2); ?>"><?php _ne('album', 'albums', $owner['album_count']); ?></span></a>
				</div>
			</div>
		</div>
	</div>
</div>