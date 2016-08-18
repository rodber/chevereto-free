<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php echo G\Render\include_theme_file('mails/header'); ?>

<?php _se('Hi %n, welcome to %w', ['%n' => G\get_global('theme_mail')['user']['name'], '%w' => CHV\getSetting('website_name')]); ?>
<br><br>
<?php _se('Now that your account is ready you can enjoy uploading your images, creating albums and setting the privacy of your content as well as many more cool things that you will discover.'); ?>
<br><br>
<?php _se('By the way, here is you very own awesome profile page: <a href="%u">%n</a>. Go ahead and customize it, its yours!.', ['%u' => G\get_global('theme_mail')['user']['url'], '%n' => G\get_global('theme_mail')['user']['username']]); ?>
<br><br>
<?php _se('Thank you for joining'); ?>,
<br>
<?php echo CHV\getSetting('website_name'); ?>

<?php echo G\Render\include_theme_file('mails/footer'); ?>