<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<?php G\Render\include_theme_header(); ?>

<div class="content-width">
	
	<div class="header header-tabs margin-bottom-10 follow-scroll">
		<div class="heading display-inline-block">
			<?php if(!get_safe_html_search()['q']) { ?>
			<?php _se('Search results'); ?>
			<?php } else { ?>
			<span class="phone-hide"><?php _se('Results for'); ?></span>
			<h1 class="display-inline"><strong><?php echo get_safe_html_search()["d"]; ?></strong></h1>
			<?php } ?>
		</div>
		
		<?php G\Render\include_theme_file("snippets/tabs"); ?>
		
		<?php
			if(is_admin()) {
				G\Render\include_theme_file("snippets/user_items_editor");
		?>
		<div class="header-content-right phone-float-none">
			<?php G\Render\include_theme_file("snippets/listing_tools_editor"); ?>
		</div>
		<?php
			}
		?>
        
    </div>
    
	<div id="content-listing-tabs" class="tabbed-listing">
        <div id="tabbed-content-group">
            <?php
                G\Render\include_theme_file("snippets/listing");
            ?>
        </div>
    </div>
	
</div>

<?php G\Render\include_theme_footer(); ?>