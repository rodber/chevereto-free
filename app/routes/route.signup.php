<?php

/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
			<inbox@rodolfoberrios.com>

  Copyright (C) Rodolfo Berrios A. All rights reserved.

  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */

$route = function($handler) {
	try {

		if(!CHV\getSetting('enable_signups')) {
			$handler->issue404();
		}

		if($_POST and !$handler::checkAuthToken($_REQUEST['auth_token'])) {
			G\set_status_header(403);
			$handler->template = 'request-denied';
			return;
		}

		if($handler->isRequestLevel(2)) return $handler->issue404(); // Allow only 1 level

		if($_SESSION['signup']) {
			$SAFE_POST['email'] = $_SESSION['signup']['email'];
			G\redirect('account/awaiting-confirmation');
		}

		$logged_user = CHV\Login::getUser();

		// User status override redirect
		CHV\User::statusRedirect($logged_user['status']);

		if($logged_user) {
			G\redirect(CHV\User::getUrl($logged_user));
		}

		// Failed access requests filter
		$failed_access_requests = $handler::getVar('failed_access_requests');
		if(CHV\is_max_invalid_request($failed_access_requests['day'])) {
			G\set_status_header(403);
			$handler->template = 'request-denied';
			return;
		}

		// Safe print $_POST
		$SAFE_POST = $handler::getVar('safe_post');

		// Conds
		$is_error = FALSE;

		// Vars
		$input_errors = [];
		$error_message = NULL;

		// reCaptcha thing
		$captcha_needed = $handler::getCond('captcha_needed');

		if($captcha_needed) {
			if($_POST) {
				$captcha = CHV\recaptcha_check();
				if(!$captcha->is_valid) {
					$is_error = TRUE;
					$error_message = _s("The reCAPTCHA wasn't entered correctly");
				}
			}
		}

		$handler::setCond('show_resend_activation', false);

		if($_POST && !$is_error && !$_SESSION['signup']) {

			$__post = [];
			$__safe_post = [];
			foreach(['username', 'email'] as $v) {
				if(isset($_POST[$v])) {
					$_POST[$v] = $v == 'email' ? trim($_POST[$v]) : strtolower(trim($_POST[$v]));
					$__post[$v] = $_POST[$v];
					$__safe_post[$v] = G\safe_html($_POST[$v]);
				}
			}

			$handler::updateVar('post', $__post);
			$handler::updateVar('safe_post', $__safe_post);

			// Input validations
			if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$input_errors['email'] = _s('Invalid email');
			}
			if(!CHV\User::isValidUsername($_POST['username'])) {
				$input_errors['username'] = _s('Invalid username');
			}
			if(!preg_match('/'.CHV\getSetting('user_password_pattern').'/', $_POST['password'])) {
				$input_errors['password'] = _s('Invalid password');
			}

			if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$input_errors['email'] = _s('Invalid email');
			}

			if($_POST['signup-accept-terms-policies'] != 1) {
				$input_errors['signup-accept-terms-policies'] = _s('You must agree to the terms and privacy policy');
			}

			if(CHV\getSetting('user_minimum_age') > 0 && !isset($_POST['minimum-age-signup'])) {
				$input_errors['minimum-age-signup'] = _s('You must be at least %s years old to use this website.', CHV\getSetting('user_minimum_age'));
			}

			if(count($input_errors) > 0) {
				$is_error = TRUE;
			}

			if(!$is_error) {
				$user_db = CHV\DB::get('users', array('username' => $_POST['username'], 'email' => $_POST['email']), 'OR', NULL);

				if($user_db) {
					$is_error = TRUE;
					$show_resend_activation = FALSE;
					foreach($user_db as $row) {
						// Invalid user, check the time
						if(!in_array($row['user_status'], ['valid', 'banned'])) { // Don't touch the valid and banned users
							$must_delete_old_user = false;
							$confirmation_db = CHV\Confirmation::get(['user_id' => $row['user_id']]);
							if($confirmation_db) {
								// 24x2 = 48 tic tac tic tac
								if(G\datetime_diff($confirmation_db['confirmation_date_gmt'], NULL, 'h') > 48) {
									CHV\Confirmation::delete(['id' => $confirmation_db['confirmation_id']]);
									$must_delete_old_user = true;
								}
							} else {
								$must_delete_old_user = true;
							}
							// Delete any old un-validated / un-banned user and allow use its things
							if($must_delete_old_user) {
								CHV\DB::delete('users', ['id' => $row['user_id']]);
								continue;
							}
						}
						if(G\timing_safe_compare($row['user_username'], $_POST['username'])) {
							$input_errors['username'] = 'Username already being used';

						}
						if(G\timing_safe_compare($row['user_email'], $_POST['email'])) {
							$input_errors['email'] = _s('Email already being used');

						}

						if(!isset($show_resend_activation) or !$show_resend_activation) {
							$show_resend_activation = $row['user_status'] == 'awaiting-confirmation';
						}

					}

					$handler::setCond('show_resend_activation' , $show_resend_activation);

				} else {

					// Populate the user array
					$user_array = [
						'username'	=> $_POST['username'],
						'email'		=> $_POST['email'],
						'timezone'	=> CHV\getSetting('default_timezone'),
						'language'	=> CHV\L10n::getLocale(),
						'status'	=> CHV\getSetting('require_user_email_confirmation') ? 'awaiting-confirmation' : 'valid'
					];

					// Ready to go, insert the new user
					try {
						$inserted_user = CHV\User::insert($user_array);
					} catch(Exception $e) {
						if($e->getCode() == 666) { // Flood detected!
							G\set_status_header(403);
							$handler->template = 'request-denied';
							return;
						} else {
							throw new Exception($e);
						}
					}


					if($inserted_user) {
						$insert_password = CHV\Login::addPassword($inserted_user, $_POST['password']);
					}

					if(!$inserted_user || !$insert_password) {
						throw new Exception("Can't insert user to the DB", 400);
					} else {
						if(CHV\getSetting('require_user_email_confirmation')) {
							$hashed_token = CHV\generate_hashed_token($inserted_user);

							$insert_confirmation = CHV\Confirmation::insert(array(
								'user_id'	=> $inserted_user,
								'type'		=> 'account-activate',
								'token_hash'=> $hashed_token['hash'],
								'status'	=> 'active'
							));

							$activation_link = G\get_base_url('account/activate/?token='.$hashed_token['public_token_format']);

							// Build the mail global
							global $theme_mail;
							$theme_mail = [
								'user' => $user_array,
								'link' => $activation_link
							];

							$mail['subject'] = _s('Confirmation required at %s', CHV\getSettings()['website_name']);
							$mail['message'] = CHV\Render\get_email_body_str('mails/account-confirm');

							try {
								if(CHV\send_mail($_POST['email'], $mail['subject'], $mail['message'])) {
									$is_process_done = true;
								}
							} catch(Exception $e) {
								echo($e->getMessage());
							}
						} else {
							$user = CHV\User::getSingle($inserted_user, 'id');
							// log the activated user if needed
							$logged_user = CHV\Login::login($user['id'], $_SESSION['login'] ? $_SESSION['login']['type'] : 'session');

							try {
								// Welcome email
								global $theme_mail;
								$theme_mail = [
									'user' => $logged_user,
									'link' => $logged_user['url']
								];

								$mail['subject'] = _s('Welcome to %s', CHV\getSetting('website_name'));
								$mail['message'] = CHV\Render\get_email_body_str('mails/account-welcome');
								CHV\send_mail($logged_user['email'], $mail['subject'], $mail['message']);
							} catch(Exception $e) {} // Silence

							G\redirect($user['url']);
						}
					}

					$_SESSION['signup'] = [
						'status'	=> 'awaiting-confirmation',
						'email'		=> $SAFE_POST['email']
					];
					G\redirect('account/awaiting-confirmation');

				}
			}

			if($is_error) {
				CHV\Requestlog::insert(['type' => 'signup', 'result' => 'fail']);
				$error_message = _s('Check the errors in the form to continue.');
				if(CHV\getSettings()['recaptcha'] && CHV\must_use_recaptcha($failed_access_requests['day'] + 1)) {
					$captcha_needed = TRUE;
				}
			}

		}

		$handler::setCond('error', $is_error);
		$handler::setCond('captcha_needed', $captcha_needed);

		if($captcha_needed && !$handler::getVar('recaptcha_html')) {
			$handler::setVar('recaptcha_html', CHV\Render\get_recaptcha_html());
		}

		$handler::setVar('pre_doctitle', _s('Create account'));
		$handler::setVar('error', $error_message);
		$handler::setVar('input_errors', $input_errors);
		$handler::setVar('signup_email', $SAFE_POST['email']);

	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};