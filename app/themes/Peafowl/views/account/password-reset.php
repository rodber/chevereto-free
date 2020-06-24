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
				<h1 class="fancy-box-heading"><?php _se('Reset password'); ?></h1>
				<?php
                    if (is_process_done()) {
                        ?>
				<div class="content-section"><?php _se('Your password has been changed. You can now try logging in using your new password.'); ?></div>
				<div class="content-section"><a href="<?php echo G\get_base_url('login'); ?>" class="btn btn-input default"><?php _se('Login now'); ?></a></div>		
				<?php
                    } else {
                        ?>
				<div data-message="new-password-confirm" class="red-warning<?php echo get_input_errors()['new-password-confirm'] ? '' : ' hidden-visibility'; ?>" data-text="<?php _se("Passwords don't match"); ?>"><?php _se("Passwords don't match"); ?></div>
				<form class="content-section" method="post" autocomplete="off" data-action="validate">	
					<fieldset class="fancy-fieldset">
						<div class="input-password position-relative">
							<input name="new-password" tabindex="1" type="password" placeholder="<?php _se('Enter your new password'); ?>" class="input animate" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" required>
							<div class="input-password-strength" rel="tooltip" title="<?php _se('Password strength'); ?>"><span style="width: 0%" data-content="password-meter-bar"></span></div>
						</div>
						<div class="input-password">
							<input name="new-password-confirm" tabindex="2" type="password" placeholder="<?php _se('Re-enter your new password'); ?>" class="input animate" required>
						</div>
					</fieldset>
					<?php G\Render\include_theme_file('snippets/quickty/recaptcha_form'); ?>
					<div class="content-section">
						<button class="btn btn-input default" type="submit"><?php _se('Submit'); ?></button>
					</div>
				</form>
				<?php
                    }
                ?>
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