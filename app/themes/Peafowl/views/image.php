<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="top-bar-placeholder"></div>

<div id="image-viewer" class="image-viewer full-viewer margin-bottom-10">
	<?php
		$image_url = get_image()['medium'] ? get_image()['medium']['url'] : get_image()['url'];
	?>
	<div id="image-viewer-container" class="image-viewer-main image-viewer-container"><img src="<?php echo $image_url; ?>"<?php if(!CHV\getSetting('theme_download_button')) { ?> class="no-select"<?php } ?> alt="<?php echo get_image_safe_html()['description']; ?>" width="<?php echo get_image()["width"]; ?>" height="<?php echo get_image()["height"]; ?>" <?php if(get_image()['medium']) { ?> data-load="full"<?php } ?>></div>
	<?php
		if(get_image()['user']['id'] != NULL) {
	?>
	<div class="image-viewer-navigation arrow-navigator">
		<?php
			if(get_image_album_slice()["prev"] !== NULL) {
		?>
		<a class="left-0" data-action="prev" href="<?php echo get_image_album_slice()["prev"]['url_viewer']; ?>"><span class="icon-prev-alt"></span></a>
		<?php
			}
			if(get_image_album_slice()["next"] !== NULL) {
		?>
		<a class="right-0" data-action="next" href="<?php echo get_image_album_slice()["next"]['url_viewer']; ?>"><span class="icon-next-alt"></span></a>
		<?php
			}
		?>
	</div>
	<?php
		}
	?>
	<?php if(get_image()['is_use_loader']) { ?>
	<div id="image-viewer-loader" data-size="<?php echo get_image()['size']; ?>"><?php if(get_image()['is_animated']) { ?><span class="btn-icon icon icon-play3"></span><?php } ?><span class="btn-text"><?php get_image()['is_animated'] ? _se('Play GIF') : _se('Load full resolution'); ?> - <?php echo get_image()['size_formatted']; ?></span></div>
	<?php } ?>
</div>

<?php CHV\Render\show_theme_inline_code('snippets/image.js'); ?>

