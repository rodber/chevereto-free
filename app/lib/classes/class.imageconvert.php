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

use G;
use Exception;

/**
 * This could be used to extend the image types allowed... Maybe .tiff support and so on.
 */
class ImageConvert
{
    public function __construct($source, $to, $destination, $quality=90)
    {
        $source_info = G\get_image_fileinfo($source);
        switch ($source_info['extension']) {
            case 'bmp':
                $temp_image = function_exists('imagecreatefrombmp') ? imagecreatefrombmp($source) : G\imagecreatefrombmp($source);
            break;
            case 'jpg':
                $temp_image = imagecreatefromjpeg($source);
            break;
            case 'gif':
                $temp_image = imagecreatefromgif($source);
            break;
            case 'png':
                $temp_image = imagecreatefrompng($source);
            break;
            default:
                return $source;
            break;
        }

        if (!$temp_image) {
            return $source;
        }

        unlink($source);

        switch ($to) {
            case 'jpg':
                imagejpeg($temp_image, $destination, $quality);
            break;
            case 'gif':
                imagegif($temp_image, $destination);
            break;
            case 'png':
                imagepng($temp_image, $destination);
            break;
            default:
                return $source;
            break;
        }

        $this->out = $destination;
    }
}
