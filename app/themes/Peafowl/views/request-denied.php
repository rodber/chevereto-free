<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">
    
		<div class="header default-margin-bottom">
			<h1><?php _se('Request denied'); ?></h1>
		</div>
		
		<div class="form-content">
			<p><?php _se("You either don't have permission to access this page or the link has expired."); ?></p>
			<div class="btn-container"><a href="<?php echo G\get_base_url(); ?>" class="btn btn-input default"><?php _se('Go to homepage'); ?></a></div>
		</div>
		
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>