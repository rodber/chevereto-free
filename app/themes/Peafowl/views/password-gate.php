<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	<div class="content-password-gate">
		<div class="c16 center-box">
			<h1><span class="icon icon-lock2"></span><?php _se('This content is password protected.'); ?></h1>
			<p></p>
			<p><?php _se('Please enter your password to continue.'); ?></p>
			<form method="post" autocomplete="off" data-action="validate">
				<?php echo G\Render\get_input_auth_token(); ?>
				<div class="input-label c12 center-box">
					<label for="content-password"><?php _se('Password'); ?></label>
					<input type="password" id="content-password" name="content-password" class="text-input" required>
				</div>
				<?php if(is_captcha_needed()) { ?>
				<div class="input-label center-box">
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>
				<div class="btn-container margin-bottom-0"><button class="btn btn-input default" type="submit"><?php _se('Send'); ?></button> <span class="btn-alt"><?php _se('or'); ?><a class="cancel" href="<?php echo G\get_base_url(); ?>"><?php _se('cancel'); ?></a></span></div>
			</form>
		</div>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>

<?php if(is_error() && get_error() !== NULL) { ?>
<script>PF.fn.growl.call("<?php echo get_error(); ?>");</script>
<?php } ?>