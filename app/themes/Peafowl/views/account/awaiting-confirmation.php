<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">
		<div class="header default-margin-bottom ">
			<h1><?php _se('Your account is almost ready'); ?></h1>
		</div>
		<p><?php _se("An email to %s has been sent with instructions to activate your account. The activation link is only valid for 48 hours. If you don't receive the instructions try checking your junk or spam filters.", '<b>' . get_signup_email() . '</b>'); ?></p>
		<div class="btn-container"><a href="<?php echo G\get_base_url(); ?>" class="btn btn-input default"><?php _se('Go to homepage'); ?></a> <span class="btn-alt"><?php _se('or'); ?> <a href="<?php echo G\get_base_url("account/resend-activation"); ?>"><?php _se('Resend activation'); ?></a></span></div>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>