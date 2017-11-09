<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	
	<div class="header header-tabs margin-bottom-10 follow-scroll">
    	<h1><strong><span class="margin-right-5 icon icon-rss"></span><?php _se('Following'); ?></strong></h1>
        <?php G\Render\include_theme_file('snippets/tabs'); ?>

		<?php
			if(is_admin()) {
				G\Render\include_theme_file('snippets/user_items_editor');
		?>
        <div class="header-content-right phone-float-none">
			<?php G\Render\include_theme_file('snippets/listing_tools_editor'); ?>
        </div>
		<?php
			}
		?>
    </div>
    
    <div id="content-listing-tabs" class="tabbed-listing">
        <div id="tabbed-content-group">
            <?php G\Render\include_theme_file('snippets/listing'); ?>
        </div>
    </div>
	
</div>

<?php G\Render\include_theme_footer(); ?>