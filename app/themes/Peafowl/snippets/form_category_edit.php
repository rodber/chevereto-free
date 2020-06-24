<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div class="input-label c7">
	 <label for="form-category-name"><?php _se('Name'); ?></label>
	 <input type="text" id="form-category-name" name="form-category-name" class="text-input" value="" placeholder="<?php _se("Category name") ?>" required maxlength="32">
</div>
<div class="input-label c7">
	 <label for="form-category-url_key"><?php _se('URL key'); ?></label>
     <input type="text" id="form-category-url_key" name="form-category-url_key" class="text-input" value="" placeholder="<?php _se("Category URL key") ?>" required maxlength="32" rel="template-tooltip" data-tiptip="right" data-title="<?php _se('Only letters, numbers, and hyphens'); ?>">
</div>
<div class="input-label">
	<label for="form-category-description"><?php _se('Description'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
	<textarea id="form-category-description" name="form-category-description" class="text-input no-resize" placeholder="<?php _se('Brief description of this category'); ?>"></textarea>
</div>