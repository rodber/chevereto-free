<?php

/* --------------------------------------------------------------------

  G\ library
  https://g.chevereto.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

namespace G\Render;

use G;

/**
 * INCLUDE TAGS
 * ---------------------------------------------------------------------
 */

function include_theme_file($filename, $args=[])
{
    $file = G_APP_PATH_THEME . $filename;
    $override = G_APP_PATH_THEME . 'overrides/' . $filename;
    if (!file_exists($file)) {
        $file .= '.php';
        $override .= '.php';
    }
    if (file_exists($override)) {
        $file = $override;
    }
    if (file_exists($file)) {
        $GLOBALS['theme_include_args'] = $args;
        include($file);
        unset($GLOBALS['theme_include_args']);
    }
}

function include_theme_header()
{
    include_theme_file('header');
}

function include_theme_footer()
{
    include_theme_file('footer');
}

function get_theme_file_contents($filename)
{
    $file = G_APP_PATH_THEME . $filename;
    return file_exists($file) ? file_get_contents($file) : null;
}

/**
 * THEME DATA FUNCTIONS
 * ---------------------------------------------------------------------
 */

function get_theme_file_url($string)
{
    return BASE_URL_THEME . $string;
}

/**
 * ASSETS
 * ---------------------------------------------------------------------
 */

// Return app lib file url
function get_app_lib_file_url($string)
{
    return (defined('APP_G_APP_LIB_URL') ? APP_G_APP_LIB_URL : G_APP_LIB_URL) . $string;
}

// Returns the HTML input with the auth token
function get_input_auth_token($name='auth_token')
{
    return '<input type="hidden" name="'.$name.'" value="'. G\Handler::getAuthToken() . '">';
}


/**
 * NON HTML OUTPUT
 * ---------------------------------------------------------------------
 */

// Outputs the REST_API array to xml
function xml_output($data=[])
{
    error_reporting(0);
    //@ini_set('display_errors', false);
    if (ob_get_level() === 0 and !ob_start('ob_gzhandler')) {
        ob_start();
    }
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Content-Type:text/xml; charset=UTF-8");
    $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    if ($data['status_code']) {
        $out .= "<status_code>$data[status_code]</status_code>\n";
        if (!$data['status_txt']) {
            $data['status_txt'] = G\get_set_status_header_desc($data['status_code']);
        }
        $out .= "<status_txt>$data[status_txt]</status_txt>\n";
    }
    unset($data['status_code'], $data['status_txt']);
    if ($data !== []) {
        foreach ($data as $key => $array) {
            $out .= "<$key>\n";
            foreach ($array as $prop => $value) {
                $out .= "	<$prop>$value</$prop>\n";
            }
            $out .= "</$key>\n";
        }
    }
    echo $out;
    die();
}

// Procedural function to output an array to json
function json_output($data=[], $callback=null)
{
    error_reporting(0);
    //@ini_set('display_errors', false);
    if (ob_get_level() === 0 and !ob_start('ob_gzhandler')) {
        ob_start();
    }
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').'GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Content-type: application/json; charset=UTF-8');
    
    // Invalid json request
    if (!G\check_value($data) || (G\check_value($callback) and preg_match('/\W/', $callback))) {
        G\set_status_header(400);
        $json_fail = [
            'status_code' => 400,
            'status_txt' => G\get_set_status_header_desc(400),
            'error' => [
                'message' => 'no request data present',
                'code' => null
            ]
        ];
        die(json_encode($json_fail));
    }
    
    if ($data['status_code'] && !$data['status_txt']) {
        $data['status_txt'] = G\get_set_status_header_desc($data['status_code']);
    }
    
    $json_encode = json_encode($data);
    
    if (!$json_encode) { // Json failed
        G\set_status_header(500);
        $json_fail = [
            'status_code' => 500,
            'status_txt' => G\get_set_status_header_desc(500),
            'error' => [
                'message' => "data couldn't be encoded into json",
                'code' => null
            ]
        ];
        die(json_encode($json_fail));
    }
    G\set_status_header($data['status_code']);
    
    if (!is_null($callback)) {
        print sprintf('%s(%s);', $callback, $json_encode);
    } else {
        print $json_encode;
    }
    die();
}
