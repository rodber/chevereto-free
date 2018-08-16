<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
}?><!DOCTYPE HTML>
<html <?php echo CHV\Render\get_html_tags(); ?> prefix="og: http://ogp.me/ns#">
<head>
<meta charset="utf-8">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="theme-color" content="#<?php echo CHV\getSetting('theme_top_bar_color') == 'black' ? '000000' : 'FFFFFF'; ?>">
<?php if (get_meta_description()) {
    ?>
<meta name="description" content="<?php echo get_meta_description(); ?>">
<?php
} ?>

<title><?php echo get_doctitle(); ?></title>

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
    echo '<style type="text/css">.top-bar-logo, .top-bar-logo img { height: '.CHV\getSetting('theme_logo_height').'px; } .top-bar-logo { margin-top: -'.(CHV\getSetting('theme_logo_height')/2).'px; } </style>';
}

$open_graph = [
    'type'			=> 'website',
    'url'			=> G\get_current_url(),
    'title'			=> CHV\getSetting('website_doctitle'),
    'image'			=> CHV\getSetting('homepage_cover_images')[0]['url'],
    'site_name' 	=> CHV\getSetting('website_name'),
    'description'	=> CHV\getSetting('website_description')
];

switch (true) {
    case function_exists('get_image') and G\is_route('image'):
        $open_graph_extend = [
            'type'			=> 'article',
            'title'			=> get_pre_doctitle(),
            'description'	=> get_image()['description'],
            'image'			=> get_image()['url'],
            'image:width'	=> get_image()['width'],
            'image:height'	=> get_image()['height']
        ];
        if (get_image()['is_animated'] && get_image()['size'] < G\get_bytes('8 MiB')) {
            $open_graph_extend['type'] = 'video.other';
            $open_graph_extend['url'] = get_image()['url'];
        }
    break;
    case function_exists('get_album') and G\is_route('album'):
        $open_graph_extend = [
            'type'			=> 'article',
            'title'			=> get_pre_doctitle(),
            'description'	=> get_album()['description'] ?: get_album()['name'],
        ];
        if (in_array(get_album()['privacy'], ['public', 'private_but_link']) && get_list()->output_count) {
            $open_graph_extend = array_merge($open_graph_extend, [
                'image'			=> get_list()->output_assoc[0]['display_url'],
                'image:width'	=> get_list()->output_assoc[0]['display_width'],
                'image:height'	=> get_list()->output_assoc[0]['display_height'],
                'image:height'	=> get_album()['height']
            ]);
        }
    break;
    case function_exists('get_user') and G\is_route('user'):
        $open_graph_extend = [
            'type'			=> 'profile',
            'title'			=> get_user()['name'],
            'description'	=> sprintf(is_user_images() ? _s("%s's Images") : _s("%s's Albums"), get_user()["name_short"]),
            'image'			=> get_user()['avatar']['url'],
        ];
    break;
    case function_exists('get_album') and G\is_route('album'):
        $open_graph_extend = [
            'title'			=> get_album()['name'],
            'description'	=> get_album()['description'],
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
    echo '<meta property="og:'.$k.'" content="'.G\safe_html($v, ENT_COMPAT).'" />' . "\n";
}

// Set twitter card
$twitter_card = [
    'card'			=> 'summary',
    'description'	=> function_exists('get_meta_description') ? get_meta_description() : null,
    'title'			=> G\str_replace_last(' - ' . CHV\getSetting('website_name'), null, get_doctitle()),
    'site' 			=> CHV\getSetting('twitter_account') ? ('@' . CHV\getSetting('twitter_account')) : null
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
            for ($i=0; $i<4; $i++) {
                $twitter_card['image' . $i] = $list_output[$i]['display_url'];
            }
        }
    break;
}
foreach ($twitter_card as $k => $v) {
    if (!$v) {
        continue;
    }
    echo '<meta name="twitter:'.$k.'" content="'.$v.'">' . "\n";
}
?>
<?php if (function_exists('get_image') and G\is_route('image')) {
    ?>
<link rel="image_src" href="<?php echo get_image()['url']; ?>">
<?php
} ?>
<?php if (CHV\getSetting('theme_custom_css_code')) {
        ?>
<style><?php echo CHV\Render\get_cond_minified_code(CHV\getSetting('theme_custom_css_code'), 'css'); ?></style>
<?php
    } ?>
