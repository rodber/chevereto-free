<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div class="input-label">
	 <div class="c7">
		<label for="form-threads"><?php _se('Threads'); ?></label>
		<select name="form-threads" id="form-threads" class="text-input">
			<option selected value="0">-- <?php _se('Select number of threads'); ?> --</option>
		<?php
            echo CHV\Render\get_select_options_html([1 => 1, 2 => 2, 3 => 3, 4 => 4], null); ?>
		</select>
	 </div>
	 <div class="input-below font-size-small"><?php _se("This determines how intensive and fast will be the import process. Don't use more than %s threads on a shared server.", 2); ?></div>
</div>