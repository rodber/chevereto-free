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
					<h1 class="fancy-box-heading"><?php _se('Sign in with your account'); ?></h1>
					<?php if (CHV\getSetting('enable_signups')) {
    ?>
					<div class="content-section"><?php _se("Don't have an account? <a href='%s'>Sign up</a> now.", G\get_base_url('signup')); ?></div>
					<?php
} ?>
					<form class="content-section" method="post" autocomplete="off" data-action="validate">	
						<fieldset class="fancy-fieldset">
							<div>
								<input name="login-subject" tabindex="1" autocorrect="off" autocapitalize="off" type="text" placeholder="<?php _se('Username or Email address'); ?>" class="input animate" required>
							</div>
							<div class="input-with-button">
								<input name="password" tabindex="2" type="password" placeholder="<?php _se('Password'); ?>" class="input animate" required>
								<button type="submit" tabindex="3" class="cursor-pointer icon-input-submit"></button>
							</div>
						</fieldset>
						<div class="input-label-below text-align-right margin-top-5">
							<a href="<?php echo G\get_base_url('account/password-forgot'); ?>"><?php _se('Forgot password?'); ?></a>
						</div>
						<?php G\Render\include_theme_file('snippets/quickty/recaptcha_form'); ?>
					</form>
					<?php G\Render\include_theme_file('snippets/quickty/login_social'); ?>
				</div>
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