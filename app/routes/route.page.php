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
	
	$request_url_key = implode('/', $handler->request);
	
	// Get this page
	$page = CHV\Page::getSingle($request_url_key);
	
	// Exists or is active or is type default?
	if(!$page or !$page['is_active'] or $page['type'] !== 'internal') {
		return $handler->issue404();
	}
	
	// No file path set
	if(!$page['file_path_absolute']) {
		return $handler->issue404();
	}
	
	// File path doesn't exists
	if(!file_exists($page['file_path_absolute'])) {
		return $handler->issue404();
	}
		
	$pathinfo = pathinfo($page['file_path_absolute']);
	$page_extension = G\get_file_extension($page['file_path_absolute']);
	
	// Inject theme based path
	$handler->path_theme = G\add_ending_slash($pathinfo['dirname']);
	$handler->template = $pathinfo['filename'] . '.' . $page_extension;
	
	// Add page meta data
	$page_metas = [
		'pre_doctitle'		=> $page['title'],
		'meta_description'	=> htmlspecialchars($page['description']),
		'meta_keywords'		=> htmlspecialchars($page['keywords'])
	];
	foreach($page_metas as $k => $v) {
		if($v == NULL) continue;
		$handler->setVar($k, $v);
	}
	
	if($_POST and $_POST['chv-contact-form'] == 1) {
		
		$allowed_subjects = [
			'general' => _s('General questions/comments'),
			'dmca' => _s('DMCA complaint'),
		];
		
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
			$error = _s("Can't submit the form: %s", implode(', ', $input_errors));
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
				'Message'	=> $_POST['message'] . "\n",
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
				$success = _s('Message sent. We will get in contact soon.');
			} catch(Exception $e) {
				$is_error = TRUE;
				$error = _s('Mail error') . ': ' . $e->getMessage();
			}
		}
		
		if($page_extension == 'html') {
			$growl = ($is_error and $error) ? $error : (($is_sent and $success) ? $success : NULL);
			if($growl) {
				$handler->hookTemplate(['where' => 'after', 'code' => 
					'<script>$(document).ready(function() {
						PF.fn.growl.call("'.$growl.'")
					});</script>']
				);
			}
		}
	}
};