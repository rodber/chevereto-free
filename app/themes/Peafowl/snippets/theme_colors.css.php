<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php
	$default_color = '#00A7DA';
	$color = CHV\getSetting('theme_main_color');
	
	if(!G\is_valid_hex_color($color)) {
		$color = $default_color;
	}
?>
<style>
a,
a.number-figures:hover, a.number-figures:hover *,
.input .icon-input-submit:hover, .input .icon-input-submit:focus, .input .icon-input-submit.focus,
.btn.default.outline, .pop-btn-text,
.top-bar.white .top-btn-text:hover:not(.btn), .top-bar.white .opened .top-btn-text:not(.btn),
.ios .top-bar.white .opened .top-btn-text:not(.btn),
.ios .top-bar.white .opened .top-btn-text:not(.top-btn-number),
.breadcrum-text a:hover,
.content-tabs li:hover a,
.upload-box-heading .icon,
.list-item-image-btn:hover span,
.content-listing-pagination a:hover {
	color: <?php echo $color; ?>;
}

input:focus, textarea:focus, select:focus, input.search:focus, .input-focus,
.tone-dark input:focus, .tone-dark textarea:focus, .tone-dark select:focus, .tone-dark input.search:focus, .tone-dark .input-focus,
.btn.default.outline,
.btn.active,
.content-tabs li:hover, .content-tabs li.current, .content-tabs li.visited, .content-tabs li.visited:hover,
.content-tabs li.current,
.list-item:hover .list-item-avatar-cover,
input:focus, textarea:focus, select:focus, input.search:focus, .input-focus,
.tone-dark input:focus, .tone-dark textarea:focus, .tone-dark select:focus, .tone-dark input.search:focus, .tone-dark .input-focus {
	border-color: <?php echo $color; ?>;
}

.btn.active,
html:not(.phone) .pop-box-menu a:hover, .pop-box-menu a.focus,
.list-item-image-btn.liked,
.list-item-desc .user:hover {
	background-color: <?php echo $color; ?>;
}

.pop-btn-text .arrow-down,
.top-bar.white .top-btn-text:hover .arrow-down, .top-bar.white .opened .arrow-down,
.ios .top-bar.white .opened .top-btn-text .arrow-down,
.header-content-breadcrum a:hover .arrow-down {
	border-top-color: <?php echo $color; ?>;
}

.top-bar ul .pop-btn.current, .top-bar ul .top-btn-el.current {
	border-bottom-color: <?php echo $color; ?>;
}

.header-content-breadcrum a:hover .arrow-right {
	border-left-color: <?php echo $color; ?>;
}

<?php if($default_color !== $color) { ?>
input:focus, textarea:focus, select:focus, input.search:focus, .input-focus {
	box-shadow: 0 0 8px 0 rgba(<?php echo implode(',', G\hex_to_rgb($color)); ?>,.45);
}
<?php } ?>
</style>