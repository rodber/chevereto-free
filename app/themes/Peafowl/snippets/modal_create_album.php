<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div data-modal="new-album" class="hidden" data-is-xhr data-submit-fn="CHV.fn.submit_create_album" data-ajax-deferred="CHV.fn.complete_create_album">
	<span class="modal-box-title"><?php _se('Create new album'); ?></span>
	<div class="modal-form">
	<?php G\Render\include_theme_file("snippets/form_album.php", ['album-switch' => false]); ?>
	</div>
</div>