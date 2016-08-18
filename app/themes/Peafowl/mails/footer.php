<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<br><br>--<br>
<?php _se('This email was sent from %w %u', ['%w' => CHV\getSetting('website_name'), '%u' => G\get_base_url()]); ?>
</body>
</html>