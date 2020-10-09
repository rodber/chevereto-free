<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	<div class="c24 center-box">
		<div class="header default-margin-bottom">
			<h1>Example page</h1>
		</div>
		<div class="text-content">
			
            <p>This is an example page for your Chevereto site. You can edit this file located at <span class="highlight"><?php echo G\absolute_to_relative(__FILE__); ?></span>. If you want a real world example you should check the <a href="<?php echo G\get_base_url('page/contact'); ?>">contact page</a> which is <code>contact.php</code> in the same folder.</p>
			
			<h2>Creating and editing pages</h2>
			
			<p>To add or modify a page go to <a href="<?php echo G\get_base_url('dashboard/settings/pages/'); ?>">Dashboard > Settings > Pages</a> and do what you need. From the admin dashboard you can add pages, change page type, set the display order and more.</p>
			
			<h2>Custom styles and coding</h2>
			
			<p>Chevereto pages uses PHP code which gives you the power to highly customize how a page should look and operate. You can use your own header, footer, style sheets, etc. You can even create pages that look completely different from the main site look and and use all the system classes and functions (Both G\ and Chevereto) to make it easier and yet more powerful.</p>
			
			<h2>More help</h2>
			
			<p>If you need more help we suggest you to read the <a href="https://v3-docs.chevereto.com/">Chevereto documentation</a>. View the code of this file will also help you to understand the magic behind this system. Go to <a href="https://chevereto.com/support">Chevereto support</a> if needed.</p>
			
		</div>
	</div>
</div>

<?php G\Render\include_theme_footer(); ?>