<?php if (CHV\getSetting('theme_custom_js_code')) {
        ?>
<script><?php echo CHV\Render\get_cond_minified_code(CHV\getSetting('theme_custom_js_code'), 'js'); ?></script>
<?php
    } ?>

<?php CHV\Render\show_theme_inline_code('snippets/theme_colors.css'); ?>

<?php if (CHV\Render\theme_file_exists('custom_hooks/style.css')) {
        ?>
<link rel="stylesheet" href="<?php echo CHV\Render\get_theme_file_url('custom_hooks/style.css'); ?>">
<?php
    } ?>

<?php G\Render\include_theme_file('custom_hooks/head'); ?>

</head>

<?php
    G\Render\include_theme_file('custom_hooks/header');
    if (!G\is_prevented_route() and in_array(G\get_template_used(), ['user', 'image']) and !is_404()) {
        $body_class = (G\is_route('image') or (G\is_route('user') and get_user()["background"]) or is_owner() or is_admin()) ? " no-margin-top" : "";
    }
    if (G\get_route_name() == 'index') {
        $body_class = CHV\getSetting('homepage_style');
    }
    if (is_maintenance() || is_show_consent_screen()) {
        $body_class = '';
    }

    $top_bar_class = CHV\getSetting('theme_top_bar_color');
?>
<?php
    if (G\get_route_name() == 'index') {
        $top_bar_class = in_array(CHV\getSetting('homepage_style'), ['landing', 'split']) ? 'black' : CHV\getSetting('theme_top_bar_color');
    }
?>
<body id="<?php echo G\Handler::getTemplateUsed(); ?>" class="<?php echo $body_class; ?>">

<?php
    if (is_show_viewer_zero()) {
        ?>
<div class="viewer viewer--zero"></div>
<?php
    }
?>

