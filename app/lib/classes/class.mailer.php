<?php

use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer
{
    public $DbgOut = '';

    public $XMailer = ' ';

    protected function edebug($str)
    {
        $this->DbgOut .= $str;
    }
}
