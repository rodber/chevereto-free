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
use G, Exception;

class Upload {
	// filename => name.ext
	// file => /full/path/to/name.ext
	// name => name

	public $source;
	public $uploaded;

	// Sets the type of resource being uploaded
	public function setType($type) {
		$this->type = $type;
	}

	// Set source
	public function setSource($source) {
		$this->source = $source;
		$this->type = G\is_url($this->source) ? 'url' : 'file';
	}

	// Set destination
	public function setDestination($destination) {
		$this->destination = G\forward_slash($destination);
	}

	// Set storage
	public function setStorageId($storage_id) {
		$this->storage_id = is_numeric($storage_id) ? $storage_id : NULL;
	}

	// Set file basename
	public function setFilename($name) {
		$this->name = $name;
	}

	// Set options
	public function setOptions($options) {
		$this->options = $options;
	}

	// Set individual option
	public function setOption($key, $value) {
		$this->options[$key] = $value;
	}

	// Default options
	public static function getDefaultOptions() {
		return array(
			'max_size'			=> G\get_bytes('2 MB'), // it should be 'max_filesize'
			'filenaming'		=> 'original',
			'exif'				=> TRUE,
			'allowed_formats' 	=> self::getAvailableImageFormats(), // array
		);
	}

	/**
	 * Do the thing
	 * @Exeption 4xx
	 */
	public function exec() {

		// Merge options
		$this->options = array_merge(self::getDefaultOptions(), (array) $this->options);

		$this->validateInput(); // Exception 1

		$this->fetchSource(); // Exception 2

		$this->validateSourceFile(); // Exception 3

		if(!is_array($this->options['allowed_formats'])) {
			$this->options['allowed_formats'] = explode(',', $this->options['allowed_formats']);
		}

		// Save the source name
		$this->source_name = G\get_filename_without_extension($this->type == "url" ? $this->source : $this->source["name"]);

		// Set file extension
		$this->extension = $this->source_image_fileinfo["extension"];

		// Workaround the $name
		if(!$this->name) {
			$this->name = $this->source_name;
		}

		// Fix conflicting starting dots (some Apache installs)
		$this->name = ltrim($this->name, '.');

		// Fix file extension
		if(G\get_file_extension($this->name) == $this->extension) {
			$this->name = G\get_filename_without_extension($this->name);
		}

		// Set the fixed filename
		$this->fixed_filename = preg_replace('/(.*)\.(th|md|original|lg)\.([\w]+)$/', '$1.$3', $this->name . '.' . $this->extension);

        // Workaround for JPEG Exif data
		if($this->extension == 'jpg' and array_key_exists('exif', $this->options)) {
            $this->source_image_exif = NULL;
            if($this->options['exif']) {
                // Fetch JPEG Exif data (when available)
                if(function_exists('exif_read_data')) {
                    $this->source_image_exif = @exif_read_data($this->downstream);
                    if($this->source_image_exif) {
                        $this->source_image_exif['FileName'] = $this->source_filename;
                    }
                }
            } else {
                // Remove JPEG Exif data
                $img = imagecreatefromjpeg($this->downstream);
                if($img) {
                    imagejpeg($img, $this->downstream, 90);
                    imagedestroy($img);
                }
            }
        }

		/*
		 * Set uploaded_file
		 * Local storage uploads will be allocated at the target destination
		 * External storage will be allocated to the temp directory
		 */
		$this->uploaded_file = G\name_unique_file($this->destination, $this->options['filenaming'], $this->fixed_filename);
		
		$this->source = [
			'filename'		=> $this->source_filename, // file.ext
			'name'				=> $this->source_name, // file
			'image_exif'	=> $this->source_image_exif, // exif-data array
			'fileinfo'		=> G\get_image_fileinfo($this->downstream), // fileinfo array
		];

		// Fix image orientation
		if($this->source_image_exif and $this->source_image_exif["Orientation"]) {
			$this->fixImageOrientation($this->downstream, $this->source_image_exif);
		}

		$uploaded = @rename($this->downstream, $this->uploaded_file);
		@unlink($this->downstream);

		if(file_exists($this->downstream)) {
			error_log("Warning: temp file " . $this->downstream . "wasn't removed.");
		}

		if(!$uploaded) {
			throw new UploadException("Can't move temp file to its destination", 400);
		}

		// For some PHP environments
		if(!$this->storage_id) {
			@chmod($this->uploaded_file, 0644);
		}

		$this->uploaded = array(
			'file'		=> $this->uploaded_file,
			'filename'	=> G\get_filename($this->uploaded_file),
			'name'		=> G\get_filename_without_extension($this->uploaded_file),
			'fileinfo'	=> G\get_image_fileinfo($this->uploaded_file)
		);

	}

	// Get available (supported) extensions
	public static function getAvailableImageFormats() {
		$formats = Settings::get('upload_available_image_formats');
		return explode(',', $formats);
	}

