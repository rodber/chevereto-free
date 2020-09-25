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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilterIterator;
use FilesystemIterator;
use LogicException;

class Import
{
    const DEBUG = 1;
    const PATH = G_APP_PATH . 'importer/';
    const PATH_JOBS = self::PATH . 'jobs/';
    const CHUNK_SIZE = 500; // How many items to iterate (directoryIterator)
    const METADATA_KEY_TYPES = ['album' => 'albumData', 'user' => 'userData', 'image' => 'imageData'];

    protected static $imageExtensions;

    protected static $imageExtensionsRegex;

    protected static $max_execution_time;

    public $path;

    /**
     * [root => users] Parse root folders as /user/album?/file.jpg
     * [root => albums] Parse root folders as /album/file.jpg
     * [root => plain] Don't parse folders /file.jpg
     * @var string[]
     */
    public $options = ['root' => 'users'];
    protected $logFile;
    protected $import;
    protected $increment;
    protected $components;

    public function __construct() // $path, $thread=1
    {
        if (!isset(static::$max_execution_time)) {
            @set_time_limit(60);
            @ini_set('max_execution_time', 60);
            static::$max_execution_time = ini_get('max_execution_time');
            static::$imageExtensions = Image::getEnabledImageFormats();
            static::$imageExtensionsRegex = '/\.(' . implode('|', static::$imageExtensions) . ')$/i';
        }
    }

    public static function refresh()
    {
        $db = DB::getInstance();
        $db->query('UPDATE ' . DB::getTable('imports') . ' SET import_status = "working" WHERE import_continuous = 1 AND DATE_ADD(import_time_updated, INTERVAL 1 MINUTE) <= UTC_TIMESTAMP();');
        $db->exec();
    }

    public static function autoJobs()
    {
        return DB::get('imports', ['continuous' => 1, 'status' => 'working'], 'AND', ['field' => 'time_updated', 'order' => 'asc']);
    }

    public static function imageExtensionsRegex()
    {
        return static::$imageExtensionsRegex;
    }
    /**
     * @return Array parsedImport
     */
    public function get()
    {
        if ($this->import = static::getSingle($this->id)) {
            $this->path = $this->import['path'];
            $this->options = $this->import['options'] ? unserialize($this->import['options']) : null;
            $this->parsedImport = array_merge($this->import, ['options' => $this->options]);
            return $this->parsedImport;
        } else {
            throw new Exception('Import ID ' . $this->id . 'not found', 100);
        }
    }
    /**
     * @return void
     */
    public function checkPath()
    {
        $this->path = G\sanitize_path_slashes(rtrim($this->path, '/'));
        $rootPath = G\sanitize_path_slashes(rtrim(G_ROOT_PATH, '/'));
        if (stream_resolve_include_path($this->path) == false) {
            throw new Exception("Target path $this->path doesn't exists", 100);
        }
        $message = "Target path $this->path can't be used for importing";
        if ($this->path == rtrim(CHV_PATH_IMAGES, '/')) {
            throw new Exception("$message (image upload path)", 101);
        }
        if ($this->path == $rootPath) {
            throw new Exception("$message (application root path)", 101);
        }
        if (G\starts_with($this->path, $rootPath)) {
            throw new Exception("$message (application folder ancestor)", 103);
        }
        if (G\starts_with($rootPath . '/importing', $this->path)) {
            throw new Exception("$message (automatic importing path)", 104);
        }
    }
    public function delete()
    {
        $import = static::getSingle($this->id);
        if ($import['continuous'] == 1) {
            throw new LogicException("Import of type continuous can't be deleted");
        }
        DB::delete('importing', ['import_id' => $this->id]);
        return DB::delete('imports', ['id' => $this->id]);
    }
    /**
     * @return import id
     */
    public function add()
    {
        $this->checkPath();
        if (!(new FilesystemIterator($this->path))->valid()) {
            throw new Exception("$this->path is empty", 101);
        }
        if ($get = static::getSingle($this->path, 'path')) {
            throw new Exception('Import ID ' . $get['id'] . ' is blocking the addition of a new importer job under the ' . $this->path . ' path', 102);
        }
        $this->id = DB::insert('imports', [
            'time_created' => G\datetimegmt(),
            'path' => $this->path,
            'options' => $this->options ? serialize($this->options) : null,
            'status' => 'queued',
        ]);
        return $this->id;
    }
    /**
     * Static aux helper for get stuff
     */
    public static function getSingle($var, $by = 'id')
    {
        $db = DB::getInstance();
        switch ($by) {
            case 'id':
                $where = 'import_id=:var';
                break;
            case 'path':
                $where = "import_path=:var AND import_status NOT IN ('completed', 'canceled')";
                break;
        }
        $db->query("SELECT * FROM " . DB::getTable('imports') . " WHERE $where LIMIT 1;");
        $db->bind(':var', $var);
        if ($import = $db->fetchSingle()) {
            return DB::formatRows($import);
        }
    }

