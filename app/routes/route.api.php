<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

  /* API v1 : PLEASE NOTE

     This API v1 is currently just a bridge to port to Chevereto 3 the API from Chevereto 2.
     From now on Chevereto 2 API will be named API v1

     In future releases there will be an API v2 which will add methods like create user, create albums, etc.

  */

$route = function ($handler) {
    try {
        $version = $handler->request[0];
        $action = $handler->request[1];

        if (is_null(CHV\getSetting('api_v1_key')) or CHV\getSetting('api_v1_key') == '') {
            throw new Exception("API v1 key can't be null. Go to your dashboard and set the API v1 key.", 0);
        }

        // Change CHV\getSetting('api_v1_key') to 'something' if you want to use 'something' as key
        if (!G\timing_safe_compare(CHV\getSetting('api_v1_key'), $_REQUEST['key'])) {
            throw new Exception("Invalid API v1 key.", 100);
        }

        if (!in_array($version, [1])) {
            throw new Exception('Invalid API version.', 110);
        }

        $version_to_actions = [
            1 => ['upload']
        ];

        if (!in_array($action, $version_to_actions[$version])) {
            throw new Exception('Invalid API action.', 120);
        }

        // API V1 upload
        $source = isset($_FILES['source']) ? $_FILES['source'] : $_REQUEST['source'];

        if (is_null($source)) {
            throw new Exception('Empty upload source.', 130);
        }

        switch (true) {
            case isset($_FILES['source']['tmp_name']):
                $source = $_FILES['source'];
            break;
            case G\is_image_url($source) || G\is_url($source):
                $sourceQs = G\getQsParams()['source'];
                $source = isset($sourceQs) ? $sourceQs : $source;
            break;
            default:
                // Base64 comes from POST?
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Upload using base64 source must be done using POST method.', 130);
                }

                // Fix the $source base64 string
                $source = trim(preg_replace('/\s+/', '', $source));

                // From _GET source should be urlencoded base64
                if (!G\timing_safe_compare(base64_encode(base64_decode($source)), $source)) {
                    throw new Exception('Invalid base64 string.', 120);
                }

                // Set the API temp file
                $api_temp_file = @tempnam(sys_get_temp_dir(), 'chvtemp');

                if (!$api_temp_file or !@is_writable($api_temp_file)) {
                    throw new UploadException("Can't get a tempnam.", 200);
                }

                $fh = fopen($api_temp_file, 'w');
                stream_filter_append($fh, 'convert.base64-decode', STREAM_FILTER_WRITE);
                if (!@fwrite($fh, $source)) {
                    throw new Exception('Invalid base64 string.', 130);
                } else {
                    // Since all the validations works with $_FILES, we're going to emulate it.
                    $source = array(
                        'name'		=> G\random_string(12).'.jpg',
                        'type'		=> 'image/jpeg',
                        'tmp_name'	=> $api_temp_file,
                        'error'		=> 'UPLOAD_ERR_OK',
                        'size'		=> '1'
                    );
                }
                fclose($fh);
            break;
        }

        // CHV\Image::uploadToWebsite($source, 'username', [params]) to inject API uploads to a given username
        $uploaded_id = CHV\Image::uploadToWebsite($source);
        $json_array['status_code'] = 200;
        $json_array['success'] = array('message' => 'image uploaded', 'code' => 200);
        $image = CHV\Image::formatArray(CHV\Image::getSingle($uploaded_id, false, false), true);
        if (!$image['is_approved']) {
            unset($image['image']['url'], $image['thumb']['url'], $image['medium']['url'], $image['url'], $image['display_url']);
        }
        $json_array['image'] = $image;

        if ($version == 1) {
            switch ($_REQUEST['format']) {
                default:
                case 'json':
                    G\Render\json_output($json_array);
                break;
                case 'txt':
                    echo $json_array['image']['url'];
                break;
                case 'redirect':
                    if ($json_array['status_code'] == 200) {
                        $redirect_url = $json_array['image']['url_viewer'];
                        header("Location: $redirect_url");
                    } else {
                        die($json_array['status_code']);
                    }
                break;
            }
            die();
        } else {
            G\Render\json_output($json_array);
        }
    } catch (Exception $e) {
        $json_array = G\json_error($e);
        if ($version == 1) {
            switch ($_REQUEST['format']) {
                default:
                case 'json':
                    G\Render\json_output($json_array);
                    break;
                case 'txt':
                case 'redirect':
                    die($json_array['error']['message']);
                    break;
            }
        } else {
            G\Render\json_output($json_array);
        }
    }
};