	// Failover since v3.8.12
	public static function getEnabledImageFormats() {
		return Image::getEnabledImageFormats();
	}

	/**
	 * validate_input aka "first stage validation"
	 * This checks for valid input source data
	 * @Exception 1XX
	 */
	protected function validateInput() {

		$check_missing = ["type", "source", "destination"];
		missing_values_to_exception($this, "CHV\UploadException", $check_missing, 100);

		// Validate $type
		if(!preg_match("/^(url|file)$/", $this->type)) {
			throw new UploadException('Invalid $type "'.$this->type.'"', 110);
		}

		// Handle flood
		$flood = self::handleFlood();
		if($flood) {
			throw new UploadException(strtr('Flood detected. You can only upload %limit% images per %time%', ['%limit%' => $flood['limit'], '%time%' => $flood['by']]), 130);
		}

		// Validate $source
		if($this->type == 'file') {
			if(count($this->source) < 5) { // Valid $_FILES ?
				throw new UploadException("Invalid file source", 120);
			}
		} else if($this->type == "url") {
			if(!G\is_image_url($this->source) && !G\is_url($this->source)) {
				throw new UploadException("Invalid image URL", 122);
			}
		}

		// Validate $destination
		if(!is_dir($this->destination)) { // Try to create the missing directory

			$base_dir = G\add_ending_slash(G_ROOT_PATH . explode('/', preg_replace('#'.G_ROOT_PATH.'#', '', $this->destination, 1))[0]);
			$base_perms = fileperms($base_dir);

			$old_umask = umask(0);
			$make_destination = mkdir($this->destination, $base_perms, true);
			chmod($this->destination, $base_perms);
			umask($old_umask);

			if(!$make_destination) {
				throw new UploadException('$destination '.$this->destination.' is not a dir', 130);
			}

		}

		// Can read $destination dir?
		if(!is_readable($this->destination)) {
			throw new UploadException("Can't read target destination dir", 131);
		}

		// Can write $destination dir?
		if(!is_writable($this->destination)) {
			throw new UploadException("Can't write target destination dir", 132);
		}

		// Fix $destination trailing
		$this->destination = G\add_ending_slash($this->destination);

	}

	/**
	 * Fetch the $source file
	 * @Exception 2XX
	 */
	protected function fetchSource() {

		// Set the downstream file
		$this->downstream = @tempnam(sys_get_temp_dir(), 'chvtemp');

		if(!$this->downstream || !@is_writable($this->downstream)) {
			$this->downstream = @tempnam($this->destination, 'chvtemp');
			if(!$this->downstream) {
				throw new UploadException("Can't get a tempnam", 200);
			}
		}

		if($this->type == 'file') {

			if($this->source['error'] !== UPLOAD_ERR_OK) {

				switch($this->source['error']) {
					case UPLOAD_ERR_INI_SIZE: // 1
						throw new UploadException('File too big', 201);
					break;
					case UPLOAD_ERR_FORM_SIZE: // 2
						throw new UploadException('File exceeds form max size', 201);
					break;
					case UPLOAD_ERR_PARTIAL: // 3
						throw new UploadException('File was partially uploaded', 201);
					break;
					case UPLOAD_ERR_NO_FILE: // 4
						throw new UploadException('No file was uploaded', 201);
					break;
					case UPLOAD_ERR_NO_TMP_DIR: // 5
						throw new UploadException('Missing temp folder', 201);
					break;
					case UPLOAD_ERR_CANT_WRITE: // 6
						throw new UploadException('System write error', 201);
					break;
					case UPLOAD_ERR_EXTENSION: // 7
						throw new UploadException('The upload was stopped', 201);
					break;
				}

			}

			if(!@rename($this->source['tmp_name'], $this->downstream)) {
				throw new UploadException("Can't move temp file to the target upload dir", 203);
			}

		} else if($this->type == "url") {
			try {
				G\fetch_url($this->source, $this->downstream);
			} catch(Exception $e) {
				throw new UploadException($e->getMessage(), 202);
			}
		}

		$this->source_filename = basename($this->type == "file" ? $this->source["name"] : $this->source);

	}

	protected function fixImageOrientation($image_filename, $exif) {
		if($exif['Orientation'] == 1) return;
		switch($this->extension) {
			case 'jpg':
				$image = imagecreatefromjpeg($image_filename);
			break;
		}
		switch($exif['Orientation']) {
			case 3:
				$image = imagerotate($image, 180, 0);
			break;
			case 6:
				$image = imagerotate($image, -90, 0);
			break;
			case 8:
				$image = imagerotate($image, 90, 0);
			break;
		}
		imagejpeg($image, $image_filename, 90);
	}

