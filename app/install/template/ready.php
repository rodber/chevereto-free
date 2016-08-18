<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<h1>Ready to install</h1>
<p>The system is connected to your database and the <code>app/settings.php</code> file contains this connection info. Don't change the contents of this file unless you also change your database connection info.</p>
<p>On submit this process will install the Chevereto database tables and it will set some default settings that you can change later.</p>
<p>To proceed with the installation fill this form with the details of the initial admin account and the default email settings that you want to use, don't worry this account and settings can be edited later.</p>
<?php if($error) { ?>
<p class="highlight padding-10"><?php echo $error_message; ?></p>
<?php } ?>
<form method="post">
	<div class="c9">
        <div class="input-label">
            <label for="username">Admin username</label>
            <input type="text" name="username" id="username" class="text-input" value="<?php echo isset($_POST['username']) ? $_POST['username'] : NULL; ?>" placeholder="Admin username" rel="tooltip" data-tipTip="right" pattern="<?php echo CHV\getSetting('username_pattern'); ?>" rel="tooltip" title='<?php echo strtr('%i to %f characters<br>Letters, numbers and "_"', ['%i' => CHV\getSetting('username_min_length'), '%f' => CHV\getSetting('username_max_length')]); ?>' maxlength="<?php echo CHV\getSetting('username_max_length'); ?>" required>
            <span class="input-warning red-warning"><?php echo $input_errors['username']; ?></span>
        </div>
        <div class="input-label">
            <label for="email">Admin email</label>
            <input type="email" name="email" id="email" class="text-input" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" placeholder="Admin email" title="Valid email address for your admin account" rel="tooltip" data-tipTip="right" required>
            <span class="input-warning red-warning"><?php echo $input_errors['email']; ?></span>
        </div>
        <div class="input-label input-password">
            <label for="password">Admin password</label>
            <input type="password" name="password" id="password" class="text-input" value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>" placeholder="Admin password" title="Password to login" pattern="<?php echo CHV\getSetting('user_password_pattern'); ?>" rel="tooltip" data-tipTip="right" required>
            <div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
            <span class="input-warning red-warning" data-text="password-meter-message"><?php echo $input_errors['password']; ?></span>
        </div>
    </div>
    <hr class="line-separator"></hr>
    <div class="c9">
        <div class="input-label">
            <label for="email_from_email"><?php _se('From email address'); ?></label>
            <input type="email" name="email_from_email" id="email_from_email" class="text-input" value="<?php echo isset($_POST['email_from_email']) ? $_POST['email_from_email'] : ''; ?>" placeholder="no-reply@example.com" title="<?php _se('Sender email for emails sent to users.'); ?>" rel="tooltip" data-tipTip="right" required>
            <span class="input-warning red-warning"><?php echo $input_errors['email_from_email']; ?></span>
        </div>
        <div class="input-label">
            <label for="email_incoming_email"><?php _se('Incoming email address'); ?></label>
            <input type="email" name="email_incoming_email" id="email_incoming_email" class="text-input" value="<?php echo isset($_POST['email_incoming_email']) ? $_POST['email_incoming_email'] : NULL; ?>" placeholder="inbox@example.com" title="<?php _se('Recipient for contact form and system alerts.'); ?>" rel="tooltip" data-tipTip="right" required>
            <span class="input-warning red-warning"><?php echo $input_errors['email_incoming_email']; ?></span>
        </div>
    </div>
	<hr class="line-separator"></hr>
    <div class="c9">
		<div class="input-label">
			<label for="website_mode"><?php _se('Website mode'); ?></label>
			<div class="c5 phablet-c1"><select type="text" name="website_mode" id="website_mode" class="text-input" data-combo="website-mode-combo" title="<?php _se('You can switch the website mode anytime.'); ?>" rel="tooltip" data-tipTip="right">
				<?php
					echo CHV\Render\get_select_options_html(['community' => _s('Community'), 'personal' => _s('Personal')], isset($_POST['website_mode']) ? $_POST['website_mode'] : NULL);
				?>
			</select></div>
			<div class="input-below input-warning red-warning"><?php echo $input_errors['website_mode']; ?></div>
		</div>
	</div>
	<?php
		if($is_2X) {
	?>
    <hr class="line-separator"></hr>
    <div class="c9">
        <div class="input-label">
            <label for="crypt_salt">__CHV_CRYPT_SALT__</label>
            <input type="crypt_salt" name="crypt_salt" id="crypt_salt" class="text-input" value="<?php echo isset($_POST['crypt_salt']) ? $_POST['crypt_salt'] : ''; ?>" placeholder="Example: changeme" title="As defined in includes/definitions.php" rel="tooltip" data-tipTip="right" required>
            <span class="input-below highlight">Value from define("__CHV_CRYPT_SALT__", "changeme");</span>
            <span class="input-warning red-warning"><?php echo $input_errors['crypt_salt']; ?></span>
        </div>
    </div>
	<?php
		}
	?>
    <span class="line-separator"></span>
	<div class="btn-container margin-bottom-0">
		<button class="btn btn-input default" type="submit">Install Chevereto</button>
	</div>
</form>