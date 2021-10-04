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

if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
}

try {
    if (!is_null(getSetting('chevereto_version_installed')) and !Login::getUser()['is_admin']) {
        G\set_status_header(403);
        die('Request denied. You must be an admin to be here.');
    }
    if (!class_exists('ZipArchive')) {
        throw new Exception("PHP ZipArchive class is not enabled in this server");
    }
    if (!is_writable(G_ROOT_PATH)) {
        throw new Exception(sprintf("Can't write into root %s path", G\absolute_to_relative(G_ROOT_PATH)));
    }
    $update_temp_dir = CHV_APP_PATH_INSTALL . 'update/temp/';
    if (!isset($_REQUEST['action'])) {
        $doctitle = _s('Update in progress');
        $system_template = CHV_APP_PATH_CONTENT_SYSTEM . 'template.php';
        $update_template = dirname($update_temp_dir) . '/template/update.php';
        if (file_exists($update_template)) {
            ob_start();
            require_once($update_template);
            $html = ob_get_contents();
            ob_end_clean();
        } else {
            throw new Exception("Can't find " . G\absolute_to_relative($update_template));
        }
        if (!@require_once($system_template)) {
            throw new Exception("Can't find " . G\absolute_to_relative($system_template));
        }
    } else {
        $CHEVERETO = Settings::getChevereto();
        set_time_limit(300);
        switch ($_REQUEST['action']) {
            case 'ask':
                try {
                    $json_array = json_decode(G\fetch_url($CHEVERETO['api']['get']['info'], false, [
                        CURLOPT_REFERER => G\get_base_url()
                    ]), true);
                    $json_array['success'] = ['message' => 'OK'];
                } catch (Exception $e) {
                    throw new Exception(sprintf('Fatal error: %s', $e->getMessage()), 400);
                }
                break;
            case 'download':
                try {
                    $version = $_REQUEST['version'];
                    $context = stream_context_create([
                        'http'=> [
                            'method' => 'GET',
                            'header' => "User-agent: " . G_APP_GITHUB_REPO . "\r\n"
                            ]
                        ]);
                    $artifactUrl = 'https://github.com/'.G_APP_GITHUB_OWNER.'/'.G_APP_GITHUB_REPO.'/releases/download/'.$version.'/'.$version.'.zip';
                    $download = @file_get_contents($artifactUrl, false, $context);
                    if ($download === false) {
                        throw new Exception(
                            sprintf("Can't fetch " . G_APP_NAME . " v%s from GitHub", $version),
                            400
                        );
                    }
                    $github_json = json_decode($download, true);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        throw new Exception("Can't proceed with update procedure");
                    } else {
                        $zip_local_filename = 'chevereto_' . $version . '_' . G\random_string(24) . '.zip';
                        if (file_put_contents($update_temp_dir . $zip_local_filename, $download) === false) {
                            throw new Exception(_s("Can't save file"));
                        }
                        $json_array = [
                            'success' => [
                                'message'   => 'Download completed',
                                'code'      => 200
                            ],
                            'download' => [
                                'filename' => $zip_local_filename
                            ]
                        ];
                    }
                } catch (Exception $e) {
                    throw new Exception(
                        _s(
                            "Can't download %s",
                            $version == 'latest'
                                ? 'Chevereto-Free'
                                : ('v' . $version)
                        ) . ' (' . $e->getMessage() . ')'
                    );
                }

            break;
            case 'extract':
                $zip_file = $update_temp_dir . $_REQUEST['file'];
                if (!is_readable($zip_file)) {
                    throw new Exception('Missing ' . $zip_file . ' file', 400);
                }
                $zip = new \ZipArchive;
                if ($zip->open($zip_file) === true) {
                    try {
                        $toggle_maintenance = !getSetting('maintenance');
                        if ($toggle_maintenance) {
                            DB::update('settings', ['value' => $toggle_maintenance], ['name' => 'maintenance']);
                        }
                    } catch (Exception $e) {
                    }
                    $zip->extractTo($update_temp_dir);
                    $zip->close();
                    @unlink($zip_file);
                } else {
                    throw new Exception(_s("Can't extract %s", G\absolute_to_relative($zip_file)), 401);
                }
                // Recursive copy UPDATE -> CURRENT
                $source = $update_temp_dir . '/';
                $dest = G_ROOT_PATH;
                foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
                    $target = $dest . $iterator->getSubPathName();
                    $target_visible = G\absolute_to_relative($target);
                    if ($item->isDir()) {
                        if (!file_exists($target) and !@mkdir($target)) {
                            $error = error_get_last();
                            throw new Exception(_s("Can't create %s directory - %e", [
                                '%s' => $target_visible,
                                '%e' => $error['message']
                            ]), 402);
                        }
                    } else {
                        if (!@copy($item, $target)) {
                            $error = error_get_last();
                            throw new Exception(_s("Can't update %s file - %e", [
                                '%s' => $target_visible,
                                '%e' => $error['message']
                            ]), 403);
                        }
                        unlink($item);
                    }
                }

                // Remove working copy (UPDATE)
                $tmp = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($update_temp_dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($tmp as $fileinfo) {
                    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                    $todo($fileinfo->getRealPath());
                }
                try {
                    if ($toggle_maintenance) {
                        DB::update('settings', ['value' => 0], ['name' => 'maintenance']);
                    }
                } catch (Exception $e) {
                }

                $json_array['success'] = ['message' => 'OK', 'code' => 200];
                break;
        }
        // Inject any missing status_code
        if (isset($json_array['success']) and !isset($json_array['status_code'])) {
            $json_array['status_code'] = 200;
        }
        $json_array['request'] = $_REQUEST;
        G\Render\json_output($json_array);
    }
    die();
} catch (Exception $e) {
    if (!isset($_REQUEST['action'])) {
        Render\chevereto_die($e->getMessage(), "This installation can't use the automatic update functionality because this server is missing some crucial elements to allow Chevereto to perform the automatic update:", "Can't perform automatic update");
    } else {
        $json_array = G\json_error($e);
        $json_array['request'] = $_REQUEST;
        G\Render\json_output($json_array);
    }
}
