<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">

	<div class="c24 center-box">

		<div class="header header-tabs default-margin-bottom">
			<h1><?php echo get_pre_doctitle(); ?></h1>
			<div class="hidden phone-show phablet-show">
				<?php
					foreach(get_settings_menu() as $tab) {
						if($tab["current"]) {
							$current = $tab;
							break;
						}
					}
				?>
				<div class="phone-show hidden tab-menu current" data-action="tab-menu"><span data-content="current-tab-label"><?php echo $current["label"]; ?></span><span class="icon icon-menu4 margin-left-5"></span></div>
				<ul class="content-tabs phone-hide phablet-show">
					<?php
						foreach(get_settings_menu() as $item) {
					?>
					<li<?php if($item["current"]) echo ' class="current"'; ?>><a href="<?php echo $item["url"]; ?>"><?php echo $item["label"]; ?></a></li>
					<?php
						}
					?>
				</ul>
			</div>
			<?php
				if(is_dashboard_user()) {
			?>
			<div class="header-content-right phone-float-none">
				<div class="list-selection">
					<a href="<?php echo get_user()['url']; ?>" class="header-link"><?php echo get_user()['username']; ?></a>
					<a class="delete-link margin-left-5" data-confirm="<?php _se("Do you really want to delete this user? This can't be undone."); ?>" data-submit-fn="CHV.fn.user.delete.submit" data-ajax-deferred="CHV.fn.complete_resource_delete" data-ajax-url="<?php echo G\get_base_url("json"); ?>"><?php _se('Delete user'); ?></a>
				</div>
			</div>
			<?php
				}
			?>
		</div>

		<div class="form-content">

			<ul class="content-tabs c5 content-tabs-vertical phone-hide phablet-hide">
				<?php
					foreach(get_settings_menu() as $item) {
				?>
				<li<?php if($item["current"]) echo ' class="current"'; ?>><a href="<?php echo $item["url"]; ?>"><?php echo $item["label"]; ?></a></li>
				<?php
					}
				?>
			</ul>

			<form data-content="main-form" class="tabbed-input-column" method="post" data-type="<?php echo get_setting(); ?>" data-action="validate">

				<?php echo G\Render\get_input_auth_token(); ?>

				<?php
					if(is_settings_account()) {

						if(is_dashboard_user() or is_admin()) {
				?>
				<ul class="tabbed-content-list table-li">
					<?php
						$user_list_values = [
							[
								'label'		=> _s('User ID'),
								'content'	=> get_user()['id'] . ' ('.get_user()['id_encoded'].')'
							],
							[
								'label'		=> _s('Images'),
								'content'	=> get_user()['image_count']
							],
							[
								'label' 	=> _s('Albums'),
								'content'	=> get_user()['album_count']
							],
							[
								'label' 	=> _s('Register date'),
								'content' 	=> get_user()['date']
							],
							[
								'label' => NULL,
								'content' => get_user()['date_gmt'] . ' (GMT)'
							]
						];
						if(get_user()['registration_ip']) {
							$user_list_values[] = [
								'label'		=> _s('Registration IP'),
								'content'	=> get_user()['registration_ip']
							];
						}
						foreach($user_list_values as $v) {
					?>
					<li><span class="c4 display-table-cell padding-right-10"><?php echo $v['label']; ?></span> <span class="display-table-cell"><?php echo $v['content']; ?></span></li>
					<?php
						}
					?>
				</ul>

				<div class="c5 phablet-c1">
					<div class="input-label">
						<label for="status"><?php _se('Status'); ?></label>
						<select name="status" id="status" class="text-input">
							<?php
								foreach([
									'valid'	 => _s('Valid'),
									'banned' => _s('Banned'),
									'awaiting-email' => _s('Awaiting email'),
									'awaiting-confirmation' => _s('Awaiting confirmation')
								] as $k => $v) {
									$selected = $k == get_user()["status"] ? " selected" : "";
									echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>'."\n";
								}
							?>
						</select>
					</div>
					<div class="input-label">
						<label for="role"><?php _se('Role'); ?></label>
						<select name="role" id="role" class="text-input">
							<?php
								foreach([
									'admin'	=> ['label' => _s('Administrator'), 'selected' => get_user()['is_admin']],
									'user'	=> ['label' => _s('User'), 'selected' => !get_user()['is_admin']]
								] as $k => $v) {
									$selected = $v['selected'] ? " selected" : "";
									echo '<option value="'.$k.'"'.$selected.'>'.$v['label'].'</option>'."\n";
								}
							?>
						</select>
					</div>
				</div>

				<hr class="line-separator"></hr>

				<?php
						}
				?>
				<div class="c9 phablet-c1">
					<div class="input-label">
						<label for="username"><?php _se('Username'); ?></label>
						<input type="text" name="username" id="username" maxlength="<?php echo CHV\getSetting('username_max_length'); ?>" class="text-input" value="<?php echo get_safe_post() ? get_safe_post()["username"] : get_user()["username"]; ?>" pattern="<?php echo CHV\getSetting('username_pattern'); ?>" rel="tooltip" title='<?php _se('%i to %f characters<br>Letters, numbers and "_"', ['%i' => CHV\getSetting('username_min_length'), '%f' => CHV\getSetting('username_max_length')]); ?>' data-tipTip="right" placeholder="<?php _se('Username'); ?>" required>
						<span class="input-warning red-warning"><?php echo get_input_errors()["username"]; ?></span>
						<?php
							if(CHV\getSetting('website_mode') == 'community') {
						?>
						<div class="input-below"><?php echo G\get_base_url(CHV\getSetting('user_routing') ? NULL : 'user') . '/'; ?><span data-text="username"><?php echo get_user()["username"]; ?></span></div>
						<?php
							}
						?>
					</div>
					<div class="input-label">
						<label for="email"><?php _se('Email address'); ?></label>
						<input type="email" name="email" id="email" class="text-input" value="<?php echo get_safe_post() ? get_safe_post()["email"] : get_user()["email"]; ?>" placeholder="<?php _se('Your email address'); ?>"<?php if(is_email_required()) { ?> required<?php } ?>>
						<span class="input-warning red-warning"><?php echo get_input_errors()["email"]; ?></span>
						<?php if(get_changed_email_message()) { ?><div class="input-below highlight padding-5"><?php echo get_changed_email_message(); ?></div><?php } ?>
					</div>
				</div>

				<hr class="line-separator"></hr>

				<?php if(CHV\getSetting('enable_expirable_uploads')) { ?>
                <div class="input-label">
                    <label for="image_expiration"><?php _se('Auto delete uploads'); ?></label>
                    <div class="c6 phablet-1">
                        <select type="text" name="image_expiration" id="image_expiration" class="text-input">
                        <?php
                            echo CHV\Render\get_select_options_html(CHV\Image::getAvailableExpirations(), get_safe_post() ? get_safe_post()['image_expiration'] : get_user()['image_expiration']);
                        ?>
                        </select>
                    </div>
					<div class="input-below input-warning red-warning"><?php echo get_input_errors()["image_expiration"]; ?></div>
                    <div class="input-below"><?php _se('This setting will apply to all your image uploads by default. You can override this setting on each upload.'); ?></div>
                </div>

                <hr class="line-separator"></hr>

				<?php } ?>

                <?php if(CHV\getSetting('upload_image_exif_user_setting')) { ?>
                <div class="input-label">
                    <label><?php _se('Image Exif data'); ?></label>
					<?php echo CHV\Render\get_checkbox_html([
						'name'		=> 'image_keep_exif',
						'label'		=> _s('Keep image <a %s>Exif data</a> on upload', 'href="https://www.google.com/search?q=Exif" target="_blank"'),
						'checked'	=> ((bool)(get_safe_post() ? get_safe_post()['image_keep_exif'] : get_user()['image_keep_exif']))
					]); ?>
                </div>
                <?php } ?>

				<div class="input-label">
					<label><?php _se('Newsletter'); ?></label>
					<?php echo CHV\Render\get_checkbox_html([
						'name'		=> 'newsletter_subscribe',
						'label'		=> _s('Send me emails with news about %s', CHV\getSetting('website_name')),
						'checked'	=> ((bool)(get_safe_post() ? get_safe_post()['newsletter_subscribe'] : get_user()['newsletter_subscribe']))
					]); ?>
				</div>

				<div class="input-label">
					<label><?php _se('Content settings'); ?></label>
					<?php echo CHV\Render\get_checkbox_html([
						'name'		=> 'show_nsfw_listings',
						'label'		=> _s('Show not safe content in listings (from others)'),
						'checked'	=> ((bool)(get_safe_post() ? get_safe_post()['show_nsfw_listings'] : get_user()['show_nsfw_listings']))
					]); ?>
				</div>

				<hr class="line-separator"></hr>

				<?php if(CHV\getSetting('language_chooser_enable')) { ?>
				<div class="c5 phablet-c1">
					<div class="input-label">
						<label for="language"><?php _se('Language'); ?></label>
						<select name="language" id="language" class="text-input">
							<?php
								$enabled_languages = CHV\get_enabled_languages();
								foreach($enabled_languages as $k => $v){
									$selected_lang = $k == CHV\get_language_used()['code'] ? " selected" : "";
									echo '<option value="'.$k.'"'.$selected_lang.'>'.$v["name"].'</option>'."\n";
								}
							?>
						</select>
					</div>
				</div>
				<?php } ?>

				<?php

				$zones = timezone_identifiers_list();

				foreach ($zones as $zone) {
					$zone = explode('/', $zone);
					if(in_array($zone[0], array("Africa", "America", "Antarctica", "Arctic", "Asia", "Atlantic", "Australia", "Europe", "Indian", "Pacific"))) {
						if (isset($zone[1]) != '') {
							$regions[$zone[0]][$zone[0]. '/' . $zone[1]] = str_replace('_', ' ', $zone[1]);
						}
					}
				}

				?>

				<div class="input-label">
					<label for="timezone"><?php _se('Timezone'); ?></label>

					<div class="overflow-auto">

						<select id="timezone-region" class="c5 phablet-c1 grid-columns margin-right-10 phone-margin-bottom-10 phablet-margin-bottom-10 text-input" data-combo="timezone-combo">
							<option><?php _se('Select region'); ?></option>
							<?php
								$user_region = preg_replace("/(.*)\/.*/", "$1", get_user()["timezone"]);
								foreach($regions as $key => $region) {
									$selected = $user_region == $key ? " selected" : "";
									echo '<option value="'.$key.'"'.$selected.'>'.$key.'</option>';
								}
							?>
						</select>
						<div id="timezone-combo" class="c5 phablet-c1 grid-columns">
							<?php
								foreach($regions as $key => $region) {
									$show_hide = $user_region == $key ? "" : " soft-hidden";
							?>
							<select id="timezone-combo-<?php echo $key; ?>" class="text-input switch-combo<?php echo $show_hide; ?>" data-combo-value="<?php echo $key; ?>">
								<?php
									foreach($region as $k => $l) {
										$selected = get_user()["timezone"] == $k ? " selected" : "";
										echo '<option value="'.$k.'"'.$selected.'>'.$l.'</option>'."\n";
									}
								?>
							</select>
							<?php
								}
							?>
						</div>

					</div>

					<input type="hidden" id="timezone" name="timezone" data-content="timezone" data-highlight="#timezone-region" value="<?php echo get_user()["timezone"]; ?>" required>

				</div>

				<?php } ?>

				<?php if(is_settings_password()) { ?>

				<?php
					// User has password
					if(get_user()["login"]["password"] !== NULL) {
				?>
				<div class="c9 phablet-c1">
					<?php
						if(!is_dashboard_user()) {
					?>
					<div class="input-label input-password">
						<label for="current-password"><?php _se('Current password'); ?></label>
						<input type="password" name="current-password" id="current-password" class="text-input" value="<?php echo get_safe_post()["current-password"]; ?>" placeholder="<?php _se('Enter your current password'); ?>" required>
						<span class="input-warning red-warning"><?php echo get_input_errors()["current-password"]; ?></span>
						<div class="input-below text-align-right"><a href="<?php echo G\get_base_url("account/password-forgot"); ?>"><?php _se('Forgot password?'); ?></a></div>
					</div>
					<?php
						}
					?>
					<div class="input-label input-password">
						<label for="new-password"><?php _se('New password'); ?></label>
						<input type="password" name="new-password" id="new-password" class="text-input" value="<?php echo get_safe_post()["new-password"]; ?>" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" placeholder="<?php _se('Enter your new password'); ?>" required>
						<div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
						<span class="input-warning red-warning" data-text="password-meter-message"><?php echo get_input_errors()["new-password"]; ?></span>
					</div>
					<div class="input-label input-password">
						<label for="new-password-confirm"><?php _se('Confirm new password'); ?></label>
						<input type="password" name="new-password-confirm" id="new-password-confirm" class="text-input" value="<?php echo get_safe_post()["new-password-confirm"]; ?>" placeholder="<?php _se('Re-enter your new password'); ?>" required>
						<span class="text-align-right input-warning input-below red-warning<?php echo get_input_errors()["new-password-confirm"] ? "" : " hidden-visibility"; ?>" data-text="<?php _se("Passwords don't match"); ?>"><?php if(get_input_errors()["new-password-confirm"]) { echo _s("Passwords don't match"); } ?></span>
					</div>
				</div>
				<?php
					} else { // User must create a password
				?>
				<p><?php if(!is_dashboard_user()) { _se('Add a password to be able to login using your username or email.'); } else { _se("This user doesn't have a password. Add one using this form."); } ?></p>
				<div class="c9 phablet-c1">
					<div class="input-label input-password">
						<label for="new-password"><?php _se('Password'); ?></label>
						<input type="password" name="new-password" id="new-password" class="text-input" value="<?php echo get_safe_post()["new-password"]; ?>" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" title="<?php _se('%d characters min', CHV\getSetting('user_password_min_length')); ?>" data-tipTip="right" placeholder="<?php _se('Enter your password'); ?>" required>
						<div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
						<span class="input-warning red-warning" data-text="password-meter-message"><?php echo get_input_errors()["new-password"]; ?></span>
					</div>
					<div class="input-label input-password">
						<label for="new-password-confirm"><?php _se('Confirm password'); ?></label>
						<input type="password" name="new-password-confirm" id="new-password-confirm" class="text-input" value="<?php echo get_safe_post()["new-password-confirm"]; ?>" maxlength="255" placeholder="<?php _se('Re-enter your password'); ?>" required>
						<span class="input-warning red-warning<?php echo get_input_errors()["new-password-confirm"] ? "" : " hidden-visibility"; ?>" data-text="<?php _se("Passwords don't match"); ?>"><?php _se("Passwords don't match"); ?></span>
					</div>
				</div>
				<?php
					}
				?>
				<?php } ?>

				<?php if(is_settings_profile()) { ?>
				<div class="c19 phablet-c1">
					<div class="input-upload user-settings-avatar">
						<div class="user-settings-avatar-container grid-columns margin-right-10 phablet-float-left">
							<?php if(get_user()["avatar"]["url"]) { ?>
							<img src="<?php echo get_user()["avatar"]["url"]; ?>" alt="" class="user-image">
							<?php } else { ?>
							<img src="" alt="" class="user-image<?php echo (!get_user()["avatar"]["url"] ? ' hidden' : ''); ?>">
							<?php } ?>
							<span class="user-image default-user-image<?php echo (get_user()["avatar"]["url"] ? ' hidden' : ''); ?>"><span class="icon icon-user"></span></span>
							<div class="user-image loading-placeholder hidden"><?php _se('loading'); ?></div>
						</div>
						<div class="actions">
							<div class="btn-container">
								<a class="btn default" data-trigger="user-avatar-upload"><?php _se('Upload new image'); ?></a>
								<div class="<?php echo (get_user()["avatar"] == NULL ? 'soft-hidden' : ''); ?>">
									<span class="btn-alt"><?php _se('or'); ?> <a data-action="delete-avatar"><?php _se('Delete existing image'); ?></a></span>
								</div>
							</div>
						</div>
						<input id="user-avatar-upload" data-content="user-avatar-upload-input" class="hidden-visibility" type="file" accept="image/*">
					</div>
				</div>

				<div class="input-label">
          <label><?php _se('Privacy'); ?></label>
					<?php echo CHV\Render\get_checkbox_html([
						'name'		=> 'is_private',
						'label'		=> _s('Make my profile and identity totally private'),
						'tooltip'	=> _s('Enable this if you want to act like an anonymous user'),
						'checked'	=> ((bool)(get_safe_post() ? get_safe_post()['is_private'] : get_user()['is_private']))
					]); ?>
				</div>

				<div class="c9 phablet-c1">
					<div class="input-label">
						<label for="name"><?php _se('Name'); ?></label>
						<input type="text" name="name" id="name" class="text-input" maxlength="60" value="<?php echo get_safe_post() ? get_safe_post()["name"] : get_safe_html_user()["name"]; ?>" placeholder="Your real name" required>
						<span class="input-warning red-warning"><?php echo get_input_errors()["name"]; ?></span>
						<?php if(!is_dashboard_user()) { ?><div class="input-below"><?php _se('This is your real name, not your username.'); ?></div><?php } ?>
					</div>
					<div class="input-label">
						<label for="website"><?php _se('Website'); ?></label>
						<input type="url" name="website" id="website" class="text-input" value="<?php echo get_safe_post() ? get_safe_post()["website"] : get_user()["website_safe_html"]; ?>" data-validate rel="tooltip" title="<?php _se('http://yourwebsite.com'); ?>" data-tipTip="right" placeholder="<?php _se('http://yourwebsite.com'); ?>">
						<span class="input-warning red-warning"><?php echo get_input_errors()["website"]; ?></span>
					</div>
					<div class="input-label">
						<label for="bio"><?php _se('Bio'); ?></label>
						<textarea name="bio" id="bio" class="resize-vertical" placeholder="<?php _se('Tell us a little bit about you'); ?>" maxlength="255"><?php echo get_safe_post() ? get_safe_post()["bio"] : get_safe_html_user()["bio"]; ?></textarea>
						<span class="input-warning red-warning"><?php echo get_input_errors()["bio"]; ?></span>
					</div>
				</div>
				<?php } ?>

				<?php
					if(is_settings_linked_accounts()) {

						if(is_dashboard_user()) {
				?>
				<p data-content="empty-message" <?php if(count(get_connections()) > 0) { ?>class="soft-hidden"<?php } ?>><?php _se('User has no connections.'); ?></p>
				<?php
						} else {
				?>
				<p><?php _se('Link your account to external services to be able to login and share content.'); ?></p>
				<?php
						}
				?>
				<?php
						foreach(get_connections() as $connection) {
							if(is_dashboard_user()) {
								$confirm_message = _s('Do you really want to disconnect %s from this account?', $connection['type_label']);
								$title = _s('This account is connected to %s', $connection['type_label']);
							} else {
								$confirm_message = _s('Do you really want to disconnect your %s account?', $connection['type_label']);
								if(CHV\Login::getSession()['type'] == $connection['type']) {
									$confirm_message .= ' ' . _s("You will be logged out and you won't be able to login to your account using this %s account.", $connection['type_label']);
								}
								$title = _s('Your account is connected to %s', $connection['type_label']);
							}
							$title .= ' (<a href="'.$connection['resource_url'].'" target="_blank">'.$connection['resource_name'].'</a>)';
				?>
				<div class="account-link account-linked input-label overflow-auto" data-connection="<?php echo $connection['type']; ?>">
					<h3><span class="btn-icon icon-<?php echo $connection['type']; ?>"></span> <?php echo $connection['type_label'];?> <a class="font-size-small margin-left-5" data-action="disconnect" data-connection="<?php echo $connection['type']; ?>" data-confirm-message="<?php echo $confirm_message; ?>"><?php _se('disconnect'); ?></a></h3>
					<div class="account-link-status"><?php echo $title; ?></div>
				</div>
				<?php
						}
				?>

				<?php
						if(!is_dashboard_user()) {
							foreach(get_available_connections() as $connection => $label) {
				?>
				<div class="account-link input-label<?php if(get_connections()[$connection]) echo ' soft-hidden'; ?>" data-connect="<?php echo $connection; ?>">
					<h3><?php echo $label; ?></h3>
					<a class="link-service link-<?php echo $connection; ?>" href="<?php echo G\get_base_url('connect/'.$connection.'/?return='.urlencode('settings/linked-accounts')); ?>"><span class="btn-icon icon-<?php echo $connection; ?>"></span><span class="sign-text"><?php echo _se('Connect %s', $label); ?></span></a>
				</div>
				<?php
							}
						}
				?>

				<?php } ?>

				<?php if(is_settings_homepage()) { ?>
				<div class="input-label">
					<label for="homepage_title_html"><?php _se('Title'); ?></label>
					<div class="c12 phablet-c1"><textarea type="text" name="homepage_title_html" id="homepage_title_html" class="text-input r2 resize-vertical" placeholder="<?php echo get_user()['name']; ?>"><?php echo CHV\Settings::get('homepage_title_html'); ?></textarea></div>
					<span class="input-warning red-warning"><?php echo get_input_errors()["homepage_title_html"]; ?></span>
				</div>

				<div class="input-label">
					<label for="homepage_paragraph_html"><?php _se('Paragraph'); ?></label>
					<div class="c12 phablet-c1"><textarea type="text" name="homepage_paragraph_html" id="homepage_paragraph_html" class="text-input r2 resize-vertical" placeholder="<?php _se('Feel free to browse and discover all my shared images and albums.'); ?>"><?php echo CHV\Settings::get('homepage_paragraph_html'); ?></textarea></div>
					<span class="input-warning red-warning"><?php echo get_input_errors()["homepage_paragraph_html"]; ?></span>
				</div>

				<div class="input-label">
					<label for="homepage_cta_html"><?php _se('Button'); ?></label>
					<div class="c12 phablet-c1"><textarea type="text" name="homepage_cta_html" id="homepage_cta_html" class="text-input r2 resize-vertical" placeholder="<?php _se('View all my images'); ?>"><?php echo CHV\Settings::get('homepage_cta_html'); ?></textarea></div>
					<span class="input-warning red-warning"><?php echo get_input_errors()["homepage_cta_html"]; ?></span>
				</div>
				<?php } ?>

				<hr class="line-separator"></hr>

				<?php if(is_captcha_needed()) { ?>
				<div class="input-label">
					<label for="recaptcha_response_field">reCAPTCHA</label>
					<?php echo get_recaptcha_html(); ?>
				</div>
				<?php } ?>

				<?php if(!is_settings_linked_accounts()) { ?>
				<div class="btn-container">
					<button class="btn btn-input default" type="submit"><?php _se('Save changes'); ?></button> <span class="btn-alt"><?php _se('or'); ?> <a href="<?php echo get_user()["url"]; ?>"><?php _se('cancel'); ?></a></span>
				</div>
				<?php } ?>

			</form>
		</div>

	</div>

</div>

<?php if(get_post() and (is_changed() or is_error())) { ?>
<script>
$(function() {
	PF.fn.growl.expirable("<?php echo is_changed() ? (get_changed_message() ? get_changed_message() : _s('Changes have been saved.')) : _s('Check the errors to proceed.'); ?>");
});
</script>
<?php } ?>

<?php G\Render\include_theme_footer(); ?>