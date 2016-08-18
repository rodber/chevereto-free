<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	
	<?php CHV\Render\show_banner('album_before_header', get_list()->sfw); ?>
	
	<div class="header header-content margin-bottom-10">
	
		<div class="header-content-left">
            <div class="header-content-breadcrum">
				
				<?php
					if(get_album()['user']['id']) {
						G\Render\include_theme_file("snippets/breadcrum_owner_card");
					} else {
				?>
				<div class="breadcrum-item">
					<div class="user-image default-user-image"><span class="icon icon-user"></span></div>
					<span class="breadcrum-text float-left"><span class="user-link"><?php _se('Private'); ?><span class="arrow arrow-right"></span></span></span>
				</div>
				<?php
					}
				?>
                
				<div class="breadcrum-item">
					<h1><span class="breadcrum-text"><span class="icon icon-eye-blocked margin-right-5 <?php if(get_album()["privacy"] == "public") echo "soft-hidden"; ?>" data-content="privacy-private" title="<?php _se('This content is private'); ?>" rel="tooltip"></span><span class="breadcrum-text"><a href="<?php echo get_album()["url"]; ?>" data-text="album-name"<?php if(get_album()['name'] !== get_album()['name_truncated']) { ?> title="<?php echo get_album_safe_html()["name"]; ?><?php } ?>"><?php echo get_album_safe_html()["name_truncated"]; ?></a></span></h1>
				</div>
				<?php
					if(is_owner() or is_admin()) {
				?>
				<div class="breadcrum-item">
					<a class="edit-link" data-modal="edit"><span class="icon-edit"></span><span><?php _se('Edit album details'); ?></span></a>
				</div>
				<div class="breadcrum-item">
					<a class="delete-link" data-confirm="<?php _se("Do you really want to delete this album and all of its images? This can't be undone."); ?>" data-submit-fn="CHV.fn.submit_resource_delete" data-ajax-deferred="CHV.fn.complete_resource_delete" data-ajax-url="<?php echo G\get_base_url("json"); ?>"><?php _se('Delete album'); ?></a>
				</div>
				<?php
					}
				?>
            </div>
        </div>
		
		<div class="header-content-right phone-hide">
        	<div class="number-figures float-left"><b data-text="image-count"><?php echo get_album()["image_count"]; ?></b> <span data-text="image-label" data-label-single="<?php _ne('image', 'images', 1); ?>" data-label-plural="<?php _ne('image', 'images', 2); ?>"><?php _ne('image', 'images', get_album()['image_count']); ?></span></div>
			<?php if(CHV\getSetting('theme_show_social_share')) { ?>
			<a class="btn red" data-modal="simple" data-target="modal-share"><span class="btn-icon icon-share"></span><span class="btn-text phone-hide"><?php _se('Share'); ?></span></a>
			<?php } ?>
        </div>
		
    </div>
	
	<h1 class="viewer-title" data-text="album-description"><?php echo nl2br(get_album_safe_html()['description']); ?></h1>
	
	<?php CHV\Render\show_banner('album_after_header', get_list()->sfw); ?>
	
	<div class="header header-tabs margin-bottom-10 follow-scroll">
		<h1><strong data-text="album-name"><?php echo get_album_safe_html()["name_truncated"]; ?></strong></h1>

        <?php G\Render\include_theme_file("snippets/tabs"); ?>
        
		<?php
			if(is_owner() or is_admin()) {
				G\Render\include_theme_file("snippets/user_items_editor");
		?>
        <div class="header-content-right phone-float-none">
			<?php G\Render\include_theme_file("snippets/listing_tools_editor"); ?>
        </div>
		<?php
			}
		?>
    </div>
	
	<div id="content-listing-tabs" class="tabbed-listing">
		
		<div id="tabbed-content-group">
        	
            <?php
				G\Render\include_theme_file("snippets/listing");
			?>
		
			<div id="tab-share" class="tabbed-content margin-top-30">
				
				<div class="growl static text-align-center margin-bottom-30 clear-both<?php if(get_album()["privacy"] == "public") echo " soft-hidden"; ?>" data-content="privacy-private"><?php echo get_album()['privacy_notes']; ?></div>
				
				<div class="panel-share c16 phablet-c1 grid-columns margin-right-10">
				
					<div class="panel-share-networks panel-share-item">
						<h4 class="title c5 phablet-c1 grid-columns"><?php _se('Social networks'); ?></h4>
						<ul class="float-left">
						<?php echo join("\n", get_share_links_array()); ?>
						</ul>
					</div>
					
					<div class="panel-share-input-label">
						<h4 class="title c5 phablet-c1 grid-columns"><?php _se('Album link'); ?></h4>
						<div class="panel-share-input c10 phablet-c1 grid-columns">
							<input type="text" class="text-input" value="<?php echo get_album()["url"]; ?>" data-focus="select-all">
						</div>
					</div>
					
				</div>
				
			</div>
			
			<?php
				if(is_admin()) {
			?>
			<div id="tab-full-info" class="tabbed-content">
				<?php echo CHV\Render\arr_printer(get_album_safe_html(), '<li><div class="c4 display-table-cell padding-right-10 font-weight-bold">%K</div> <div class="display-table-cell">%V</div></li>', ['<ul class="tabbed-content-list table-li">', '</ul>']); ?>
			</div>
			<?php
				}
			?>
			
		</div>
		
	</div>

</div>
	
	
<!--googleoff: index-->
<?php
	if(is_owner() or is_admin()) {
?>
<div id="form-modal" class="hidden" data-before-fn="CHV.fn.before_album_edit" data-submit-fn="CHV.fn.submit_album_edit" data-ajax-deferred="CHV.fn.complete_album_edit" data-ajax-url="<?php echo G\get_base_url("json"); ?>">
    <h1><?php _se('Edit album details'); ?></h1>
    <div class="modal-form">
		<?php G\Render\include_theme_file('snippets/form_album'); ?>
    </div>
</div>
<?php
	}
	if(CHV\getSetting('theme_show_social_share')) {
		G\Render\include_theme_file("snippets/modal_share");
	}
?>
<!--googleon: index-->

<?php G\Render\include_theme_footer(); ?>

<?php if((is_owner() or is_admin()) and isset($_REQUEST["deleted"])) : ?>
<script>PF.fn.growl.expirable("<?php _se('The content has been deleted.'); ?>");</script>
<?php endif; ?>