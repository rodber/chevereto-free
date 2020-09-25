<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

$route = function ($handler) {
    try {
        if ($_POST and !$handler::checkAuthToken($_REQUEST['auth_token'])) {
            G\set_status_header(403);
            $handler->template = 'request-denied';
            return;
        }
        
        if ($handler->isRequestLevel(2)) {
            return $handler->issue404();
        } // Allow only 1 level
        
        $logged_user = CHV\Login::getUser();
        
        // User status override redirect
        CHV\User::statusRedirect($logged_user['status']);
        
        if ($logged_user) {
            G\redirect(CHV\User::getUrl($logged_user));
        }

        // Request log
        $request_log_insert = ['type' => 'login', 'user_id' => null];
        $failed_access_requests = $handler::getVar('failed_access_requests');

        // Safe print $_POST
        $SAFE_POST = $handler::getVar('safe_post');

        // conds
        $is_error = false;
        $captcha_needed = $handler::getCond('captcha_needed');

        // vars
        $error_message = null;

        if ($captcha_needed && $_POST) {
            $captcha = CHV\recaptcha_check();
            if (!$captcha->is_valid) {
                $is_error = true;
                $error_message = _s('%s says you are a robot', 'reCAPTCHA');
            }
        }

        if ($_POST && !$is_error) {
            $login_by = filter_var($_POST['login-subject'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            if (trim($_POST['login-subject']) == '' or trim($_POST['password']) == '') {
                $is_error = true;
            }

            if (!$is_error) {

                // Get user candidate
                $user_db = CHV\User::getSingle(trim($_POST['login-subject']), $login_by, false);

                if ($user_db) {
                    $request_log_insert['user_id'] = $user_db['user_id'];
                    switch ($user_db['user_status']) {
                        case 'awaiting-confirmation':
                            CHV\Login::setSingup([
                                'status'    => 'awaiting-confirmation',
                                'email'        => $user_db['user_email']
                            ]);
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

                if ($is_login) {
                    $request_log_insert['result'] = 'success';
                    CHV\Requestlog::insert($request_log_insert);
                    $logged_user = CHV\Login::login($user_db['user_id']);
                    CHV\Login::insert(['type' => 'cookie', 'user_id' => $user_db['user_id']]);
                    $redirect_to = CHV\User::getUrl(CHV\Login::getUser());
                    if ($_SESSION['last_url']) {
                        $redirect_to = $_SESSION['last_url'];
                    }
                    if ($user_db['user_status'] == 'awaiting-email') {
                        $redirect_to = 'account/email-needed';
                    }
                    G\redirect($redirect_to);
                } else {
                    $is_error = true;
                }
            }

            if ($is_error) {
                $request_log_insert['result'] = 'fail';
                CHV\Requestlog::insert($request_log_insert);
                $error_message = _s('Wrong Username/Email password combination');
                if (CHV\getSettings()['recaptcha'] && CHV\must_use_recaptcha($failed_access_requests['day'] + 1)) {
                    $captcha_needed = true;
                }
            }
        }

        $handler::setCond('error', $is_error);

        if ($captcha_needed) {
            $handler::setCond('captcha_show', true);
            $handler::setVar(...CHV\Render\get_recaptcha_component());
        }
        $handler::setCond('captcha_needed', $captcha_needed);

        $handler::setVar('pre_doctitle', _s('Sign in'));
        $handler::setVar('error', $error_message);
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
