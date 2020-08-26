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

$route = function ($handler) {
  try {
    if (!CHV\Login::isAdmin()) {
      $this->template = 'request-denied';
      return;
    }
    // Allow 3 levels only -> /importer-jobs/X/process
    if ($handler->isRequestLevel(4)) {
      return $handler->issue404();
    }
    if (is_null($handler->request[0]) || is_null($handler->request[1])) {
      return $handler->issue404();
    }
    $filepath = G_ROOT_PATH . sprintf('app/importer/jobs/%1$s/%2$s.txt', $handler->request[0], $handler->request[1]);
    if (!file_exists($filepath)) {
      return $handler->issue404();
    }
    if (!headers_sent()) {
      header('Content-Type: text/plain');
    }
    readfile($filepath);
    exit;
  } catch (Exception $e) {
    G\exception_to_error($e);
  }
};
