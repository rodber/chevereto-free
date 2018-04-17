<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">

		<div class="header default-margin-bottom">
			<h1><?php _se('Add your email address'); ?></h1>
		</div>
		
		<div class="form-content">
			<p><?php _se(CHV\getSetting('require_user_email_confirmation') ? 'A confirmation link will be sent to this email with details to activate your account.' : 'You must add an email to continue with the account sign up.'); ?></p>
			<form id="form-signup" class="c9 grid-columns" method="post" autocomplete="off" data-action="validate">
				<div class="input-label">
					<label for="signup-email"><?php _se('Email address'); ?></label>
					<input type="email" name="email" id="signup-email" class="text-input" autocomplete="off" value="<?php echo get_safe_post()["email"]; ?>" placeholder="<?php _se('Your email address'); ?>" required>
					<span class="input-warning red-warning"><?php echo get_input_errors()["email"]; ?></span>
				</div>
				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>
				<div class="btn-container">
					<button class="btn btn-input default" type="submit"><?php _se('Add email'); ?></button>
				</div>
			</form>
		</div>
		
	</div>
</div>

<?php if(get_post() and is_error()) { ?>
<script>
$(function() {
	PF.fn.growl.expirable("<?php echo get_error(); ?>");
});
</script>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>