<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<?php
// Output the button
$i = 0;
$services = CHV\Login::getSocialServices(['get' => 'enabled']);
foreach ($services as $service => $label) {
    if ($i == 0) {
        $attr = ' class="margin-left-0"';
    }
    if ($i == count($services) - 1) {
        $attr = ' class="margin-left-0 margin-right-0"';
    }
    $i++; ?><li<?php echo $attr; ?>><a class="sign-service btn-<?php echo $service; ?>" href="<?php echo G\get_base_url('connect/'.$service); ?>"><span class="btn-icon icon-<?php echo $service; ?>"></span><span class="btn-text phone-hide"><?php echo $label; ?></span></a></li><?php
} ?>