<div class="content-width">

	<div class="header header-content margin-bottom-10">
		<div class="header-content-left">
            <div class="header-content-breadcrum">
				<?php
					if(get_image()['user']['id']) {
						G\Render\include_theme_file("snippets/breadcrum_owner_card");
					} else {
				?>
				<div class="breadcrum-item">
					<div class="user-image default-user-image"><span class="icon icon-user"></span></div>
					<span class="breadcrum-text float-left"><span class="user-link"><?php echo get_image()['user']['is_private'] ? _s('Private') : _s('Guest'); ?></span></span>
				</div>
				<?php
					}
				?>
				<div class="breadcrum-item">
					<span class="breadcrum-text"><span class="icon icon-eye-blocked margin-right-5 <?php if(!get_image()["album"] or get_image()["album"]["privacy"] == "public") echo "soft-hidden"; ?>" data-content="privacy-private" title="<?php _se('This content is private'); ?>" rel="tooltip"></span>
				</div>
				<?php
					if(is_owner() or is_admin()) {
				?>
				<div class="breadcrum-item">
					<a class="edit-link" data-modal="edit"><span class="icon-edit"></span><span><?php _se('Edit image details'); ?></span></a>
				</div>
				<?php
						if(is_allowed_to_delete_content()) {
				?>
				<div class="breadcrum-item">
					<a class="delete-link" data-confirm="<?php _se("Do you really want to delete this image? This can't be undone."); ?>" data-submit-fn="CHV.fn.submit_resource_delete" data-ajax-deferred="CHV.fn.complete_resource_delete" data-ajax-url="<?php echo G\get_base_url("json"); ?>"><?php _se('Delete image'); ?></a>
				</div>
				<?php
						}
					}
				?>
            </div>
        </div>

    	<div class="header-content-right">
			<?php if(CHV\getSetting('theme_download_button')) { ?>
			<a href="<?php echo get_image()['url']; ?>" download="<?php echo get_image()['filename']; ?>" class="btn btn-download default" rel="tooltip" title="<?php echo get_image()["width"] . ' x ' . get_image()["height"] . ' - ' . strtoupper(get_image()["extension"]) . ' ' . get_image()["size_formatted"]; ?>"><span class="btn-icon icon-download"></span></a>
			<?php } ?>
			<?php
				if(CHV\getSetting('enable_likes')) {
			?>
			<a class="btn-like" data-type="image" data-id="<?php echo get_image()['id_encoded']; ?>" data-liked="<?php echo (int)get_image()['liked']; ?>" data-action="like">
				<span class="btn btn-liked blue" rel="tooltip" title="<?php _se("You like this"); ?>"><span class="btn-icon icon-heart3"></span><span class="btn-text phone-hide"><?php _se('Liked'); ?></span></span>
				<span class="btn btn-unliked blue outline"><span class="btn-icon icon-heart4"></span><span class="btn-text phone-hide"><?php _se('Like'); ?></span></span>
			</a>
			<?php
				}
			?>
            <?php if(CHV\getSetting('theme_show_social_share')) { ?>
			<a class="btn red" data-modal="simple" data-target="modal-share"><span class="btn-icon icon-share"></span><span class="btn-text phone-hide"><?php _se('Share'); ?></span></a>
			<?php } ?>
        </div>

    </div>

	<?php
		if(!get_image()['title']) {
	?>
	<h1 class="viewer-title soft-hidden" data-text="image-title"><?php echo get_pre_doctitle(); ?></h1>
	<?php
		} else {
	?>
	<h1 class="viewer-title" data-text="image-title"><?php echo nl2br(get_image_safe_html()['title']); ?></h1>
	<?php
		}
	?>

    <div class="header">
		<?php G\Render\include_theme_file("snippets/tabs"); ?>
        <div class="header-content-right">
        	<div class="number-figures float-left"><?php echo get_image()["views"]; ?> <span><?php echo get_image()["views_label"]; ?></span></div>
			<?php if(CHV\getSetting('enable_likes')) { ?><div class="number-figures float-left"><span class="icon icon-heart4"></span> <b data-text="likes-count"><?php echo get_image()["likes"]; ?></b></div><?php } ?>
        </div>
    </div>

    <div id="tabbed-content-group">

		<div id="tab-about" class="tabbed-content<?php echo (get_current_tab() == 'about' ? ' visible' : NULL); ?>">
        	<div class="c9 phablet-c1 fluid-column grid-columns">
				<div class="panel-description default-margin-bottom">
					<p class="description-text margin-bottom-5" data-text="image-description"><?php echo nl2br(get_image_safe_html()['description']); ?></p>
					<p class="description-meta margin-bottom-5">
					<?php
						$category = get_categories()[get_image()['category_id']];
						$category_link = '<a href="'.$category['url'].'" rel="tag">'.$category['name'].'</a>';
						$time_elapsed_string = '<span title="'.get_image()['date_fixed_peer'].'">' . CHV\time_elapsed_string(get_image()['date_gmt']) . '</span>';
						if(get_image()['album']['id'] and (get_image()['album']['privacy'] !== 'private_but_link' or is_owner() or is_admin())) {
							$album_link = '<a href="'.get_image()['album']['url'].'"'. (get_image()['album']['name'] !== get_image()['album']['name_truncated'] ? (' title="' . get_image()['album']['name_html'] . '"') : NULL)  .'>' . get_image()['album']['name_truncated_html'] . '</a>';
							if($category) {
								echo _s('Added to %a and categorized in %c', ['%a' => $album_link, '%c' => $category_link]);
							} else {
								echo _s('Added to %s', $album_link);
							}
							echo ' — ' .  $time_elapsed_string;
						} else {
							if($category) {
								echo _s('Uploaded to %s', $category_link) . ' — ' .  $time_elapsed_string;
							} else {
								_se('Uploaded %s', $time_elapsed_string);
							}
						}
					?>
					</p>
					<?php
						if(CHV\getSetting('theme_show_exif_data')) {
							$image_exif = CHV\Render\getFriendlyExif(get_image()['original_exifdata']);
							if($image_exif) {
					?>
					<p class="exif-meta margin-top-20">
						<span class="camera-icon icon-camera4"></span><?php echo $image_exif->Simple->Camera; ?>
						<span class="exif-data"><?php echo $image_exif->Simple->Capture; ?> — <a class="font-size-small" data-toggle="exif-data" data-html-on="<?php _se('Less Exif data'); ?>" data-html-off="<?php _se('More Exif data'); ?>"><?php _se('More Exif data'); ?></a></span>
					</p>
					<div data-content="exif-data" class="soft-hidden">
						<ul class="tabbed-content-list table-li">
						<?php
								foreach($image_exif->Full as $k => $v) {
									$label = preg_replace('/(?<=\\w)(?=[A-Z])/',' $1', $k);
									if(ctype_upper(preg_replace('/\s+/', '', $label))) {
										$label = $k;
									}
						?>
							<li><span class="c5 display-table-cell padding-right-10"><?php echo $label; ?></span> <span class="display-table-cell"><?php echo $v; ?></span></li>
						<?php
								}
						?>
						</ul>
					</div>
					<?php
							} // $image_exif
						} // theme_show_exif_data
					?>
				</div>

				<?php if(CHV\getSetting('theme_show_social_share')) { ?>
				<div class="phone-show phablet-show hidden panel-share-networks margin-bottom-30">
					<h4 class="title"><?php _se('Share image'); ?></h4>
					<ul>
					<?php echo join("\n", get_share_links_array()); ?>
					</ul>
				</div>
				<?php } ?>

				<?php
					if(is_admin()) {
				?>
				<div class="tabbed-content-section margin-bottom-30">
					<ul class="tabbed-content-list table-li">
						<?php
							$image_admin_list_values = get_image_admin_list_values();
							if(!is_null(get_image()['album']['id'])) {
								$album_values = [
									'label'		=> _s('Album ID'),
									'content'	=> get_image()['album']['id'] . ' ('.get_image()['album']['id_encoded'].')'
								];
								$image_admin_list_values = array_slice($image_admin_list_values, 0, 1, true) +
									['album' =>
										[
											'label'		=> _s('Album ID'),
											'content'	=> get_image()['album']['id'] . ' ('.get_image()['album']['id_encoded'].')'
										]
									] +
									array_slice($image_admin_list_values, 1, count($image_admin_list_values) - 1, true) ;
							}

							foreach($image_admin_list_values as $v) {
						?>
						<li><span class="c5 display-table-cell padding-right-10"><?php echo $v['label']; ?></span> <span class="display-table-cell"><?php echo $v['content']; ?></span></li>
						<?php
							}
						?>
					</ul>
					<div data-modal="modal-add-ip_ban" class="hidden" data-submit-fn="CHV.fn.ip_ban.add.submit" data-before-fn="CHV.fn.ip_ban.add.before" data-ajax-deferred="CHV.fn.ip_ban.add.complete">
						<span class="modal-box-title"><?php _se('Add IP ban'); ?></span>
						<div class="modal-form">
							<?php G\Render\include_theme_file('snippets/form_ip_ban_edit'); ?>
						</div>
					</div>
				</div>
				<?php
					}
				?>

				<div class="comments">
					<?php CHV\Render\showComments(); ?>
				</div>
            </div>

			<?php if(CHV\getSetting('theme_show_social_share')) { ?>
			<div class="tablet-show laptop-show desktop-show hidden c15 phablet-c1 fluid-column grid-columns default-margin-bottom margin-left-10 panel-share-networks">
				<h4 class="title c4 grid-columns"><?php _se('Share image'); ?></h4>
				<ul>
				<?php echo join("\n", get_share_links_array()); ?>
				</ul>
			</div>
			<?php } ?>

			<?php
				if(get_image()['album']) {
			?>
            <div class="c15 phablet-c1 fluid-column grid-columns margin-left-10 phablet-margin-left-0 phone-margin-left-0">
            	<h4 class="title c4 phablet-c1 grid-columns"><span data-content="album-panel-title"<?php if(get_image()["album"]["id"] == NULL) echo ' class="soft-hidden"'?>><?php _se('In this album'); ?></span></h4>
                <ul class="panel-thumb-list grid-columns" data-content="album-slice">
					<?php G\Render\include_theme_file("snippets/image_album_slice"); ?>
                </ul>
            </div>
			<?php
				}
			?>

			<div class="c15 phablet-c1 fluid-column grid-columns margin-left-10 phablet-margin-left-0">
			</div>

        </div>

		<?php if(CHV\getSetting('theme_show_embed_content')) { ?>
        <div id="tab-codes" class="tabbed-content<?php echo (get_current_tab() == 'codes' ? ' visible' : NULL); ?>">

			<div class="growl static text-align-center margin-bottom-30 clear-both<?php if(get_image()['album']['privacy'] == 'public' || get_image()['album']['privacy'] == NULL) echo " soft-hidden"; ?>" data-content="privacy-private"><?php echo get_image()['album']['privacy_notes']; ?></div>

        	<div class="panel-share c16 phablet-c1 grid-columns margin-right-10">
				<?php
					foreach(get_embed() as $embed) {
				?>
                <div class="panel-share-item">
                	<h4 class="pre-title"><?php echo $embed['label']; ?></h4>
					<?php foreach($embed['entries'] as $entry) { ?>
                    <div class="panel-share-input-label copy-hover-display">
                        <h4 class="title c5 grid-columns"><?php echo $entry['label']; ?></h4>
                        <div class="c10 phablet-c1 grid-columns">
                            <input id="<?php echo $entry['id']; ?>" type="text" class="text-input" value="<?php echo $entry['value']; ?>" data-focus="select-all">
							<button class="input-action" data-action="copy" data-action-target="#<?php echo $entry['id']; ?>">copy</button>
                        </div>
                    </div>
					<?php } ?>
                </div>
				<?php
					}
				?>
            </div>

        </div>
		<?php } ?>

		<?php
			if(is_admin()) {
		?>
		<div id="tab-full-info" class="tabbed-content<?php echo (get_current_tab() == 'full-info' ? ' visible' : NULL); ?>">
			<?php echo CHV\Render\arr_printer(get_image_safe_html(), '<li><div class="c4 display-table-cell padding-right-10 font-weight-bold">%K</div> <div class="display-table-cell">%V</div></li>', ['<ul class="tabbed-content-list table-li">', '</ul>']); ?>
		</div>
		<?php
			}
		?>

    </div>

</div>


<?php
	if(is_owner() or is_admin()) {
?>
<div data-modal="form-modal" class="hidden" data-submit-fn="CHV.fn.submit_image_edit" data-before-fn="CHV.fn.before_image_edit" data-ajax-deferred="CHV.fn.complete_image_edit" data-ajax-url="<?php echo G\get_base_url("json"); ?>">
    <span class="modal-box-title"><?php _se('Edit image details'); ?></span>
    <div class="modal-form">
    	<?php
			G\Render\include_theme_file('snippets/form_image');
        ?>
    </div>
</div>
<?php
	}

	if(CHV\getSetting('theme_show_social_share')) {
		G\Render\include_theme_file('snippets/modal_share');
	}

?>

<?php G\Render\include_theme_footer(); ?>