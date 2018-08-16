<div class="list-item c%COLUMN_SIZE_IMAGE% gutter-margin-right-bottom" data-category-id="%IMAGE_CATEGORY_ID%" data-flag="%IMAGE_FLAG%" data-id="%IMAGE_ID_ENCODED%" data-album-id="%IMAGE_ALBUM_ID_ENCODED%" data-type="image" data-liked="%IMAGE_LIKED%" data-description="%IMAGE_DESCRIPTION%" data-title="%IMAGE_TITLE%" data-privacy="%IMAGE_ALBUM_PRIVACY%" %DATA_OBJECT%>
	<div class="list-item-image %SIZE_TYPE%">
		<a href="%IMAGE_URL_VIEWER%" class="image-container">
			%tpl_list_item/image_cover_empty%
			%tpl_list_item/image_cover_image%
		</a>
		%tpl_list_item/item_privacy%
		%tpl_list_item/item_image_edit_tools%
		%tpl_list_item/item_image_play_gif%
	</div>
	<div class="list-item-desc">
		<div class="list-item-desc-title list-item-desc-title--center-y">
			<a href="%IMAGE_URL_VIEWER%" class="list-item-desc-title-link" data-text="image-title" data-content="image-link">%IMAGE_TITLE%</a>
		</div>
		%tpl_list_item/item_like%
	</div>
</div>