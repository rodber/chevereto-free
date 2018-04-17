<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	<div class="page-not-found">
		<h1><?php _se("That page doesn't exist"); ?></h1>
		<p><?php _se('The requested page was not found.'); ?></p>
        <?php if(CHV\getSetting('website_search')) { ?>
		<div class="input-search c7">
			<form action="<?php echo G\get_base_url("search/images"); ?>" method="get">
				<input class="search" type="text" placeholder="<?php _se("Search something else"); ?>" autocomplete="off" spellcheck="false" name="q">
			</form>
			<span class="icon-search"></span><span class="icon-close soft-hidden" data-action="clear-search"></span>
		</div>
        <?php } ?>
    </div>
</div>

<?php G\Render\include_theme_footer(); ?>

<?php if(isset($_REQUEST["deleted"])) { ?>
<script>
	$(function() {
		PF.fn.growl.call("<?php echo (G\get_route_name() == 'user' ? _s('The user has been deleted') : _s('The content has been deleted.')); ?>");
	});
</script>
<?php } ?>