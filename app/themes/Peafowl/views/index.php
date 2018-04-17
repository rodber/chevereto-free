<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<?php
	if(CHV\Settings::get('homepage_style') == 'split') {
		CHV\Render\show_theme_inline_code('snippets/index.js');
	}
?>

<div id="home-cover" data-content="follow-scroll-opacity">
	<div id="home-cover-slideshow">
		<?php
			$i = 0;
			foreach(CHV\getSetting('homepage_cover_images_shuffled') as $k => $v) {
				if($i > 1 && is_mobile_device()) break;
		?>
		<div class="home-cover-img" data-src="<?php echo $v['url']; ?>"></div>
		<?php
				$i++;
			}
		?>
	</div>
	<div id="home-cover-content" class="c20 fluid-column center-box padding-left-10 padding-right-10">
		<h1><?php echo CHV\getSetting('homepage_title_html') ?: _s('Upload and share your images.'); ?></h1>
		<p class="c20 center-box text-align-center phone-hide phablet-hide"><?php echo CHV\getSetting('homepage_paragraph_html') ?: _s('Drag and drop anywhere you want and start uploading your images now. %s limit. Direct image links, BBCode and HTML thumbnails.', G\format_bytes(G\get_bytes(CHV\getSetting('upload_max_filesize_mb').'MB'))); ?></p>
		<div class="home-buttons">
			<?php
				$homepage_cta = [
					'<a',
					CHV\getSetting('homepage_cta_fn') == 'cta-upload' ? 'data-trigger="anywhere-upload-input"' : 'href="'.CHV\getSetting('homepage_cta_fn_extra').'"',
					(CHV\getSetting('homepage_cta_fn') == 'cta-upload' and !CHV\getSetting('guest_uploads')) ? 'data-login-needed="true"' : NULL,
					'class="btn btn-big ' . CHV\getSetting('homepage_cta_color') . (CHV\getSetting('homepage_cta_outline') ? ' outline' : NULL) . '">' . (CHV\getSetting('homepage_cta_html') ?: _s('Start uploading')) . '</a>'
				];
				echo join(' ', $homepage_cta)
			?>
		</div>
	</div>
</div>

<?php if (CHV\Settings::get('homepage_style') == 'split') { ?>
<div class="content-width">

	<?php
		$list = get_list();
		if(is_admin()) {
			G\Render\include_theme_file("snippets/user_items_editor");
		}
		if(is_admin()) {
	?>
	<div class="header header-tabs margin-bottom-10">
		<h1><strong><?php $home_user ? _se("%s's Images", $home_user['name_short']) : _se('Most recent'); ?></strong></h1>
		<div class="header-content-right phone-float-none">
			<?php G\Render\include_theme_file("snippets/listing_tools_editor"); ?>
        </div>
	</div>
	<?php
		}
	?>

	<div id="home-pics-feature" class="<?php echo count($list->output) == 0 ? 'empty' : 'filled'; ?>">
		<div id="content-listing-tabs" class="tabbed-listing">
			<?php if(count($list->output) > 0) { ?><div id="home-pics-feature-overlay"></div><?php } ?>
			<?php
				if(count($list->output) == 0 and is_admin()) {
			?>
			<div class="content-empty">
				<span class="icon icon-drawer"></span>
				<p><?php $home_user ? _se( "Fill this section uploading pictures to %s account.<br>You can edit the target user in your dashboard settings.", $home_user['name_short']) : _se("There's nothing to show here."); ?></p>
			</div>
			<?php
				} else {
					G\Render\include_theme_file("snippets/listing");
				}
			?>
		</div>
	</div>

	<?php CHV\Render\show_banner('home_after_listing', get_list()->sfw); ?>

	<?php
		if(!get_logged_user() and CHV\getSetting('enable_signups')) {
	?>
	<div id="home-join" class="c20 fluid-column center-box text-align-center">
		<h1><?php _se('Sign up to unlock all the features'); ?></h1>
		<p><?php _se('Manage your content, create private albums, customize your profile and more.'); ?></p>
		<div class="home-buttons"><a href="<?php echo G\get_base_url('signup'); ?>" class="btn btn-big blue"><?php _se('Create account'); ?></a></div>
	</div>
	<?php
		}
	?>

</div>

<?php } ?>

<?php G\Render\include_theme_footer(); ?>