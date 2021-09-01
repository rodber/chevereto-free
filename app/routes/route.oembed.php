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

use function G\safe_html;

$route = function ($handler) {
    try {
        if ($handler->isRequestLevel(2)) {
            return $handler->issue404();
        }
        $viewer = CHV\Image::getUrlViewer('%id');
        $viewer = str_replace('/', '\/', $viewer);
        $regex = G\str_replace_last('%id', '(.*)', $viewer);
        $regex = G\str_replace_first('https:', 'https?:', $regex);
        $regex = G\str_replace_first('http:', 'https?:', $regex);
        if (!preg_match('#^' . $regex . '$#', $_GET['url'], $matches)) {
            G\set_status_header(403);
            die();
        }
        $id = CHV\getIdFromURLComponent($matches[1]);
        if ($id == false) {
            G\set_status_header(404);
            die();
        }
        $image = CHV\Image::getSingle($id, false, true, null);
        if (!$image) {
            G\set_status_header(404);
            die();
        }
        if (!$image['is_approved']) {
            G\set_status_header(403);
            die();
        }
        if (in_array($image['album']['privacy'], array('password', 'private', 'custom'))) {
            G\set_status_header(401);
            die();
        }
        if ($image['user']['is_private']) {
            unset($image['user']);
            $image['user'] = CHV\User::getPrivate();
        }
        $data = [
            'version' => '1.0',
            'type' => 'photo',
            'provider_name' => safe_html(CHV\Settings::get('website_name')),
            'provider_url' => G\get_base_url(),
            'title' => safe_html($image['title']),
            'url' => $image['display_url'],
            'web_page' => $image['url_viewer'],
            'width' => $image['width'],
            'height' => $image['height'],
        ];
        if (isset($image['user'])) {
            $data = array_merge($data, [
                'author_name' => safe_html($image['user']['username']),
                'author_url' => $image['user']['url'],
            ]);
        }
        $thumb = 'display_url';
        $maxWidth = isset($_GET['maxwidth']) ? intval($_GET['maxwidth']) : $image['width'];
        $maxHeight = isset($_GET['maxHeight']) ? intval($_GET['maxHeight']) : $image['height'];
        if ($image['display_width'] > $maxWidth || $image['display_height'] > $maxHeight) {
            $thumb = null;
            if (CHV\getSetting('upload_thumb_width') <= $maxWidth && CHV\getSetting('upload_thumb_height') <= $maxHeight) {
                $thumb = 'thumb';
            }
        }
        if ($thumb !== null) {
            if ($thumb == 'thumb') {
                $display_url = $image['thumb']['url'];
                $display_width = CHV\getSetting('upload_thumb_width');
                $display_height = CHV\getSetting('upload_thumb_height');
            } else {
                $display_url = $image['display_url'];
                $display_width = $image['display_width'];
                $display_height = $image['display_height'];
            }
            $data = array_merge($data, [
                'thumbnail_url' => $display_url,
                'thumbnail_width' => $display_width,
                'thumbnail_height' => $display_height,
            ]);
        }
        switch ($_GET['format']) {
            case 'xml':
                G\Render\xml_output(['oembed' => $data]);
            break;
            case 'json':
            default:
                G\Render\json_output($data);
            break;
        }
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
