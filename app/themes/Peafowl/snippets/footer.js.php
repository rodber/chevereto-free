<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<?php if(CHV\getSetting('website_search')) { ?>
<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "WebSite",
	"url": "<?php echo G\get_base_url(); ?>",
	"potentialAction": {
		"@type": "SearchAction",
		"target": "<?php echo G\get_base_url('search/images/?q={q}'); ?>",
		"query-input": "required name=q"
	}
}
</script>
<?php } ?>

<script data-cfasync="false">
document.getElementById("chevereto-js").addEventListener("load", function () {
	PF.obj.devices = window.devices;
	PF.fn.window_to_device = window.window_to_device;
	PF.obj.config.base_url = "<?php echo G\get_base_url(); ?>";
	PF.obj.config.json_api = "<?php echo G\get_base_url('json'); ?>";
	PF.obj.config.listing.items_per_page = "<?php echo CHV\getSetting('listing_items_per_page'); ?>";
	PF.obj.config.listing.device_to_columns = <?php echo json_encode(CHV\getSetting('listing_device_to_columns')); ?>;
	PF.obj.config.auth_token = "<?php echo get_auth_token(); ?>";

	PF.obj.l10n = <?php echo json_encode(CHV\get_translation_table()) ;?>;

	if(typeof CHV == "undefined") {
		CHV = {obj: {}, fn: {}, str:{}};
	}

	CHV.obj.vars = {
		urls: {
			home: PF.obj.config.base_url,
			search: "<?php echo G\get_base_url("search"); ?>"
		}
	};

	CHV.obj.config = {
		image : {
			max_filesize: "<?php echo CHV\getSetting('upload_max_filesize_mb') . ' MB'; ?>",
			right_click: <?php echo json_encode(CHV\getSetting('theme_image_right_click')); ?>,
			load_max_filesize: "<?php echo CHV\getSetting('image_load_max_filesize_mb') . ' MB'; ?>",
			max_width: <?php echo json_encode(CHV\getSetting('upload_max_image_width')); ?>,
			max_height: <?php echo json_encode(CHV\getSetting('upload_max_image_height')); ?>,
		},
		upload: {
			redirect_single_upload: <?php echo json_encode(CHV\getSetting('enable_redirect_single_upload')); ?>,
			threads: <?php echo json_encode(CHV\getSetting('upload_threads')); ?>,
			image_types: <?php echo json_encode(CHV\Image::getEnabledImageFormats()); ?>,
		},
		user: {
			avatar_max_filesize: "<?php echo CHV\getSetting('user_image_avatar_max_filesize_mb') . ' MB'; ?>",
			background_max_filesize: "<?php echo CHV\getSetting('user_image_background_max_filesize_mb') . ' MB'; ?>",
		},
		recaptcha: {
			enabled: <?php echo CHV\getSettings()['recaptcha'] ? 'true' : 'false'; ?>,
			sitekey: "<?php echo CHV\getSetting('recaptcha_public_key'); ?>",
		},
		listing: {
			viewer: <?php echo CHV\Settings::get('listing_viewer') ? 'true' : 'false'; ?>,
		}
	};

	<?php
		$page_info = [
			'doctitle'		=> function_exists('get_doctitle') ? get_doctitle() : NULL,
			'pre_doctitle'	=> function_exists('get_pre_doctitle') ? get_pre_doctitle() : NULL
		];
		if($page_info['pre_doctitle']) {
			$page_info['pos_doctitle'] = G\str_replace_first($page_info['pre_doctitle'], '', $page_info['doctitle']);
		}
	?>
	CHV.obj.page_info = <?php echo json_encode($page_info); ?>;

	<?php
		$logged_user = CHV\Login::getUser();
		if($logged_user) {
			$logged_user_array = [];
			foreach(['name', 'username', 'id', 'url', 'url_albums'] as $arr) {
				$logged_user_array[$arr] = $logged_user[$arr == 'id' ? 'id_encoded' : $arr];
			}

	?>
	CHV.obj.logged_user = <?php echo json_encode($logged_user_array); ?>;
	<?php
		}
		if($logged_user['is_admin']) {
	?>
	CHV.obj.system_info = <?php echo json_encode(['version' => G\get_app_version()]); ?>;
	<?php
		}
	?>

	<?php
	if(!G\is_prevented_route() and !is_404() && in_array(G\get_route_name(), ["image", "album", "user", "settings"]) or (function_exists('is_dashboard_user') and is_dashboard_user())) {
		if(in_array(G\get_route_name(), ["settings", "dashboard"])) {
			$route = ['id'	=> NULL, 'url'	=> NULL];
			$route_user = get_user();
		} else {
			$route_fn = "get_".G\get_route_name();
			if(is_callable($route_fn)) {
				$route = $route_fn();
				$route_user = G\get_route_name() == "user" ? $route : $route["user"];
			}
		}
	?>
	CHV.obj.resource = {
		id: "<?php echo $route["id_encoded"]; ?>",
		type: "<?php echo G\get_route_name(); ?>",
		url: "<?php echo (G\get_route_name() == "image" ?  $route["url_viewer"] : $route["url"]); ?>",
		parent_url: "<?php echo G\get_route_name() == "image" ? (get_image()['user']['is_private'] ? G\get_base_url() : get_image()['album']['url']) : (G\get_route_name() == 'dashboard' ? NULL : $route_user['url']) ?>"
	};

	<?php
		if($route_user["id"]) {
	?>
	CHV.obj.resource.user = {
		name: "<?php echo G\safe_html($route_user["name"]); ?>",
		username: "<?php echo G\safe_html($route_user["username"]); ?>",
		id: "<?php echo $route_user["id_encoded"]; ?>",
		url: "<?php echo $route_user["url"]; ?>",
		url_albums: "<?php echo $route_user["url_albums"]; ?>"
	};
	<?php
		}
	}
	?>
});
</script>