<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<div class="content-empty">
	<span class="icon icon-drawer"></span>
	<h2><?php _se("There's nothing to show here."); ?></h2>
	<div>
	<?php
		$buttons = [
			'upload_images' => [
				'%icon' => 'icon-cloud-upload',
				'%attr' => 'data-trigger="anywhere-upload-input"',
				'%text' => _s('Upload images'),
			],
			'new_album' => [
				'%icon' => 'icon-folder',
				'%attr' => 'data-modal="edit" data-target="new-album"',
				'%text' => _s('Create new album'),
			],
		];
		if(function_exists('is_owner') && is_owner() && is_upload_allowed()) {
			if(G\Handler::getCond('user_images') || function_exists('get_album')) {
				$button = $buttons['upload_images'];
			}
			if(G\Handler::getCond('user_albums')) {
				$button = $buttons['new_album'];
			}
		}
		if(in_array(G\get_route_name(), ['explore', 'category']) && !isset($button) && is_upload_allowed()) {
			$button = $buttons['upload_images'];
		}
		if(isset($button)) {
			echo strtr('<button class="btn default margin-top-10" %attr><span class="btn-icon %icon"></span><span class="btn-text">%text</span></button>', $button);
		}
	?>
	</div>
</div>