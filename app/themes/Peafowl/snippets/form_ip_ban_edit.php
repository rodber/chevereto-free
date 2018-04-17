<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<div class="input-label c7">
	 <label for="form-ip_ban-ip"><?php _se('IP address'); ?></label>
	 <input type="text" <?php if(function_exists('get_image')) { echo 'readonly'; } ?> id="form-ip_ban-ip" name="form-ip_ban-ip" class="text-input" value="<?php if(function_exists('get_image')) { echo get_image()['uploader_ip']; } ?>" placeholder="127.0.0.1" required>
	 <div class="input-below font-size-small"><?php _se('You can use wildcard * characters.'); ?></div>
</div>
<div class="input-label">
	 <div class="c7">
		<label for="form-ip_ban-expires"><?php _se('Expiration date'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
		<input type="datetime" id="form-ip_ban-expires" name="form-ip_ban-expires" class="text-input" value="" placeholder="<?php _se('YYYY-MM-DD HH:MM:SS'); ?>" pattern="^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$" rel="template-tooltip" data-tiptip="right" data-title="<?php echo _s('Example') . ' ' . G\datetimegmt(); ?>">
	 </div>
	 <div class="input-below font-size-small"><?php _se('Until which date this IP address will be banned? Leave it empty for no expiration.'); ?></div>
</div>
<div class="input-label">
	<label for="form-ip_ban-message"><?php _se('Message'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
	<textarea id="form-ip_ban-message" name="form-ip_ban-message" class="text-input no-resize" placeholder="<?php _se('Text message, HTML or a redirect URL'); ?>"></textarea>
</div>