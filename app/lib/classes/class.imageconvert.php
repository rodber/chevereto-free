<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

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
                $temp_image = G\imagecreatefrombmp($source);
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
