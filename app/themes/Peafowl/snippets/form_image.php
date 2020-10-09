<?php

if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div class="input-label">
	<label for="form-image-title"><?php _se('Title'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
	<input type="text" id="form-image-title" name="form-image-title" class="text-input" value="<?php echo function_exists('get_image_safe_html') ? get_image_safe_html()["title"] : null; ?>" placeholder="<?php _se('Untitled image'); ?>" maxlength="<?php echo CHV\getSetting('image_title_max_length'); ?>">
</div>
<?php
    if (!function_exists('get_image') or (function_exists('get_image') && get_image()['user'])) {
        ?>
<div id="move-existing-album" data-view="switchable" class="c7 input-label">
    <?php G\Render\include_theme_file("snippets/form_move_existing_album"); ?>
</div>
<div id="move-new-album" data-content="form-new-album" data-view="switchable" class="soft-hidden">
    <?php G\Render\include_theme_file("snippets/form_album"); ?>
</div>
<?php
    }
?>
<?php
    if (get_categories()) {
        ?>
<div class="input-label c7">
	<?php G\Render\include_theme_file('snippets/form_category'); ?>
</div>
<?php
    }
?>
<div class="checkbox-label"><span rel="template-tooltip" data-tiptip="right" data-title="<?php _se('Mark this if the image is not safe for work'); ?>"><label for="form-nsfw"><input class="float-left" type="checkbox" name="form-nsfw" id="form-nsfw"<?php if (function_exists('get_image') and get_image()['nsfw']) {
    echo ' checked';
}
if (!is_allowed_nsfw_flagging()) {
    echo ' disabled';
}
?>><span class="no-select"><?php _se('Flag not safe'); ?></span></label></span></div>
<div class="input-label">
    <label for="form-image-description"><?php _se('Description'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
    <textarea id="form-image-description" name="form-image-description" class="text-input no-resize" placeholder="<?php _se('Brief description of this image'); ?>"><?php echo function_exists('get_image_safe_html') ? get_image_safe_html()["description"] : null; ?></textarea>
</div>