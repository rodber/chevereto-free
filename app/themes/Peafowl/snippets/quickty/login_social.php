<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php
if (CHV\getSetting('social_signin')) {
    ?>
    <div class="margin-top-30 margin-bottom-30">
        <div class="or-separator"><span><?php _se('or'); ?></span></div>
    </div>
    <div class="content-section"><?php _se('Sign in with another account'); ?></div>
    <div class="content-section social-icons">
        <?php
            $services = CHV\Login::getSocialServices(['get' => 'enabled']);
            $tpl = '<a class="social-button social-button--%s" href="%u"><span class="icon icon-%s"> </span><span class="text">' . _s('Continue with %label%') . '</span></a>';
            foreach ($services as $service => $label) {
                echo strtr($tpl, [
                    '%s' => $service,
                    '%label%' => $label,
                    '%u' => G\get_base_url('connect/' . $service),
                ]);
            } ?>
    </div>
<?php
}
?>