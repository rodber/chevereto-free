<?php
set_error_handler(function () {
  echo file_get_contents(dirname(__DIR__).'/INSTALL');
  exit(1);
}, E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';

restore_error_handler();
