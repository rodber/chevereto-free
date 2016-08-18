<div class="list-item c%COLUMN_SIZE_ALBUM% gutter-margin-right-bottom privacy-%ALBUM_PRIVACY%" data-type="album" data-id="%ALBUM_ID_ENCODED%" data-name="%ALBUM_NAME%" data-privacy="%ALBUM_PRIVACY%" data-description="%ALBUM_DESCRIPTION%" data-password="%ALBUM_PASSWORD%">
	<div class="list-item-image fixed-size">
		<a href="%ALBUM_URL%" class="image-container">
			%tpl_list_item/album_cover_empty%
			%tpl_list_item/album_cover_image%
		</a>
		%tpl_list_item/item_album_edit_tools%
		%tpl_list_item/item_privacy%
		%tpl_list_item/item_image_play_gif%
	</div>
	%tpl_list_item/album_thumbs%
	<div class="list-item-desc">
		<div class="position-absolute left-10"><a class="text-decoration-none list-item-desc-title" href="%ALBUM_URL%" data-text="album-name">%ALBUM_NAME_TRUNCATED%</a><span class="display-block font-size-small">%ALBUM_HOW_LONG_AGO%</span></div>
		<div class="position-absolute right-10 text-align-right"><span>%ALBUM_IMAGE_COUNT%</span><span class="display-block font-size-small">%ALBUM_IMAGE_COUNT_LABEL%</span></div>
	</div>
</div>