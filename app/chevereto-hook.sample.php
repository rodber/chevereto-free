<?php

/* --------------------------------------------------------------------

  G\ library
  http://gbackslash.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.
  
  Licensed under the MIT license
  http://opensource.org/licenses/MIT
  
  --------------------------------------------------------------------- */
  
  # Use this file to include / hook anything you want
  # Any code in this file will be added just before the G\Handler
  
  # NOTE: To use it in production you will need to rename this file to chevereto-hook.php

namespace CHV;
use G, Exception;

if(!defined('access') or !access) die('This file cannot be directly accessed.');