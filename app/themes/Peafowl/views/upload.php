<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<?php if(get_post() and is_error()) { ?>
<script>
$(function() {
	PF.fn.growl.expirable("<?php echo get_error(); ?>");
});
</script>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>