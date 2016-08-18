<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div id="maintenance-cover" style="background-image: url(<?php echo CHV\get_system_image_url(CHV\getSetting('maintenance_image')); ?>);">
	<div id="maintenance-cover-inner">
		<div id="maintenance-cover-content" class="c16 center-box">
			<a href="<?php echo G\get_base_url(); ?>" id="logo"><img class="replace-svg" src="<?php echo CHV\get_system_image_url(CHV\getSetting(CHV\getSetting('logo_vector_enable') ? 'logo_vector' : 'logo_image')); ?>" alt="<?php echo CHV\getSetting('website_name'); ?>"></a>
			<h1><?php _se('Website under maintenance'); ?></h1>
			<p><?php _se("We're performing scheduled maintenance tasks in the website. Please come back in a few minutes."); ?></p>
		</div>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>