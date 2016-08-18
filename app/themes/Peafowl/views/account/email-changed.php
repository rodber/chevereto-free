<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">
		<div class="header default-margin-bottom">
			<h1><?php _se('Email changed'); ?></h1>
		</div>
		<div>
			<p><?php _se('You have successfully changed your account email to %s', '<b>'.CHV\Login::getUser()['email'].'</b>'); ?></p>
			<div class="btn-container"><a href="<?php echo CHV\Login::getUser()['url']; ?>" class="btn btn-input default"><?php _se('Go to my profile'); ?></a> <span class="btn-alt"><?php _se('or'); ?> <a href="<?php echo G\get_base_url(); ?>"><?php _se('Go to homepage'); ?></a></span></div>
		</div>
	</div>
</div>

<?php if(is_error() and get_error()) : ?>
<script>
$(document).ready(function() {
	PF.fn.growl.expirable("<?php echo get_error(); ?>");
});
</script>
<?php endif; ?>

<?php G\Render\include_theme_footer(); ?>