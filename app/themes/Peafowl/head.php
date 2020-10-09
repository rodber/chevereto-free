<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<!DOCTYPE HTML>
<html <?php echo CHV\Render\get_html_tags(); ?> prefix="og: http://ogp.me/ns#">

<head>
    <?php G\Render\include_theme_file('custom_hooks/head_open'); ?>
    <meta charset="utf-8">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#<?php echo get_theme_top_bar_color() == 'black' ? '000000' : 'FFFFFF'; ?>">
    <?php if (get_safe_html_meta_description()) {
    ?>
        <meta name="description" content="<?php echo get_safe_html_meta_description(); ?>">
    <?php
} ?>
    <?php if (function_exists('get_canonical') && get_canonical() && get_canonical() != CHV\get_current_url(true, ['lang'])) {
        ?>
        <link rel="canonical" href="<?php echo get_canonical(); ?>">
    <?php
    } ?>
    <title><?php echo get_safe_html_doctitle(); ?></title>

    <?php CHV\Render\include_peafowl_head(); ?>

    <link rel="shortcut icon" href="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>">
    <link rel="icon" type="image/png" href="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>" sizes="192x192">
    <link rel="apple-touch-icon" href="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>" sizes="180x180">

    <?php

    if (!is_maintenance()) {
        G\Render\include_theme_file('snippets/embed');
    }

    if (CHV\getSetting('theme_logo_height') !== null) {
        $logo_height = CHV\getSetting('theme_logo_height');
        echo '<style type="text/css">.top-bar-logo, .top-bar-logo img { height: ' . CHV\getSetting('theme_logo_height') . 'px; } .top-bar-logo { margin-top: -' . (CHV\getSetting('theme_logo_height') / 2) . 'px; } </style>';
    }

    $open_graph = [
        'type' => 'website',
        'url' => CHV\get_current_url(true, ['lang']),
        'title' => G\safe_html(CHV\getSetting('website_doctitle')),
        'image' => CHV\getSetting('homepage_cover_images')[0]['url'],
        'site_name' => get_safe_html_website_name(),
        'description' => get_safe_html_meta_description(),
    ];
    if (CHV\getSetting('facebook_app_id')) {
        $open_graph['fb:app_id'] = CHV\getSetting('facebook_app_id');
    }

    switch (true) {
        case function_exists('get_image') and G\is_route('image'):
            $open_graph_extend = [
                'type' => 'article',
                'title' => get_pre_doctitle(),
                // 'description'	=> get_image()['description'],
                'image' => get_image()['url'],
                'image:width' => get_image()['width'],
                'image:height' => get_image()['height'],
            ];
            if (get_image()['is_animated'] && get_image()['size'] < G\get_bytes('8 MiB')) {
                $open_graph_extend['type'] = 'video.other';
                $open_graph_extend['url'] = get_image()['url'];
            }
            break;
        case function_exists('get_album') and G\is_route('album'):
            $open_graph_extend = [
                'type' => 'article',
                'title' => get_pre_doctitle(),
                // 'description'	=> get_album()['description'] ?: get_album()['name'],
            ];
            if (in_array(get_album()['privacy'], ['public', 'private_but_link']) && get_list()->output_count) {
                $open_graph_extend = array_merge($open_graph_extend, [
                    'image' => get_list()->output_assoc[0]['display_url'],
                    'image:width' => get_list()->output_assoc[0]['display_width'],
                    'image:height' => get_list()->output_assoc[0]['display_height'],
                    'image:height' => get_album()['height'],
                ]);
            }
            break;
        case function_exists('get_user') and G\is_route('user'):
            $open_graph_extend = [
                'type' => 'profile',
                'title' => get_user()['name'],
                // 'description'	=> sprintf(is_user_images() ? _s("%s's Images") : _s("%s's Albums"), get_user()["name_short"]),
                'image' => get_user()['avatar']['url'],
            ];
            break;
        case function_exists('get_album') and G\is_route('album'):
            $open_graph_extend = [
                'title' => get_album()['name'],
                // 'description'	=> get_album()['description'],
            ];
            break;
    }
    if ($open_graph_extend) {
        $open_graph = array_merge($open_graph, $open_graph_extend);
    }
    foreach ($open_graph  as $k => $v) {
        if (!$v) {
            continue;
        }
        $prop = strpos($k, ':') !== false ? $k : "og:$k";
        echo '<meta property="' . $prop . '" content="' . G\safe_html($v, ENT_COMPAT) . '" />' . "\n";
    }

    // Set twitter card
    $twitter_card = [
        'card' => 'summary',
        'description' => function_exists('get_safe_html_meta_description') ? get_safe_html_meta_description() : null,
        'title' => G\str_replace_last(' - ' . get_safe_html_website_name(), null, get_safe_html_doctitle()),
        'site' => CHV\getSetting('twitter_account') ? ('@' . CHV\getSetting('twitter_account')) : null,
    ];
    switch (true) {
        case G\is_route('image'):
            $twitter_card['card'] = 'photo';
            break;
        case function_exists('get_admin') and G\is_route('album'):
        case function_exists('get_user') and G\is_route('user'):
            $twitter_card['card'] = 'gallery';
            if (G\is_route('album')) {
                $twitter_card['creator'] = get_album()['user']['twitter']['username'];
            } else {
                $twitter_card['creator'] = get_user()['twitter']['username'];
            }
            $list_output = function_exists('get_list') ? get_list()->output_assoc : null;
            if (is_array($list_output) && count($list_output) > 0) {
                for ($i = 0; $i < 4; ++$i) {
                    $twitter_card['image' . $i] = $list_output[$i]['display_url'];
                }
            }
            break;
    }
    foreach ($twitter_card as $k => $v) {
        if (!$v) {
            continue;
        }
        echo '<meta name="twitter:' . $k . '" content="' . $v . '">' . "\n";
    }

    if (function_exists('get_image') and G\is_route('image')) {
        foreach (['json', 'xml'] as $format) {
            echo '    <link rel="alternate" type="application/'.$format.'+oembed" href="'
            . G\get_base_url('oembed/?url='.urlencode(get_image()['url_viewer']).'&format='. $format)
            . '" title="'.get_image()['title'].'">' . "\n";
        } ?>
    <link rel="image_src" href="<?php echo get_image()['url']; ?>">
            <?php
    }

            if (CHV\getSetting('theme_custom_css_code')) {
                ?>
                <style>
                    <?php echo CHV\Render\get_cond_minified_code(CHV\getSetting('theme_custom_css_code'), 'css'); ?>
                </style>
            <?php
            }
            if (CHV\getSetting('theme_custom_js_code')) {
                ?>
                <script>
                    <?php echo CHV\Render\get_cond_minified_code(CHV\getSetting('theme_custom_js_code'), 'js'); ?>
                </script>
            <?php
            }
            CHV\Render\show_theme_inline_code('snippets/theme_colors.css');
            if (CHV\Render\theme_file_exists('custom_hooks/style.css')) {
                ?>
                <link rel="stylesheet" href="<?php echo CHV\Render\get_theme_file_url('custom_hooks/style.css'); ?>">
            <?php
            }
            ?>

            <link rel="alternate" hreflang="x-default" href="<?php echo CHV\get_current_url(true, ['lang']); ?>">
            <?php
            foreach (G\Handler::getVar('langLinks') as $k => $v) {
                echo '<link rel="alternate" hreflang="' . $v['hreflang'] . '" href="' . $v['url'] . '">' . "\n";
            }
            G\Render\include_theme_file('custom_hooks/head'); ?>

</head>

<?php G\Render\include_theme_file('custom_hooks/head_after'); ?>