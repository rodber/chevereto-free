<?php

/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

$route = function ($handler) {
    try {
        if (!CHV\getSetting('enable_plugin_route') && !CHV\Login::isAdmin()) {
            return $handler->issue404();
        }
        $src = CHV\getSetting('sdk_pup_url') ?: G\get_base_url('sdk/pup.js');
        $src = G\str_replace_first('https:', '', $src);
        G\Render\include_theme_file('snippets/embed');
        $embed_tpl = G\get_global('embed_tpl');
        $embed = [];
        foreach ($embed_tpl as $kGroup => $vGroup) {
            foreach ($vGroup['options'] as $k => $v) {
                if ($k == 'bbcode-embed-medium') {
                    $k = null;
                }
                $embed[$k] = $v['label'];
            }
        }
        $tagAttrs = [
            'async' => '',
            'src' => $src,
            'data-url' => G\get_base_url('upload')
        ];
        $tagCode = '<script';
        foreach ($tagAttrs as $k => $v) {
            $tagCode .= ' ' .  $k;
            if ($v) {
                $tagCode .= '="' . $v .'"';
            }
        }
        $tagCode .= '></script>';
        $plugin = [
            'tagAttrs' => $tagAttrs,
            'tagCode' => $tagCode,
            'table' => [
                '%cClass' => 'chevereto-pup-container',
                '%bClass' => 'chevereto-pup-button',
                '%iClass' => 'chevereto-pup-button-icon',
                '%iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M76.7 87.5c12.8 0 23.3-13.3 23.3-29.4 0-13.6-5.2-25.7-15.4-27.5 0 0-3.5-0.7-5.6 1.7 0 0 0.6 9.4-2.9 12.6 0 0 8.7-32.4-23.7-32.4 -29.3 0-22.5 34.5-22.5 34.5 -5-6.4-0.6-19.6-0.6-19.6 -2.5-2.6-6.1-2.5-6.1-2.5C10.9 25 0 39.1 0 54.6c0 15.5 9.3 32.7 29.3 32.7 2 0 6.4 0 11.7 0V68.5h-13l22-22 22 22H59v18.8C68.6 87.4 76.7 87.5 76.7 87.5z"/></svg>',
            ],
            'vendors' => [
                'bbPress',
                'Discourse',
                'Discuz!',
                'Invision Power Board',
                'MyBB',
                'NodeBB',
                'ProBoards',
                'phpBB',
                'Simple Machines Forum',
                'Vanilla Forums',
                'vBulletin',
                'WoltLab',
                'XenForo'
            ],
            'palettes' => [
                'default' => ['#ececec','#000','#2980b9','#fff'],
                'clear' => ['inherit','inherit','inherit','#2980b9'],
                'turquoise' => ['#16a085','#fff','#1abc9c','#fff'],
                'green' => ['#27ae60','#fff','#2ecc71','#fff'],
                'blue' => ['#2980b9','#fff','#3498db','#fff'],
                'purple' => ['#8e44ad','#fff','#9b59b6','#fff'],
                'darkblue' => ['#2c3e50','#fff','#34495e','#fff'],
                'yellow' => ['#f39c12','#fff','#f1c40f','#fff'],
                'orange' => ['#d35400','#fff','#e67e22','#fff'],
                'red' => ['#c0392b','#fff','#e74c3c','#fff'],
                'grey' => ['#ececec','#000','#e0e0e0','#000'],
                'black' => ['#333','#fff','#666','#fff']
            ],
            'button' => '<div class="%cClass"><button id="pup-preview" class="%bClass %bClass--palette-default"><span class="%iClass">%iconSvg</span><span class="%tClass">' . _s('Upload images') . '</span></button></div>',
            'baseCss' => '.%cClass{display:inline-block;margin-top:5px;margin-bottom:5px}.%bClass{-webkit-transition:all .2s;-o-transition:all .2s;transition:all .2s;outline:0;border:none;cursor:pointer;border:1px solid rgba(0,0,0,.05);border-radius:.2em;padding:.5em 1em;font-size:12px;font-weight:700;text-shadow:none}.%bClass:hover{border-top-color:rgba(255,255,255,.1);border-right-color:rgba(0,0,0,.05);border-bottom-color:rgba(0,0,0,.1);border-left-color:rgba(0,0,0,.05);}.%iClass{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;-ms-flex-item-align:center;align-self:center;position:relative;height:1em;width:1em;margin-right:.25em}.%iClass img,.%iClass svg{width:1em;height:1em;bottom:-.125em;position:absolute}.%iClass svg{fill:currentColor}',
            'paletteCss' => '.%bClass--palette-%palette{color:%2;background:%1}.%bClass--palette-%palette:hover{background:%3;color:%4}',
            'embed' => $embed,
        ];
        $palette_css_rules = '';
        foreach ($plugin['palettes'] as $palette => $colors) {
            $paletteTable = $plugin['table'];
            $paletteTable['%palette'] = $palette;
            foreach ($colors as $index => $color) {
                $paletteTable['%' . ($index + 1)] = $color;
            }
            $palette_css_rules .= strtr($plugin['paletteCss'], $paletteTable);
        }
        foreach (['button', 'baseCss'] as  $v) {
            $plugin[$v] = strtr($plugin[$v], $plugin['table']);
        }
        $plugin['stylesheet'] = '<style type="text/css" id="chevereto-pup-style">' . $plugin['baseCss'] . $palette_css_rules . '</style>';
        $handler::setVar('plugin', $plugin);
        $handler::setVar('pre_doctitle', _s('Upload plugin'));
    } catch (Exception $e) {
        G\exception_to_error($e);
    }
};
