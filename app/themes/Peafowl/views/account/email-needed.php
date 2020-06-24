<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php G\Render\include_theme_file('head'); ?>
<body id="login" class="full--wh">
	<?php G\Render\include_theme_file('custom_hooks/body_open'); ?>
	<div class="display-flex height-min-full">
		<?php G\Render\include_theme_file('snippets/quickty/background_cover'); ?>
		<div class="flex-center">
			<div class="content-box card-box col-8-max text-align-center">
			<div class="fancy-box">
				<h1 class="fancy-box-heading"><?php _se('Add your email address'); ?></h1>
				<div class="content-section"><?php _se(CHV\getSetting('require_user_email_confirmation') ? 'A confirmation link will be sent to this email with details to activate your account.' : 'You must add an email to continue with the account sign up.'); ?></div>
				<form class="content-section" method="post" autocomplete="off" data-action="validate">	
					<fieldset class="fancy-fieldset">
						<div>
							<input type="email" name="email" class="input animate" autocomplete="off" value="<?php echo get_safe_post()['email']; ?>" placeholder="<?php _se('Your email address'); ?>" required>
							<div class="text-align-left red-warning"><?php echo get_input_errors()['email']; ?></div>
						</div>
					</fieldset>
					<?php G\Render\include_theme_file('snippets/quickty/recaptcha_form'); ?>
					<div class="content-section">
						<button class="btn btn-input default" type="submit"><?php _se('Submit'); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php G\Render\include_theme_file('snippets/quickty/top_left'); ?>
</div>
<?php if (get_post() && is_error()) {
    ?>
<script>
$(document).ready(function() {
	PF.fn.growl.expirable("<?php echo get_error(); ?>");
});
</script>
<?php
}
G\Render\include_theme_footer(); ?>