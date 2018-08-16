<ul class="list-item-image-tools" data-action="list-tools">
	<li class="tool-select" data-action="select">
		<span data-icon-selected="icon-ok" data-icon-unselected="icon-checkbox-unchecked" class="btn-icon icon-checkbox-unchecked" title="<?php _se('Select'); ?>"></span>
		<span class="label label-select"><?php _se('Select'); ?></span>
	</li>
	<li class="tool-edit" data-action="edit"><span class="btn-icon icon-edit"></span><span class="label label-edit"><?php _se('Edit'); ?></span></li>
	<li class="tool-move" data-action="move">
		<span class="btn-icon icon-folder" title="<?php _ne('Album', 'Albums', 1); ?>"></span>
		<span class="label label-move"><?php _ne('Album', 'Albums', 1); ?></span>
	</li>
	<?php
		if(G\Handler::getCond('allowed_to_delete_content')) {
	?>
	<li class="tool-delete" data-action="delete">
		<span class="btn-icon icon-remove" title="<?php _se('Delete'); ?>"></span>
		<span class="label label-delete"><?php _se('Delete'); ?></span>
	</li>
	<?php
		}
	?>
</ul>