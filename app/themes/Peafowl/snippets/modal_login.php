<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<div data-modal="login" class="hidden">
	<span class="modal-box-title"><?php _se('Login needed'); ?></span>
	<p><?php _se("To use all the features of this site you must be logged in."); ?><?php
		if(CHV\getSetting('enable_signups')) {
			echo ' ' . _s("If you don't have an account you can <a href=\"%s\">sign up</a> right now.", G\get_base_url("signup"));
		}
	?></p>
	<div class="position-relative overflow-auto margin-top-20">
    	<?php
			if(CHV\getSetting('social_signin')) {
		?>
		<div class="text-align-center hidden phone-show phablet-show">
			<span><?php _se('Sign in with another account'); ?></span>
			<ul class="sign-services margin-top-10"><?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?></ul>
			<div class="or-separator margin-top-30"><span class="separator"><?php _se('or'); ?></span></div>
		</div>
        <?php
			}
		?>
		<div class="c6 phone-c1 phablet-c1 grid-columns">
			<form method="post" action="<?php echo G\get_base_url("login"); ?>" data-beforeunload="continue">
				<div class="input"><input type="text" class="text-input" name="login-subject" placeholder="<?php _se('Username or Email address'); ?>" autocomplete="off" required></div>
				<div class="input"><input type="password" class="text-input" name="password" placeholder="<?php _se('Password'); ?>" autocomplete="off" required><button type="submit" class="icon-input-submit"></button></div>
				<div class="input">
					<div class="checkbox-label margin-0 display-inline-block"><label for="keep-login-modal"><input type="checkbox" name="keep-login-modal" id="keep-login-modal"><?php _se('Keep me logged in'); ?></label></div>
					<div class="float-right"><a href="<?php echo G\get_base_url("account/password-forgot"); ?>"><?php _se('Forgot password?'); ?></a></div>
				</div>
				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo CHV\Render\get_recaptcha_html('g-recaptcha-modal'); ?>
				</div>
				<?php } ?>
			</form>
		</div>
		<?php
			if(CHV\getSetting('social_signin')) {
		?>
		<div class="c9 phone-hide phablet-hide grid-columns float-right">
			<div class="or-separator c2 float-left margin-left-10 margin-right-10"><span class="separator"><?php _se('or'); ?></span></div>
			<div class="c6 float-left margin-left-10">
				<span class="h2-title"><?php _se('Sign in with another account'); ?></span>
				<ul class="sign-services sign-services-compact margin-top-10"><?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?></ul>
			</div>
		</div>
		<?php
			}
		?>
	</div>
</div>