<?php

// Hail PHPMailer
require_once CHV_APP_PATH_LIB_VENDOR . 'phpmailer/class.phpmailer.php';
require_once CHV_APP_PATH_LIB_VENDOR . 'phpmailer/class.smtp.php';

class Mailer extends PHPMailer {
	public $DbgOut = '';
	protected function edebug($str) {
		$this->DbgOut .= $str;
	}
}