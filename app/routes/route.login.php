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
		
		if($_POST and !$handler::checkAuthToken($_REQUEST['auth_token'])) {
			G\set_status_header(403);
			$handler->template = 'request-denied';
			return;
		}
		
		if($handler->isRequestLevel(2)) return $handler->issue404(); // Allow only 1 level
		
		$logged_user = CHV\Login::getUser();
		
		// User status override redirect
		CHV\User::statusRedirect($logged_user['status']);
		
		if($logged_user) {
			G\redirect(CHV\User::getUrl($logged_user));
		}

		// Request log
		$request_log_insert = ['type' => 'login'];
		$failed_access_requests = $handler::getVar('failed_access_requests');
		
		if(CHV\is_max_invalid_request($failed_access_requests['day'])) {
			G\set_status_header(403);
			$handler->template = 'request-denied';
			return;
		}
		
		// Safe print $_POST
		$SAFE_POST = $handler::getVar('safe_post');	
		
		// conds
		$is_error = false;
		$captcha_needed = $handler::getCond('captcha_needed');
		
		// vars
		$error_message = NULL;
		
		if($captcha_needed && $_POST) {
			$captcha = CHV\recaptcha_check();
			if(!$captcha->is_valid) {
				$is_error = true;
				$error_message = _s("The reCAPTCHA wasn't entered correctly");
			}
		}
		
		if($_POST && !$is_error) {
			
			$login_by = filter_var($_POST['login-subject'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
			
			if(trim($_POST['login-subject']) == '' or trim($_POST['password']) == '') {
				$is_error = true;
			}
			
			if(!$is_error) {
			
				// Get user candidate
				$user_db = CHV\User::getSingle(trim($_POST['login-subject']), $login_by, false);
				
				if($user_db) {
					switch($user_db['user_status']) {
						case 'awaiting-confirmation':
							$_SESSION['signup'] = array(
								'status'	=> 'awaiting-confirmation',
								'email'		=> $user_db['user_email']
							);
							G\redirect('account/awaiting-confirmation');
						break;
						case 'banned':
							G\set_status_header(403);
							$handler->template = 'request-denied';
							return;
						break;
					}
							
					$is_login = CHV\Login::checkPassword($user_db['user_id'], $_POST['password']);

				}
				
				if($is_login) {
					$request_log_insert['result'] = 'success';
					CHV\Requestlog::insert($request_log_insert);
					$logged_user = CHV\Login::login($user_db['user_id'], 'password', isset($_POST['keep-login']));
					
					$redirect_to = CHV\User::getUrl(CHV\Login::getUser());
					if($_SESSION['last_url']) {
						$redirect_to = $_SESSION['last_url'];
					}
					
					G\redirect($redirect_to);
				} else {
					$is_error = true;
					$request_log_insert['user_id'] = $user_db ? $user_db['user_id'] : NULL;
				}
				
			}
			
			if($is_error) {
				$request_log_insert['result'] = $is_error ? 'fail' : 'success';
				CHV\Requestlog::insert($request_log_insert);
				$error_message = _s('Wrong Username/Email password combination');
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
		
		$handler::setVar('pre_doctitle', _s('Sign in'));
		$handler::setVar('error', $error_message);
		
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};