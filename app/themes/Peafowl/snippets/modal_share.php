<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php $share_modal = function_exists('get_share_modal') ? get_share_modal() : G\get_global("share_modal"); ?>
<div id="modal-share" class="hidden">
	<span class="modal-box-title"><?php _se('Share'); ?></span>
    <p class="highlight margin-bottom-20 font-size-small text-align-center<?php if (is_null($share_modal["privacy"]) || $share_modal["privacy"] == "public") {
    echo " soft-hidden";
} ?>" data-content="privacy-private"><?php echo $share_modal['privacy_notes']; ?></p>
	<ul class="panel-share-networks">
		<?php echo join("\n", $share_modal["links_array"]); ?>
	</ul>
	<div class="c8 phablet-c1">
		<div class="input-label margin-bottom-0">
			<label for="modal-share-url"><?php _se('Link'); ?></label>
			<input type="text" name="modal-share-url" id="modal-share-url" class="text-input" value="<?php echo $share_modal["url"]; ?>" data-focus="select-all">
		</div>
	</div>
</div>