<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php $user_items_editor = function_exists('get_user_items_editor') ? get_user_items_editor() : G\get_global('user_items_editor'); ?>
<label for="form-category-id"><?php _se('Category'); ?></label>
<select name="form-category-id" id="form-category-id" class="text-input">
	<?php
        $categories = get_categories();
        array_unshift($categories, [
            'id'		=> null,
            'name'		=> _s('Select category'),
            'url_key'	=> null,
            'url'		=> null
        ]);
        foreach ($categories as $category) {
            ?>
	<option value="<?php echo $category['id']; ?>"<?php if ($category['id'] == $user_items_editor['category_id']) {
                echo " selected";
            } ?>><?php echo $category['name']; ?></option>
	<?php
        }
    ?>
</select>