    public static function getContinuous()
    {
        if ($all = DB::get('imports', ['continuous' => 1])) {
            $format = DB::formatRows($all);
            foreach ($format as &$v) {
                $v['options'] = $v['options'] ? unserialize($v['options']) : null;
            }
            return $format;
        }
    }

    public static function getOneTime()
    {
        if ($all = DB::get('imports', ['continuous' => 0])) {
            $format = DB::formatRows($all);
            foreach ($format as &$v) {
                $v['options'] = $v['options'] ? unserialize($v['options']) : null;
            }
            return $format;
        }
    }

    public function edit($values = null)
    {
        if (isset($values['options'])) {
            $values['options'] = serialize($values['options']);
        }
        $values['time_updated'] = G\datetimegmt();
        DB::update('imports', $values, ['id' => $this->id]);
    }
    
    protected function getImportingLock($pathName)
    {
        $this->logProcess("About to get DB importing lock for $pathName");
        if ($importing = DB::get('importing', ['path' => $pathName])[0]) {
            return DB::formatRows($importing);
        };
    }

    private function getLogPath()
    {
        return static::PATH_JOBS . $this->id . '/';
    }

    public function reset()
    {
        $this->edit([
            'status' => 'working',
            'users' => '0',
            'images' => '0',
            'albums' => '0',
            'errors' => '0',
            'started' => '0',
        ]);
        foreach (['errors', 'process'] as $type) {
            $filename = $this->getLogPath() . $type . '.txt';
            if (!file_exists($filename)) {
                continue;
            }
            if (!@unlink($filename)) {
                throw new Exception('File ' . $filename . " can't be removed", 100);
            }
        }
        $this->get();
    }

    public function resume()
    {
        if (!$this->import['continuous']) {
            throw new Exception('Only continuous importing can be resumed', 100);
        }
        $this->edit(['status' => 'working']);
        $this->get();
    }

    /**
     * Logger helper
     * Writes logs in importer/jobs/<id> with filenames like error.2.txt for
     * errors being catched by the thread id "2"
     */
    protected function log($message, $type)
    {
        $logPath = $this->getLogPath();
        if (stream_resolve_include_path($logPath) == false) {
            @mkdir($logPath, 0755, true);
        }
        // $logFile = $logPath . $type . '.' . $this->thread . '.txt';
        $logFile = $logPath . $type . '.txt';
        $message = time() . ' - ' . '[Thread #' . $this->thread . '] ' . $message;
        $fpc = file_put_contents($logFile, $message . "\n", FILE_APPEND);
        return $fpc !== false;
    }

    /**
     * Log process action, useful for debugging
     */
    public function logProcess($message, $logError = false)
    {
        if ($logError) {
            $this->log($message, 'errors');
        }
        $this->log($message, 'process');
    }

