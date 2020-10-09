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

class ModerateContent
{
    private $imageFilename;
    private $imageInfo;
    private $moderation;
    private $error_message = '';
    private $error_code = 0;
    private $imageOptimized;

    public function __construct($imageFilename, $info=[])
    {
        $this->imageFilename = $imageFilename;
        $this->imageInfo = $info;
        if ($this->imageInfo === []) {
            $this->imageInfo = G\get_image_fileinfo($this->imageFilename);
        }
        $this->optimizeImage();
        $url = "http://api.moderatecontent.com/moderate/?key=".Settings::get('moderatecontent_key');
        $this->error_message = '';
        $this->error_code = 0;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file'=> new \CURLFile($this->imageOptimized)),
        ));
        $curl_response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        if ($curl_response === false) {
            $this->error_message = $error_msg;
            $this->error_code = 1402;
        } else {
            $json = json_decode($curl_response);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->error_message = 'Malformed content moderation response';
                $this->error_code = 1403;
            } else {
                $this->moderation = $json;
                $this->assertIsAllowed();
            }
        }
        curl_close($curl);
    }

    public function moderation()
    {
        return $this->moderation;
    }

    public function isSuccess()
    {
        return $this->error_code === 0;
    }

    public function isError()
    {
        return $this->error_code !== 0;
    }

    public function errorCode()
    {
        return $this->error_code;
    }

    public function errorMessage()
    {
        return $this->error_message;
    }

    private function assertIsAllowed()
    {
        $block = [];
        $blockRating = Settings::get('moderatecontent_block_rating');
        switch ($blockRating) {
            case 'a':
                $block[] = 'a';
            break;
            case 't':
                $block[] = 'a';
                $block[] = 't';
            break;
        }
        $ratings = [
            'a' => _s('adult'),
            't' => _s('teen'),
        ];
        foreach ($block as $rating) {
            if ($this->moderation->rating_letter == $rating) {
                throw new UploadException(_s('Content of type %s is forbidden', $ratings[$rating]), 1404);
            }
        }
    }

    private function optimizeImage()
    {
        $this->imageOptimized = $this->imageFilename;
        // if ($this->imageInfo['size'] > G\get_bytes('1 MB') && !G\is_animated_image($this->imageFilename)) {
        //     $this->imageOptimized = Upload::getTempNam(sys_get_temp_dir());
        //     if (copy($this->imageFilename, $this->imageOptimized)) {
        //         $option = $this->imageInfo['ratio'] >= 1 ? 'width' : 'height';
        //         Image::resize($this->imageOptimized, null, null, [$option => 300]);
        //     }
        // }
    }
}
