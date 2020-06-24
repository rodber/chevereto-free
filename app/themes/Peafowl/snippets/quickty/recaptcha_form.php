<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php
if (is_captcha_needed() && function_exists('get_recaptcha_html')) {
    ?>
<div class="content-section content-section--recaptchaFix">
    <?php echo get_recaptcha_html(); ?>
</div>
<?php
} ?>