<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<h1><?php _se('Update in progress'); ?><span class="animated-ellipsis"></span></h1>
<ul class="log margin-bottom-0"></ul>
<script>
var vars = {
    url: "<?php echo G\get_base_url('update'); ?>",
    current_version: "<?php echo G\get_app_version(); ?>",
}
</script>
<script src="<?php echo G\absolute_to_url(__DIR__ . '/update.js'); ?>"></script>