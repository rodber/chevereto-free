<?php
G\Render\include_theme_file('head');
G\Render\include_theme_file('custom_hooks/header');
if (!G\is_prevented_route() and in_array(G\get_template_used(), ['user', 'image']) and !is_404()) {
    $body_class = (G\is_route('image') or (G\is_route('user') and get_user()['background']) or is_owner() or is_content_manager()) ? ' no-margin-top' : '';
}
$top_bar_class = get_theme_top_bar_color();
$templateUsed = G\Handler::getTemplateUsed();
if (is_maintenance() || is_show_consent_screen() || in_array($templateUsed, ['request-denied', '404'])) {
    $body_class = '';
} else {
    if (G\get_route_name() == 'index') {
        $body_class = CHV\getSetting('homepage_style');
        if (function_exists('get_list')) {
            $list = get_list();
            $hasPrev = $list->has_page_prev;
            if ($hasPrev) {
                $body_class = '';
            } else {
                $top_bar_class = in_array(CHV\getSetting('homepage_style'), ['landing', 'split']) ? 'black' : get_theme_top_bar_color();
            }
        } else {
            $top_bar_class = 'black';
        }
    }
}
?>

<body id="<?php echo $templateUsed; ?>" class="<?php echo $body_class; ?>">
    <?php G\Render\include_theme_file('custom_hooks/body_open'); ?>
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
        if ($body_class && G\get_route_name() == 'index' and in_array(CHV\getSetting('homepage_style'), ['landing', 'split'])) {
            $logo_header .= '_homepage';
        }
        $logo_header = CHV\getSetting($logo_header); ?>
                <div id="logo" class="top-bar-logo"><a href="<?php echo get_header_logo_link(); ?>"><img class="replace-svg" src="<?php echo CHV\get_system_image_url($logo_header); ?>" alt="<?php echo get_safe_html_website_name(); ?>"></a></div>

                <?php if (CHV\getSetting('website_privacy_mode') == 'public' or (CHV\getSetting('website_privacy_mode') == 'private' and CHV\Login::getUser())) {
            ?>
                    <ul class="top-bar-left float-left">

                        <li data-action="top-bar-menu-full" data-nav="mobile-menu" class="top-btn-el phone-show hidden">
                            <span class="top-btn-text"><span class="icon icon-menu3"></span></span>
                        </li>

                        <?php
                                if (is_explore_enabled()) { // cat selector
                                    ?>
                            <li id="top-bar-explore" data-nav="explore" class="phone-hide pop-keep-click pop-btn pop-btn-show<?php if (in_array(G\get_route_name(), ['explore', 'category'])) {
                                        ?> current<?php
                                    } ?>">
                                <?php
                                            $cols = 1;
                                            $categories = get_categories();
                                            if (count($categories) > 0) {
                                                array_unshift($categories, [
                                                    'id' => null,
                                                    'name' => _s('All'),
                                                    'url_key' => null,
                                                    'url' => G\get_base_url('explore'),
                                                ]);
                                                $cols = min(5, round(count($categories) / 5, 0, PHP_ROUND_HALF_UP));
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
                                                                'icon' => 'icon-rss',
                                                                'url' => G\get_base_url('following'),
                                                            ];
                                                        } ?>
                                            <div class="pop-box-label"><?php _se('Discovery'); ?></div>
                                            <ul>
                                                <?php
                                                                foreach ($explore_semantics as $k => $v) {
                                                                    echo '<li><a href="' . $v['url'] . '"><span class="btn-icon ' . $v['icon'] . '"></span><span class="btn-text">' . $v['label'] . '</span></a></li>';
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
                                                                    echo '<li data-content="category" data-category-id="' . $v['id'] . '"><a data-content="category-name" data-link="category-url" href="' . $v['url'] . '">' . $v['name'] . '</a></li>' . "\n";
                                                                    ++$count;
                                                                } ?>
                                            </ul>
                                        <?php
                                                    } ?>
                                    </div>
                                </div>
                            </li>
                        <?php
                                } ?>
                        <?php if (is_content_manager()) { ?>
                        <li id="top-bar-moderate" data-nav="moderate" class="top-btn-el<?php if (in_array(G\get_route_name(), ['moderate'])) {
                                    ?> current<?php
                                } ?>"">
                            <a href="<?php echo G\get_base_url('moderate'); ?>"><span class="top-btn-text"><span class="icon icon-download2"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Moderate'); ?></span></span></a>
                        </li>
                        <?php } ?>

                        <?php if (is_search_enabled()) {
                                    ?>
                            <li data-action="top-bar-search" data-nav="search" class="phone-hide pop-btn">
                                <span class="top-btn-text"><span class="icon icon-search"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Search'); ?></span></span>
                            </li>
                            <li data-action="top-bar-search-input" class="top-bar-search-input phone-hide pop-btn pop-keep-click hidden">
                                <div class="input-search">
                                    <form action="<?php echo G\get_base_url('search/images'); ?>/" method="get">
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
                            <li id="top-bar-random" data-nav="random" class="top-btn-el">
                                <a href="<?php echo G\get_base_url('?random'); ?>"><span class="top-btn-text"><span class="icon icon-shuffle"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Random'); ?></span></span></a>
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

                    <li data-action="top-bar-tone" data-nav="tone" class="top-btn-el phone-hide" data-login-needed="true">
                        <span class="top-btn-text"><span class="icon icon-light-bulb"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Lights'); ?></span></span>
                    </li>

                    <?php if (is_upload_enabled()) {
            ?>
                        <li data-action="top-bar-upload" data-link="<?php echo CHV\getSetting('upload_gui') != 'js'; ?>" data-nav="upload" class="<?php if (G\is_route('upload')) {
                echo 'current ';
            } ?>top-btn-el phone-hide" <?php if (!CHV\getSetting('guest_uploads')) {
                ?> data-login-needed="true" <?php
            } ?>>
                            <<?php echo CHV\getSetting('upload_gui') == 'page' ? ('a href="' . G\get_base_url('upload') . '"') : 'span'; ?> class="top-btn-text"><span class="icon icon-cloud-upload"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Upload'); ?></span></<?php echo CHV\getSetting('upload_gui') == 'js' ? 'span' : 'a'; ?>>
                        </li>
                    <?php
        } ?>

                    <?php
                        if (!CHV\Login::isLoggedUser()) {
                            ?>
                        <li id="top-bar-signin" data-nav="signin" class="<?php if (G\is_route('login')) {
                                echo 'current ';
                            } ?>top-btn-el">
                            <a href="<?php echo G\get_base_url('login'); ?>" class="top-btn-text"><span class="icon icon-login tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('Sign in'); ?></span></a>
                        </li>
                        <?php
                                if (CHV\getSetting('enable_signups')) {
                                    ?>
                            <li id="top-bar-signup" data-nav="signup" class="<?php if (G\is_route('signup')) {
                                        echo 'current ';
                                    } ?>phone-hide top-btn-el">
                                <a href="<?php echo G\get_base_url('signup'); ?>" class="top-btn-text top-btn-create-account btn <?php echo CHV\getSetting('theme_top_bar_button_color'); ?> text"><span class="icon icon-user2 phablet-hide tablet-hide laptop-hide desktop-hide"></span><?php _se('Create account'); ?></a>
                            </li>
                        <?php
                                } // signups
                                ?>
                        <?php
                        } else {
                            if (is_show_notifications()) {
                                $notifications_unread = CHV\Login::getUser()['notifications_unread'];
                                $notifications_display = CHV\Login::getUser()['notifications_unread_display'];
                                $notifications_counter = strtr('<span data-content="notifications-counter" class="top-btn-number%c">' . $notifications_display . '</span>', ['%c' => $notifications_unread > 0 ? ' on' : null]); ?>
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
                                <?php if (CHV\Login::getUser()['avatar']['url']) {
                                ?>
                                    <img src="<?php echo CHV\Login::getUser()['avatar']['url']; ?>" alt="" class="user-image">
                                <?php
                            } else {
                                ?>
                                    <img src="" alt="" class="user-image hidden">
                                <?php
                            } ?>
                                <span class="user-image default-user-image<?php echo CHV\Login::getUser()['avatar']['url'] ? ' hidden' : ''; ?>"><span class="icon icon-user2"></span></span>
                                <span class="text phone-hide"><?php echo CHV\Login::getUser()['name_short_html']; ?></span><span class="phone-hide arrow-down"></span>
                            </span>
                            <div class="pop-box arrow-box arrow-box-top anchor-right">
                                <div class="pop-box-inner pop-box-menu">
                                    <ul>
                                        <li><a href="<?php echo CHV\Login::getUser()['url']; ?>"><?php _se('My Profile'); ?></a></li>
                                        <li><a href="<?php echo CHV\Login::getUser()['url_albums']; ?>"><?php _se('Albums'); ?></a></li>
                                        <?php if (CHV\getSetting('enable_likes')) {
                                ?>
                                            <li><a href="<?php echo CHV\Login::getUser()['url_liked']; ?>"><?php _se('Liked'); ?></a></li>
                                        <?php
                            } ?>
                                        <?php
                                                if (CHV\getSetting('enable_followers')) {
                                                    ?>
                                            <li><a href="<?php echo CHV\Login::getUser()['url_following']; ?>"><?php _se('Following'); ?></a></li>
                                            <li><a href="<?php echo CHV\Login::getUser()['url_followers']; ?>"><?php _se('Followers'); ?></a></li>
                                        <?php
                                                } ?>
                                        <li><a href="<?php echo G\get_base_url('settings'); ?>"><?php _se('Settings'); ?></a></li>
                                        <?php if (is_admin()) {
                                                    ?>
                                            <li><a href="<?php echo G\get_base_url('dashboard'); ?>"><?php _se('Dashboard'); ?></a></li>
                                        <?php
                                                } ?>
                                        <li><a href="<?php echo G\get_base_url(sprintf('logout/?auth_token=%s', get_auth_token())); ?>"><?php _se('Sign out'); ?></a></li>
                                        <?php if (is_admin()) { ?>
                                        <div class="or-separator margin-0" style="height: 1px;"></div>
                                        <li><a data-action="upgrade">🚀 Upgrade</a></li>
                                        <?php } ?>
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
                                                            } ?>><a <?php echo $page['link_attr']; ?>><?php echo $page['title_html']; ?></a>
                            </li>
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
                            $langLinks = G\Handler::getVar('langLinks');
                    $cols = min(5, ceil(count($langLinks) / 6)); ?>
                <span class="top-btn-text"><span class="text"><?php echo CHV\get_language_used()['short_name']; ?></span><span class="arrow-down"></span></span>
                <div class="pop-box <?php if ($cols > 1) {
                        echo sprintf('pbcols%d ', $cols);
                    } ?>arrow-box arrow-box-top anchor-center">
                    <div class="pop-box-inner pop-box-menu<?php if ($cols > 1) {
                        ?> pop-box-menucols<?php
                    } ?>">
                        <ul>
                            <?php
                                        foreach ($langLinks as $k => $v) {
                                            echo '<li' . (CHV\get_language_used()['code'] == $k ? ' class="current"' : '') . '><a href="' . $v['url'] . '">' . $v['name'] . '</a></li>' . "\n";
                                            ++$count;
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