	/**
	 * validate_source_file aka "second stage validation"
	 * This checks for valid input source data
	 * @Exception 3XX
	 */
	protected function validateSourceFile() {

		// Nothing to do here
		if(!file_exists($this->downstream)) {
			throw new UploadException("Can't fetch target upload source (downstream)", 300);
		}

		$this->source_image_fileinfo = G\get_image_fileinfo($this->downstream);

		// file info?
		if(!$this->source_image_fileinfo) {
			throw new UploadException("Can't get target upload source info", 310);
		}

		// Valid image fileinto?
		if($this->source_image_fileinfo['width'] == '' || $this->source_image_fileinfo['height'] == '') {
			throw new UploadException("Invalid image", 311);
		}

		// Available image format?
		if(!in_array($this->source_image_fileinfo['extension'], self::getAvailableImageFormats())) {
			throw new UploadException("Unavailable image format", 313);
		}

		// Enabled image format?
		if(!in_array($this->source_image_fileinfo['extension'], $this->options['allowed_formats'])) {
			throw new UploadException(sprintf("Disabled image format (%s)", $this->source_image_fileinfo['extension']), 314);
		}

		// Mime
		if(!$this->isValidImageMime($this->source_image_fileinfo["mime"])) {
			throw new UploadException("Invalid image mimetype", 312);
		}

		// Size
		if(!$this->options['max_size']) {
			$this->options['max_size'] = self::getDefaultOptions()['max_size'];
		}
		if($this->source_image_fileinfo["size"] > $this->options["max_size"]) {
			throw new UploadException("File too big - max " . G\format_bytes($this->options["max_size"]), 313);
		}

		// BMP?
		if($this->source_image_fileinfo['extension'] == 'bmp') {
			$this->ImageConvert = new ImageConvert($this->downstream, "png", $this->downstream);
			$this->downstream = $this->ImageConvert->out;
			$this->source_image_fileinfo = G\get_image_fileinfo($this->downstream);
		}

	}

	// Handle flood uploads
	protected static function handleFlood() {

		$logged_user = Login::getUser();

		if(!getSetting('flood_uploads_protection') || $logged_user['is_admin']) {
			return FALSE;
		}

		$flood_limit = [];
		foreach(['minute', 'hour', 'day', 'week', 'month'] as $v) {
			$flood_limit[$v] = getSetting('flood_uploads_' . $v);
		}

		try {
			$db = DB::getInstance();
			$flood_db = $db->queryFetchSingle(
			"SELECT
				COUNT(IF(image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE), 1, NULL)) AS minute,
				COUNT(IF(image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR), 1, NULL)) AS hour,
				COUNT(IF(image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY), 1, NULL)) AS day,
				COUNT(IF(image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 WEEK), 1, NULL)) AS week,
				COUNT(IF(image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MONTH), 1, NULL)) AS month
			FROM ".DB::getTable('images')." WHERE image_uploader_ip='".G\get_client_ip()."' AND image_date_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MONTH)");
		} catch(Exception $e) {} // Silence

		$is_flood = FALSE;
		$flood_by = '';
		foreach(['minute', 'hour', 'day', 'week', 'month'] as $v) {
			if($flood_limit[$v] > 0 and $flood_db[$v] >= $flood_limit[$v]) {
				$flood_by = $v;
				$is_flood = TRUE;
				break;
			}
		}

		if($is_flood) {
			if(getSetting('flood_uploads_notify') and !$_SESSION['flood_uploads_notify'][$flood_by]) {
				try {
					$message = strtr('Flooding IP <a href="'.G\get_base_url('search/images/?q=ip:%ip').'">%ip</a>', ['%ip' => G\get_client_ip()]) . '<br>';
					if($logged_user) {
						$message .= 'User <a href="'.$logged_user['url'].'">'.$logged_user['name'].'</a><br>';
					}
					$message .= '<br>';
					$message .= '<b>Uploads per time period</b>'."<br>";
					$message .= 'Minute: '.$flood_db['minute']."<br>";
					$message .= 'Hour: '.$flood_db['hour']."<br>";
					$message .= 'Week: '.$flood_db['day']."<br>";
					$message .= 'Month: '.$flood_db['week']."<br>";
					system_notification_email(['subject' => 'Flood report IP '. G\get_client_ip(), 'message' => $message]);
					$_SESSION['flood_uploads_notify'][$flood_by] = true;
				} catch(Exception $e) {} // Silence
			}

			return ['flood' => TRUE, 'limit' => $flood_limit[$flood_by], 'count' => $flood_db[$flood_by], 'by' => $flood_by];
		}

		return FALSE;
	}

	protected function isValidImageMime($mime) {
		return preg_match("@image/(gif|pjpeg|jpeg|png|x-png|bmp|x-ms-bmp|x-windows-bmp)$@", $mime);
	}

	protected function isValidNamingOption($string) {
		return in_array($string, array("mixed", "random", "original"));
	}
}

class UploadException extends Exception {}
