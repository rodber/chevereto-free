<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<h1>Edit app/settings.php</h1>
<p>The database details are correct but Chevereto wasn't able to edit the <code>app/settings.php</code> file for you. This file is the one that stores this data.</p>
<p>Edit the <code><?php echo G_APP_PATH . 'settings.php'; ?></code> file with this content:</p>
<code class="display-block"><pre><?php echo htmlspecialchars($settings_php); ?></pre></code>
<p>Once done <a href="<?php echo G\get_current_url(); ?>">click here</a>.</p>