<?php if (is_show_header()) {
    ?>
<header id="top-bar" class="top-bar<?php if (in_array($body_class, ['landing', 'split']) and $top_bar_class == 'black') {
        echo ' transparent';
    } ?><?php echo ' ' . $top_bar_class; ?>">
    <div class="content-width">
		<?php
            $logo_header = CHV\getSetting('logo_vector_enable') ? 'logo_vector' : 'logo_image';
    if (G\get_route_name() == 'index' and in_array(CHV\getSetting('homepage_style'), ['landing', 'split'])) {
        $logo_header .= '_homepage';
    }
    $logo_header = CHV\getSetting($logo_header); ?>
        <div id="logo" class="top-bar-logo"><a href="<?php echo get_header_logo_link(); ?>"><img class="replace-svg" src="<?php echo CHV\get_system_image_url($logo_header); ?>" alt="<?php echo CHV\getSetting('website_name'); ?>"></a></div>

		<?php if (CHV\getSetting('website_privacy_mode') == 'public' or (CHV\getSetting('website_privacy_mode') == 'private' and CHV\Login::getUser())) {
        ?>
        <ul class="top-bar-left float-left">

			<li data-action="top-bar-menu-full" data-nav="mobile-menu" class="top-btn-el phone-show hidden">
				<span class="top-btn-text"><span class="icon icon-menu3"></span></span>
			</li>

			<?php
                if (is_explore_enabled()) { // cat selector?>
			<li id="top-bar-explore" data-nav="explore" class="phone-hide pop-keep-click pop-btn pop-btn-show<?php if (in_array(G\get_route_name(), ['explore','category'])) {
                    ?> current<?php
                } ?>">
				<?php
                    $cols = 1;
                    $categories = get_categories();
                    if (count($categories) > 0) {
                        array_unshift($categories, [
                            'id'		=> null,
                            'name'		=> _s('All'),
                            'url_key'	=> null,
                            'url'		=> G\get_base_url('explore')
                        ]);
                        $cols = min(5, round(count($categories)/5, 0, PHP_ROUND_HALF_UP));
                    } ?>
                <span class="top-btn-text"><span class="icon icon-stack"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Explore'); ?></span></span>
                <div class="pop-box <?php if ($cols > 1) {
                        echo sprintf('pbcols%d ', $cols);
                    } ?>arrow-box arrow-box-top anchor-left">

					<div class="pop-box-inner pop-box-menu<?php if ($cols > 1) {
                        ?> pop-box-menucols<?php
                    } ?>">
						<?php
                            if (function_exists('get_explore_semantics')) {
                                $explore_semantics = get_explore_semantics();
                                if (CHV\Login::isLoggedUser() && CHV\getSetting('enable_followers')) {
                                    $explore_semantics['following'] = [
                                        'label' => _s('Following'),
                                        'icon'	=> 'icon-rss',
                                        'url'	=> G\get_base_url('following'),
                                    ];
                                } ?>
						<div class="pop-box-label"><?php _se('Discovery'); ?></div>
						<ul>
							<?php
                                foreach ($explore_semantics as $k => $v) {
                                    echo '<li><a href="'.$v['url'].'"><span class="btn-icon '.$v['icon'].'"></span><span class="btn-text">'.$v['label'].'</span></a></li>';
                                } ?>
						</ul>
						<?php
                            } ?>
						<?php
                            if (count($categories) > 0) {
                                ?>
						<div class="pop-box-label phone-margin-top-20"><?php _se('Categories'); ?></div>
						<ul>
						<?php
                            foreach ($categories as $k => $v) {
                                echo '<li data-content="category" data-category-id="' . $v['id'] . '"><a data-content="category-name" data-link="category-url" href="' . $v['url'] . '">' . $v["name"] . '</a></li>'."\n";
                                $count++;
                            } ?>
                        </ul>
						<?php
                            } ?>
                    </div>
                </div>
			</li>
            <?php
                } ?>

			<?php if (CHV\getSetting('website_search')) {
                    ?>
            <li data-action="top-bar-search"  data-nav="search" class="phone-hide pop-btn">
                <span class="top-btn-text"><span class="icon icon-search"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Search'); ?></span></span>
            </li>
            <li data-action="top-bar-search-input" class="top-bar-search-input phone-hide pop-btn pop-keep-click hidden">
                <div class="input-search">
                	<form action="<?php echo G\get_base_url("search/images"); ?>/" method="get">
                    	<input class="search" type="text" placeholder="<?php _se('Search'); ?>" autocomplete="off" spellcheck="false" name="q">
                    </form>
                    <span class="icon-search"></span><span class="icon close icon-close" data-action="clear-search" title="<?php _se('Close'); ?>"></span><span class="icon settings icon-triangle-down" data-modal="form" data-target="advanced-search" title="<?php _se('Advanced search'); ?>"></span>
                </div>
            </li>
			<div class="hidden" data-modal="advanced-search">
				<span class="modal-box-title"><?php _se('Advanced search'); ?></span>
				<?php G\Render\include_theme_file('snippets/form_advanced_search'); ?>
			</div>
			<?php
                } ?>

			<?php if (CHV\getSetting('website_random')) {
                    ?>
			<li id="top-bar-random"  data-nav="random" class="top-btn-el phone-hide">
                <a href="<?php echo G\get_base_url("?random"); ?>"><span class="top-btn-text"><span class="icon icon-shuffle"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Random'); ?></span></span></a>
            </li>
			<?php
                } ?>

        </ul>
		<?php
    } ?>
        <ul class="top-bar-right float-right keep-visible">

			<?php if (get_system_notices()) {
        ?>
				<li data-nav="notices" class="phone-hide pop-btn pop-keep-click">
                <span class="top-btn-text"><span class="icon icon-notification color-red"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Notices (%s)', count(get_system_notices())); ?></span></span>
				<div class="pop-box anchor-center c8 arrow-box arrow-box-top anchor-center">
					<div class="pop-box-inner padding-20">
						<ul class="list-style-type-decimal list-style-position-inside">
						<?php foreach (get_system_notices() as $notice) {
            ?>
							<li><?php echo $notice; ?></li>
						<?php
        } ?>
						</ul>
					</div>
				</div>
            </li>
			<?php
    } ?>

			<?php if (is_upload_enabled()) {
        ?>
            <li data-action="top-bar-upload" data-nav="upload" class="<?php if (G\is_route("upload")) {
            echo "current ";
        } ?>pop-btn phone-hide"<?php if (!CHV\getSetting('guest_uploads')) {
            ?> data-login-needed="true"<?php
        } ?>>
                <span class="top-btn-text"><span class="icon icon-cloud-upload"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Upload'); ?></span></span>
            </li>
			<?php
    } ?>

        	<?php
                if (!CHV\Login::isLoggedUser()) {
                    ?>
            <?php
                    if (is_captcha_needed()) {
                        ?>
			<li id="top-bar-signin" data-nav="signin" class="<?php if (G\is_route("login")) {
                            echo "current ";
                        } ?>top-btn-el">
				<a href="<?php echo G\get_base_url('login'); ?>" class="top-btn-text"><span class="icon icon-login tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('Sign in'); ?></span></a>
			</li>
			<?php
                    } else {
                        ?>
			<li id="top-bar-signin" data-nav="signin" class="<?php if (G\is_route("login")) {
                            echo "current ";
                        } ?>pop-btn pop-btn-delayed pop-account pop-keep-click">
				<span class="top-btn-text"><span class="icon icon-login tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('Sign in'); ?></span></span>
                <div id="top-signin-menu" class="pop-box anchor-center c8 arrow-box arrow-box-top">
                    <div class="pop-box-inner">
                    		<?php
                            if (CHV\getSetting('social_signin')) {
                                ?>
                        <span class="title"><?php _se('Sign in with another account'); ?></span>
                   			<ul class="sign-services text-align-center">
                            <?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?>
                        </ul>
                        <div class="or-separator"><span><?php _se('or'); ?></span></div>
                        <?php
                            } ?>
                        <form method="post" action="<?php echo G\get_base_url("login"); ?>" autocomplete="off">
													<?php echo G\Render\get_input_auth_token(); ?>
                        	<div class="input"><input type="text" class="text-input" name="login-subject" placeholder="<?php _se('Username or Email address'); ?>" autocomplete="off" required></div>
                            <div class="input"><input type="password" class="text-input" name="password" placeholder="<?php _se('Password'); ?>" autocomplete="off" required><button type="submit" class="icon-input-submit"></button></div>
                            <div class="input overflow-auto">
                            	<div class="checkbox-label"><label for="keep-login"><input type="checkbox" name="keep-login" id="keep-login" value="1"><?php _se('Keep me logged in'); ?></label></div>
                                <div class="float-right"><a href="<?php echo G\get_base_url("account/password-forgot"); ?>"><?php _se('Forgot password?'); ?></a></div>
                            </div>
														<?php
                                                            if (CHV\getSetting('enable_signups')) {
                                                                ?>
														<div class="input text-align-center margin-top-10"><?php _se("Don't have an account? <a href='%s'>Sign up</a> now.", G\get_base_url('signup')); ?></div>
														<?php
                                                            } ?>
                        </form>
                    </div>
                </div>
            </li>
			<?php
                    } ?>
			<?php
                    if (CHV\getSetting('enable_signups')) {
                        if (is_captcha_needed()) {
                            ?>
			<li id="top-bar-signup" data-nav="signup" class="<?php if (G\is_route("signup")) {
                                echo "current ";
                            } ?>phone-hide top-btn-el">
				<a href="<?php echo G\get_base_url('signup'); ?>" class="top-btn-text top-btn-create-account btn <?php echo CHV\getSetting('theme_top_bar_button_color'); ?> text"><span class="icon icon-user2 phablet-hide tablet-hide laptop-hide desktop-hide"></span><?php _se('Create account'); ?></a>
			</li>
			<?php
                        } else {
                            ?>
			<li id="top-bar-signup" data-nav="signup" class="<?php if (G\is_route("signup")) {
                                echo "current ";
                            } ?>phone-hide pop-btn pop-btn-delayed pop-account pop-keep-click">
            	<span class="top-btn-text top-btn-create-account btn <?php echo CHV\getSetting('theme_top_bar_button_color'); ?> text"><span class="icon icon-user2 phablet-hide tablet-hide laptop-hide desktop-hide"></span><?php _se('Create account'); ?></span>
                <div id="top-signup-menu" class="pop-box anchor-center c8 arrow-box arrow-box-top">
                    <div class="pop-box-inner">
                    	<?php
                            if (CHV\getSetting('social_signin')) {
                                ?>
                        <span class="title"><?php _se('Sign up with another account'); ?></span>
                   			<ul class="sign-services text-align-center">
                        	<?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?>
                        </ul>
                        <div class="or-separator"><span><?php _se('or'); ?></span></div>
                        <?php
                            } ?>
                        <form method="post" action="<?php echo G\get_base_url("signup"); ?>" autocomplete="off">
							<?php echo G\Render\get_input_auth_token(); ?>
                        	<div class="input"><input type="email" class="text-input" name="email" placeholder="<?php _se('Email address'); ?>" autocomplete="off" required></div>
                        	<div class="input"><input type="text" class="text-input" name="username" placeholder="<?php _se('Username'); ?>" autocomplete="off" required></div>
                            <div class="input"><input type="password" class="text-input" name="password" placeholder="<?php _se('Password'); ?>" autocomplete="off" required><button type="submit" class="icon-input-submit"></button></div>
							<?php if (CHV\getSetting('user_minimum_age') > 0) {
                                ?>
                                <div class="input">
                                    <div class="checkbox-label">
                                        <label for="minimum-age-signup"><input type="checkbox" name="minimum-age-signup" id="minimum-age-signup" value="1" required><?php _se("I'm at least %s years old", CHV\getSetting('user_minimum_age')); ?></label>
                                    </div>
                                </div>
							<?php
                            } ?>
                            <div class="input">
								<div class="checkbox-label">
								<label for="signup-accept-terms-policies-top">
									<input type="checkbox" name="signup-accept-terms-policies" id="signup-accept-terms-policies-top" value="1" required><?php _se('I agree to the %terms_link and %privacy_link', ['%terms_link' => '<a ' . get_page_tos()['link_attr'] . '>'. _s('terms') .'</a>', '%privacy_link' => '<a ' . get_page_privacy()['link_attr'] . '>' . _s('privacy policy'). '</a>']); ?>
								</label>
								</div>
							</div>
                        </form>
                    </div>
                </div>
            </li>
			<?php
                        }
                    } // signups
            ?>

			<?php
                } else {
                    if (is_show_notifications()) {
                        $notifications_unread = CHV\Login::getUser()['notifications_unread'];
                        $notifications_display = CHV\Login::getUser()['notifications_unread_display'];
                        $notifications_counter = strtr('<span data-content="notifications-counter" class="top-btn-number%c">'.$notifications_display.'</span>', ['%c' => $notifications_unread > 0 ? ' on' : null]); ?>
			<li data-action="top-bar-notifications" class="top-bar-notifications pop-btn pop-keep-click">
				<div class="top-btn-text">
					<div class="soft-hidden menu-fullscreen-show"><span class="icon icon-bell2"></span><?php echo $notifications_counter; ?><span class="btn-text"><?php _se('Notifications'); ?></span></div>
					<div class="menu-fullscreen-hide"><span class="icon icon-bell2"></span><?php echo $notifications_counter; ?></div>
				</div>
                <div class="top-bar-notifications-container c9 pop-box arrow-box arrow-box-top anchor-center">
                    <div class="pop-box-inner">
                    	<div class="top-bar-notifications-header phone-hide phablet-hide">
                    		<h2><?php _se('Notifications'); ?></h2>
                            <!--<a href="#setting"><?php _se('Settings'); ?></a>-->
                        </div>
						<div class="top-bar-notifications-list antiscroll-wrap hidden">
							<ul class="antiscroll-inner r8 overflow-scroll overflow-x-hidden touch-scroll"></ul>
						</div>
						<div class="loading text-align-center margin-top-20 margin-bottom-20 hidden">
							<div class="loading-indicator"></div>
							<div class="loading-text"><?php _se('loading'); ?></div>
						</div>
						<div class="empty text-align-center margin-top-20 margin-bottom-20 hidden">
							<?php _se("You don't have notifications"); ?>
						</div>
                    </div>
                </div>
            </li>
			<?php
                    } ?>
            <li id="top-bar-user" data-nav="user" class="pop-btn pop-keep-click pop-btn-delayed <?php echo is_show_notifications() ? ' margin-left-10' : null; ?>">
                <span class="top-btn-text">
					<?php if (CHV\Login::getUser()["avatar"]["url"]) {
                        ?>
					<img src="<?php echo CHV\Login::getUser()["avatar"]["url"]; ?>" alt="" class="user-image">
					<?php
                    } else {
                        ?>
					<img src="" alt="" class="user-image hidden">
					<?php
                    } ?>
					<span class="user-image default-user-image<?php echo(CHV\Login::getUser()["avatar"]["url"] ? ' hidden' : ''); ?>"><span class="icon icon-user2"></span></span>
					<span class="text phone-hide"><?php echo CHV\Login::getUser()["name_short_html"]; ?></span><span class="phone-hide arrow-down"></span>
				</span>
                <div class="pop-box arrow-box arrow-box-top anchor-right">
                    <div class="pop-box-inner pop-box-menu">
                        <ul>
                            <li><a href="<?php echo CHV\Login::getUser()["url"]; ?>"><?php _se('My Profile'); ?></a></li>
                            <li><a href="<?php echo CHV\Login::getUser()["url_albums"]; ?>"><?php _se('Albums'); ?></a></li>
							<?php if (CHV\getSetting('enable_likes')) {
                        ?>
							<li><a href="<?php echo CHV\Login::getUser()["url_liked"]; ?>"><?php _se('Liked'); ?></a></li>
							<?php
                    } ?>
                            <?php
                                if (CHV\getSetting('enable_followers')) {
                                    ?>
							<li><a href="<?php echo CHV\Login::getUser()['url_following']; ?>"><?php _se('Following'); ?></a></li>
							<li><a href="<?php echo CHV\Login::getUser()['url_followers']; ?>"><?php _se('Followers'); ?></a></li>
							<?php
                                } ?>
							<li><a href="<?php echo G\get_base_url("settings"); ?>"><?php _se('Settings'); ?></a></li>
							<?php if (is_admin()) {
                                    ?>
							<li><a href="<?php echo G\get_base_url("dashboard"); ?>"><?php _se('Dashboard'); ?></a></li>
							<?php
                                } ?>
                            <li><a href="<?php echo G\get_base_url(sprintf("logout?auth_token=%s", get_auth_token())); ?>"><?php _se('Sign out'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </li>
			<?php
                } ?>
			<?php
                if (CHV\getSetting('website_privacy_mode') == 'public' or (CHV\getSetting('website_privacy_mode') == 'private' and CHV\Login::getUser())) {
                    ?>
			<?php
                    if (get_pages_link_visible()) {
                        ?>
            <li data-nav="about" class="phone-hide pop-btn pop-keep-click pop-btn-delayed">
                <span class="top-btn-text"><span class="icon icon-info tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('About'); ?></span><span class="arrow-down"></span></span>
                <div class="pop-box arrow-box arrow-box-top anchor-right">
                    <div class="pop-box-inner pop-box-menu">
                        <ul>
                            <?php
                                foreach (get_pages_link_visible() as $page) {
                                    ?>
							<li<?php if ($page['icon']) {
                                        echo ' class="with-icon"';
                                    } ?>><a <?php echo $page['link_attr']; ?>><?php echo $page['title_html']; ?></a></li>
							<?php
                                } ?>
                        </ul>
                    </div>
                </div>
            </li>
            <?php
                    } ?>
			<?php
                    if (!CHV\Login::isLoggedUser() and CHV\getSetting('language_chooser_enable')) {
                        ?>
            <li data-nav="language" class="phablet-hide phone-hide pop-btn">
				<?php
                    // Language selector
                    $enabled_languages = CHV\get_enabled_languages();
                        $cols = min(6, ceil(count($enabled_languages) / 6)); ?>
                <span class="top-btn-text"><span class="text"><?php echo CHV\get_language_used()['short_name']; ?></span><span class="arrow-down"></span></span>
                <div class="pop-box <?php if ($cols > 1) {
                            echo sprintf('pbcols%d ', $cols);
                        } ?>arrow-box arrow-box-top anchor-center">
                    <div class="pop-box-inner pop-box-menu<?php if ($cols > 1) {
                            ?> pop-box-menucols<?php
                        } ?>">
                        <ul>
						<?php
                            foreach ($enabled_languages as $k => $v) {
                                echo '<li' . (CHV\get_language_used()['code'] == $k ? ' class="current"' : '') . '><a href="' . G\get_base_url('?lang=' . $k) . '">' . $v["name"] . '</a></li>'."\n";
                                $count++;
                            } ?>
                        </ul>
                    </div>
                </div>
            </li>
			<?php
                    } ?>
			<?php
                } ?>

        </ul>

    </div>
</header>
<?php
} ?>
