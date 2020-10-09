<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php G\Render\include_theme_header(); ?>

<?php
    if (CHV\Settings::get('homepage_style') == 'split') {
        CHV\Render\show_theme_inline_code('snippets/index.js');
        if (function_exists('get_list')) {
            $list = get_list();
            $hasPrev = $list->has_page_prev;
        }
    }
?>

<?php if ($hasPrev == false) {
    ?>
<div id="home-cover" data-content="follow-scroll-opacity">
	<?php G\Render\include_theme_file('snippets/homepage_cover_slideshow'); ?>
	<div id="home-cover-content" class="c20 fluid-column center-box padding-left-10 padding-right-10">
		<h1><?php echo CHV\getSetting('homepage_title_html') ?: _s('Upload and share your images.'); ?></h1>
		<p class="c20 center-box text-align-center phone-hide phablet-hide"><?php echo CHV\getSetting('homepage_paragraph_html') ?: _s('Drag and drop anywhere you want and start uploading your images now. %s limit. Direct image links, BBCode and HTML thumbnails.', G\format_bytes(G\get_bytes(CHV\getSetting('upload_max_filesize_mb').'MB'))); ?></p>
		<div class="home-buttons">
			<?php
                $homepage_cta = [
                    '<a',
                    CHV\getSetting('homepage_cta_fn') == 'cta-upload' ? (CHV\getSetting('upload_gui') == 'js' ? 'data-trigger="anywhere-upload-input"' : 'href="' . G\get_base_url('upload') . '"') : 'href="' . CHV\getSetting('homepage_cta_fn_extra') . '"',
                    (CHV\getSetting('homepage_cta_fn') == 'cta-upload' and !CHV\getSetting('guest_uploads')) ? 'data-login-needed="true"' : null,
                    'class="btn btn-big ' . CHV\getSetting('homepage_cta_color') . (CHV\getSetting('homepage_cta_outline') ? ' outline' : null) . '">' . (CHV\getSetting('homepage_cta_html') ?: _s('Start uploading')) . '</a>'
                ];
    echo join(' ', $homepage_cta)
            ?>
		</div>
	</div>
</div>
<?php
} ?>

<?php if (CHV\Settings::get('homepage_style') == 'split') {
                ?>
<div class="content-width">

	<div class="header header-tabs margin-bottom-10 follow-scroll">
		<h1><strong><?php echo $home_user ? _s("%s's Images", $home_user['name_short']) : ('<span class="margin-right-5 icon ' . get_listing()['icon'] . '"></span>' . get_listing()['label']); ?></strong></h1>
        <?php G\Render\include_theme_file("snippets/tabs"); ?>
		<?php
            if (is_content_manager()) {
                G\Render\include_theme_file("snippets/user_items_editor"); ?>
        <div class="header-content-right phone-float-none">
			<?php G\Render\include_theme_file("snippets/listing_tools_editor"); ?>
        </div>
		<?php
            } ?>
    </div>

	<div class="<?php echo count($list->output) == 0 ? 'empty' : 'filled'; ?>">
		<div id="content-listing-tabs" class="tabbed-listing">
			<div id="tabbed-content-group">
				<?php
                    G\Render\include_theme_file("snippets/listing"); ?>
			</div>
		</div>
	</div>

	<?php
        if (!get_logged_user() and CHV\getSetting('enable_signups')) {
            ?>
	<div id="home-join" class="c20 fluid-column center-box text-align-center">
		<h1><?php _se('Sign up to unlock all the features'); ?></h1>
		<p><?php _se('Manage your content, create private albums, customize your profile and more.'); ?></p>
		<div class="home-buttons"><a href="<?php echo G\get_base_url('signup'); ?>" class="btn btn-big blue"><?php _se('Create account'); ?></a></div>
	</div>
	<?php
        } ?>

</div>

<?php
            } ?>

<?php if (CHV\getSetting('enable_powered_by')) { ?>
<div class="footer"><?php _se('Powered by'); ?> <a href="https://chevereto.com" rel="generator" target="_blank">Chevereto</a></div>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>
