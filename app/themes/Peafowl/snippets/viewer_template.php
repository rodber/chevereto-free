<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<script id="viewer-template" type="text/x-chv-template">
	<div class="viewer viewer--hide list-item">
		<div class="viewer-content no-select">
			<a data-href="%url_viewer%"><img class="viewer-src no-select animate" src="%display_url%" alt="%filename%" width="%width%" height="%height%"></a>
      <div class="viewer-loader"></div>
		</div>
		<div class="viewer-wheel phone-hide phablet-hide tablet-hide hover-display">
			<div class="viewer-wheel-prev animate" data-action="viewer-prev"><span class="icon icon-arrow-left10"></span></div>
			<div class="viewer-wheel-next animate" data-action="viewer-next"><span class="icon icon-uniE8A9"></span></div>
		</div>
		<ul class="viewer-tools list-item-image-tools hover-display idle-display no-select" data-action="list-tools">
			<li data-action="viewer-close">
				<span class="btn-icon icon-cross4"></span>
				<span class="label"><?php _se('Close'); ?></span>
			</li>
		</ul>
		<div class="viewer-foot hover-display hover-display--flex">
			<div class="viewer-owner viewer-owner--user">
				<a href="%user.url%" class="user-image">
					<span class="user-image default-user-image"><span class="icon icon-user"></span></span>
					<img class="user-image" src="%user.avatar.url%" alt="%user.username%">
				</a>
				<a href="%user.url%" class="user-name">%user.name_short_html%</a>
			</div>
			<div class="viewer-owner viewer-owner--guest">
				<div class="user-image default-user-image"><span class="icon icon-user"></span></div>
				<span class="user-name"><?php _se('Guest'); ?></span>
			</div>
			<div class="viewer-kb phone-hide phablet-hide tablet-hide no-select">
				<div class="viewer-kb-input" title="<?php _se('Keyboard shortcuts'); ?>">
					<?php
                        if (CHV\getSetting('enable_likes')) {
                            ?>
					<div class="viewer-kb-key" data-key="L"><kbd>L</kbd><span><?php _se('Like'); ?></span></div>
					<?php
                        }
                    ?>
					<div class="viewer-kb-key" data-key="X"><kbd>X</kbd><span><?php _se('Close'); ?></span></div>
				</div>
			</div>
		</div>
		<div class="viewer-privacy list-item-privacy">
			<span class="btn-lock icon-eye-blocked"></span>
		</div>
		<?php
            if (CHV\getSetting('enable_likes')) {
                ?>
		<div class="viewer-like list-item-like" data-action="like">
			<span class="btn-like btn-liked icon-heart3"></span>
			<span class="btn-like btn-unliked icon-heart4"></span>
		</div>
		<?php
            }
        ?>
	</div>
</script>