    /**
     * Log error
     */
    public function logError($message)
    {
        if ($this->import['errors'] == 0) {
            $this->logProcess('Adding "errors" flag to import row');
            $this->edit(['errors' => 1]);
        }
        return $this->log($message, 'errors');
    }
    /**
     * Issue or resume a target import job
     */
    public function process()
    {
        // only paused and queued reach here...
        if (in_array($this->import['status'], ['paused', 'canceled', 'completed'])) {
            throw new Exception('Import job ID ' . $this->id . ' is ' . $this->import['status'], 900);
            return;
        }
        $values = [];
        $this->metadata = [];
        $this->parsed = [];
        $this->logProcess('Import process started (job ID ' . $this->id . ')');
        $this->logProcess(str_repeat('=', 80));
        if ($this->import['started'] == 0) {
            $values['started'] = 1;
            $this->logProcess('Import row has been updated adding the "started" flag');
        }
        if ($this->import['status'] != 'working') {
            $values['status'] = 'working';
        }
        if ($values) {
            $this->edit($values);
            $this->get();
        }
        $killed = false;
        $i = 0;
        $parsedItems = 0;
        $cwd = null; // Current Working Directory
        $pwd = null; // Previous Working Directory
        foreach ($this->getItems() as $fileinfo) {
            if (in_array($this->import['status'], ['queued', 'working']) == false) {
                throw new Exception('Import job ID ' . $this->id . ' is ' . $this->import['status'], 900);
                return;
            }
            if ($i > 0) {
                $this->logProcess(str_repeat('-', 80));
                // Refresh $import on each loop, needed for hot editing
                $this->get();
            }
            if ($parsedItems > static::CHUNK_SIZE - 1 || isSafeToExecute(static::$max_execution_time) == false) {
                $abortMessage = ($parsedItems > static::CHUNK_SIZE - 1) ? 'Chunk limit reached (' . static::CHUNK_SIZE . ')' : 'About to run out of time';
                $this->logProcess("$abortMessage, breaking iteration now");
                $killed = true;
                break;
            }
            $pathHandle = null;
            $insertId = null;
            $parsed = false;
            $i++;
            $this->setParse(null);
            $pathName = $fileinfo->getPathName();
            $this->logProcess("Current iteration: $pathName");
            if (!file_exists($pathName)) {
                $this->logProcess("PathName is gone, continue iteration");
                continue;
            }
            if ($fileinfo->isFile()) {
                // File already locked
                if ($lock = static::getImportingLock($pathName)) {
                    $this->logProcess("Concurrency: $pathName is locked by another process, continue iteration");
                    continue;
                } else {
                    if ($fileinfo->isWritable()) {
                        // Insert DB lock
                        try {
                            DB::insert('importing', [
                                'import_id' => $this->id,
                                'path' => $pathName,
                                'content_type' => 'image',
                                'content_id' => 0,
                            ]);
                        } catch (Exception $e) {
                            $this->logProcess("Unable to insert DB lock for $pathName: " . $e->getMessage() . ', breaking iteration');
                            $killed = true;
                            break;
                        }
                    }
                }
            }
            if (!file_exists($pathName)) {
                $this->logProcess("PathName is gone!, continue iteration");
                continue;
            }
            if (!$fileinfo->isWritable()) {
                $this->logProcess("Path $pathName is not writable, the job #" . $this->id ." must be canceled", true);
                $this->edit(['status' => 'canceled', 'errors' => '1']);
                $this->logProcess('Updating importing status to canceled (the error must be addressed)');
                $killed = true;
                break;
            }
            $component = $this->getComponent($fileinfo);
            $this->parseComponent($component);
            if ($this->parse == null) {
                $this->logProcess('No parse applicable, continue iteration');
                continue;
            }
            // For images, we remove the file.ext part
            if ($fileinfo->isFile() && is_array($this->components)) {
                array_pop($this->components);
            }
            // Analyze $cwd (at this point, containing previous scanned dir)
            if ($cwd !== null) {
                $pwd = $cwd;
                $this->logProcess("Previous working directory is: $pwd");
            }
            if ($fileinfo->isDir()) {
                $cwd = $pathName;
            } else {
                $cwd = $fileinfo->getPath(); // no filename
            }
            $this->logProcess("Current working directory is: $cwd");
            /**
             * On directory change, check and delete the already parsed directories
             */
            if ($pwd && $pwd != $cwd) {
                $this->logProcess('Directory changed, about to detect if the previous directory should be removed or not');
                $delete_dir = null;
                // Detect kind of jump
                $pwd_explode = explode('/', ltrim($pwd, '/'));
                $cwd_explode = explode('/', ltrim($cwd, '/'));
                $cnt_pwd = count($pwd_explode);
                $cnt_cwd = count($cwd_explode);
                switch (true) {
                    case $cnt_pwd > $cnt_cwd:
                        $delete_dir = $pwd;
                        $this->logProcess("$delete_dir should be removed");
                        break;
                    case $cnt_pwd < $cnt_cwd:
                        $this->logProcess('Entering sub-directory, nothing to remove yet');
                        break;
                    case $cnt_pwd == $cnt_cwd && $pwd != $cwd:
                        $this->logProcess("Entering sibling directory, $pwd should be removed");
                        $delete_dir = $pwd;
                        break;
                }
                if ($delete_dir) {
                    $this->removeDir($delete_dir);
                }
            }
            /**
             * Flatenize deeps, used to ignore sub-directories beyond the base
             * structure.
             */
            if ($this->options['root'] == 'plain') {
                $pathHandle = null;
            } else {
                $pathHandle = rtrim($this->path, '/') . '/'; // The actual path used for lock, relative to importing path
                if (strpos($component, '/') !== false) { // /some/dir/
                    // /0/1/2/3/n...
                    switch ($this->options['root']) {
                        case 'users': // /0:user/1:album/<ignore>
                            if ($this->components[2] == null) {
                                $pathHandle .= implode('/', $this->components);
                            } else {
                                $pathHandle .= $this->components[0] . '/' . $this->components[1];
                                $this->logProcess("Extra sub-directory structure detected, path handle has been capped to 2 levels");
                            }
                            break;
                        case 'albums':
                            $pathHandle .= $this->components[0];
                            $this->logProcess("Extra sub-directory structure detected, path handle has been capped to 1 level");
                            break;
                    }
                } else { // No sub-dirs here, just files in /
                    $this->logProcess("Plain directory structure detected in component");
                    if ($fileinfo->isFile()) {
                        $pathHandle = null; // file.ext -> null
                    }
                    // Why this??
                    if ($fileinfo->isDir()) {
                        $pathHandle .= $component; // /dir
                    }
                }
                $this->logProcess('Path handle is: ' . ($pathHandle ?: 'null'));
            }
            /**
             * If we are handling a folder, check for any locks preventing dir
             * parsing
             */
            if ($pathHandle) {
                if ($lock = static::getImportingLock($pathHandle)) {
                    $this->logProcess("Path handle $pathHandle is already locked in DB");
                    /**
                     * No content id: The lock is being created. Terminate.
                     */
                    if ($lock['content_id'] == 0) {
                        $this->logProcess("Content id has not been set, another process is working in this same path, delaying operation");
                        die();
                        break;
                    }
                    /**
                     * Content id: This folder has been parsed. Get the content
                     * id + type associated to this dir
                     */
                    $content_id = $lock['content_id'];
                    $content_type = $lock['content_type'];
                    $this->logProcess("Content ID ($content_type): $content_id (taken from DB lock)");
                } else {
                    /**
                     * Note: No image should be here anyway...
                     */
                    if ($this->parse == 'image') {
                        $this->logProcess("This shouldn't be loged!!!!! PANIC!");
                        break;
                    }
                    /**
                     * Try to create the lock AND parse path contents
                     */
                    try {
                        $this->logProcess("Path handle $pathHandle is not locked, about to create DB lock for it");
                        // Insert DB lock
                        $lockId = DB::insert('importing', [
                            'import_id' => $this->id,
                            'path' => $pathHandle,
                            'content_type' => $this->parse,
                            'content_id' => 0, // dummy
                        ]);
                        $this->logProcess('DB lock inserted (' . $lockId . '), about to parse directory as ' . $this->parse);
                        $this->parseMetadata($cwd . '/metadata.json');
                        // TODO: Always parse metadata updates (if needed)
                        // Switch depending on dir kind
                        switch ($this->parse) {
                            case 'user':
                                // By default we look for matching users...
                                $userLookup = true;
                                $username = basename($pathHandle);
                                $username_max_length = Settings::get('username_max_length');
                                $username_min_length = Settings::get('username_min_length');
                                // Replace spaces
                                $usernameClean = preg_replace('/\s+/', '_', $username);
                                // Get only \w
                                $usernameClean = preg_replace('/\W/', null, $usernameClean);
                                // Make sure to fullfill the limit
                                $usernameClean = substr($usernameClean, 0, $username_max_length);
                                // Add some padding
                                if (strlen($usernameClean) < $username_min_length) {
                                    $usernameClean .= '_' . G\random_string($username_min_length - $usernameClean);
                                }
                                // Folder name doesn't satisfy a valid username string
                                if ($username != $usernameClean) {
                                    $this->logProcess("Username $username is invalid username string, switching to $usernameClean");
                                    // Don't look, just create a new user
                                    $userLookup = false;
                                }
                                $parsed = array_merge([
                                    'username' => $username,
                                    'registration_ip' => '127.0.0.1',
                                ], $this->parsed);
                                // If username exists, assing its $content_id
                                if ($userLookup && $user = User::getSingle($username, 'username')) {
                                    $this->logProcess("Username $username already exists");
                                    $insertId = $user['id'];
                                    if ($this->parsed !== []) {
                                        $this->logProcess("About to update $username ($insertId) with parsed data " . var_export($this->parsed, true));
                                        User::update($insertId, $this->parsed);
                                        $this->logProcess("Updated parsed user metadata");
                                    }
                                } else {
                                    // Make sure to insert a new user
                                    $u = 0;
                                    while (User::getSingle($usernameClean, 'username')) {
                                        $this->logProcess("Must try a different username as $usernameClean already exists");
                                        // It strips the number previously appended, so we get user1, user2, and so on.
                                        if ($u > 0) {
                                            $usernameClean = G\str_replace_last($u, null, $usernameClean);
                                        }
                                        // Soon as this gets too big, we trim the last $usernameClean char
                                        if (strlen($usernameClean . $u) > $username_max_length) {
                                            $usernameClean = substr($usernameClean, 0, -1);
                                        }
                                        $u++;
                                        $usernameClean .= $u;
                                        $parsed['username'] = $usernameClean;
                                    }
                                    $this->logProcess("About to insert user $usernameClean");
                                    $insertId = User::insert($parsed);
                                    $this->logProcess("Username $usernameClean (id $insertId) inserted");
                                    $user = User::getSingle($insertId, 'id');
                                }
                                if ($user && $this->metadata['profileImages']) {
                                    try {
                                        // Insert user assets (profile images)
                                        foreach ($this->metadata['profileImages'] as $k => $v) {
                                            $userAsset = [
                                                'name' => 'asset.jpg',
                                                'type' => 'image/jpeg', // dummy
                                                'tmp_name' => $pathName . '/.assets/' . $v,
                                                'error' => 0,
                                                'size' => 1,
                                            ];
                                            $this->logProcess("Uploading user $k image");
                                            User::uploadPicture($user, $k, $userAsset);
                                        }
                                    } catch (Exception $e) {
                                        $this->logProcess("Failed to upload user $k: " . $e->getMessage());
                                    }
                                }
                                break;
                            case 'album':
                                $albumName = ltrim(basename($pathHandle), '/');
                                // Username = Album parent dir
                                $user_lock = static::getImportingLock(dirname($pathHandle));
                                $user_id = $user_lock ? $user_lock['content_id'] : null;
                                $db = DB::getInstance();
                                $query = 'SELECT album_id FROM ' . DB::getTable('albums') . ' WHERE album_name = :name AND ';
                                if ($user_id) {
                                    $query .= 'album_user_id = :user_id';
                                } else {
                                    $query .= 'album_user_id IS NULL';
                                }
                                $query .= ' ORDER BY album_id DESC;';
                                $db->query($query);
                                $db->bind(':name', $albumName);
                                if ($user_id) {
                                    $db->bind(':user_id', $user_id);
                                }
                                $album = $db->fetchSingle();
                                if ($album) {
                                    $insertId = $album['album_id'];
                                    $this->logProcess("Album $albumName already exists (id $insertId)");
                                    // Album::update($insertId, $this->parsed);
                                    $this->logProcess("Updated parsed album metadata");
                                } else {
                                    $parsed = array_merge([
                                        'name' => $albumName,
                                        'user_id' => $user_id,
                                        'privacy' => 'public',
                                        'description' => '',
                                        'password' => null,
                                        'creation_ip' => '127.0.0.1'
                                    ], $this->parsed);
                                    $this->logProcess('About to insert album "'  . $parsed['name'] . '" under user_id ' . ($user_id ?: 'guest'));
                                    $insertId = Album::insert($parsed);
                                    $this->logProcess("Album $username inserted (id $insertId)");
                                }
                                break;
                        }
                        // Update lock content_id
                        $this->logProcess("About to update importing table (current job)");
                        DB::update('importing', ['content_id' => $insertId], ['id' => $lockId]);
                        $this->logProcess("Importing table updated");
                        // continue;
                    } catch (Exception $e) {
                        $this->logProcess("Process interrupted when parsing $pathHandle, check the error log");
                        $this->logError('Exception ' . $e->getMessage() . ' thrown when parsing directory ' . $pathHandle);
                        // If $lockId == insertion failed (user or album)
                        if ($lockId) {
                            $this->logProcess("Unable to parse directory, about to release DB lock ($lockId)");
                            DB::delete('importing', ['id' => $lockId]);
                            $this->logProcess("DB lock ($lockId) released");
                        } else {
                            $this->logProcess("Unable to insert DB lock for $pathHandle: " . $e->getMessage() . ', continue iteration');
                        }
                        continue;
                    }
                }
            }
            /**
             * Image parsing goes now...
             */
            if ($this->parse == 'image') {
                $this->logProcess('About to parse image: ' . $pathName);
                // Forged $_FILES for Image::uploadToWebsite()
                $parsed = [
                    'name' => $fileinfo->getFilename(),
                    'type' => 'image/jpeg', // dummy
                    'tmp_name' => $pathName,
                    'error' => 0,
                    'size' => 1, // $fileinfo->getSize() sometimes fails...
                ];
                $user_id = null;
                $params = [];
                // Has a parent context (user, user/album OR album)
                // Note: $content_id has being set from DB lock data.
                if ($pathHandle && $content_type && $content_id) {
                    $this->logProcess("Using DB lock for content context data (id and type)");
                    if ($content_type == 'user') {
                        $user_id = $content_id;
                    } else {
                        $params['album_id'] = $content_id;
                        $album = Album::getSingle($content_id, false, false);
                        if ($album['user_id']) {
                            $user_id = $album['user_id'];
                        }
                    }
                }
                try {
                    $insertId = Image::uploadToWebsite($parsed, $user_id, $params, false, '127.0.0.1');
                    $this->logProcess("Image ID $insertId inserted");
                    // Parse image date only after image insert
                    $this->parseMetadata(G\change_pathname_extension($pathName, 'json'));
                    // $this->metadata contains categoryId // category {}
                    if ($this->parsed['category_id'] == false) {
                        $this->logProcess("No implicit categoryId property found, about to check category metadata object");
                        if ($urlKey = $this->metadata['category']['urlKey']) {
                            $this->logProcess("Explicit urlKey property declared, determine its category ID (create if doesn't exists)");
                            if ($categoryId = DB::get('categories', ['url_key' => $this->metadata['category']['urlKey']])[0]['category_id']) {
                                $this->logProcess("Category ID set: $categoryId");
                            } else {
                                $category = [
                                    'url_key' => $urlKey,
                                    'name' =>  $this->metadata['category']['name'] ?: $urlKey,
                                    'description' => $this->metadata['category']['description'] ?: null
                                ];
                                try {
                                    $categoryId = DB::insert('categories', $category);
                                    $this->logProcess("Category ID: $categoryId created");
                                } catch (Exception $e) {
                                    $this->logProcess("Unable to create category $urlKey: " . $e->getMessage(), true);
                                } // sshhh
                            }
                            if ($categoryId) {
                                $this->parsed['category_id'] = $categoryId;
                            }
                        }
                    }
                    if ($this->parsed) {
                        Image::update($insertId, $this->parsed);
                        $this->logProcess("Image updated with parsed metadata");
                    }
                    // $this->parse updates image info
                } catch (Exception $e) {
                    if ($e->getCode() == 666) {
                        $this->logProcess($e->getMessage());
                    } else {
                        $this->logProcess('Failed to insert image, exception thrown: ' . $e->getMessage());
                        $this->logError("Image insertion failed for $pathName: " . $e->getMessage());
                        if (stream_resolve_include_path($pathName) && @unlink($pathName) == false) {
                            $this->logProcess("Failed to remove $pathName from importing path", true);
                        }
                        $this->logProcess("Image $pathName removed from importing path", true);
                    }
                }
            }
            if ($insertId) {
                $this->logProcess('Inserted content, items++');
                DB::increment('imports', [$this->parseGroup => '+1'], ['id' => $this->id]);
                $this->edit(); // updates timestamp
                $parsedItems++;
            }
        } // foreach ($this->getItems() as $fileinfo) {
        // Nothing left to parse, complete the process and wipe the path
        if ($killed == false && ($i == 0 || $parsedItems == 0)) {
            $this->logProcess('Nothing parsed in ' . $this->path);
            try {
                $this->edit(['status' => 'completed']);
                DB::delete('importing', ['import_id' => $this->id]);
                $this->logProcess('DB status changed to completed');
                if ($this->import['continuous']) {
                    $this->logProcess('DB status should be changed to "working" to keep this job alive');
                }
            } catch (Exception $e) {
                $this->logProcess('Error updating DB: ' . $e->getMessage(), true);
            }
            if ($this->import['continuous']) {
                if ($this->removeDir($this->path, false) == false) {
                    $this->logProcess('Unable to remove ' . $this->path . ' contents', true);
                }
            } else {
                if ($this->removeDir($this->path) == false) {
                    $this->logProcess('Unable to remove ' . $this->path, true);
                }
            }
        }
        $this->logProcess('Chunked process ended' . ($killed ? ' (killed)' : null));
        $this->logProcess(str_repeat('=', 80));
    }

