<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<script>
	var hasClass = function(element, cls) {
		return (" " + element.className + " ").indexOf(" " + cls + " ") > -1;
	}
	
	user_background_full_fix = function() {
		var top_bar = {
				node: document.getElementById("top-bar")
			},
			cover = document.getElementById("background-cover")
			top_user = {
				node: document.getElementById("top-user")
			},
			canvas = {
				height: window.innerHeight
			},
			html = document.getElementsByTagName("html")[0];
		
		if(hasClass(cover, 'no-background')) {
			return;
		}
		
		top_user.style = top_user.node.currentStyle || window.getComputedStyle(top_user.node);
		top_user.outerHeight = parseInt(top_user.node.offsetHeight) + parseInt(top_user.style.marginTop) + parseInt(top_user.style.marginBottom);
		
		var cover_size_chart = {
			ratio: 0.7,
			min: 300
		}
		
		cover.style.height = Math.max(cover_size_chart.min, cover_size_chart.ratio*(canvas.height - top_user.outerHeight)) + "px";
		
		if(!hasClass(top_bar.node, "white")) {
			if(!hasClass(top_bar.node, "background-transparent")) {
				top_bar.node.className += " background-transparent";
			}
			if(!hasClass(top_bar.node, "transparent")) {
				top_bar.node.className += " transparent";
			}
			if(!document.getElementById("top-bar-shade")) {
				var top_bar_placeholder = document.createElement('div');
				
				top_bar_placeholder.className = "top-bar";
				if(top_bar.node.className.indexOf("white") > -1) {
					top_bar_placeholder.className += " white";
				}
				
				top_bar_placeholder.setAttribute("id", "top-bar-shade");
				
				document.getElementsByTagName("body")[0].insertBefore(top_bar_placeholder, document.getElementsByTagName("body")[0].firstChild);
			}
		}
	
	}
	user_background_full_fix();
</script>