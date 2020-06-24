<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<script data-cfasync="false">

	// Loader js
    var divLoading = document.createElement("div");
    divLoading.id = "image-viewer-loading";
    divLoading.className = "soft-hidden";
    document.getElementById("image-viewer").appendChild(divLoading)

	// Topbar native js thing
	document.getElementById("top-bar").className += ' transparent';

	// Fix the image-viewer height (or width) asap with native js
	image_viewer_full_fix = function() {

		var viewer = document.getElementById("image-viewer"),
			viewer_container = document.getElementById("image-viewer-container"),
			top = document.getElementById("top-bar"),
			imgSource = {
				width: <?php echo get_image()["width"]; ?>,
				height: <?php echo get_image()["height"]; ?>
			},
			img = {width: imgSource.width, height: imgSource.height},
			ratio = imgSource.width/imgSource.height;

		var canvas = {
				height: window.innerHeight - (typeof top !== "undefined" ? top.clientHeight : 0),
				width: viewer.clientWidth
			};

		var viewer_banner_top = <?php echo CHV\getSetting('banner_image_image-viewer_top') ? 1 : 0; ?>,
			viewer_banner_foot = <?php echo CHV\getSetting('banner_image_image-viewer_foot') ? 1 : 0; ?>;

		var viewer_banner_height = 90;

		if(viewer_banner_top) {
			canvas.height -= viewer_banner_height + 20;
		}
		if(viewer_banner_foot) {
			canvas.height -= viewer_banner_height + 20;
		}

		var hasClass = function(element, cls) {
			return (" " + element.className + " ").indexOf(" " + cls + " ") > -1;
		}

		if(hasClass(document.documentElement, "phone") || hasClass(document.documentElement, "phablet")) {

		}

		if(img.width > canvas.width) {
			img.width = canvas.width;
		}
		img.height = (img.width/ratio);

		if(img.height > canvas.height && (img.height/img.width) < 3) {
			img.height = canvas.height;
		}
		if(img.height == canvas.height) {
			img.width = (img.height * ratio);
		}

        if(imgSource.width !== img.width) {
            if(img.width > canvas.width) {
                img.width = canvas.width;
                img.height = (img.width/ratio);
            } else if((img.height/img.width) > 3) { // wow, very tall. such heights
                img = imgSource;
                if(img.width > canvas.width) {
                    img.width = canvas.width * 0.8;
                }
                img.height = (img.width/ratio);
            }
        }

		if(imgSource.width > img.width || img.width <= canvas.width) {
			if(img.width == canvas.width || imgSource.width == img.width) { // Canvas width or max src width reached
				viewer_container.className = viewer_container.className.replace(/\s+cursor-zoom-(in|out)\s+/, " ");
			} else {
				if(!hasClass(viewer_container, "jscursor-zoom-in")) {
					viewer_container.className += " jscursor-zoom-in";
				} else {
					viewer_container.className = viewer_container.className.replace(/\s+jscursor-zoom-in\s+/, " ");
                    if(!hasClass(viewer_container, "cursor-zoom-in")) {
                        viewer_container.className += " cursor-zoom-in";
                        styleContainer = false;
                    }

				}
			}
            viewer_container.className = viewer_container.className.trim().replace(/ +/g, ' ');
		}

         img = {
            width: img.width + "px",
            height: img.height + "px",
            display: "block"
        }

        if(viewer_container.style.width !== img.width) {
            for(var k in img) {
                viewer_container.style[k] = img[k];
            }
        }

	}

	image_viewer_full_fix();

	// Bind the native fn to the CHV object
	document.addEventListener('DOMContentLoaded', function(event) {
		CHV.obj.image_viewer.image = {
			width: <?php echo get_image()["width"]; ?>,
			height: <?php echo get_image()["height"]; ?>,
			ratio: <?php echo number_format(get_image()['ratio'], 6, '.', ''); ?>,
			url: "<?php echo get_image()["url"]; ?>",
			medium: {
				url: "<?php echo get_image()["medium"]["url"]; ?>"
			},
			url_viewer: "<?php echo get_image()["url_viewer"]; ?>"
		};
		CHV.obj.image_viewer.album = {
			id_encoded: "<?php echo get_image()["album"]["id_encoded"]; ?>"
		};
		image_viewer_full_fix();
		CHV.fn.image_viewer_full_fix = window["image_viewer_full_fix"];
	});
</script>