    public function getItems($path = null)
    {
        if ($path == null) {
            $path = $this->path;
        }
        $iterator = new RecursiveDirectoryIterator($path);
        $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
        return new ImporterFilterIterator(new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));
    }

    protected function setParse($parse)
    {
        $this->parse = $parse;
        $this->parseGroup = $parse == null ? null : ($this->parse . 's');
        if ($parse !== null) {
            $this->logProcess("Parse has been set to: $parse");
        }
    }

    /**
     * Determine the item compontent
     *
     * @param $filepath string Filepath
     */
    public function getComponent($filepath)
    {
        $component = G\str_replace_first($this->path, null, (string) $filepath);
        $return = ltrim(rtrim($component, '/'), '/');
        $this->logProcess("Component is: $return");
        return $return;
    }

    /**
     * Parse path component
     *
     * @param string $component Path section (without the $importer path)
     */
    public function parseComponent($component)
    {
        $this->logProcess("About to parse component for $component (root: " . $this->options['root'] . ')');
        $this->components = explode('/', $component); // /0/1/2/3/n...
        $this->setParse(null);
        if (preg_match(static::$imageExtensionsRegex, $component) == true) {
            $this->setParse('image');
            return;
        }
        $component_cnt = count($this->components);
        switch ($this->options['root']) {
            case 'users':
                switch ($component_cnt) {
                    case 1:
                        $this->setParse('user');
                        break;
                    case 2:
                        $this->setParse('album');
                        break;
                }
                break;
            case 'albums':
                switch ($component_cnt) {
                    case 1:
                        $this->setParse('album');
                        break;
                }
                break;
        }
        if ($this->parse == null) {
            $this->logProcess("Parse is null");
        }
    }
    /**
     * Recursive directory remove (files and folders)
     *
     * @param string $dir Directory to wipe
     * @return mixed TRUE if the directory was wiped *or empty. Array of items
     * failed to delete
     */
    protected function removeDir($dir, $removeSelf = true)
    {
        $contents = !$removeSelf ? ' contents' : '';
        $failed = [];
        $this->logProcess("About to remove $dir directory$contents (recursively)...");
        if (stream_resolve_include_path($dir) == false) {
            $this->logProcess("The directory doesn't exists, no need to remove it");
            return true;
        }
        $isDirEmpty = !(new FilesystemIterator($dir))->valid();
        if ($isDirEmpty) {
            $this->logProcess("The directory is already empty, no need to iterate its contents");
            if ($removeSelf) {
                $res = @rmdir($dir);
            } else {
                $res = true;
            }
        } else {
            $this->logProcess("The directory is not empty, prepate to iterate and remove its contents");
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $filepath = $fileinfo->getRealPath();
                $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
                $type = $fileinfo->isDir() ? ('directory' . $contents) : 'file';
                $this->logProcess("Loop: Removing $type $filepath ($todo)");
                $res = @$todo($filepath);
                if ($res == false) {
                    $this->logProcess("Unable to remove $filepath", true);
                    $failed[] = ['filepath' => $filepath, 'isDir' => $fileinfo->isDir()];
                }
            }
            if ($removeSelf) {
                $res = @rmdir($dir);
            } else {
                $res = true;
            }
        }
        if ($res == true) {
            $this->logProcess("Directory $dir$contents removed");
            return true;
        }
        return $failed;
    }

    /**
     * Metadata parsing, used to inject user, album and image data
     */
    public function parseMetadata($filename, $type = null)
    {
        $this->metadata = [];
        $this->parsed = [];
        if (stream_resolve_include_path($filename) == false) {
            // Nothing to do here!
            return;
        }
        if ($type == null) {
            $type = $this->parse;
        }
        if (array_key_exists($type, static::METADATA_KEY_TYPES) == false) {
            $this->logProcess("Error: Invalid type $type metadata key", true);
            return;
        } else {
            $metadataKey = static::METADATA_KEY_TYPES[$type];
        }
        if (is_readable($filename) == false) {
            $this->logProcess("File reading error: $filename is not readable", true);
            return;
        }
        if ($contents = @file_get_contents($filename)) {
            $this->logProcess("$filename readed");
            $metadata = json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logProcess("File format error: $filename contains invalid JSON", true);
                $metadata = null;
            }
        } else {
            $this->logProcess("Unable to read $filename", true);
            return;
        }
        if ($metadata = $metadata[$metadataKey]) {
            // [0 => 'TYPE', keys] Wow, such typing. Very modern.
            switch ($type) {
                case 'album':
                    $tr = [
                        'name' => [0 => 'string', 'title'],
                        'description' => [0 => 'string', 'description'],
                        'privacy' => [0 => 'string', ['privacy', 'type']],
                        'password' => [0 => 'string', ['privacy', 'password']],
                    ];
                    break;
                case 'user':
                    $tr = [
                        'name' => [0 => 'string', 'name'],
                        'email' => [0 => 'string', 'email'],
                        'website' => [0 => 'string', 'website'],
                        'bio' => [0 => 'string', 'bio'],
                        'facebook_username' => [0 => 'string', ['networks', 'facebook']],
                        'twitter_username' => [0 => 'string', ['networks', 'twitter']],
                        'timezone' => [0 => 'string', 'timezone'],
                        'language' => [0 => 'string', 'language'],
                        'is_private' => [0 => 'boolean', 'is_private'],
                        'is_manager' => [0 => 'boolean', 'is_manager'],
                        'is_admin' => [0 => 'boolean', 'is_admin'],
                    ];
                    break;
                case 'image':
                    $tr = [
                        'title' => [0 => 'string', 'title'],
                        'description' => [0 => 'string', 'description'],
                        'category_id' => [0 => 'integer', 'categoryId'],
                        'nsfw' => [0 => 'boolean', 'nsfw'],
                    ];
                    break;
            }
            $parsed = [];
            // date->timestamp must be handled as date + date_gmt
            // Assing the parse props based on the $tr array
            foreach ($tr as $metaProp => $metaValue) {
                $propValue = null;
                $propType = $metaValue[0];
                $val = $metaValue[1];
                if ($propValue = $metadata[is_array($val) ? $val[0] : $val]) {
                    if (is_array($val) && is_array($propValue)) {
                        unset($val[0]); // Get rid of the parent (already taken just above)
                        foreach ($val as $k => $v) {
                            if ($propValue[$v] == false) {
                                break;
                            }
                            $propValue = $propValue[$v];
                        }
                    }
                }
                if ($propValue) {
                    $gettype = gettype($propValue);
                    if ($gettype != $propType) {
                        $this->logProcess("Metadata error: Type $gettype provided, expected $propType for $metaProp");
                        continue;
                    }
                    $parsed[$metaProp] = $propValue;
                }
            }
            $this->metadata = $metadata;
            $this->parsed = $parsed;
        } else {
            $this->logProcess("Metadata error: Missing metakey $metadataKey");
        }
        return;
    }
}

// Accept images + folders
class ImporterFilterIterator extends FilterIterator
{
    protected $fileinfo;
    public function accept()
    {
        $this->fileinfo = $this->getInnerIterator()->current();
        if ($this->fileinfo->isFile() && (preg_match(Import::imageExtensionsRegex(), $this->fileinfo) == false || $this->filterAssets())) {
            return false;
        }
        if ($this->fileinfo->isDir() && $this->filterAssets()) {
            return false;
        }
        return true;
    }
    protected function filterAssets()
    {
        return $this->fileinfo->getBasename() == '.assets' || basename($this->fileinfo->getPath()) == '.assets';
    }
}
