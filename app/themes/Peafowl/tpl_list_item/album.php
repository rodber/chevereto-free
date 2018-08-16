<div class="list-item c%COLUMN_SIZE_ALBUM% gutter-margin-right-bottom" data-type="album" data-id="%ALBUM_ID_ENCODED%" data-liked="%ALBUM_LIKED%" data-flag="%ALBUM_COVER_FLAG%" data-privacy="%ALBUM_PRIVACY%" >
	<div class="list-item-image fixed-size">
		<a href="%ALBUM_URL%" class="image-container">
			%tpl_list_item/album_cover_empty%
			%tpl_list_item/album_cover_image%
		</a>
		%tpl_list_item/item_privacy%
		%tpl_list_item/item_album_admin_tools%
		%tpl_list_item/item_image_play_gif%
	</div>
	%tpl_list_item/album_thumbs%
	<div class="list-item-desc">
		<div class="list-item-desc-title">
			<a class="list-item-desc-title-link" href="%ALBUM_URL%">%ALBUM_NAME%</a><span class="display-block font-size-small">%ALBUM_IMAGE_COUNT% %ALBUM_IMAGE_COUNT_LABEL%</span>
		</div>
		%tpl_list_item/item_like%
	</div>
</div>