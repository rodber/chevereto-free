<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php $album = function_exists('get_album_safe_html') ? get_album_safe_html() : NULL; ?>
<div class="c7 input-label">
	<?php
		$label = 'form-album-name';
	?>
    <label for="<?php echo $label; ?>"><?php _se('Album name'); ?></label>
    <input type="text" id="<?php echo $label; ?>" name="<?php echo $label; ?>" class="text-input" value="<?php echo $album["name"]; ?>" placeholder="<?php _se('Album name'); ?>" maxlength="<?php echo CHV\getSetting('album_name_max_length'); ?>" required>
	<?php if($GLOBALS['theme_include_args']['album-switch'] !== FALSE) { ?>
    <span data-action="album-switch" class="btn-alt c7"><?php _se('or'); ?> <a data-switch="move-existing-album"><?php _se('move to existing album'); ?></a></span>
	<?php } ?>
</div>
<div class="input-label">
	<label for="form-album-description"><?php _se('Album description'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
	<textarea id="form-album-description" name="form-album-description" class="text-input no-resize" placeholder="<?php _se('Brief description of this album'); ?>"><?php echo $album['description']; ?></textarea>
</div>
<?php if(CHV\getSetting('website_privacy_mode') == 'public' or (CHV\getSetting('website_privacy_mode') == 'private' and CHV\getSetting('website_content_privacy_mode') == 'default')) { ?>
<div class="input-label overflow-auto">
    <div class="c7 grid-columns">
		<label for="form-privacy"><?php _se('Album privacy'); ?></label>
		<select name="form-privacy" id="form-privacy" class="text-input" data-combo="form-privacy-combo" rel="template-tooltip" data-tiptip="right" data-title="<?php _se('Who can view this content'); ?>">
			<?php
				foreach([
					'public' 			=> ['label' => _s('Public')],
					'private'			=> ['label' => _s('Private (just me)')],
					'private_but_link'	=> ['label' => _s('Private (anyone with the link)')],
					'password' 			=> ['label' => _s('Private (password protected)')],
				] as $k => $v) {
				echo '<option value="'.$k.'"' . ($album['privacy'] == $k ? '  selected' : NULL) . '>'.$v['label'].'</option>';
				}
			?>
		</select>
	</div>
</div>
<div id="form-privacy-combo">
	<div data-combo-value="password" class="switch-combo<?php echo ($album['privacy'] !== 'password' ? ' soft-hidden' : NULL); ?>">
		<div class="input-label overflow-auto">
			<div class="c7 grid-columns">
				<label for="form-album-password"><?php _se('Album password'); ?></label>
				<input type="text" id="form-album-password" name="form-album-password" class="text-input" value="<?php echo $album['password']; ?>" data-required<?php echo ($album['privacy'] == 'password' ? ' required' : NULL); ?>>
			</div>
		</div>
	</div>
</div>
<?php } ?>