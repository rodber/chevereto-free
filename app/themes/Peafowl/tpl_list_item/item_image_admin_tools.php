<ul class="list-item-image-tools" data-action="list-tools">
	<li class="tool-select" data-action="select">
		<span data-icon-selected="icon-ok" data-icon-unselected="icon-checkbox-unchecked" class="btn-icon icon-checkbox-unchecked" title="<?php _se('Select'); ?>"></span>
		<span class="label label-select"><?php _se('Select'); ?></span>
	</li>
    <?php
    if (G\Handler::getCond('allowed_nsfw_flagging')) {
        ?>
	<li class="tool-flag" data-action="flag">
		<span class="btn-icon icon-flag" title="<?php _se('Toggle unsafe flag'); ?>"></span>
		<span class="label label-flag label-flag-unsafe"><?php _se('Flag as unsafe'); ?></span>
		<span class="label label-flag label-flag-safe"><?php _se('Flag as safe'); ?></span>
	</li>
    <?php
    }
    if (G\Handler::getRouteName() == 'moderate') {
        ?>
    <li class="tool-approve" data-action="approve">
		<span class="btn-icon icon-checkmark" title="<?php _se('Approve'); ?>"></span>
		<span class="label label-approve"><?php _se('Approve'); ?></span>
	</li>
    <?php
    }
    ?>
	<li class="tool-delete" data-action="delete">
		<span class="btn-icon icon-remove" title="<?php _se('Delete'); ?>"></span>
		<span class="label label-delete"><?php _se('Delete'); ?></span>
	</li>
</ul>