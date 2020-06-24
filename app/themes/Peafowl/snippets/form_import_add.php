<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div class="input-label c15">
	 <label for="form-path"><?php _se('Path'); ?></label>
	 <input type="text" id="form-path" name="form-path" class="text-input" value="" placeholder="<?php _se('Local path (absolute)'); ?>" required>
</div>
<div class="input-label">
	 <div class="c15">
		<label for="form-structure"><?php _se('Directory structure'); ?></label>
		<select name="form-structure" id="form-structure" class="text-input">
			<option value="users"><?php _se('Parse top level folders as users (username)'); ?></option>
			<option value="albums"><?php _se('Parse top level folders as albums'); ?></option>
			<option value="plain"><?php _se("Don't parse folders"); ?></option>
		</select>
	 </div>
	 <div class="input-below font-size-small"><?php _se('When parsing top level folders as users, second level folders will be parsed as user albums. Parsing top level folders as albums adds these as guest albums.'); ?></div>
</div>