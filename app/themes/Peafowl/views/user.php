<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<?php
	if(get_user()["background"] or is_owner() or is_admin()) {
?>
<div id="background-cover" data-content="user-background-cover"<?php if(!get_user()["background"]) { ?> class="no-background"<?php } ?>>
	<div id="background-cover-wrap">
		<div id="background-cover-src" data-content="user-background-cover-src"<?php if(get_user()["background"]["url"]) { ?> style="background-image: url('<?php echo get_user()["background"]["url"]; ?>');"<?php } ?>></div>
	</div>
	<div class="content-width">
		<?php
        	if(is_owner() or is_admin()) {
		?>
		<span data-content="user-upload-background" class="btn btn-input default<?php if(get_user()["background"]) { ?> hidden<?php } ?>" data-trigger="user-background-upload"><?php _se('Upload profile background'); ?></span>
		<div id="change-background-cover" data-content="user-change-background" class="pop-btn<?php if(!get_user()["background"]) { ?> hidden<?php } ?>">
			<span class="btn btn-capsule"><span class="btn-icon icon-camera"></span><span class="btn-text"><?php _se('Change background'); ?></span></span>
			<div class="pop-box anchor-right arrow-box arrow-box-top">
				<div class="pop-box-inner pop-box-menu">
					<ul>
						<li><a data-trigger="user-background-upload"><?php _se('Upload new image'); ?></a></li>
						<li><a data-confirm="<?php _se("The profile background image will be deleted. This can't be undone. Are you sure that you want to delete the profile background image?"); ?>" data-submit-fn="CHV.fn.user_background.delete.submit" data-ajax-deferred="CHV.fn.user_background.delete.deferred"><?php _se('Delete background'); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<input id="user-background-upload" data-content="user-background-upload-input" class="hidden-visibility" type="file" accept="image/*">
		<?php
        	}
		?>
	</div>
	<div class="loading-placeholder hidden"></div>
</div>
<?php
	}
?>

