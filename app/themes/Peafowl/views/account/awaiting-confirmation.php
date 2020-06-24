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
				<h1 class="fancy-box-heading"><?php _se('Your account is almost ready'); ?></h1>
				<div class="content-section"><?php _se("An email to %s has been sent with instructions to activate your account. The activation link is only valid for 48 hours. If you don't receive the instructions try checking your junk or spam filters.", '<b>'.get_signup_email().'</b>'); ?></div>
				<div class="content-section"><a href="<?php echo G\get_base_url('account/resend-activation'); ?>" class="btn btn-input default"><?php _se('Resend activation'); ?></a></div>
			</div>
		</div>
	</div>
	<?php G\Render\include_theme_file('snippets/quickty/top_left'); ?>
</div>

<?php G\Render\include_theme_footer(); ?>