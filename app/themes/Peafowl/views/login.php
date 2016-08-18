<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="center-box c24"> 
	<div class="content-width">

		<div class="header default-margin-bottom">
			<h1><?php _se('Sign in'); ?></h1>
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
			
			<form class="c9 phablet-c1 grid-columns" method="post" autocomplete="off" data-action="validate">
				
				<?php echo G\Render\get_input_auth_token(); ?>
				
				<div class="input-label margin-top-0">
					<label for="login-subject"><?php _se('Username or Email address'); ?></label>
					<input type="text" name="login-subject" id="login-subject" class="text-input" autocomplete="off" value="<?php echo get_post() ? get_safe_post()["login-subject"] : ""; ?>" placeholder="<?php _se('Username or Email address'); ?>" required>
					<span class="input-warning red-warning"></span>
				</div>
				<div class="input-label input-password">
					<label for="login-password"><?php _se('Password'); ?></label>
					<input type="password" name="password" id="login-password" class="text-input" autocomplete="off" placeholder="<?php _se('Enter your password'); ?>" required>
					<span class="input-below text-align-right"><a href="<?php echo G\get_base_url("account/password-forgot"); ?>"><?php _se('Forgot password?'); ?></a></span>
				</div>
				
				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>
				
				<div class="btn-container">
					<button class="btn btn-input default" type="submit"><?php _se('Sign in'); ?></button>
					<span class="btn-alt checkbox-label color-inherit margin-left-10"><label for="form-keep-login"><input type="checkbox" name="keep-login" id="form-keep-login" value="1"><?php _se('Keep me logged in'); ?></label></span>
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
$(document).ready(function() {
	PF.fn.growl.expirable("<?php echo get_error(); ?>");
});
</script>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>