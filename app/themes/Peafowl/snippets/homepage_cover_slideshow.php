<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<div id="home-cover-slideshow">
    <?php
        $i = 0;
        foreach (CHV\getSetting('homepage_cover_images_shuffled') as $k => $v) {
            if ($i > 1 && is_mobile_device()) {
                break;
            } ?>
    <div class="home-cover-img" data-src="<?php echo $v['url']; ?>"></div>
    <?php
            $i++;
        }
    ?>
</div>