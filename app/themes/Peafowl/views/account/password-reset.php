<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">

		<div class="header default-margin-bottom">
			<h1><?php _se('Reset password'); ?></h1>
		</div>
		
		<?php
        	if(is_process_done()) {
		?>
		<div>
			<p><?php _se('Your password has been changed. You can now try logging in using your new password.'); ?></p>
			<div class="btn-container"><a href="<?php echo G\get_base_url(); ?>" class="btn btn-input default"><?php _se('Go to homepage'); ?></a> <span class="btn-alt"><?php _se('or'); ?> <a href="<?php echo G\get_base_url("login"); ?>"><?php _se('Login now'); ?></a></span></div>
		</div>
		<?php
        	} else {
		?>
		
		<p><?php _se('Enter the new password that you want to use.'); ?></p>
		
		<form class="form-content" method="post" autocomplete="off" data-action="validate">		
			
			<div class="c9">
				<div class="input-label input-password">
					<label for="new-password"><?php _se('New Password'); ?></label>
					<input type="password" name="new-password" id="new-password" class="text-input" value="<?php echo get_safe_post()["new-password"]; ?>" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" placeholder="<?php _se('Enter your new password'); ?>" required>
					<div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
					<span class="input-warning red-warning" data-text="password-meter-message"><?php echo get_input_errors()["new-password"]; ?></span>
				</div>
				<div class="input-label input-password">
					<label for="new-password-confirm"><?php _se('Confirm password'); ?></label>
					<input type="password" name="new-password-confirm" id="new-password-confirm" class="text-input" value="<?php echo get_safe_post()["new-password-confirm"]; ?>" placeholder="<?php _se('Re-enter your new password'); ?>" required>
					<span class="input-warning red-warning<?php echo get_input_errors()["new-password-confirm"] ? "" : " soft-hidden"; ?>" data-text="<?php _se("Passwords don't match"); ?>"><?php _se("Passwords don't match"); ?></span>
				</div>
				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>
			</div>

			<div class="btn-container">
				<button class="btn btn-input default" type="submit"><?php _se('Submit'); ?></button>
			</div>
			
		</form>
		<?php } ?>
		
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