<?php
if(!defined('access') or !access) die('This file cannot be directly accessed.');
if(CHV\Login::isLoggedUser()) {
	$user_albums = [];
	if(CHV\Login::getUser()['album_count'] > 0) {
		$user_albums = function_exists('is_owner') && is_owner() && function_exists('get_user_items_editor') && isset(get_user_items_editor()['user_albums']) ? get_user_items_editor()['user_albums'] : CHV\User::getAlbums(CHV\Login::getUser());
	}
}
?>
<div id="anywhere-upload" class="upload-box upload-box--fixed upload-box--hidden queueEmpty" data-queue-size="0">

	<div class="content-width">

    	<div class="upload-box-inner">

        	<div class="upload-box-heading c16 center-box">
				<div class="upload-box-status">
					<div data-group="upload">
						<span class="icon icon-download2 cursor-pointer" data-trigger="anywhere-upload-input"></span>
						<div class="heading device-mobile--hide"><a data-trigger="anywhere-upload-input"><?php _se('Drag and drop or paste images here to upload'); ?></a></div>
						<div class="heading device-nonmobile--hide"><a data-trigger="anywhere-upload-input"><?php _se('Select the images to upload'); ?></a></div>
						<div class="device-mobile--hide upload-box-status-text"><?php _se('You can also %i or %u.', [
							'%i' => '<a data-trigger="anywhere-upload-input">' . _s('browse from your computer') . '</a>',
							'%u' => '<a data-modal="form" data-target="anywhere-upload-paste-url">' . _s('add image URLs') . '</a>',
						]); ?></div>
						<div class="device-nonmobile--hide upload-box-status-text"><?php _se('You can also %i, %c or %u.', [
							'%i' => '<a data-trigger="anywhere-upload-input">' . _s('browse from your device') . '</a>',
							'%c' => '<a data-trigger="anywhere-upload-input-camera">' . _s('take a picture') . '</a>',
							'%u' => '<a data-modal="form" data-target="anywhere-upload-paste-url">' . _s('add image URLs') . '</a>',
						]); ?></div>
					</div>
					<div data-group="upload-queue-ready" class="soft-hidden">
						<span class="icon icon-images" data-trigger="anywhere-upload-input"></span>
						<div class="heading device-mobile--hide"><?php _se('Edit or resize any image by clicking the image preview'); ?></div>
						<div class="heading device-nonmobile--hide"><?php _se('Edit any image by touching the image preview'); ?></div>
						<div class="device-mobile--hide upload-box-status-text"><?php _se('You can add more images from %i or %u.', [
							'%i' => '<a data-trigger="anywhere-upload-input">' . _s('your computer') . '</a>',
							'%u' => '<a data-modal="form" data-target="anywhere-upload-paste-url">' . _s('add image URLs') . '</a>',
						]); ?></div>
						<div class="device-nonmobile--hide upload-box-status-text"><?php _se('You can add more images from %i, %c or %u.', [
							'%i' => '<a data-trigger="anywhere-upload-input">' . _s('your device') . '</a>',
							'%c' => '<a data-trigger="anywhere-upload-input-camera">' . _s('take a picture') . '</a>',
							'%u' => '<a data-modal="form" data-target="anywhere-upload-paste-url">' . _s('add image URLs') . '</a>',
						]); ?></div>
					</div>
					<div data-group="uploading" class="soft-hidden">
						<span class="icon icon-cloud-upload"></span>
						<div class="heading"><?php _se('Uploading %q %o', [
							'%q' => '<span data-text="queue-size">0</span>',
							'%o' => '<span data-text="queue-objects">' . _s('image', 'images', 10) . '</span>',
						]); ?> (<span data-text="queue-progress">0</span>% <?php _se('complete'); ?>)</div>
						<div class="upload-box-status-text"><?php _se('The queue is being uploaded, it should take just a few seconds to complete.'); ?></div>
					</div>
					<div data-group="upload-result" data-result="success" class="soft-hidden">
						<span class="icon icon-checkmark-circle color-green"></span>
						<div class="heading"><?php _se('Upload complete'); ?></div>
						<div class="upload-box-status-text">
							<div data-group="user" class="soft-hidden">
								<div data-group="user-stream" class="soft-hidden">
								<?php
									$uploaded_message = _s('Uploaded content added to %s.') . ' ';
									if(CHV\Login::getUser()['album_count'] > 0) {
										$uploaded_message .= _s('You can %c with the content just uploaded or %m.');
									} else {
										$uploaded_message .= _s('You can %c with the content just uploaded.');
									}
									echo strtr($uploaded_message, [
										'%s'	=> '<a data-text="upload-target" data-link="upload-target"></a>',
										'%c'	=> '<a data-modal="form" data-target="form-uploaded-create-album">' . _s('create a new album') . '</a>',
										'%m'	=> '<a data-modal="form" data-target="form-uploaded-move-album">' . _s('move it to an existing album') . '</a>',
									]);
								?>
								</div>
								<div data-group="user-album" class="soft-hidden"><?php _se('Uploaded content added to %s.', '<a data-text="upload-target" data-link="upload-target"></a>'); ?></div>
							</div>
							<div data-group="guest" class="soft-hidden"><?php _se('You can %s or %l to save this content into your account.', [
								'%s' => '<a href="' . G\get_base_url("signup") . '">' . _s('create an account') . '</a>',
								'%l' => '<a href="' . G\get_base_url("login") . '">' . _s('sign in') . '</a>'
							]); ?></div>
						</div>
					</div>
					<div data-group="upload-result" data-result="error" class="soft-hidden">
						<span class="icon icon-cross4 color-grey"></span>
						<div class="heading"><?php _se('No %s have been uploaded', '<span data-text="queue-objects">' . _s('image', 'images', 10) . '</span>') ;?></div>
						<div class="upload-box-status-text"><?php _se("Some errors have occured and the system couldn't process your request."); ?></div>
					</div>
				</div>
            </div>

			<input id="anywhere-upload-input" data-action="anywhere-upload-input"<?php if(!CHV\getSetting('guest_uploads')) { ?> data-login-needed="true"<?php } ?> class="hidden-visibility" type="file" accept="<?php echo '.' . implode(',.', CHV\Image::getEnabledImageFormats()); ?>" multiple>
			<input id="anywhere-upload-input-camera" data-action="anywhere-upload-input"<?php if(!CHV\getSetting('guest_uploads')) { ?> data-login-needed="true"<?php } ?> class="hidden-visibility" type="file" capture="camera" accept="image/*">
			<ul id="anywhere-upload-queue" class="upload-box-queue content-width soft-hidden" data-group="upload-queue"></ul>

			<div id="anywhere-upload-submit" class="btn-container text-align-center margin-bottom-0 soft-hidden" data-group="upload-queue-ready">
				<div data-group="upload-queue-ready">

					<?php
						if(CHV\Login::isLoggedUser() && CHV\Login::getUser()['album_count'] > 0) {
					?>
					<div class="input-label upload-input-col center-box text-align-left">
						<label for="upload-album-id"><?php _se('Album', 'Albums', 1); ?></label>
						<select name="upload-album-id" id="upload-album-id" class="text-input">
							<?php
								$user_album_options_html = [];
								foreach($user_albums as $album) {
									$user_album_options_html[] = strtr('<option value="%id"%selected>%name</option>', [
											'%selected' => (function_exists('get_album') && get_album()['id_encoded'] == $album['id_encoded']) ? ' selected' : NULL,
											'%id'	=> $album['id_encoded'],
											'%name' => $album['name_with_privacy_readable_html']
										]);
								}
								$user_album_options_html = implode("\n", $user_album_options_html);
								echo $user_album_options_html;
							?>
						</select>
					</div>
					<?php
						}
					?>
					<?php
						if(get_categories()) {
					?>
					<div class="input-label upload-input-col center-box text-align-left">
						<label for="upload-category-id"><?php _se('Category'); ?></label>
						<select name="upload-category-id" id="upload-category-id" class="text-input">
							<option value><?php _se('Select category'); ?></option>
							<?php
								foreach(get_categories() as $category) {
							?>
							<option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
							<?php
								} //for
							?>
						</select>
					</div>
					<?php
						} // categories?
					?>
					<?php
						if(CHV\getSetting('theme_nsfw_upload_checkbox')) {
					?>
					<div class="margin-10"><span rel="tooltip" data-tiptip="top" title="<?php _se('Mark this if the upload is not family safe'); ?>"><input type="checkbox" name="upload-nsfw" id="upload-nsfw" class="margin-right-5" value="1"><label for="upload-nsfw"><?php _se('Not family safe upload'); ?></label></span></div>
					<?php
						}
					?>
					<button class="btn btn-big green" data-action="upload" data-public="<?php _se('Upload'); ?>" data-private="<?php _se('Private upload'); ?>"><?php echo is_forced_private_mode() ? _s('Private upload') : _s('Upload'); ?></button></span>
				</div>
				<div data-group="uploading" class="soft-hidden">
					<button class="btn plain disabled btn-big plain margin-right-5" disabled data-action="upload-privacy-copy"><span class="icon icon-unlocked" data-lock="icon-lock" data-unlock="icon-unlocked"></span></button><button class="btn btn-big disabled off" disabled><?php _se('Uploading'); ?></button> <span class="btn-alt"><?php _se('or'); ?> <a data-action="cancel-upload" data-button="close-cancel"><?php _se('cancel'); ?></a><a data-action="cancel-upload-remaining" data-button="close-cancel" class="soft-hidden"><?php _se('cancel remaining'); ?></a></span>
				</div>
			</div>

			<div id="anywhere-upload-report">
				<div data-group="upload-result" data-result="mixted" class="soft-hidden margin-top-10 text-align-center upload-box-status-text"><?php _se("Note: Some images couldn't be uploaded."); ?> <a data-modal="simple" data-target="failed-upload-result"><?php _se('learn more'); ?></a></div>
				<div data-group="upload-result" data-result="error" class="soft-hidden margin-top-10 text-align-center upload-box-status-text"><?php _se('Check the <a data-modal="simple" data-target="failed-upload-result">error report</a> for more information.'); ?></div>
			</div>

      <div class="upload-box-allowed-files position-absolute">
				<span><?php echo str_replace(',', ' ', strtoupper(CHV\getSetting('upload_enabled_image_formats'))); ?></span>
				<span><?php echo G\format_bytes(G\get_bytes(CHV\getSetting('upload_max_filesize_mb').'MB')); ?>
				<?php
					if(CHV\getSetting('upload_max_filesize_mb_bak') != NULL && CHV\getSetting('upload_max_filesize_mb') != CHV\getSetting('upload_max_filesize_mb_bak')) {
				?>
				<span class="icon icon-warning color-red margin-left-5" rel="tooltip" data-tiptip="top" title="<?php _se('Register to get %s', G\format_bytes(G\get_bytes(CHV\getSetting('upload_max_filesize_mb_bak').'MB'))); ?>"></span>
				<?php
					}
				?>
				</span>
			</div>

			<div class="upload-box-close position-absolute">
				<a data-action="reset-upload" data-button="close-cancel"><span class="btn-icon icon-loop2"></span><span class="btn-text"><?php _se('reset'); ?></span></a>
				<a data-action="close-upload" data-button="close-cancel"><span class="btn-icon icon-close"></span><span class="btn-text"><?php _se('close'); ?></span></a>
				<a data-action="cancel-upload" data-button="close-cancel"><span class="btn-icon icon-close"></span><span class="btn-text"><?php _se('cancel'); ?></span></a>
				<a data-action="cancel-upload-remaining" data-button="close-cancel"><span class="btn-icon icon-close"></span><span class="btn-text"><?php _se('cancel remaining'); ?></span></a>
			</div>

			<?php if(CHV\getSetting('theme_show_embed_uploader')) { ?>
			<div data-group="upload-result" data-result="success" class="c16 center-box soft-hidden">
				<div class="input-label margin-bottom-0 copy-hover-display">
					<label for="uploaded-embed-toggle"><?php _se('Embed codes'); ?></label>
					<div class="c7 margin-bottom-10">
						<select name="uploaded-embed-toggle" id="uploaded-embed-toggle" class="text-input" data-combo="uploaded-embed-toggle-combo">
							<?php
								foreach(G\get_global('embed_tpl') as $key => $value) {
									echo '<optgroup label="'.$value['label'].'">'."\n";
									foreach($value['options'] as $k => $v) {
										echo '	<option value="'.$k.'" data-size="'.$v["size"].'">'.$v["label"].'</option>'."\n";
									}
									echo '</optgroup>';
								}
							?>
						</select>
					</div>
					<div id="uploaded-embed-toggle-combo">
						<?php
							$i=0;
							foreach(G\get_global('embed_tpl') as $key => $value) {
								foreach($value['options'] as $k => $v) {
									echo '<div data-combo-value="'.$k.'" class="switch-combo'.($i>0 ? " soft-hidden" : "").'">
										<textarea id="uploaded-embed-code-'.$i.'" class="r2 resize-vertical" name="'.$k.'" data-size="'.$v["size"].'" data-focus="select-all"></textarea>
										<button class="input-action" data-action="copy" data-action-target="#uploaded-embed-code-'.$i.'">'._s('copy').'</button>
										<button class="input-action" data-action="openerPostMessage" data-action-target="#uploaded-embed-code-'.$i.'">'._s('insert').'</button>
									</div>'."\n";
									$i++;
								}
							}
						?>
					</div>
				</div>
			</div>
			<?php } ?>

        </div>

    </div>

	<div class="hidden">
		<div id="anywhere-upload-item-template">
			<li class="queue-item">
				<a class="block image-link" data-group="image-link" href="#file" target="_blank"></a>
				<div class="result done block"><span class="icon icon-checkmark-circle"></span></div>
				<div class="result failed block"><span class="icon icon-warning"></span></div>
				<div class="load-url block"><span class="big-icon icon-url-loading"></span></div>
				<div class="preview block"></div>
				<div class="progress block">
					<div class="progress-percent"><b data-text="progress-percent">0</b><span>%</span></div>
					<div class="progress-bar" data-content="progress-bar"></div>
				</div>
				<div class="block edit" data-action="edit" title="<?php _se('Edit'); ?>">
				</div>
				<div class="queue-item-button edit" data-action="edit" title="<?php _se('Edit'); ?>">
					<span class="icon icon-edit"></span>
				</div>
				<div class="queue-item-button cancel hover-display" data-action="cancel" title="<?php _se('Remove'); ?>">
					<span class="icon icon-cross"></span>
				</div>
			</li>
		</div>
		<div id="anywhere-upload-edit-item">
			<span class="modal-box-title"><?php _se('Edit image'); ?></span>
			<div class="modal-form">
				<div class="image-preview"></div>
				<div class="input-label">
					<label for="form-title"><?php _se('Title'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
					<input type="text" id="form-title" name="form-title" class="text-input" value="" maxlength="<?php echo CHV\getSetting('image_title_max_length'); ?>">
				</div>
				<?php
					if(CHV\Login::isLoggedUser() && CHV\Login::getUser()['album_count'] > 0) {
				?>
				<div class="input-label c7">
					<label for="form-album-id"><?php _se('Album'); ?></label>
					<select name="form-album-id" id="form-album-id" class="text-input">
						<?php
							echo $user_album_options_html;
						?>
					</select>
				</div>
				<?php
					}
				?>
				<?php
					if(get_categories()) {
				?>
				<div class="input-label c7">
					<?php G\Render\include_theme_file('snippets/form_category'); ?>
				</div>
				<?php
					}
				?>
				<div class="input-label" data-action="resize-combo-input">
					<label for="form-width" class="display-block-forced"><?php _se('Resize image'); ?></label>
					<div class="c6 overflow-auto clear-both">
						<div class="c3 float-left">
							<input type="number" min="16" pattern="\d+" name="form-width" id="form-width" class="text-input" title="<?php _se('Width'); ?>" rel="template-tooltip" data-tiptip="top">
						</div>
						<div class="c3 float-left margin-left-10">
							<input type="number" min="16" pattern="\d+" name="form-height" id="form-height" class="text-input" title="<?php _se('Height'); ?>" rel="template-tooltip" data-tiptip="top">
						</div>
					</div>
					<div class="input-below font-size-small" data-content="animated-gif-warning"><?php _se("Note: Animated GIF images won't be resized."); ?></div>
				</div>
                <?php if(CHV\getSetting('enable_expirable_uploads')) { ?>
                <div class="input-label">
                    <label for="form-expiration"><?php _se('Auto delete image'); ?></label>
                    <div class="c6 phablet-1">
                        <select type="text" name="form-expiration" id="form-expiration" class="text-input">
                        <?php
							$expirations = CHV\Image::getAvailableExpirations();
							if(!CHV\Login::isLoggedUser() && CHV\getSetting('auto_delete_guest_uploads') != NULL) {
								$expirations = [$expirations[CHV\getSetting('auto_delete_guest_uploads')]];
							}
                            echo CHV\Render\get_select_options_html($expirations, CHV\Login::isLoggedUser() ? CHV\Login::getUser()['image_expiration'] : NULL);
                        ?>
                        </select>
                    </div>
					<?php
						if(!CHV\Login::isLoggedUser() && CHV\getSetting('auto_delete_guest_uploads') != NULL) {
					?>
					<div class="input-below"><?php _se('%s to be able to customize or disable image auto delete.', '<a href="' . G\get_base_url('signup') . '">' . _s('Sign up') . '</a>'); ?></div>
					<?php
						}
					?>
                </div>
                <?php } ?>
                <?php if(CHV\getSetting('theme_nsfw_upload_checkbox')) { ?>
				<div class="checkbox-label">
					<div class="display-inline" rel="template-tooltip" data-tiptip="right" data-title="<?php _se('Mark this if the image is not family safe'); ?>">
						<label for="form-nsfw">
							<input class="float-left" type="checkbox" name="form-nsfw" id="form-nsfw" value="1"><?php _se('Flag as unsafe'); ?>
						</label>
					</div>
				</div>
                <?php } ?>
				<div class="input-label">
					<label for="form-description"><?php _se('Description'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
					<textarea id="form-description" name="form-description" class="text-input no-resize" placeholder="<?php _se('Brief description of this image'); ?>"></textarea>
				</div>
			</div>
		</div>
		<div id="anywhere-upload-paste-url" data-submit-fn="CHV.fn.uploader.pasteURL">
			<span class="modal-box-title"><?php _se('Add image URLs'); ?></span>
			<div class="modal-form">
				<textarea class="resize-vertical" placeholder="<?php _se('Add the image URLs here'); ?>" name="urls"></textarea>
			</div>
		</div>
	</div>

	<?php
		if(CHV\Login::isLoggedUser()) {
			global $new_album, $user_items_editor;
			$new_album = true;
			$user_items_editor = [
				"user_albums"	=> $user_albums,
				"type"			=> "albums"
			];
	?>
	<div data-modal="form-uploaded-create-album" class="hidden" data-is-xhr data-submit-fn="CHV.fn.submit_upload_edit" data-ajax-deferred="CHV.fn.complete_upload_edit">
		<span class="modal-box-title"><?php _se('Create album'); ?></span>
		<p><?php _se('The uploaded content will be moved to this newly created album. You can also move the content to an <a class="modal-switch" data-switch="move-existing-album">existing album</a>.'); ?></p>
		<div class="modal-form">
			<div name="move-existing-album" id="move-existing-album" data-view="switchable" class="c7 input-label soft-hidden">
				<?php G\Render\include_theme_file("snippets/form_move_existing_album"); ?>
			</div>
			<div name="move-new-album" id="move-new-album" data-content="form-new-album" data-view="switchable">
				<?php G\Render\include_theme_file("snippets/form_album"); ?>
			</div>
		</div>
	</div>
	<div data-modal="form-uploaded-move-album" class="hidden" data-is-xhr data-submit-fn="CHV.fn.submit_upload_edit" data-ajax-deferred="CHV.fn.complete_upload_edit">
		<span class="modal-box-title"><?php _se('Move to album'); ?></span>
		<p><?php _se('Select an existing album to move the uploaded content. You can also <a class="modal-switch" data-switch="move-new-album">create a new album</a> and move the content there.'); ?></p>
		<div class="modal-form">
			<div name="move-existing-album" id="move-existing-album" data-view="switchable" class="c7 input-label">
				<?php G\Render\include_theme_file("snippets/form_move_existing_album"); ?>
			</div>
			<div name="move-new-album" id="move-new-album" data-content="form-new-album" data-view="switchable" class="soft-hidden">
				<?php G\Render\include_theme_file("snippets/form_album"); ?>
			</div>
		</div>
	</div>

	<?php
		}
	?>

	<div data-modal="failed-upload-result" class="hidden">
		<span class="modal-box-title"><?php _se('Error report'); ?></span>
		<ul data-content="failed-upload-result" style="max-height: 115px;" class="overflow-auto"></ul>
	</div>

</div>