<div class="content-width">
	
	<div id="top-user" class="top-user<?php echo (!get_user()["background"] and (!is_owner() and !is_admin())) ? ' user-has-no-background' : NULL; ?>">
		<div class="top-user-credentials">
			<a href="<?php echo get_user()["url"]; ?>">
				<?php
					if(get_user()["avatar"]) {
				?>
				<img class="user-image" src="<?php echo get_user()["avatar"]["url"]; ?>" alt="">
				<?php
					} else {
				?>
				<span class="user-image default-user-image"><span class="icon icon-user"></span></span>
				<?php
					}
				?>
			</a>
			<h1><a href="<?php echo get_user()["url"]; ?>"><?php echo get_safe_html_user()[get_user()["name"] ? "name" : "username"]; ?></a></h1>
			<?php if(get_user()['is_private']) { ?>
			<span class="user-meta font-size-small"><span class="icon icon-lock"></span><?php _se('Private profile'); ?></span>
			<?php } ?>
			<div class="user-meta"><span class="user-social-networks"><?php if(get_user()["twitter"]) { ?><a class="icon-twitter" href="<?php echo get_user()["twitter"]["url"]; ?>" rel="nofollow" target="_blank"></a><?php } if(get_user()["facebook"]) { ?><a class="icon-facebook" href="<?php echo get_user()["facebook"]["url"]; ?>" rel="nofollow" target="_blank"></a><?php } if(get_user()["website"]) { ?><a class="icon-globe" href="<?php echo get_user()['website_display']; ?>"<?php echo !get_user()['is_admin'] ? ' rel="nofollow"' : NULL; ?> target="_blank"></a><?php } ?></span><?php
				if(is_owner() or is_admin()) {
			?>
				<a class="edit-link" href="<?php echo G\get_base_url(is_owner() ? 'settings/profile' : 'dashboard/user/' . get_user()['id']); ?>"><span class="icon-edit"></span><span><?php _se('Edit profile'); ?></span></a>
			<?php
					if(!is_owner() and is_admin()) {
			?>
				<a class="delete-link margin-left-5" data-confirm="<?php _se("Do you really want to delete this user? This can't be undone."); ?>" data-submit-fn="CHV.fn.submit_resource_delete" data-ajax-deferred="CHV.fn.complete_resource_delete" data-ajax-url="<?php echo G\get_base_url("json"); ?>"><?php _se('Delete user'); ?></a>
			<?php
					}
			?>
			<?php
				}
			?></div>

			<?php if(CHV\getSetting('enable_followers') && !get_user()['is_private']) { ?>
			<div class="user-meta margin-bottom-5">
				<a class="number-figures display-inline-block margin-bottom-5" href="<?php echo get_user()['url_following']; ?>"><b data-text="following-count"><?php echo get_user()['following']; ?></b> <span><?php _se('Following'); ?></span></a>
				<a class="number-figures display-inline-block margin-bottom-5" href="<?php echo get_user()['url_followers']; ?>"><b data-text="followers-count"><?php echo get_user()['followers']; ?></b> <span data-text="followers-label" data-label-single="<?php _ne('Follower', 'Followers', 1); ?>" data-label-plural="<?php _ne('Follower', 'Followers', 2); ?>"><?php _ne('Follower', 'Followers', get_user()['followers']); ?></span></a>
				<?php
					if(is_show_follow_button()) {
				?>
				<a class="btn-follow" data-followed="<?php echo (int)get_user()['followed']; ?>" data-action="follow">
					<span class="btn btn-capsule btn-followed blue"><?php _se('Following'); ?></span>
					<span class="btn btn-capsule btn-unfollowed blue outline"><span class="btn-icon icon-user-add"></span><span class="btn-text"><?php _se('Follow'); ?></span></span>
				</a>
				<?php
					}
				?>
			</div>
			<?php } ?>

			<?php if(get_user()['bio']) { ?>
			<div class="user-meta overflow-hidden">
				<p class="c18 word-break-break-word"><?php echo get_user()['bio_linkify']; ?></p>
			</div>
			<?php } ?>

		</div>

		<div class="header-content-right phone-float-none">
			<div class="text-align-right">
				<a class="number-figures" href="<?php echo get_user()["url"]; ?>"><b data-text="image-count"><?php echo get_user()["image_count"]; ?></b> <span data-text="image-label" data-label-single="<?php _ne('image', 'images', 1); ?>" data-label-plural="<?php _ne('image', 'images', 2); ?>"><?php _ne('image', 'images', get_user()['image_count']); ?></span></a>
				<a class="number-figures" href="<?php echo get_user()["url_albums"]; ?>"><b data-text="album-count"><?php echo get_user()["album_count"]; ?></b> <span data-text="album-label" data-label-single="<?php _ne('album', 'albums', 1); ?>" data-label-plural="<?php _ne('album', 'albums', 2); ?>"><?php _ne('album', 'albums', get_user()['album_count']); ?></span></a>
				<?php if(CHV\getSetting('enable_likes') && !get_user()['is_private']) { ?>
				<a class="number-figures" href="<?php echo get_user()["url"]; ?>/liked"><span class="icon icon-heart4"></span> <b data-text="likes-count"><?php echo get_user()["liked"]; ?></b></a>
				<?php } ?>
			</div>
			<div class="input-search">
				<form action="<?php echo get_user()["url"] . "/search"; ?>">
					<input class="search two-icon-padding" type="text" placeholder="<?php echo get_safe_html_user()["name"]; ?>" autocomplete="off" spellcheck="false" name="q">
				</form>
				<span class="icon-search"></span><span class="icon close icon-close soft-hidden" data-action="clear-search"></span>
			</div>
			<?php
				if(is_owner()) {
			?>
			<div class="text-align-right">
				<button class="btn default" data-modal="edit" data-target="new-album"><span class="btn-icon icon-folder"></span><span class="btn-text"><?php _se('Create new album'); ?></span></button>
				<?php G\Render\include_theme_file('snippets/modal_create_album.php'); ?>
			</div>
			<?php
				}
			?>
		</div>
	</div>

	<?php
		if(get_user()["background"] or is_owner() or is_admin()) {
			CHV\Render\show_theme_inline_code('snippets/user.js');
		}
	?>
	
	<div class="header header-tabs margin-bottom-10 follow-scroll">
		<?php
			if(is_user_search()) {
		?>
		<div class="heading display-inline-block">
			<span class="phone-hide"><?php echo get_title(); ?></span>
			<h1 class="display-inline"><strong><?php echo get_safe_html_user()["search"]["d"]; ?></strong></h1>
		</div>
		<?php
			} else {
		?>
		<a href="<?php echo get_user()["url"]; ?>" class="user-image margin-right-10 float-left hidden" data-show-on="follow-scroll">
			<?php if(get_user()["avatar"]) { ?>
			<img src="<?php echo get_user()["avatar"]["url"]; ?>" alt="">
			<?php } else { ?>
			<span class="user-image default-user-image margin-top-0"><span class="icon icon-user"></span></span>
			<?php } ?>
		</a>
		<h1 class="phone-hide"><?php echo get_title(); ?></h1>
		<h1 class="phone-show hidden"><?php echo get_title_short(); ?></h1>
		<?php
			}
		?>

    <?php G\Render\include_theme_file("snippets/tabs"); ?>

		<?php
			if(is_show_user_items_editor() or (is_owner() or is_admin())) {
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
      </div>
  </div>

</div>

<?php if((is_owner() or is_admin()) and isset($_REQUEST["deleted"])) { ?>
<script>
$(function() {
	PF.fn.growl.expirable("<?php _se('The content has been deleted.'); ?>");
});
</script>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>