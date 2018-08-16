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

class Image
{
    public static $table_chv_image = [
                'name',
                'extension',
                'album_id',
                'size',
                'width',
                'height',
                'date',
                'date_gmt',
                'nsfw',
                'user_id',
                'uploader_ip',
                'storage_mode',
                'storage_id',
                'md5',
                'source_md5',
                'original_filename',
                'original_exifdata',
                'category_id',
                'description',
                'chain',
                'thumb_size',
                'medium_size',
                'title',
                'expiration_date_gmt',
                'likes',
                'is_animated',
      ];

    public static $chain_sizes = ['original', 'image', 'medium', 'thumb'];

    public static function getSingle($id, $sumview=false, $pretty=false, $requester=null)
    {
        $tables = DB::getTables();
        $query = 'SELECT * FROM '.$tables['images']."\n";

        $joins = [
            'LEFT JOIN '.$tables['users'].' ON '.$tables['images'].'.image_user_id = '.$tables['users'].'.user_id',
            'LEFT JOIN '.$tables['albums'].' ON '.$tables['images'].'.image_album_id = '.$tables['albums'].'.album_id'
        ];

        if ($requester) {
            if (!is_array($requester)) {
                $requester = User::getSingle($requester, 'id');
            }
        }

        $query .=  implode("\n", $joins) . "\n";
        $query .= 'WHERE image_id=:image_id;'."\n";

        if ($sumview) {
            $query .= 'UPDATE '.$tables['images'].' SET image_views = image_views + 1 WHERE image_id=:image_id';
        }

        try {
            $db = DB::getInstance();
            $db->query($query);
            $db->bind(':image_id', $id);
            $image_db = $db->fetchSingle();
            if ($image_db) {
                if ($sumview) {
                    $image_db['image_views'] += 1;
                    // Track stats
                    Stat::track([
                        'action'	=> 'update',
                        'table'		=> 'images',
                        'value'		=> '+1',
                        'user_id'	=> $image_db['image_user_id'],
                    ]);
                }
                $return = $image_db;
                $return = $pretty ? self::formatArray($return) : $return;

                if (!$return['file_resource']) {
                    $return['file_resource'] =  self::getSrcTargetSingle($image_db, true);
                }
                return $return;
            } else {
                return $image_db;
            }
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function getMultiple($ids, $pretty=false)
    {
        if (!is_array($ids)) {
            throw new ImageException('Expecting $ids array in Image::get_multiple', 100);
        }
        if (count($ids) == 0) {
            throw new ImageException('Null $ids provided in Image::get_multiple', 100);
        }

        $tables = DB::getTables();
        $query = 'SELECT * FROM '.$tables['images']."\n";

        $joins = array(
            'LEFT JOIN '.$tables['users'].' ON '.$tables['images'].'.image_user_id = '.$tables['users'].'.user_id',
            'LEFT JOIN '.$tables['albums'].' ON '.$tables['images'].'.image_album_id = '.$tables['albums'].'.album_id'
        );

        $query .=  implode("\n", $joins) . "\n";
        $query .= 'WHERE image_id IN ('. join(',', $ids). ')' . "\n";

        try {
            $db = DB::getInstance();
            $db->query($query);
            $images_db = $db->fetchAll();
            if ($images_db) {
                foreach ($images_db as $k => $v) {
                    $images_db[$k] = array_merge($v, self::getSrcTargetSingle($v, true)); // todo
                }
            }
            if ($pretty) {
                $return = [];
                foreach ($images_db as $k => $v) {
                    $return[] = self::formatArray($v);
                }
                return $return;
            }
            return $images_db;
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function getAlbumSlice($image_id, $album_id=null, $padding=2)
    {
        $tables = DB::getTables();

        if ($image_id == null) {
            throw new ImageException("Image id can't be NULL", 100);
        }

        if ($album_id == null) {
            try {
                $db = DB::getInstance();
                $db->query('SELECT image_album_id FROM '.$tables['images'].' WHERE image_id=:image_id');
                $db->bind(':image_id', $image_id);
                $image_album_db = $db->fetchSingle();
                $album_id = $image_album_db['image_album_id'];
            } catch (Excepton $e) {
                throw new ImageException($e->getMessage(), 400);
            }

            if ($album_id == null) {
                return;
            }
        }

        if (!is_numeric($padding)) {
            $padding = 2;
        }

        //$where_album = $album_id !== NULL ? "image_album_id=:image_album_id" : "image_album_id IS NULL";

        try {
            $db = DB::getInstance();
            $db->query('SELECT * FROM (
                    (SELECT * FROM '. $tables['images'] . ' WHERE image_album_id=:image_album_id AND image_id <= :image_id ORDER BY image_id DESC LIMIT 0,'.($padding*2 + 1).')
                    UNION
                    (SELECT * FROM ' . $tables['images'] . ' WHERE image_album_id=:image_album_id AND image_id > :image_id ORDER BY image_id ASC LIMIT 0,'.($padding*2).')
                ) images ORDER BY images.image_id ASC');

            $db->bind(':image_album_id', $album_id);
            $db->bind(':image_id', $image_id);

            $image_album_slice_db = $db->fetchAll();

            $album_offset = array('top' => 0, 'bottom' => 0);
            foreach ($image_album_slice_db as $v) {
                if ($image_id > $v['image_id']) {
                    $album_offset['top']++;
                }
                if ($image_id < $v['image_id']) {
                    $album_offset['bottom']++;
                }
            }

            $album_chop_count = count($image_album_slice_db);
            $album_iteration_times = $album_chop_count - ($padding*2 + 1);

            if ($album_chop_count > ($padding*2 + 1)) {
                if ($album_offset['top'] > $padding && $album_offset['bottom'] > $padding) {
                    // Cut on top
                    for ($i=0; $i<$album_offset['top']-$padding; $i++) {
                        unset($image_album_slice_db[$i]);
                    }
                    // Cut on bottom
                    for ($i=1; $i<=$album_offset['bottom']-$padding; $i++) {
                        unset($image_album_slice_db[$album_chop_count - $i]);
                    }
                } elseif ($album_offset['top'] <= $padding) {
                    // Cut bottom
                    for ($i=0; $i<$album_iteration_times; $i++) {
                        unset($image_album_slice_db[$album_chop_count - 1 - $i]);
                    }
                } elseif ($album_offset['bottom'] <= $padding) {
                    // Cut top
                    for ($i=0; $i<$album_iteration_times; $i++) {
                        unset($image_album_slice_db[$i]);
                    }
                }

                // Some cleaning after the unsets
                $image_album_slice_db = array_values($image_album_slice_db);
            }

            $album_cursor = '';
            foreach ($image_album_slice_db as $k => $v) {
                if ($v['image_id'] == $image_id) {
                    $album_cursor = $k;
                    break;
                }
            }

            $image_album_slice['images'] = array();

            foreach ($image_album_slice_db as $k => $v) {
                $image_album_slice['images'][$k] = self::formatArray($v);
            }

            if ($image_album_slice['images'][$album_cursor-1]) {
                $image_album_slice['prev'] =  $image_album_slice['images'][$album_cursor-1];
            }

            if ($image_album_slice['images'][$album_cursor+1]) {
                $image_album_slice['next'] = $image_album_slice['images'][$album_cursor+1];
            }

            return array(
                'db'		=> $image_album_slice_db,
                'formatted'	=> $image_album_slice
            );
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function getSrcTargetSingle($filearray, $prefix=true)
    {
        $prefix = $prefix ? 'image_' : null;
        $folder = CHV_PATH_IMAGES;
        $pretty = !isset($filearray['image_id']);

        $chain_mask = str_split((string) str_pad(decbin($filearray[$pretty ? 'chain' : 'image_chain']), 4, '0', STR_PAD_LEFT));

        $chain_to_sufix = [
            //'original'	=> '.original.',
            'image'		=> '.',
            'thumb'		=> '.th.',
            'medium'	=> '.md.'
        ];

        $type = 'path';

        switch ($filearray[$prefix.'storage_mode']) {
            case 'datefolder':
                $datetime = $filearray[$prefix.'date'];
                $datefolder = preg_replace('/(.*)(\s.*)/', '$1', str_replace('-', '/', $datetime));
                $folder .= G\add_ending_slash($datefolder); // Y/m/d/
            break;
            case 'old':
                $folder .= 'old/';
            break;
            case 'direct':
                // use direct $folder
            break;
        }

        $targets = [
            'type' => $type,
            'chain'	=> [
                //'original'	=> NULL,
                'image'		=> null,
                'thumb'		=> null,
                'medium'	=> null
            ]
        ];

        foreach ($targets['chain'] as $k => $v) {
            $targets['chain'][$k] = $folder.$filearray[$prefix.'name'] . $chain_to_sufix[$k] . $filearray[$prefix.'extension'];
        }

        if ($type == 'path') {
            foreach ($targets['chain'] as $k => $v) {
                if (!file_exists($v)) {
                    unset($targets['chain'][$k]);
                };
            }
        } else {
            foreach ($chain_mask as $k => $v) {
                if (!(bool) $v) {
                    unset($targets['chain'][self::$chain_sizes[$k]]);
                }
            }
        }

        return $targets;
    }

    public static function getUrlViewer($id_encoded)
    {
        return G\get_base_url(getSetting('route_image') . '/' . $id_encoded);
    }

    public static function getAvailableExpirations()
    {
        $string = _s('After %n %t');
        $translate = [ // Just for gettext parsing
                'minute'=> _n('minute', 'minutes', 1),
                'hour'	=> _n('hour', 'hours', 1),
                'day'		=> _n('day', 'days', 1),
                'week'	=> _n('week', 'weeks', 1),
                'month'	=> _n('month', 'months', 1),
                'year'	=> _n('year', 'years', 1),
            ];
        $return = [
                null => _s("Don't autodelete"),
            ];
        $table = [
                ['minute', 5],
                ['minute', 15],
                ['minute', 30],
                ['hour', 1],
                ['hour', 2],
                ['hour', 6],
                ['hour', 12],
                ['day', 1],
                ['day', 2],
                ['day', 3],
                ['week', 1],
                ['week', 2],
                ['month', 1],
            ];
        foreach ($table as $expire) {
            $unit = $expire[0];
            $interval_spec = 'P' . (in_array($unit, ['second', 'minute', 'hour']) ? 'T' : null) . $expire[1] . strtoupper($unit[0]);
            $return[$interval_spec] = strtr($string, ['%n' => $expire[1], '%t' => _n($unit, $unit . 's', $expire[1])]);
        }
        return $return;
    }

    public static function watermark($image_path, $options=[])
    {

        // Watermark options
        $options = array_merge([
            'ratio'		=> getSetting('watermark_percentage') / 100,
            'position'	=> explode(' ', getSetting('watermark_position')),
            'file'		=> CHV_PATH_CONTENT_IMAGES_SYSTEM . getSetting('watermark_image')
        ], $options);

        if (!is_readable($options['file'])) {
            throw new Exception("Can't read watermark file", 100);
        }

        // Fail-safe ratio
        $options['ratio'] = min(1, (!is_numeric($options['ratio']) ? 0.01 : max(0.01, $options['ratio'])));

        // Fail-safe positioning
        if (!in_array($options['position'][0], ['left', 'center', 'right'])) {
            $options['position'][0] = 'right';
        }
        if (!in_array($options['position'][1], ['top', 'center', 'bottom'])) {
            $options['position'][0] = 'bottom';
        }

        // Get source fileinfo
        $image_fileinfo = G\get_image_fileinfo($image_path);

        // Create working source image
        switch ($image_fileinfo['extension']) {
            case 'gif':
                $src = imagecreatefromgif($image_path);
            break;
            case 'png':
                $src = imagecreatefrompng($image_path);
            break;
            case 'jpg':
                $src = imagecreatefromjpeg($image_path);
            break;
        }
        $src_width = imagesx($src);
        $src_height = imagesy($src);

        // Create working watermark image
        $watermark_fileinfo = G\get_image_fileinfo($options['file']);

        $watermark_width = $watermark_fileinfo['width'];
        $watermark_height = $watermark_fileinfo['height'];
        $watermark_max_upscale = 1.2;
        $watermark_max_width = $watermark_fileinfo['width'] * $watermark_max_upscale;
        $watermark_max_height = $watermark_fileinfo['height'] * $watermark_max_upscale;

        $watermark_area = $image_fileinfo['width'] * $image_fileinfo['height'] * $options['ratio'];
        $watermark_image_ratio = $watermark_fileinfo['ratio'];

        $watermark_new_height = round(sqrt($watermark_area/$watermark_image_ratio), 0);

        if ($watermark_new_height > $watermark_max_height) { // Set a max cap
            $watermark_new_height = $watermark_max_height;
        }

        // To legit to quit
        if ($watermark_new_height > $src_height) {
            $watermark_new_height = $src_height;
        }

        // Fix watermark margin issues on height
        if (getSetting('watermark_margin') and $options['position'][1] !== 'center' and $watermark_new_height + getSetting('watermark_margin') > $src_height) {
            $watermark_new_height -= $watermark_new_height + 2*getSetting('watermark_margin') - $src_height;
        }

        $watermark_new_width  = round($watermark_image_ratio * $watermark_new_height, 0);

        // To legit to quit yo
        if ($watermark_new_width > $src_width) {
            $watermark_new_width = $src_width;
        }

        // Fix watermark margin issues on width
        if (getSetting('watermark_margin') and $options['position'][0] !== 'center' and $watermark_new_width + getSetting('watermark_margin') > $src_width) {
            $watermark_new_width -= $watermark_new_width + 2*getSetting('watermark_margin') - $src_width;
            $watermark_new_height = $watermark_new_width / $watermark_image_ratio;
        }

        if ($watermark_new_height <= $watermark_max_height or $watermark_new_width <= $watermark_fileinfo['width']*2) {
            // Resizable watermark image
            try {
                $watermark_tempnam = @tempnam(sys_get_temp_dir(), 'chvtemp');
                if (!$watermark_tempnam) {
                    $watermark_tempnam = @tempnam(dirname($image_path), 'chvtemp');
                }
                if (!$watermark_tempnam) {
                    throw new Exception("Can't tempnam a watermak file", 400);
                }
                self::resize($options['file'], dirname($watermark_tempnam), basename($watermark_tempnam), ['width' => $watermark_new_width]);
                $watermark_temp = $watermark_tempnam . '.png';
                $watermark_src = imagecreatefrompng($watermark_temp);
                $watermark_width = imagesx($watermark_src);
                $watermark_height = imagesy($watermark_src);
                @unlink($watermark_tempnam);
            } catch (Exception $e) {
                error_log($e);
            } // Silence
        } else {
            // Watermark "as is"
            $watermark_src = imagecreatefrompng($options['file']);
        }


        // Calculate the watermark position
        switch ($options['position'][0]) {
            case 'left':
                $watermark_x = getSetting('watermark_margin');
            break;
            case 'center':
                $watermark_x = $src_width/2 - $watermark_width/2;
            break;
            case 'right':
                $watermark_x = $src_width - $watermark_width - getSetting('watermark_margin');
            break;
        }
        switch ($options['position'][1]) {
            case 'top':
                $watermark_y = getSetting('watermark_margin');
            break;
            case 'center':
                $watermark_y = $src_height/2 - $watermark_height/2;
            break;
            case 'bottom':
                $watermark_y = $src_height - $watermark_height - getSetting('watermark_margin');
            break;
        }

        /*
        // Watermark has the same or greater size of the image ?
        // --> Center the watermark
        if($watermark_width == $src_width && $watermark_height == $src_height) {
            $watermark_x = $src_width/2 - $watermark_width/2;
            $watermark_y = $src_height/2 - $watermark_height/2;
        }

        // Watermark is too big ?
        // --> Fit the watermark on the image
        if($watermark_width > $src_width || $watermark_height > $src_height) {
            // Watermark is wider than the image
            if($watermark_width > $src_width) {
                $watermark_new_width  = $src_width;
                $watermark_new_height = $src_width * $watermark_height / $watermark_width;
                if($watermark_new_height > $src_height) {
                    $watermark_new_width  = $src_height * $watermark_width / $watermark_height;
                    $watermark_new_height = $src_height;
                }
            } else {
                $watermark_new_width  = $src_height * $watermark_width / $watermark_height;
                $watermark_new_height = $src_height;
            }
            try {
                $watermark_temp = @tempnam(sys_get_temp_dir(), 'chvtemp');
                self::resize($options['file'], $watermark_temp, ['width' => $watermark_new_width]);
                $watermark_width = $watermark_new_width;
                $watermark_height = $watermark_new_height;
                $watermark_src = imagecreatefrompng($watermark_temp);
                $watermark_x = $src_width/2 - $watermark_width/2;
                $watermark_y = $src_height/2 - $watermark_height/2;
            } catch(Exception $e) {} // Silence
        }
        */

        // Apply and save the watermark
        G\imagecopymerge_alpha($src, $watermark_src, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height, getSetting('watermark_opacity'), $image_fileinfo['extension']);

        switch ($image_fileinfo['extension']) {
            case 'gif': // Cast gif as png (gif has very poor quality)
                imagegif($src, $image_path);
                // no break
            case 'png':
                imagepng($src, $image_path);
            break;
            case 'jpg':
                imagejpeg($src, $image_path, 90);
            break;
        }
        imagedestroy($src);
        @unlink($watermark_temp);

        return true;
    }

    public static function upload($source, $destination, $filename=null, $options=[], $storage_id=null)
    {
        $default_options = Upload::getDefaultOptions();

        $options = array_merge($default_options, $options);

        if (!is_null($filename) && !$options['filenaming']) {
            $options['filenaming'] = 'original';
        }

        try {
            $upload = new Upload;
            $upload->setSource($source);
            $upload->setDestination($destination);
            $upload->setOptions($options);
            if (!is_null($filename)) {
                $upload->setFilename($filename);
            }
            $upload->exec();

            $original_md5 = $upload->uploaded['fileinfo']['md5'];

            $is_animated_image = ($upload->uploaded['fileinfo']['extension'] == 'gif' and G\is_animated_image($upload->uploaded['file']));
            $apply_watermark = ($options['watermark'] and !$is_animated_image);

            // Disable animated image watermark
            if ($is_animated_image) {
                $apply_watermark = false;
            }

            if ($apply_watermark) {
                // Detect watermark min image requirement
                foreach (['width', 'height'] as $k) {
                    $min_value = getSetting('watermark_target_min_' . $k);
                    if ($min_value == 0) { // Skip on zero
                        continue;
                    }
                    $apply_watermark = $upload->uploaded['fileinfo'][$k] >= $min_value;
                }
                // Disable on GIF image?
                if ($apply_watermark and $upload->uploaded['fileinfo']['extension'] == 'gif' and !$options['watermark_gif']) {
                    $apply_watermark = false;
                }
            }

            if ($apply_watermark && self::watermark($upload->uploaded['file'])) {
                $upload->uploaded['fileinfo'] = G\get_image_fileinfo($upload->uploaded['file']); // Remake the fileinfo array, new full array file info (todo: faster!)
                $upload->uploaded['fileinfo']['md5'] = $original_md5; // Preserve original MD5 for watermarked images
            }

            return [
                'uploaded'	=> $upload->uploaded,
                'source'	=> $upload->source
            ];
        } catch (Exception $e) {
            throw new UploadException($e->getMessage(), $e->getCode());
        }
    }

    // Mostly for people uploading two times the same image to test or just bug you
    // $mixed => $_FILES or md5 string
    public static function isDuplicatedUpload($mixed, $time_frame='P1D')
    {
        $md5_file = (is_array($mixed) && isset($mixed['tmp_name'])) ? md5_file($mixed['tmp_name']) : $mixed;
        if (!isset($md5_file)) {
            throw new Exception('Unable to process md5_file', 100);
        }
        $db = DB::getInstance();
        $db->query('SELECT * FROM ' . DB::getTable('images') . ' WHERE (image_md5=:md5 OR image_source_md5=:md5) AND image_uploader_ip=:ip AND image_date_gmt > :date_gmt');
        $db->bind(':md5', $md5_file);
        $db->bind(':ip', G\get_client_ip());
        $db->bind(':date_gmt', G\datetime_sub(G\datetimegmt(), $time_frame));
        $db->exec();
        return $db->fetchColumn();
    }

    public static function uploadToWebsite($source, $user=null, $params=[])
    {
        try {

            // Get user
            if ($user) {
                switch (gettype($user)) {
                    case 'string':
                        $user = User::getSingle($user, 'username');
                    break;
                    case 'integer':
                        $user = User::getSingle($user);
                    break;
                }
                if ($user !== null && getSetting('upload_max_filesize_mb_bak') !== null && getSetting('upload_max_filesize_mb') == getSetting('upload_max_filesize_mb_guest')) {
                    Settings::setValue('upload_max_filesize_mb', getSetting('upload_max_filesize_mb_bak'));
                }
            }

            $do_dupe_check = !getSetting('enable_duplicate_uploads') && !$user['is_admin'];

            // Detect duplicated uploads ($_FILES) by IP + MD5 (first layer)
            if ($do_dupe_check && self::isDuplicatedUpload($source)) {
                throw new Exception(_s('Duplicated upload'), 101);
            }

            
            $storage_mode = getSetting('upload_storage_mode');
            switch ($storage_mode) {
                case 'direct':
                    $upload_path = CHV_PATH_IMAGES;
                break;
                case 'datefolder':
                    // Stock 'now' datetime
                    $datefolder_stock = [
                        'date' => G\datetime(),
                        'date_gmt' => G\datetimegmt(),
                    ];
                    $datefolder = date('Y/m/d/', strtotime($datefolder_stock['date']));
                    $upload_path = CHV_PATH_IMAGES . $datefolder;
                break;
            }

            $filenaming = getSetting('upload_filenaming');

            if ($filenaming !== 'id' and in_array($params['privacy'], ['password', 'private', 'private_but_link'])) {
                $filenaming = 'random';
            }

            $upload_options = [
                'max_size'		=> G\get_bytes(getSetting('upload_max_filesize_mb') . ' MB'),
                'watermark'		=> getSetting('watermark_enable'),
        'exif'        => (getSetting('upload_image_exif_user_setting') && $user) ? $user['image_keep_exif'] : getSetting('upload_image_exif'),
            ];

            // Workaround watermark by user group
            if ($upload_options['watermark']) {
                $watermark_enable = [];
                $watermark_user = $user ? ($user['is_admin'] ? 'admin' : 'user') : 'guest';
                $upload_options['watermark'] = getSetting('watermark_enable_' . $watermark_user);
            }

            // Watermark by filetype
            $upload_options['watermark_gif'] = (bool) getSetting('watermark_enable_file_gif');

            // Reserve this ID
            if ($filenaming == 'id') {
                try {
                    $dummy = [
                        'name' => '',
                        'extension' => '',
                        'size' => 0,
                        'width' => 0,
                        'height' => 0,
                        'date' => '0000-01-01 00:00:00',
                        'date_gmt' => '0000-01-01 00:00:00',
                        'nsfw' => 0,
                        'uploader_ip' => '',
                        'md5' => '',
                        'original_filename' => '',
                        'chain' => 0,
                        'thumb_size' => 0,
                        'medium_size' => 0,
                    ];
                    $dummy_insert = DB::insert('images', $dummy);
                    DB::delete('images', ['id' => $dummy_insert]);
                    $target_id = $dummy_insert;
                } catch (Exception $e) {
                    error_log($e);
                    // Fallback
                    $filenaming = 'original';
                }
            }

            // Filenaming
            $upload_options['filenaming'] = $filenaming;

            // Allowed extensions
            $upload_options['allowed_formats'] = self::getEnabledImageFormats();

            $image_upload = self::upload($source, $upload_path, $filenaming == 'id' ? encodeID($target_id) : null, $upload_options, null);

            $chain_mask = [0, 1, 0, 1]; // original image medium thumb
            $chain_array = [];

            // Detect duplicated uploads (all) by IP + MD5 (second layer)
            if ($do_dupe_check && self::isDuplicatedUpload($image_upload['uploaded']['fileinfo']['md5'])) {
                throw new Exception(_s('Duplicated upload'), 102);
            }

            // Handle resizing KEEP 'source', change 'uploaded'
            $image_ratio = $image_upload['uploaded']['fileinfo']['width'] / $image_upload['uploaded']['fileinfo']['height'];
            $must_resize = false;

            // This dumb step is to have a failover for 0 values
            $image_max_size_cfg = [
                'width'		=> Settings::get('upload_max_image_width') ?: $image_upload['uploaded']['fileinfo']['width'],
                'height'	=> Settings::get('upload_max_image_height') ?: $image_upload['uploaded']['fileinfo']['height'],
            ];

            // Is too large? (like my big fat dick...)
            if ($image_max_size_cfg['width'] < $image_upload['uploaded']['fileinfo']['width'] || $image_max_size_cfg['height'] < $image_upload['uploaded']['fileinfo']['height']) {

                // Adjust image_max to max_size_cfg (setting value)
                $image_max = $image_max_size_cfg;

                $image_max['width'] = (int) round($image_max_size_cfg['height'] * $image_ratio);
                $image_max['height'] =  (int) round($image_max_size_cfg['width'] / $image_ratio);

                if ($image_max['height'] > $image_max_size_cfg['height']) {
                    $image_max['height'] = $image_max_size_cfg['height'];
                    $image_max['width'] =  (int) round($image_max['height'] * $image_ratio);
                }

                if ($image_max['width'] > $image_max_size_cfg['width']) {
                    $image_max['width'] = $image_max_size_cfg['width'];
                    $image_max['height'] =  (int) round($image_max['width'] / $image_ratio);
                }

                // Detect if resize is needed, if so, use $image_max values
                if ($image_max != ['width' => $image_upload['uploaded']['fileinfo']['width'], 'height' => $image_max_size_cfg['height']]) { // loose just in case..
                    $must_resize = true;
                    $params['width'] = $image_max['width'];
                    $params['height'] = $image_max['height'];
                }
            }

            foreach (['width', 'height'] as $k) {
                if (!isset($params[$k]) || !is_numeric($params[$k])) {
                    continue;
                }
                if ($params[$k] != $image_upload['uploaded']['fileinfo'][$k]) {
                    $must_resize = true;
                }
            }

            // Disable resize for animated images (for now)
            if (G\is_animated_image($image_upload['uploaded']['file'])) {
                $must_resize = false;
            }

            if ($must_resize) {
                $source_md5 = $image_upload['uploaded']['fileinfo']['md5'];
                // Detect duplicated uploads (all + resized) by IP + MD5(source not resized) (third layer)
                // The idea is to reject these before the actual final resize (avoids potential ddos)
                if ($do_dupe_check && self::isDuplicatedUpload($source_md5)) {
                    throw new Exception(_s('Duplicated upload'), 103);
                }
                if ($image_ratio == $params['width']/$params['height']) {
                    $image_resize_options = [
                        'width'		=> $params['width'],
                        'height'	=> $params['height']
                    ];
                } else {
                    $image_resize_options = ['width' => $params['width']];
                }
                $image_upload['uploaded'] = self::resize($image_upload['uploaded']['file'], dirname($image_upload['uploaded']['file']), null, $image_resize_options);
            }

            // Try to generate the thumb
            $image_thumb_options = [
                'width'		=> getSetting('upload_thumb_width'),
                'height'	=> getSetting('upload_thumb_height')
            ];

            // Try to generate the medium
            $medium_size = getSetting('upload_medium_size');
            $medium_fixed_dimension = getSetting('upload_medium_fixed_dimension');

            $is_animated_image = $image_upload['uploaded']['fileinfo']['extension'] == 'gif' && G\is_animated_image($image_upload['uploaded']['file']);

            // Medium sized image
            if ($image_upload['uploaded']['fileinfo'][$medium_fixed_dimension] > $medium_size or $is_animated_image) {
                $image_medium_options = [];
                $image_medium_options[$medium_fixed_dimension] = $medium_size;
                if ($is_animated_image) {
                    $image_medium_options['forced'] = true;
                    $image_medium_options[$medium_fixed_dimension] = min($image_medium_options[$medium_fixed_dimension], $image_upload['uploaded']['fileinfo'][$medium_fixed_dimension]);
                }
                $image_medium = self::resize($image_upload['uploaded']['file'], dirname($image_upload['uploaded']['file']), $image_upload['uploaded']['name'] . '.md', $image_medium_options);
                $chain_mask[2] = 1;
            }

            // Thumb sized image
            $image_thumb = self::resize($image_upload['uploaded']['file'], dirname($image_upload['uploaded']['file']), $image_upload['uploaded']['name'] . '.th', $image_thumb_options);

            // Image chain (binary)
            $chain_value = bindec((int)implode('', $chain_mask));

            $disk_space_needed = $image_upload['uploaded']['fileinfo']['size'] + $image_thumb['fileinfo']['size'] + ($image_medium['fileinfo']['size'] ?: 0);

            $image_insert_values = [
                'storage_mode'	=> $storage_mode,
                'storage_id'	=> $storage_id,
                'user_id'		=> $user['id'],
                'album_id'		=> $params['album_id'] ?: null,
                'nsfw'			=> $params['nsfw'],
                'category_id'	=> $params['category_id'] ?: null,
                'title'			=> $params['title'],
                'description'	=> $params['description'] ?: null,
                'chain'			=> $chain_value,
                'thumb_size'	=> $image_thumb['fileinfo']['size'],
                'medium_size'	=> $image_medium['fileinfo']['size'] ?: 0,
                'is_animated'	=> $is_animated_image,
                'source_md5' => $source_md5 ?: null,
            ]; // Note: NULL is strongly needed here to avoid integer 0 issues on sql

            if (isset($datefolder_stock)) {
                $image_insert_values = array_merge($image_insert_values, $datefolder_stock);
            }

            // Expirable upload
            if (getSetting('enable_expirable_uploads')) {

                // Inject guest forced auto delete
                if (!$user && getSetting('auto_delete_guest_uploads') !== null) {
                    $params['expiration'] = getSetting('auto_delete_guest_uploads');
                }

                // Inject user's default expiration date
                if (!isset($params['expiration']) && !is_null($user['image_expiration'])) {
                    $params['expiration'] = $user['image_expiration'];
                }
                try {
                    // Handle image expire time (source comes as DateInterval string)
                    if (!empty($params['expiration']) && array_key_exists($params['expiration'], self::getAvailableExpirations())) {
                        $params['expiration_date_gmt'] = G\datetime_add(G\datetimegmt(), strtoupper($params['expiration']));
                    }
                    // Image expirable handling
                    if (!empty($params['expiration_date_gmt'])) {
                        $expirable_diff = G\datetime_diff(G\datetimegmt(), $params['expiration_date_gmt'], 'm');
                        // 5 minutes minimum
                        $image_insert_values['expiration_date_gmt'] = $expirable_diff < 5 ? G\datetime_modify(G\datetimegmt(), '+5 minutes') : $params['expiration_date_gmt'];
                    }
                } catch (Exception $e) {
                } // Silence
            }

            // Inject image title
            if (!array_key_exists('title', $params)) {
                // From Exif
                $title_from_exif = $image_upload['source']['image_exif']['ImageDescription'] ? trim($image_upload['source']['image_exif']['ImageDescription']) : null;
                if ($title_from_exif) {
                    // Get rid of any unicode stuff
                    $title_from_exif = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $title_from_exif);
                    $image_title = $title_from_exif;
                } else {
                    // From filename
                    $title_from_filename = preg_replace('/[-_\s]+/', ' ', trim($image_upload['source']['name']));
                    $image_title = $title_from_filename;
                }
                $image_insert_values['title'] = $image_title;
            }

            if ($filenaming == 'id' && $target_id) { // Insert as a reserved ID
                $image_insert_values['id'] = $target_id;
            }

            // Trim image_title to the actual DB limit
            $image_insert_values['title'] = mb_substr($image_insert_values['title'], 0, 100, 'UTF-8');

            if ($user && $image_insert_values['album_id']) {
                $album = Album::getSingle($image_insert_values['album_id']);
                // Check album ownership
                if ($album['user']['id'] != $user['id']) {
                    unset($image_insert_values['album_id'], $album);
                }
            }

            $uploaded_id = self::insert($image_upload, $image_insert_values);

            if ($filenaming == 'id') {
                unset($reserved_id);
            }

            // Private upload? Create a private album then (if needed)
            if (in_array($params['privacy'], ['private', 'private_but_link'])) {
                if (is_null($album) or !in_array($album['privacy'], ['private', 'private_but_link'])) {
                    $upload_timestamp = $params['timestamp'] ?: time();
                    $session_handle = 'upload_'.$upload_timestamp;
                    // Get timestamp based album
                    if (isset($_SESSION[$session_handle])) {
                        $album = Album::getSingle(decodeID($_SESSION[$session_handle]));
                    } else {
                        $album = null;
                    }
                    // Test that...
                    if (!$album or !in_array($album['privacy'], ['private', 'private_but_link'])) {
                        $inserted_album = Album::insert(_s('Private upload').' '.G\datetime('Y-m-d'), $user['id'], $params['privacy']);
                        $_SESSION[$session_handle] = encodeID($inserted_album);
                        $image_insert_values['album_id'] = $inserted_album;
                    } else {
                        $image_insert_values['album_id'] = $album['id'];
                    }
                }
            }

            // Update album (if any)
            if (isset($image_insert_values['album_id'])) {
                Album::addImage($image_insert_values['album_id'], $uploaded_id);
            }

            // Update user (if any)
            if ($user) {
                DB::increment('users', ['image_count' => '+1'], ['id' => $user['id']]);
            } else {
                // Save this upload into "session" record
                if (!isset($_SESSION['guest_uploads'])) {
                    $_SESSION['guest_uploads'] = [];
                }
                $_SESSION['guest_uploads'][] = $uploaded_id;
            }


            return $uploaded_id;
        } catch (Exception $e) {
            @unlink($image_upload['uploaded']['file']);
            @unlink($image_medium['file']);
            @unlink($image_thumb['file']);
            throw $e;
        }
    }

    public static function getEnabledImageFormats()
    {
        $formats = explode(',', Settings::get('upload_enabled_image_formats'));
        if (in_array('jpg', $formats)) {
            $formats[] = 'jpeg';
        }
        return $formats;
    }

    public static function resize($source, $destination, $filename=null, $options=[])
    {
        try {
            $resize = new Imageresize;
            $resize->setSource($source);
            $resize->setDestination($destination);
            if ($filename) {
                $resize->setFilename($filename);
            }
            $resize->setOptions($options);
            if ($options['width']) {
                $resize->set_width($options['width']);
            }
            if ($options['height']) {
                $resize->set_height($options['height']);
            }
            if ($options['width'] == $options['height']) {
                $resize->set_fixed();
            }
            if ($options['forced']) {
                $resize->setOption('forced', true);
            }
            $resize->exec();
            return $resize->resized;
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), $e->getCode());
        }
    }

    public static function insert($image_upload, $values=[])
    {
        try {
            $table_chv_image = self::$table_chv_image;
            foreach ($table_chv_image as $k => $v) {
                $table_chv_image[$k] = 'image_' . $v;
            }

            // Remove eternal/useless Exif MakerNote
            if ($image_upload['source']['image_exif']['MakerNote']) {
                unset($image_upload['source']['image_exif']['MakerNote']);
            }

            $original_exifdata = $image_upload['source']['image_exif'] ? json_encode(G\array_utf8encode($image_upload['source']['image_exif'])) : null;

            // Fix some values
            $values['nsfw'] = in_array(strval($values['nsfw']), ['0','1']) ? $values['nsfw'] : 0;

            $populate_values = [
                'uploader_ip' => G\get_client_ip(),
                'md5' => $image_upload['uploaded']['fileinfo']['md5'],
                'original_filename' => $image_upload['source']['filename'],
                'original_exifdata' => $original_exifdata
            ];

            if (!isset($values['date'])) {
                $populate_values = array_merge($populate_values, [
                    'date' => G\datetime(),
                    'date_gmt' => G\datetimegmt(),
                ]);
            }

            // Populate values with fileinfo + populate_values
            $values = array_merge($image_upload['uploaded']['fileinfo'], $populate_values, $values);

            // This doesn't work all the time...
            foreach (['title', 'description', 'category_id', 'album_id'] as $v) {
                G\nullify_string($values[$v]);
            }

            // Now use only the values accepted by the table
            foreach ($values as $k => $v) {
                if (!in_array('image_' . $k, $table_chv_image) && $k !== 'id') {
                    unset($values[$k]);
                }
            }

            // Insert image
            $insert = DB::insert('images', $values);

            $disk_space_used = $values['size'] + $values['thumb_size'] + $values['medium_size'];

            // Track stats
            Stat::track([
                'action'	=> 'insert',
                'table'		=> 'images',
                'value'		=> '+1',
                'date_gmt'	=> $values['date_gmt'],
                'disk_sum'	=> $disk_space_used,
            ]);

            // Update album count
            if (!is_null($values['album_id']) and $insert) {
                Album::updateImageCount($values['album_id'], 1);
            }

            return $insert;
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), $e->getCode());
        }
    }

    public static function update($id, $values)
    {
        try {
            $values = G\array_filter_array($values, self::$table_chv_image, 'exclusion');

            foreach (['title', 'description', 'category_id', 'album_id'] as $v) {
                if (!array_key_exists($v, $values)) {
                    continue;
                }
                G\nullify_string($values[$v]);
            }

            if (isset($values['album_id'])) {
                $image_db = self::getSingle($id, false, false);
                $old_album = $image_db['image_album_id'];
                $new_album = $values['album_id'];
                $update = DB::update('images', $values, ['id' => $id]);
                if ($update and $old_album !== $new_album) {
                    if (!is_null($old_album)) { // Update the old album
                        Album::updateImageCount($old_album, 1, '-');
                    }
                    if (!is_null($new_album)) { // Update the new album
                        Album::updateImageCount($new_album, 1);
                    }
                }
                return $update;
            } else {
                return DB::update('images', $values, ['id' => $id]);
            }
        } catch (Excepton $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function delete($id, $update_user=true)
    {
        try {
            $image = self::getSingle($id, false, true);
            $disk_space_used = $image['size'] + $image['thumb']['size'] + $image['medium']['size'];

            if ($image['file_resource']['type'] == 'path') {
                foreach ($image['file_resource']['chain'] as $file_delete) {
                    if (file_exists($file_delete) and !@unlink($file_delete)) {
                        throw new ImageException("Can't delete file", 200);
                    }
                }
            }

            if ($update_user and isset($image['user']['id'])) {
                DB::increment('users', ['image_count' => '-1'], ['id' => $image['user']['id']]);
            }

            // Update album count
            if ($image['album']['id'] > 0) {
                Album::updateImageCount($image['album']['id'], 1, '-');
            }

            // Track stats
            Stat::track([
                'action'	=> 'delete',
                'table'		=> 'images',
                'value'		=> '-1',
                'date_gmt'	=> $image['date_gmt'],
                'disk_sum'	=> $disk_space_used,
                'likes'		=> $image['likes'],
            ]);

            // Remove "liked" counter for each user who liked this image
            DB::queryExec('UPDATE '.DB::getTable('users').' INNER JOIN '.DB::getTable('likes').' ON user_id = like_user_id AND like_content_type = "image" AND like_content_id = '.$image['id'].' SET user_liked = GREATEST(cast(user_liked AS SIGNED) - 1, 0);');
            

            // Log image deletion
            DB::insert('deletions', [
                'date_gmt'			=> G\datetimegmt(),
                'content_id'		=> $image['id'],
                'content_date_gmt'	=> $image['date_gmt'],
                'content_user_id'	=> $image['user']['id'],
                'content_ip'		=> $image['uploader_ip'],
                'content_views'		=> $image['views'],
                'content_md5'		=> $image['md5'],
                'content_likes'		=> $image['likes'],
                'content_original_filename'	=> $image['original_filename'],
            ]);

            return DB::delete('images', ['id' => $id]);
        } catch (Exception $e) {
            throw new ImageException($e->getMessage() .' (LINE:' . $e->getLine() . ')', 400);
        }
    }

    public static function deleteMultiple($ids)
    {
        if (!is_array($ids)) {
            throw new ImageException('Expecting array argument, '.gettype($ids).' given in '. __METHOD__, 100);
        }
        try {
            $affected = 0;
            foreach ($ids as $id) {
                if (self::delete($id)) {
                    $affected += 1;
                }
            }
            return $affected;
        } catch (Excepton $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function deleteExpired($limit=50)
    {
        try {
            if (!$limit || !is_numeric($limit)) {
                $limit = 50;
            }
            $db = DB::getInstance();
            $db->query('SELECT image_id FROM ' . DB::getTable('images') . ' WHERE image_expiration_date_gmt IS NOT NULL AND image_expiration_date_gmt < :datetimegmt ORDER BY image_expiration_date_gmt DESC LIMIT ' . $limit . ';'); // Just 50 files per request to prevent CPU meltdown or something like that
            $db->bind(':datetimegmt', G\datetimegmt());
            $expired_db = $db->fetchAll();
            if ($expired_db) {
                $expired = [];
                foreach ($expired_db as $k => $v) {
                    $expired[] = $v['image_id'];
                }
                self::deleteMultiple($expired);
            } else {
                return null;
            }
            return $return ? $return['image_id'] : false;
        } catch (Exception $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }

    public static function fill(&$image)
    {
        $image['id_encoded'] = encodeID($image['id']);

        $targets = self::getSrcTargetSingle($image, false);

        if ($targets['type'] == 'path') {

            // Re-create missing stuff
            if ($image['size'] == 0) {
                $get_image_fileinfo = G\get_image_fileinfo($targets['chain']['image']);
                $update_missing_values = [
                    'width'		=> $get_image_fileinfo['width'],
                    'height'	=> $get_image_fileinfo['height'],
                    'size'		=> $get_image_fileinfo['size'],
                ];
                foreach (['thumb', 'medium'] as $k) {
                    if (!array_key_exists($k, $targets['chain'])) {
                        continue;
                    }
                    if ($image[$k . '_size'] == 0) {
                        $update_missing_values[$k . '_size'] = intval(filesize(G\get_image_fileinfo($targets['chain'][$k])));
                    }
                }
                self::update($image['id'], $update_missing_values);
                $image = array_merge($image, $update_missing_values);
            }

            $is_animated = $image['extension'] == 'gif' && G\is_animated_image($targets['chain']['image']);

            // Recreate thumb
            if (count($targets['chain']) > 0 && !$targets['chain']['thumb']) {
                try {
                    $thumb_options = [
                        'width'		=> getSetting('upload_thumb_width'),
                        'height'	=> getSetting('upload_thumb_height'),
                        'forced'	=> $image['extension'] == 'gif' && $is_animated
                    ];
                    $targets['chain']['thumb'] = self::resize($targets['chain']['image'], pathinfo($targets['chain']['image'], PATHINFO_DIRNAME), $image['name'] . '.th', $thumb_options)['file'];
                } catch (Exception $e) {
                }
            }

            // Recreate medium
            $medium_size = getSetting('upload_medium_size');
            $medium_fixed_dimension = getSetting('upload_medium_fixed_dimension');

            if ($image[$medium_fixed_dimension] > $medium_size && count($targets['chain']) > 0 && !$targets['chain']['medium']) {
                try {
                    $medium_options = [
                        $medium_fixed_dimension		=> $medium_size,
                        'forced'					=> $image['extension'] == 'gif' && $is_animated
                    ];
                    $targets['chain']['medium'] = self::resize($targets['chain']['image'], pathinfo($targets['chain']['image'], PATHINFO_DIRNAME), $image['name'] . '.md', $medium_options)['file'];
                } catch (Exception $e) {
                }
            }

            if (count($targets['chain']) > 0) {
                $original_md5 = $image['md5'];
                $image = array_merge($image, (array) @get_image_fileinfo($targets['chain']['image'])); // Never do an array merge over an empty thing!
                $image['md5'] = $original_md5;
            }

            // Update is_animated flag
            if ($is_animated && !$image['is_animated']) {
                self::update($image['id'], ['is_animated' => 1]);
                $image['is_animated'] = 1;
            }
        } else {
            $image_fileinfo = [
                'ratio'				=> $image['width'] / $image['height'],
                'size'				=> intval($image['size']),
                'size_formatted'	=> G\format_bytes($image['size'])
            ];
            $image = array_merge($image, get_image_fileinfo($targets['chain']['image']), $image_fileinfo);
        }

        $image['file_resource'] = $targets;
        $image['url_viewer'] = self::getUrlViewer($image['id_encoded']);

        foreach ($targets['chain'] as $k => $v) {
            if ($targets['type'] == 'path') {
                $image[$k] = file_exists($v) ? get_image_fileinfo($v) : null;
            } else {
                $image[$k] = get_image_fileinfo($v);
            }
            $image[$k]['size'] = $image[($k == 'image' ? '' : $k . '_') . 'size'];
        }

        $display = $image['medium'] !== null ? $image['medium'] : ($image['size'] < G\get_bytes('500 KB') ? $image : $image['thumb']);
        $display_thumb = $display == $image['thumb'];

        $image['size_formatted'] = G\format_bytes($image['size']);

        $image['display_url'] = $display['url'];
        $image['display_width'] = $display_thumb ? getSetting('upload_thumb_width') : $image['width'];
        $image['display_height'] = $display_thumb ? getSetting('upload_thumb_height') : $image['height'];

        $image['views_label'] = _n('view', 'views', $image['views']);
        $image['likes_label'] = _n('like', 'likes', $image['likes']);
        $image['how_long_ago'] = time_elapsed_string($image['date_gmt']);

        $image['date_fixed_peer'] = Login::getUser() ? G\datetimegmt_convert_tz($image['date_gmt'], Login::getUser()['timezone']) : $image['date_gmt'];

        $image['title_truncated'] = G\truncate($image['title'], 28);
        $image['title_truncated_html'] = G\safe_html($image['title_truncated']);

        $image['is_use_loader'] = getSetting('image_load_max_filesize_mb') !== '' ? ($image['size'] > G\get_bytes(getSetting('image_load_max_filesize_mb') . 'MB')) : false;
    }

    public static function formatArray($dbrow, $safe=false)
    {
        try {
            $output = DB::formatRow($dbrow);

            if (!is_null($output['user']['id'])) {
                User::fill($output['user']);
            } else {
                unset($output['user']);
            }

            if (!is_null($output['album']['id']) or !is_null($output['user']['id'])) {
                Album::fill($output['album'], $output['user']);
            } else {
                unset($output['album']);
            }

            self::fill($output);

            if ($safe) {
                unset($output['storage']);
                unset($output['id'], $output['path'], $output['uploader_ip']);
                unset($output['album']['id'], $output['album']['privacy_extra'], $output['album']['user_id']);
                unset($output['user']['id']);
                unset($output['file_resource']);
            }

            return $output;
        } catch (Excepton $e) {
            throw new ImageException($e->getMessage(), 400);
        }
    }
}

class ImageException extends Exception
{
}
