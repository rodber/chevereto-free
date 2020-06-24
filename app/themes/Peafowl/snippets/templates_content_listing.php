<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<div data-template="content-listing" class="hidden">
	<div class="pad-content-listing"></div>
	<div class="content-listing-more">
		<button class="btn btn-big grey" data-action="load-more"><?php _se('Load more'); ?></button>
	</div>
	<div class="content-listing-loading"></div>
	<div class="content-listing-pagination"><a data-action="load-more"><?php _se('Load more'); ?></a></div>
</div>
<div data-template="content-listing-empty" class="hidden">
	<?php G\Render\include_theme_file("snippets/template_content_empty"); ?>
</div>
<div data-template="content-listing-loading" class="hidden">
	<div class="content-listing-loading"></div>
</div>