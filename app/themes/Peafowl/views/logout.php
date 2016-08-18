<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">

		<div class="header default-margin-bottom">
			<h1><?php _se('Logged out'); ?></h1>
		</div>
		
		<div>
			<p><?php _se('You have been logged off %s. Hope to see you soon.', CHV\getSetting('website_name')); ?></p>
			<div class="btn-container"><a href="<?php echo G\get_base_url(); ?>" class="btn btn-input default"><?php _se('Go to homepage'); ?></a></div>
		</div>
		
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>