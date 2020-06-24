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

namespace CHV;

use Exception;
use G;
use TijsVerkoyen\Akismet\Akismet as BaseAkismet;

class Akismet extends BaseAkismet
{
    final public function __construct()
    {
        parent::__construct(getSetting('akismet_api_key'), G\get_base_url());
    }

    /**
     * @param array $source_db
     */
    final public static function checkImage($title = null, $description = null, $source_db = null)
    {
        $akismet = new static();
        $userName = $source_db['user_name'] ?: $source_db['user_username'];
        if (isset($description)) {
            $isDescriptionSpam = $akismet->isSpam(
                $description,
                $userName,
                $source_db['user_email']
            );
            if ($isDescriptionSpam) {
                throw new Exception(_s('Spam detected'), 910);
            }
        }

        if (isset($title)) {
            $isTitleSpam = $akismet->isSpam(
                $title,
                $userName,
                $source_db['user_email']
            );
            if ($isTitleSpam) {
                throw new Exception(_s('Spam detected'), 900);
            }
        }
    }

    /**
     * @param array $source_db
     */
    final public static function checkAlbum($name, $description = null, $source_db = null)
    {
        $akismet = new static();
        $userName = $source_db['user_name'] ?: $source_db['user_username'];
        $isNameSpam = $akismet->isSpam(
            $name,
            $userName,
            $source_db['user_email']
        );
        if ($isNameSpam) {
            throw new Exception(_s('Spam detected'), 900);
        }
        if (isset($description)) {
            $isDescriptionSpam = $akismet->isSpam(
                $description,
                $userName,
                $source_db['user_email']
            );
            if ($isDescriptionSpam) {
                throw new Exception(_s('Spam detected'), 910);
            }
        }
    }
}
