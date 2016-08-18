<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<script>
var hasClass = function(element, cls) {
	return (" " + element.className + " ").indexOf(" " + cls + " ") > -1;
};
var top_bar = {
		node: document.getElementById("top-bar")
	},
	html = document.getElementsByTagName("html")[0];

if(!hasClass(html, "phone") && !hasClass(top_bar.node, "white")) {
	if(!hasClass(top_bar.node, "background-transparent")) {
		top_bar.node.className = top_bar.node.className + " transparent background-transparent";
	}
	
	if(!document.getElementById("top-bar-shade")) {
		var top_bar_placeholder = document.createElement("div");
		
		top_bar_placeholder.className = "top-bar";
		if(top_bar.node.className.indexOf("white") > -1) {
			top_bar_placeholder.className = top_bar_placeholder.className + " white";
		}
		top_bar_placeholder.setAttribute("id", "top-bar-shade");
		
		document.getElementsByTagName("body")[0].insertBefore(top_bar_placeholder, document.getElementsByTagName("body")[0].firstChild);
	}
}
</script>