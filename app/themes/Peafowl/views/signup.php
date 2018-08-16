<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24">
	<div class="content-width">

		<div class="header default-margin-bottom">
			<h1><?php _se('Create account'); ?></h1>
		</div>

		<div class="form-content overflow-auto">

			<?php
				if(CHV\getSetting('social_signin')) {
			?>
			<div class="phablet-show phone-show hidden">
				<div class="signup-services-column c11 phablet-c1 phone-text-align-center phablet-text-align-center grid-columns">
					<h2><?php _se('Sign in with another account'); ?></h2>
					<ul class="sign-services"><?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?></ul>
				</div>
				<div class="c4 phablet-c1 grid-columns">
					<div class="or-separator c2 phablet-c1 margin-right-auto margin-left-auto margin-top-40"><span><?php _se('or'); ?></span></div>
				</div>
			</div>
			<?php
				}
			?>

			<form id="form-signup" class="c9 phablet-c1 grid-columns" method="post" autocomplete="off" data-action="validate">

				<?php echo G\Render\get_input_auth_token(); ?>

				<div class="input-label margin-top-0">
					<label for="signup-email"><?php _se('Email address'); ?></label>
					<input type="email" name="email" id="signup-email" class="text-input" autocomplete="off" value="<?php echo get_safe_post()['email']; ?>" placeholder="<?php _se('Email address'); ?>" required>
					<span class="input-warning red-warning"><?php echo get_input_errors()["email"]; ?></span>
				</div>
				<div class="input-label">
					<label for="signup-username"><?php _se('Username'); ?></label>
					<input type="text" name="username" id="signup-username" class="text-input" autocomplete="off" value="<?php echo get_safe_post()["username"]; ?>" pattern="<?php echo CHV\getSetting('username_pattern'); ?>" rel="tooltip" title='<?php _se('%i to %f characters<br>Letters, numbers and "_"', ['%i' => CHV\getSetting('username_min_length'), '%f' => CHV\getSetting('username_max_length')]); ?>' data-tipTip="right" placeholder="<?php _se('Username'); ?>" required>
					<span class="input-warning red-warning"><?php echo get_input_errors()["username"]; ?></span>
				</div>

				<?php if(is_show_resend_activation()) { ?>
				<div class="font-size-small phone-text-align-center"><?php _se('If you have already signed up maybe you need to request to %s', '<a href="' . G\get_base_url('account/resend-activation') . '">' . _s('resend account activation') . '</a> to activate your account.'); ?></div>
				<?php } ?>

				<div class="input-label input-password">
					<label for="signup-password"><?php _se('Password'); ?></label>
					<input type="password" name="password" id="signup-password" class="text-input" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" placeholder="<?php _se('Password'); ?>" required>
					<div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
					<span class="input-warning red-warning" data-text="password-meter-message"><?php echo get_input_errors()["password"]; ?></span>
				</div>

				<?php if(CHV\getSetting('user_minimum_age') > 0) { ?>
				<div class="input">
					<div class="checkbox-label"><label for="form-minimum-age-signup"><input type="checkbox" name="minimum-age-signup" id="form-minimum-age-signup" value="1" required><?php _se("I'm at least %s years old", CHV\getSetting('user_minimum_age')); ?></label></div>
					<span class="red-warning"><?php echo get_input_errors()['minimum-age-signup']; ?></span>
				</div>
				<?php } ?>

				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>

				<div class="input-label">
				  <div class="checkbox-label">
				    <label for="signup-accept-terms-policies">
				      <input type="checkbox" name="signup-accept-terms-policies" id="signup-accept-terms-policies" value="1" required><?php _se('I agree to the %terms_link and %privacy_link', ['%terms_link' => '<a ' . get_page_tos()['link_attr'] . '>'. _s('terms') .'</a>', '%privacy_link' => '<a ' . get_page_privacy()['link_attr'] . '>' . _s('privacy policy'). '</a>']); ?>
				    </label>
				  </div>
					<span class="red-warning"><?php echo get_input_errors()['signup-accept-terms-policies']; ?></span>
				</div>

				<div class="btn-container">
					<button class="btn btn-input default" type="submit"><?php _se('Create account'); ?></button>
				</div>
			</form>

      <?php
				if(CHV\getSetting('social_signin')) {
			?>
			<div class="phone-hide phablet-hide">
				<div class="c4 phablet-c1 grid-columns">
					<div class="or-separator c2 phablet-c1 margin-right-auto margin-left-auto margin-top-40"><span><?php _se('or'); ?></span></div>
				</div>
				<div class="signup-services-column c11 phablet-c1 phablet-text-align-center grid-columns">
					<h2><?php _se('Sign in with another account'); ?></h2>
					<ul class="sign-services"><?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?></ul>
				</div>
			</div>
            <?php
				}
			?>

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