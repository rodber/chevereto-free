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
				<h1 class="fancy-box-heading"><?php _se('Email changed'); ?></h1>
				<div class="content-section"><?php _se('You have successfully changed your account email to %s', '<b>'.CHV\Login::getUser()['email'].'</b>'); ?></div>
				<div class="content-section"><a href="<?php echo CHV\Login::getUser()['url']; ?>" class="btn btn-input default"><?php _se('Go to my profile'); ?></a></div>
			</div>
		</div>
	</div>
	<?php G\Render\include_theme_file('snippets/quickty/top_left'); ?>
</div>

<?php G\Render\include_theme_footer(); ?>