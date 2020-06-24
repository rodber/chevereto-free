<div class="list-item c%COLUMN_SIZE_ALBUM% gutter-margin-right-bottom" data-type="album" data-id="%ALBUM_ID_ENCODED%" data-liked="%ALBUM_LIKED%" data-flag="%ALBUM_COVER_FLAG%" data-privacy="%ALBUM_PRIVACY%" >
	<div class="list-item-image fixed-size">
		<a href="%ALBUM_URL%" class="image-container">
			%tpl_list_item/album_cover_password%
		</a>
		%tpl_list_item/item_privacy%
		%tpl_list_item/item_album_admin_tools%
	</div>
	%tpl_list_item/album_thumbs%
	<div class="list-item-desc">
		<div class="list-item-desc-title">
			<a class="list-item-desc-title-link" href="%ALBUM_URL%"><?php _se('Private album'); ?></a><span class="display-block font-size-small"><?php _se('Password protected'); ?></span>
		</div>
	</div>
</div>