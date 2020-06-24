<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<div id="top-left">
    <div class="top-button pop-btn">
        <div class="top-button-icon icon icon-menu"><span class="btn-text display-none"><?php echo get_safe_html_website_name(); ?></span></div>
        <div class="pop-box menu-box">
            <?php
            $buttons = get_pages_link_visible();
            array_unshift($buttons, [
                'icon'    => 'icon-home2',
                'title'    => _s('Home'),
                'url'    => G\get_base_url(),
            ]);
            if (CHV\Login::isLoggedUser() == false) {
                array_push($buttons, [
                    'icon'    => 'icon-login',
                    'title'    => _s('Sign in'),
                    'url'    => G\get_base_url('login'),
                ]);
                if (CHV\getSetting('enable_signups')) {
                    array_push($buttons, [
                        'icon'    => 'icon-user2',
                        'title'    => _s('Sign up'),
                        'url'    => G\get_base_url('signup'),
                    ]);
                }
            }
            foreach ($buttons as $k => $button) {
                ?>
                <a role="button" href="<?php echo $button['url']; ?>">
                    <span class="icon <?php echo $button['icon']; ?>"></span>
                    <span class="text"><?php echo $button['title']; ?></span>
                </a>
            <?php
            } ?>
        </div>
    </div>