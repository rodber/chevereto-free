<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<?php
$list = function_exists('get_list') ? get_list() : G\get_global('list');
$tabs = (array) (G\get_global('tabs') ? G\get_global('tabs') : (function_exists('get_tabs') ? get_tabs() : NULL));
foreach($tabs as $tab) {
	if($tab["list"] === false or $tab["tools"] === false) continue;
?>
<div data-content="list-selection" data-tab="<?php echo $tab["id"]; ?>" class="list-selection <?php $class = []; if(count($list->output) == 0) { $class[] = 'disabled'; } if(!$tab["current"]) { $class[] = 'hidden'; } echo implode(' ', $class); ?>">
	<div class="display-inline-block margin-right-10"><a data-action="list-select-all" class="header-link" data-text-select-all="<?php _se('Select all'); ?>" data-text-clear-all="<?php _se('Clear selection'); ?>"><?php _se('Select all'); ?></a><span class="margin-left-10">&middot;</span></div>
	
	<div data-content="pop-selection" class="disabled sort-listing pop-btn header-link display-inline-block">
		<span class="phone-hide"><?php _se('Selection'); ?> </span><span class="selection-count" data-text="selection-count"></span><span class="pop-btn-text no-select"><?php _se('Action'); ?><span class="arrow-down"></span></span>
		<div class="pop-box anchor-right arrow-box arrow-box-top">
			<div class="pop-box-inner pop-box-menu">
				<ul>
					<?php
                    	if($tab['type'] == 'images') {
					?>
					<li><a data-action="get-embed-codes"><?php _se('Get embed codes'); ?></a></li>
					<?php
						}
					?>
					<?php
						if(in_array(G\get_route_name(), ['user', 'album']) and (array_key_exists('tools_available', $tab) ? in_array('album', $tab['tools_available']) : TRUE)) {
					?>
					<li><a data-action="create-album"><?php _se('Create album'); ?></a></li>
					<li><a data-action="move"><?php _se('Move to album'); ?></a></li>
					<?php
						}
					?>
                    <?php
                    	if($tab['type'] == 'images') {
					?>
					<?php
						if((array_key_exists('tools_available', $tab) ? in_array('category', $tab['tools_available']) : TRUE) and get_categories()) {
					?>
					<li><a data-action="assign-category"><?php _se('Assign category'); ?></a></li>
					<?php
						}
					?>
					<?php 
						if((array_key_exists('tools_available', $tab) ? in_array('flag', $tab['tools_available']) : TRUE)) {
					?>
					<li><a data-action="flag-safe" class="hidden"><?php _se('Flag as safe'); ?></a></li>
					<li><a data-action="flag-unsafe" class="hidden"><?php _se('Flag as unsafe'); ?></a></li>
					<?php 
						}
					?>
                    <?php
						}
					?>
					<?php
						if(is_allowed_to_delete_content() && (array_key_exists('tools_available', $tab) ? in_array('delete', $tab['tools_available']) : TRUE)) {
					?>
					<li><a data-action="delete"><?php _se('Delete'); ?></a></li>
					<?php
						}
					?>
					<li><a data-action="clear"><?php _se('Clear selection'); ?></a></li>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
	}
?>