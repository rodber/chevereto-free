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
					<h1 class="fancy-box-heading"><?php _se('Create account'); ?></h1>
					<div class="content-section"><?php _se('Already have an account? %s now.', '<a href="'.G\get_base_url('login').'">'._s('Login').'</a>'); ?> <?php
                        if (is_show_resend_activation()) {
                            ?><?php _se('If you have already signed up maybe you need to request to %s to activate your account.', '<a href="'.G\get_base_url('account/resend-activation').'">'._s('resend account activation').'</a>'); ?><?php
                        } else {
                            ?><?php _se('You can also %s.', '<a href="'.G\get_base_url('account/resend-activation').'">'._s('resend account activation').'</a>'); ?></div>
					<?php
                        }
                    ?>
					<form class="content-section" method="post" autocomplete="off" data-action="validate">	
						<fieldset class="fancy-fieldset">
							<div class="position-relative">
								<input name="email" tabindex="1" autocomplete="off" autocorrect="off" autocapitalize="off" type="email" placeholder="<?php _se('Email address'); ?>" class="input animate" required value="<?php echo get_safe_post()['email']; ?>">
								<div class="text-align-left red-warning"><?php echo get_input_errors()['email']; ?></span>
							</div>
							<div class="position-relative">
								<input name="username" tabindex="2" autocomplete="off" autocorrect="off" autocapitalize="off" type="text" class="input animate" value="<?php echo get_safe_post()['username']; ?>" pattern="<?php echo CHV\getSetting('username_pattern'); ?>" rel="tooltip" title='<?php _se('%i to %f characters<br>Letters, numbers and "_"', ['%i' => CHV\getSetting('username_min_length'), '%f' => CHV\getSetting('username_max_length')]); ?>' data-tipTip="right" placeholder="<?php _se('Username'); ?>" required>
								<div class="text-align-left red-warning"><?php echo get_input_errors()['username']; ?></div>
							</div>
							<div class="input-password margin-bottom-10 position-relative">
								<input name="password" tabindex="4" type="password" placeholder="<?php _se('Password'); ?>" class="input animate" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" required>
								<div class="input-password-strength" rel="tooltip" title="<?php _se('Password strength'); ?>"><span style="width: 0%" data-content="password-meter-bar"></span></div>
							</div>
							<?php
                                if (CHV\getSetting('user_minimum_age') > 0) {
                                    ?>
							<div class="input-label text-align-left">
								<div class="checkbox-label"><label for="form-minimum-age-signup"><input type="checkbox" name="minimum-age-signup" id="form-minimum-age-signup" value="1" required><?php _se("I'm at least %s years old", CHV\getSetting('user_minimum_age')); ?></label></div>
								<div class="text-align-left red-warning"><?php echo get_input_errors()['minimum-age-signup']; ?></div>
							</div>
							<?php
                                } ?>
							<div class="input-label text-align-left">
								<div class="checkbox-label">
									<label for="signup-accept-terms-policies">
										<input type="checkbox" name="signup-accept-terms-policies" id="signup-accept-terms-policies" value="1" required>
										<span><?php _se('I agree to the %terms_link and %privacy_link', ['%terms_link' => '<a '.get_page_tos()['link_attr'].'>'._s('terms').'</a>', '%privacy_link' => '<a '.get_page_privacy()['link_attr'].'>'._s('privacy policy').'</a>']); ?></span>
									</label>
								</div>
								<div class="text-align-left red-warning"><?php echo get_input_errors()['signup-accept-terms-policies']; ?></div>
							</div>
						</fieldset>
						<?php G\Render\include_theme_file('snippets/quickty/recaptcha_form'); ?>
						<div class="btn-container">
							<button class="btn btn-input default" type="submit"><?php _se('Create account'); ?></button>
						</div>
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