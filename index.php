<?php
/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

define('access', 'index');

/*** Load the G app ***/
if (!include_once('app/loader.php')) {
    die("Can't find app/loader.php");
}
