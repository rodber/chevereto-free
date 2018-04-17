<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php
$is_error = FALSE;
$input_errors = [];

$allowed_subjects = [
	'general' => _s('General questions/comments'),
	'dmca' => _s('DMCA complaint'),
];

if($_POST) {

	if(!G\Handler::checkAuthToken($_REQUEST['auth_token'])) {
		die(_s("Request denied"));
	}

	// Validate post data
	if(strlen($_POST['name']) == 0) {
		$input_errors['name'] = _s('Invalid name');
	}
	if(strlen($_POST['message']) == 0) {
		$input_errors['message'] = _s('Invalid message');
	}
	if(!array_key_exists($_POST['subject'], $allowed_subjects)) {
		$input_errors['subject'] = _s('Invalid subject');
	}
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$input_errors['email'] = _s('Invalid email');
	}
	if(CHV\getSettings()['recaptcha']) {
		$captcha = CHV\recaptcha_check();
		if(!$captcha->is_valid) {
			$input_errors['recaptcha'] = _s('Invalid reCAPTCHA');
		}
	}

	if(count($input_errors) > 0) {
		$is_error = TRUE;
	} else {
		$email		= trim($_POST['email']);
		$subject	= CHV\getSettings()['website_name'] . ' contact form';
		$name		= $_POST['name'];
		$send_mail = [
			'to' 		=> CHV\getSettings()['email_incoming_email'],
			'from' 		=> [CHV\getSettings()['email_from_email'], $name . ' (' . CHV\getSettings()['website_name'] . ' contact form)'],
			'reply-to'	=> [$email]
		];
		$body_arr	= [ // Mail body array (easier to edit)
			'Name'		=> $name,
			'E-mail'	=> $email,
			'User'		=> (CHV\Login::isLoggedUser() ? CHV\Login::getUser()['url'] : 'not user'),
			'Subject'	=> $_POST['subject'] . "\n",
			'Message'	=> strip_tags($_POST['message']) . "\n",
			'IP'		=> G\get_client_ip(),
			'Browser'	=> getenv("HTTP_USER_AGENT"),
			'URL'		=> G\get_base_url() . "\n"
		];
		// Format body message
		$body = '';
		foreach($body_arr as $k => $v) {
			$body .= $k . ': ' . $v . "\n";
		}
		// Mail send handle
		try {
			CHV\send_mail($send_mail, $subject, $body);
			$is_sent = TRUE;
		} catch(Exception $e) {
			$is_error = TRUE;
			$error = _s('Mail error') . ': ' . $e->getMessage();
		}
	}
}
?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	<div class="c24 center-box">
		<div class="header default-margin-bottom">
			<h1><?php echo $is_sent ? '<span class="icon-checkmark-circle color-green margin-right-5"></span>' . _s('Message sent') : _s('Contact'); ?></h1>
		</div>
		<form method="post" class="form-content">

			<?php echo G\Render\get_input_auth_token(); ?>

			<p><?php echo $is_sent ? _s('Message sent. We will get in contact soon.') : _s('If you want to send a message fill the form below.'); ?></p>
			<div class="input-label c9">
				<label for="name"><?php _se('Name'); ?></label>
				<input type="text" name="name" id="name" class="text-input" placeholder="<?php _se('Your name'); ?>" value="<?php if($is_error) { echo get_safe_post()['name']; } ?>" required>
				<div class="input-warning red-warning"><?php echo $input_errors['name']; ?></div>
			</div>
			<div class="input-label c9">
				<label for="email"><?php _se('Email address'); ?></label>
				<input type="email" name="email" id="email" class="text-input" placeholder="<?php _se('Your email address'); ?>" value="<?php if($is_error) { echo get_safe_post()['email']; } ?>" required>
				<div class="input-warning red-warning"><?php echo $input_errors['name']; ?></div>
			</div>
			<div class="input-label c9">
				<label for="subject"><?php _se('Subject'); ?></label>
				<select type="text" name="subject" id="subject" class="text-input">
					<?php
						$ask_for = get_safe_post() ? get_safe_post()['subject'] : '';
						foreach($allowed_subjects as $k => $v) {
					?>
					<option value="<?php echo $k; ?>"<?php if($ask_for == $k) { ?> selected<?php } ?>><?php echo $v; ?></option>
					<?php
						}
					?>
				</select>
				<div class="input-warning red-warning"><?php echo $input_errors['subject']; ?></div>
			</div>
			<div class="input-label c12">
				<label for="message"><?php _se('Message'); ?></label>
				<textarea name="message" id="message" class="text-input r3" required><?php if($is_error) { echo get_safe_post()['message']; } ?></textarea>
				<div class="input-warning red-warning"><?php echo $input_errors['message']; ?></div>
			</div>
			<?php if(CHV\getSettings()['recaptcha']) { ?>
			<div class="input-label">
				<label for="recaptcha_response_field">reCAPTCHA</label>
				<?php echo CHV\Render\get_recaptcha_html(); ?>
				<div class="input-below red-warning"><?php echo $input_errors['recaptcha']; ?></div>
			</div>
			<?php } ?>
			<div class="btn-container">
				<button class="btn btn-input default" type="submit"><?php _se('Send'); ?></button> <span class="btn-alt"><?php _se('or'); ?> <a href="<?php echo G\get_base_url(); ?>"><?php _se('cancel'); ?></a></span>
			</div>
		</form>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>

<?php if($_POST and $is_error) { ?>
<script>PF.fn.growl.call("<?php echo $error ? $error : _s('Check the errors in the form to continue.'); ?>"); </script>
<?php } ?>