<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php $user_items_editor = G\get_global('user_items_editor') ?: (function_exists('get_user_items_editor') ? get_user_items_editor() : null); ?>
<label for="form-album-id"><?php echo !isset($user_items_editor['album']) ? _s('Existing album') : _n('Album', 'Albums', 1); ?></label>
<select name="form-album-id" id="form-album-id" class="text-input">
	<?php
        if (is_array($user_items_editor)) {
            foreach ($user_items_editor['user_albums'] as $album) {
                ?>
	<option value="<?php echo $album['id_encoded']; ?>"<?php if ($album['id'] == $user_items_editor['album']['id']) {
                    echo " selected";
                } ?>><?php echo $album['name_with_privacy_readable_html']; ?></option>
	<?php
            }
        }
    ?>
</select>
<span class="btn-alt c7"><?php _se('or'); ?> <a data-switch="move-new-album"><?php _se('create new album'); ?></a></span>