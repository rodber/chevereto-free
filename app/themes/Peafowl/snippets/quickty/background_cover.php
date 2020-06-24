<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<style>
    .background-cover {
        background-image: url(<?php echo CHV\getSetting('homepage_cover_images')[0]['url']; ?>);
    }
</style>
<div class="background-cover"></div>