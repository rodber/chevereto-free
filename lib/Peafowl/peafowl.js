/**
 * Peafowl JS
 * Copyright 2016 Rodolfo Berrios <www.rodolfoberrios.com>
 */

/**
 * Peafowl DOM functions and event listeners
 */
$(function(){

	$.ajaxSetup({
		url: PF.obj.config.json_api,
		cache: false,
		dataType: "json",
		data: {auth_token: PF.obj.config.auth_token}
	});

	/**
	 * WINDOW LISTENERS
	 * -------------------------------------------------------------------------------------------------
	 */
	$(window).bind("beforeunload",function() {
		if($("form", PF.obj.modal.selectors.root).data("beforeunload") == "continue") return;
		if($(PF.obj.modal.selectors.root).is(":visible") && PF.fn.form_modal_has_changed()) {
			return PF.fn._s("All the changes that you have made will be lost if you continue.");
		}
	});

	$(window).bind("hashchange", function() {
		// Call edit modal on #edit
		if(window.location.hash=="#edit" && !$(PF.obj.modal.selectors.root).exists()) $("[data-modal=edit]").first().click();
	});

	// Blind the tipTips on load
	PF.fn.bindtipTip();

	var resizeTimer, scrollTimer, width = $(window).width();
	// Fluid width on resize
	$(window).on("resize", function(){
		PF.fn.growl.fixPosition();
    PF.fn.modal.fixScrollbars();

		var device = PF.fn.getDeviceName(),
			handled = ["phone", "phablet"],
			desktop = ["tablet", "laptop", "desktop"];

		clearTimeout(resizeTimer);
		clearTimeout(scrollTimer);

		scrollTimer = setTimeout(function() {
			$(".follow-scroll-wrapper, .follow-scroll-placeholder").css({minHeight: ""});
			PF.obj.follow_scroll.set(true);
			PF.obj.follow_scroll.process(true);
		}, 100);

		//PF.fn.window_to_device(); // handled by window event parent
		var new_device = PF.fn.getDeviceName();

		if(new_device !== device && ($.inArray(device, handled) >= 0 && $.inArray(new_device, handled) == -1) || ($.inArray(device, desktop) >= 0 && $.inArray(new_device, desktop) == -1)) {
			PF.fn.close_pops();
		}

		$(".top-bar").css("top", "");
		$(PF.fn.topMenu.vars.menu).css("height", $(window).height());

		$("body").css({position: "", height: ""});

		$(".antiscroll").removeClass("jsly").data("antiscroll", ""); // Destroy for this?
		$(".antiscroll-inner").css({height: "", width: "", maxheight: ""}); // .pop-box, .pop-box-inner ?

		PF.fn.list_fluid_width();

		if(width !== $(window).width()) {

			if($("[data-action=top-bar-menu-full]", "#top-bar").hasClass("current")) {
				PF.fn.topMenu.hide(0);
			}

			var cols_fn = function() {
					PF.fn.listing.columnizer(true, 0);
					$(PF.obj.listing.selectors.list_item).show();
				};
			cols_fn();
		}

		width = $(window).width();

	});

	// Close the opened pop-boxes on HTML click
	$(document).on("click", "html", function(){
		PF.fn.close_pops();
	});


	/**
	 * SMALL HELPERS AND THINGS
	 * -------------------------------------------------------------------------------------------------
	 */

	// Attemp to replace .svg with .png for browsers that doesn't support it
	if($("html").hasClass("no-svg")){
		$("img.replace-svg").replace_svg();
	}

	// Keydown numeric input (prevents non numeric keys)
	$(document).on("keydown", ".numeric-input", function(e){
		e.keydown_numeric();
	});

	// The handly data-scrollto. IT will scroll the elements to the target
	$(document).on("click", "[data-scrollto]", function(e) {
		var target = $(this).data("scrollto"),
			$target = $(!target.match(/^\#|\./) ? "#"+target : target);

		if($target.exists()) {
			PF.fn.scroll($target);
		} else {
			console.log("PF scrollto error: target doesn't exists", $target);
		}
	});

	// The handly data-trigger. It will trigger click for elements with data-trigger
	$(document).on("click", "[data-trigger]", function(e) {

		var trigger = $(this).data("trigger"),
			$target = $(!trigger.match(/^\#|\./) ? "#"+trigger : trigger);

		if($target.exists()) {
			e.stopPropagation();
			e.preventDefault();
			if(!$target.closest(PF.obj.modal.selectors.root).length) {
				PF.fn.modal.close();
			}
			$target.click();
		} else {
			console.log("PF trigger error: target doesn't exists", $target);
		}
	});


	// Fix the auth_token inputs
	$("form[method=post]").each(function() {
		if(!$("input[name=auth_token]", this).exists()) {
			$(this).append($('<input>', {type: 'hidden', name: "auth_token", value: PF.obj.config.auth_token}));
		}
	});

	// Clear form like magic
	$(document).on("click", ".clear-form", function(){
		$(this).closest("form")[0].reset();
	});

	$(document).on("submit", "form[data-action=validate]", function(e) {

		var type = $(this).data("type"),
			errors = false,
			$validate = $(this).find("[required], [data-validate]");

		$validate.each(function() {

			var input_type = $(this).attr("type"),
				pattern = $(this).attr("pattern"),
				errorFn = function(el) {
					$(el).highlight();
					errors = true;
				};

			if($(this).is("[required]") && $(this).val() == "") {
				if($(this).is(":hidden")) {
					var $hidden_target = $($($(this).data("highlight")).exists() ? $(this).data("highlight") : "#" + $(this).data("highlight"));
					$($hidden_target).highlight();
				}
				errorFn(this);
			}

			if(typeof pattern == "undefined" && /mail|url/.test(input_type) == false) {
				return true;
			}

			if(pattern) {
				pattern = new RegExp(pattern);
				if(!pattern.test($(this).val())) {
					errorFn(this);
				}
			}

			if(input_type == "email" && !$(this).val().isEmail()) {
				errorFn(this);
			}

		});

		if(errors) {
			PF.fn.growl.expirable(PF.fn._s("Check the errors in the form to continue."));
			return false;
		}
	});

	// Co-combo breaker
	$(document).on("change", "select[data-combo]", function(){

		var $combo = $("#"+$(this).data("combo"));

		if($combo.exists()) {
			$combo.children(".switch-combo").hide();
		}

		var $combo_container = $("#" + $(this).closest("select").data("combo")),
			$combo_target = $("[data-combo-value~=" + $("option:selected", this).attr("value") + "]", $combo_container);

		if($combo_target.exists()){
			$combo_target
				.show()
				.find("[data-required]")
				.each(function() {
					$(this).attr("required", "required"); // re-enable any disabled required
				});
		}

		// Disable [required] in hidden combos
		$(".switch-combo", $combo_container).each(function() {
			if($(this).is(":visible")) return;
			$("[required]", this).attr("data-required", true).removeAttr("required");
		});

	});

	// Y COMO DICE: ESCAPE FROM THE PLANET OF THE APES
	$(document).on("keyup", function(e) {
		$this = $(e.target);
		if(e.keyCode == 27) {
			if($(PF.obj.modal.selectors.root).is(":visible") && !$this.is(":input")) {
				$("[data-action=cancel],[data-action=close-modal]", PF.obj.modal.selectors.root).first().click();
			}
		}
	});

	// Input events
	$(document).on("change", ":input", function(e){
		PF.fn.growl.close();
	});
	$(document).on("keyup", ":input", function(e){
		$(".input-warning", $(this).closest(".input-label")).html("");
	});
	$(document).on("blur", ":input", function(){
		var this_val = $.trim($(this).prop("value"));
		$(this).prop("value", this_val);
	});

	// Select all on an input type
	$(document).on("click", ":input[data-focus=select-all]", function() {
		 this.select();
	});

	// Input password strength
	$(document).on("keyup change blur", ":input[type=password]", function(){
		var password = testPassword($(this).val()),
			$parent = $(this).closest("div");

		if($(this).val() == "") {
			password.percent = 0;
			password.verdict = "";
		}

		$("[data-content=password-meter-bar]", $parent).width(password.percent);
		$("[data-text=password-meter-message]", $parent).removeClass("red-warning").text(password.verdict !== "" ? PF.fn._s(password.verdict) : "");

	});

	// Popup links
	$(document).on("click", "[rel=popup-link], .popup-link", function(e){
		e.preventDefault();
		var href = $(this)[typeof $(this).attr("href") !== "undefined" ? "attr" : "data"]("href");
		if(typeof href == "undefined") {
			return;
		}
		if(href.substring(0, 6) == "mailto" && PF.fn.isDevice(["phone", "phablet"])) {
			window.location = href;
			return false;
		}
		PF.fn.popup({href: href});
	});

	/**
	 * FOWLLOW SCROLL
	 * -------------------------------------------------------------------------------------------------
	 */
	$(window).scroll(function(){
		PF.obj.follow_scroll.process(); // todo:optimize
	});


	/**
	 * MODAL
	 * -------------------------------------------------------------------------------------------------
	 */

	// Call plain simple HTML modal
	$(document).on("click", "[data-modal=simple],[data-modal=html]", function(){
		var $target = $("[data-modal=" + $(this).data("target") + "], #"+$(this).data("target")).first();
		PF.fn.modal.call({template: $target.html(), buttons: false});
	});

	// Prevent modal submit form since we only use the form in the modal to trigger HTML5 validation
	$(document).on("submit", PF.obj.modal.selectors.root + " form", function(e){
		if($(this).data("prevented")) return false; // Don't send the form if is prevented
		if(typeof $(this).attr("method") !== "undefined") return; // Don't bind anything extra if is normal form
		return false; // Prevent default form handling
	});

	// Form/editable/confirm modal
	$(document).on("click", "[data-modal=edit],[data-modal=form],[data-confirm]", function(e){

		e.preventDefault();

		var $this = $(this);
		var $target;

		if($this.is("[data-confirm]")) {
			$target = $this;
			PF.obj.modal.type = "confirm";
		} else {

			$target = $("[data-modal=" + $this.data("target") + "], #"+$this.data("target")).first();

			if($target.length == 0) {
				$target = $("[data-modal=form-modal], #form-modal").first();
			}

			if($target.length == 0) {
				console.log("PF Error: Modal target doesn't exists.");
			}

			PF.obj.modal.type = $this.data("modal");
		}

		var args = $this.data("args"),
			submit_function = window[$target.data("submit-fn")],
			cancel_function = window[$target.data("cancel-fn")],
			onload_function = window[$target.data("load-fn")],
			submit_done_msg = $target.data("submit-done"),
			ajax = {
				url: $target.data("ajax-url") || (typeof $target.data("is-xhr") !== typeof undefined ? PF.obj.config.json_api : null),
				deferred: window[$target.data("ajax-deferred")]
			};

		// Window functions failed? Maybe those are named fn...
		if(typeof submit_function !== "function" && $target.data("submit-fn")) {
			var submit_fn_split = $target.data("submit-fn").split(".");
			submit_function = window;
			for(var i=0; i<submit_fn_split.length; i++) {
				submit_function = submit_function[submit_fn_split[i]];
			}
		}
		if(typeof cancel_function !== "function" && $target.data("cancel-fn")) {
			var cancel_fn_split = $target.data("cancel-fn").split(".");
			cancel_function = window;
			for(var i=0; i<cancel_fn_split.length; i++) {
				cancel_function = cancel_function[cancel_fn_split[i]];
			}
		}
		if(typeof load_function !== "function" && $target.data("load-fn")) {
			var load_fn_split = $target.data("load-fn").split(".");
			load_function = window;
			for(var i=0; i<load_fn_split.length; i++) {
				load_function = load_function[load_fn_split[i]];
			}
		}

		if(typeof ajax.deferred !== "object" && $target.data("ajax-deferred")) {
			var deferred_obj_split = $target.data("ajax-deferred").split(".");
			ajax.deferred = window;
			for(var i=0; i<deferred_obj_split.length; i++) {
				ajax.deferred = ajax.deferred[deferred_obj_split[i]];
			}
		}

		// Before fn
		var fn_before = window[$target.data("before-fn")];
		if(typeof fn_before !== "function" && $target.data("before-fn")) {
			var before_obj_split = $target.data("before-fn").split(".");
			fn_before = window;
			for(var i=0; i<before_obj_split.length; i++) {
				fn_before = fn_before[before_obj_split[i]];
			}
		}
		if(typeof fn_before == "function") {
			fn_before(e);
		}

		var inline_options = $(this).data("options") || {};

		// Confirm modal
		if($this.is("[data-confirm]")) {

			var default_options = {
					message: $this.data("confirm"),
					confirm: typeof submit_function == "function" ? submit_function(args) : "",
					cancel: typeof cancel_function == "function" ? cancel_function(args) : "",
					ajax: ajax
				};

			if($this.attr("href") && default_options.confirm == "") {
				default_options.confirm = function() {
					return window.location.replace($this.attr("href"));
				}
			}

			PF.fn.modal.confirm($.extend(default_options, inline_options));

		} else { // Form/editable

			var default_options = {
					template: $target.html(),
					button_submit: $(this).is("[data-modal=edit]") ? PF.fn._s("Save changes") : PF.fn._s("Submit"),
					confirm: function() {

						var form_modal_has_changed = PF.fn.form_modal_has_changed();

						// Conventional form handling
						var $form = $("form", PF.obj.modal.selectors.root);
						if(typeof $form.attr("action") !== "undefined") {
							$form.data("prevented", !form_modal_has_changed);
							PF.fn.modal.close();
							return;
						}

						// Handle the required thing for non-visible elements
						$(":input[name]", $form).each(function() {
							if(!$(this).is(":visible")) {
								var input_attr = $(this).attr("required");
								if(typeof input_attr !== typeof undefined && input_attr !== false) {
									$(this).prop("required", false).attr("data-required", "required");
								}
							} else {
								if($(this).attr("data-required") == "required") {
									$(this).prop("required", true);
								}
							}
						});

						// Detect HTML5 validation
						if(!$form[0].checkValidity()) {
							return false;
						}

						// Run the full function only when the form changes
						if(!form_modal_has_changed && !inline_options.forced) {
							PF.fn.modal.close();
							return;
						}

						if(typeof submit_function == "function") submit_fn = submit_function();
						if(typeof submit_fn !== "undefined" && submit_fn == false) {
							return false;
						}


						$(":input", PF.obj.modal.selectors.root).each(function(){
							$(this).val($.trim($(this).val()));
						});

						if($this.is("[data-modal=edit]")) {
							// Set the input values before cloning the html
							$target.html($(PF.obj.modal.selectors.body, $(PF.obj.modal.selectors.root).bindFormData()).html().replace(/rel=[\'"]tooltip[\'"]/g, 'rel="template-tooltip"'));
						}

						if(typeof ajax.url !== "undefined") {
							return true;
						} else {
							PF.fn.modal.close(
								function(){
									if(typeof submit_done_msg !== "undefined"){
										PF.fn.growl.expirable(submit_done_msg !== "" ? submit_done_msg : PF.fn._s("Changes saved successfully."));
									}
								}
							);
						}

					},
					cancel: function() {
						if(typeof cancel_fn == "function") cancel_fn = cancel_fn();
						if(typeof cancel_fn !== "undefined" && cancel_fn == false) {
							return false;
						}
						// nota: falta template aca
						if(PF.fn.form_modal_has_changed()) {
							if($(PF.obj.modal.selectors.changes_confirm).exists()) return;
							$(PF.obj.modal.selectors.box, PF.obj.modal.selectors.root).css({transition: "none"}).hide();
							$(PF.obj.modal.selectors.root).append('<div id="'+PF.obj.modal.selectors.changes_confirm.replace("#", "")+'"><div class="content-width"><h2>'+PF.fn._s("All the changes that you have made will be lost if you continue.")+'</h2><div class="'+ PF.obj.modal.selectors.btn_container.replace(".", "") +' margin-bottom-0"><button class="btn btn-input default" data-action="cancel">'+PF.fn._s("Go back to form")+'</button> <span class="btn-alt">'+PF.fn._s("or")+' <a data-action="submit">'+PF.fn._s("continue anyway")+'</a></span></div></div>');
							$(PF.obj.modal.selectors.changes_confirm).css("margin-top", -$(PF.obj.modal.selectors.changes_confirm).outerHeight(true)/2).hide().fadeIn("fast");
						} else {
							PF.fn.modal.close();
							if(window.location.hash=="#edit") window.location.hash = "";
						}
					},
					load: function() {
						if(typeof load_function == "function") load_function();
					},
					callback: function(){},
					ajax: ajax
			};
			PF.fn.modal.call($.extend(default_options, inline_options));
		}

	});

	// Check user login modal -> Must be login to continue
	if(!PF.fn.is_user_logged()){
		$("[data-login-needed]:input, [data-user-logged=must]:input").each(function(){
			$(this).attr("readonly", true);
		});
	}
	// nota: update junkstr
	$(document).on("click focus", "[data-login-needed], [data-user-logged=must]", function(e) {
		if(!PF.fn.is_user_logged()){
			e.preventDefault();
			e.stopPropagation();
			if($(this).is(":input")) $(this).attr("readonly", true).blur();
			PF.fn.modal.call({type: "login"});
		}
	});

	// Modal form keydown listener
	$(document).on("keydown", PF.obj.modal.selectors.root + " input", function(e){ // nota: solia ser keyup
		var $this = $(e.target),
			key = e.charCode || e.keyCode;
		if(key !== 13){
			PF.fn.growl.close();
			return;
		}
		if(key==13 && $("[data-action=submit]", PF.obj.modal.selectors.root).exists() && !$this.is(".prevent-submit")){ // 13 == enter key
			$("[data-action=submit]", PF.obj.modal.selectors.root).click();
		}
	});


	// Trigger modal edit on hash #edit
	// It must be placed after the event listener
	if(window.location.hash && window.location.hash=="#edit"){
		$("[data-modal=edit]").first().click();
	}

	/**
	 * MOBILE TOP BAR MENU
	 * -------------------------------------------------------------------------------------------------
	 */
	$(document).on("click", "[data-action=top-bar-menu-full]", function() {
		var hasClass = $('[data-action=top-bar-menu-full]', "#top-bar").hasClass("current");
		PF.fn.topMenu[hasClass ? "hide" : "show"]();
	});

	/**
	 * SEARCH INPUT
	 * -------------------------------------------------------------------------------------------------
	 */

	// Top-search feature
	$(document).on("click", "[data-action=top-bar-search]", function(){
		$("[data-action=top-bar-search-input]", ".top-bar").removeClass("hidden").show();
		$("[data-action=top-bar-search-input]:visible input").first().focus();
		if(is_ios() && !$(this).closest(PF.fn.topMenu.vars.menu).exists()) {
			$('.top-bar').css('position', 'absolute');
		}
		$("[data-action=top-bar-search]", ".top-bar").hide();
	});

	// Search icon click -> focus input
	$(document).on("click", ".input-search .icon-search", function(e){
		$("input", e.currentTarget.offsetParent).focus();
	});

	// Clean search input
	$(document).on("click", ".input-search .icon-close, .input-search [data-action=clear-search]", function(e){
		var $input = $("input", e.currentTarget.offsetParent);

		if($input.val()==""){
			if($(this).closest("[data-action=top-bar-search-input]").exists()){
				$("[data-action=top-bar-search-input]", ".top-bar").hide();
				$("[data-action=top-bar-search]", ".top-bar").removeClass("opened").show();
				if(is_ios() && $(this).closest("#top-bar").css("position") !== "fixed") {
					$(".top-bar").css("position", "fixed");
				}
			}
		} else {
			if(!$(this).closest("[data-action=top-bar-search-input]").exists()){
				$(this).hide();
			}
			$input.val("").change();
		}
	});

	// Input search clear search toggle
	$(document).on("keyup change", "input.search", function(e){
		var $input = $(this),
			$div = $(this).closest(".input-search");
		if(!$(this).closest("[data-action=top-bar-search-input]").exists()) {
			var todo = $input.val() == "" ? "hide" : "show";
			$(".icon-close, [data-action=clear-search]", $div)[todo]();
		}
	});

	/**
	 * POP BOXES (MENUS)
	 * -------------------------------------------------------------------------------------------------
	 */
	$(document).on("click mouseenter", ".pop-btn", function(e) {

		if(PF.fn.isDevice(["phone", "phablet"]) && (e.type=="mouseenter" || $(this).hasClass("pop-btn-desktop"))) {
			return;
		}

		var $this_click = $(e.target);
		var $pop_btn;
		var $pop_box;
		var devices = $.makeArray(["phone", "phablet"]);
		var $this = $(this);

		if(e.type=="mouseenter" && !$(this).hasClass("pop-btn-auto")) return;
		if($(this).hasClass("disabled") || (($this_click.closest(".current").exists() && !PF.fn.isDevice("phone")) && !$this_click.closest(".pop-btn-show").exists())) {
			return;
		}

		PF.fn.growl.close();

		e.stopPropagation();

		$pop_btn = $(this);
		$pop_box = $(".pop-box", $pop_btn);
		$pop_btn.addClass("opened");

		$(".pop-box-inner", $pop_box).css("max-height", "");

		if(PF.fn.isDevice(devices)) {
			var text =  $('.btn-text,.text,.pop-btn-text', $pop_btn).first().text();
			if(typeof text == "undefined" || text == "") {
				text = PF.fn._s("Select");
			}
			if(!$(".pop-box-header", $pop_box).exists()) {
				$pop_box.prepend($('<div/>', {
					"class": 'pop-box-header',
					"html": text + '<span class="btn-icon icon-close"></span></span>'
				}));
			}
		} else {
			$('.pop-box-header', $pop_box).remove();
			$pop_box.css({bottom: ''});
		}
		if($pop_box.hasClass("anchor-center") && typeof $pop_box.data("guidstr") == typeof undefined){
			if(!PF.fn.isDevice(devices)) {
				$pop_box.css("margin-left", -($pop_box.width()/2));
			} else {
				$pop_box.css("margin-left", "");
			}
		}

		// Pop button changer
		if($this_click.is("[data-change]")){
			$("li", $pop_box).removeClass("current");
			$this_click.closest("li").addClass("current");
			$("[data-text-change]", $pop_btn).text($("li.current a", $pop_box).text());
			e.preventDefault();
		}

		if(!$pop_box.exists()) return;

		// Click inside the bubble only for .pop-keep-click
		var $this = e.istriggered ? $(e.target) : $(this);
		if($pop_box.is(":visible") && $(e.target).closest(".pop-box-inner").exists() && $this.hasClass("pop-keep-click")){
			return;
		}

		$(".pop-box:visible").not($pop_box).hide().closest(".pop-btn").removeClass("opened");

		var callback = function($pop_box) {
			if(!$pop_box.is(":visible")) {
				var guidstr = $pop_box.attr("data-guidstr");
				$pop_box
					.css("marginLeft", "")
					.removeClass(guidstr)
					.removeAttr("data-guidstr")
					.closest(".pop-btn")
					.removeClass("opened");
				if(typeof guidstr !== typeof undefined) {
					$("style#" + guidstr).remove();
				}
			} else {
				if(!PF.fn.isDevice(devices)) {
					var posMargin = $pop_box.css("marginLeft");
					if(typeof posMargin !== typeof undefined) {
						posMargin = parseFloat(posMargin);
						$pop_box.css("marginLeft", "");
					}
					var cutoff = $pop_box.getWindowCutoff();
					if((cutoff && (cutoff.left || cutoff.right)) && cutoff.right < posMargin) {
						var guidstr = "guid-" + PF.fn.guid();
						$pop_box.css("marginLeft", cutoff.right + "px").addClass(guidstr).attr("data-guidstr", guidstr);
						var posArrow = $this.outerWidth()/2 + $this.offset().left - $pop_box.offset().left;
						var selectors = [];
						$.each(["top", "bottom"], function(i, v) {
							$.each(["after", "before"], function(ii, vv) {
								selectors.push('.' + guidstr + '.arrow-box-' + v + ':' + vv);
							})
						});
						$('<style id="' + guidstr +'">' + selectors.join() + ' { left: '+ posArrow +'px; }</style>').appendTo("head");
					} else {
						$pop_box.css("marginLeft", posMargin + "px");
					}
					$(".antiscroll-wrap:not(.jsly):visible", $pop_box).addClass("jsly").antiscroll();
				} else {
					$(".antiscroll-inner", $pop_box).height("100%");
				}
			}
		};

		if(PF.fn.isDevice(devices)) {

			if($(this).is("[data-action=top-bar-notifications]")) {
				$pop_box.css({height: $(window).height()});
			}
			var pop_box_h = $pop_box.height()+'px';

			var menu_top = (parseInt($(".top-bar").outerHeight()) + parseInt($(".top-bar").css("top")) + parseInt($(".top-bar").css("margin-top")) + parseInt($(".top-bar").css("margin-bottom"))) + "px";

			// hide
			if($pop_box.is(":visible")) {
				$('#pop-box-mask').css({opacity: 0});
				$pop_box.css({transform: "none"});
				if($this.closest(PF.fn.topMenu.vars.menu).exists()) {
					$(".top-bar").css({transform: "none"});
					$(PF.fn.topMenu.vars.menu).css({
						height: $(window).height() + parseInt(menu_top),
					});
				}
                setTimeout(function() {
                    $pop_box.hide().attr("style", "");
                    $('#pop-box-mask').remove();
					callback($pop_box);
					if($this.closest(PF.fn.topMenu.vars.menu).exists()) {
						$(PF.fn.topMenu.vars.menu).css({
							height: "",
						});
						$(PF.fn.topMenu.vars.menu).animate({scrollTop: PF.fn.topMenu.vars.scrollTop}, PF.obj.config.animation.normal / 2);
					}
                }, PF.obj.config.animation.normal);
				if(!$("body").data("hasOverflowHidden")) {
					$("body").removeClass("overflow-hidden");
				}

			} else { // show
				$('#pop-box-mask').remove();
				$pop_box.parent().prepend($('<div/>', {
					"id": 'pop-box-mask',
					"class": 'fullscreen soft-black'
				}).css({
                    zIndex: 400,
                    display: "block"
                }));
				PF.fn.topMenu.vars.scrollTop = $(PF.fn.topMenu.vars.menu).scrollTop();
                setTimeout(function() {
                    $("#pop-box-mask").css({opacity: 1});
                    setTimeout(function() {
                        $pop_box.show().css({
                            bottom: '-' + pop_box_h,
                            maxHeight: $(window).height(),
                            zIndex: 1000,
                            transform: "translate(0,-"+pop_box_h+")"
                        });

                         setTimeout(function() {
                            callback($pop_box);
                        }, PF.obj.config.animation.normal);

                        if($("body").hasClass("overflow-hidden")) {
                            $("body").data("hasOverflowHidden", 1);
                        } else {
                            $("body").addClass("overflow-hidden");
                        }

                        if($this.closest(PF.fn.topMenu.vars.menu).exists()) {
                            $(".top-bar").css({transform: "translate(0, -" + menu_top + ")"});
                            $(PF.fn.topMenu.vars.menu).css({
                                height: $(window).height() + parseInt(menu_top),
                            });
                        }
                        $(".pop-box-inner", $pop_box).css("height", $pop_box.height() - $('.pop-box-header', $pop_box).outerHeight(true));

                    }, 1);

                }, 1);
			}
		} else {

			$pop_box[($pop_box.is(":visible") ? "hide" : "show")](0, function() {
				callback($pop_box);
			});
		}


	}).on("mouseleave", ".pop-btn", function(){
		if(!PF.fn.isDevice(["laptop", "desktop"])) {
			return;
		}
		var $pop_btn = $(this),
			$pop_box = $(".pop-box", $pop_btn);

		if(!$pop_btn.hasClass("pop-btn-auto") || (PF.fn.isDevice(["phone", "phablet"]) && $pop_btn.hasClass("pop-btn-auto"))) {
			return;
		}

		if(!PF.fn.isDevice(['phone', 'phablet', 'tablet']) && $(this).hasClass("pop-btn-delayed")) {
			$(this).removeClass("pop-btn-auto");
		}

		$pop_box.hide().closest(".pop-btn").removeClass("opened");
	});

	$(".pop-btn-delayed").delayedAction(
		{
			delayedAction: function($element) {
				if(PF.fn.isDevice(['phone', 'phablet', 'tablet'])) return;
				var $el = $(".pop-box-inner", $element);
				if($el.is(":hidden")) {
					$element.addClass("pop-btn-auto").click();
				}
	        },
	        hoverTime: 2000
		}
	);

	/**
	 * TABS
	 * -------------------------------------------------------------------------------------------------
	 */

	// Hash on load (static tabs) changer
	if(window.location.hash){
		/*
		var $hash_node = $("[href="+ window.location.hash +"]");

		if($hash_node.exists()) {
			$.each($("[href="+ window.location.hash +"]")[0].attributes, function(){
				PF.obj.tabs.hashdata[this.name] = this.value;
			});
			PF.obj.tabs.hashdata.pushed = "tabs";
			History.replaceState({
				href: window.location.hash,
				"data-tab": $("[href="+ window.location.hash +"]").data("tab"),
				pushed: "tabs",
				statenum: 0
			}, null, null);
		}
		*/
	}

	// Stock tab onload data
	if($(".content-tabs").exists()/* && !window.location.hash*/) {
		var $tab = $("a", ".content-tabs .current");
		History.replaceState({
			href: $tab.attr("href"),
			"data-tab": $tab.data("tab"),
			pushed: "tabs",
			statenum: 0
		}, null, null);
	}

	// Keep scroll position (history.js)
	var State = History.getState();
	if(typeof State.data == "undefined") {
		History.replaceState({scrollTop: 0}, document.title, window.location.href); // Stock initial scroll
	}
	History.Adapter.bind(window,"popstate", function(){
		var State = History.getState();
		if(State.data && typeof State.data.scrollTop !== "undefined") {
			if($(window).scrollTop() !== State.data.scrollTop) {
				$(window).scrollTop(State.data.scrollTop);
			}
		}
		return;
	});

	// Toggle tab display
	$("a", ".content-tabs").click(function(e) {

		if($(this).data("link") == true) {
			$(this).data("tab", false);
		}

		if($(this).closest(".current,.disabled").exists()){
			e.preventDefault();
			return;
		}
		if(typeof $(this).data("tab") == "undefined") return;

		var dataTab = {};
		$.each(this.attributes, function(){
			dataTab[this.name] = this.value;
		});
		dataTab.pushed = "tabs";

		// This helps to avoid issues on ?same and ?same#else
		/*dataTab.statenum = 0;
		console.log({
			data: History.getState().data,
			state: History.getState().data.statenum
		})
		if(History.getState().data && typeof History.getState().data.statenum !== "undefined") {
			dataTab.statenum = History.getState().data.statenum + 1
		}*/

		/*if($(this).attr("href") && $(this).attr("href").indexOf("#") === 0) {  // to ->#Hash
			PF.obj.tabs.hashdata = dataTab;
			if(typeof e.originalEvent == "undefined") {
				window.location.hash = PF.obj.tabs.hashdata.href.substring(1);
			}
		} else { // to ->?anything
			if($("#" + dataTab["data-tab"]).data("load") != "classic") {
				History.pushState(dataTab, document.title, $(this).attr("href"));
				e.preventDefault();
			}
		}
		*/
		if($("#" + dataTab["data-tab"]).data("load") != "classic") {
			if(window.location.hash) {
				var url = window.location.href;
				url = url.replace(window.location.hash, "");
			}
			History.pushState(dataTab, document.title, (typeof url !== "undefined") ? url : $(this).attr("href"));
			e.preventDefault();
		}

		var $tab_menu = $("[data-action=tab-menu]", $(this).closest(".header"));

		$tab_menu.find("[data-content=current-tab-label]").text($(this).text());

		if($tab_menu.is(":visible")) {
			$tab_menu.click();
		}

	});

	$(document).on("click", "[data-action=tab-menu]", function() {
		var $tabs = $(this).closest(".header").find(".content-tabs"),
			visible = $tabs.is(":visible"),
			$this = $(this);
		if(!visible) {
			$tabs.data("classes", $tabs.attr("class"));
			$tabs.removeClass(function (index, css) {
				return (css.match(/\b\w+-hide/g) || []).join(' ');
			});
			$tabs.hide();
		}
		if(!visible) {
			$this.removeClass("current");
		}
        $tabs[visible ? "hide" : "show"]();
        if(visible) {
            $tabs.css("display", "").addClass($tabs.data("classes"));
            $this.addClass("current");
        }
	});

	// On state change bind tab changes
	$(window).bind("statechange", function(e) {
		PF.fn.growl.close();
		var dataTab;
		dataTab = History.getState().data;
		if(dataTab && dataTab.pushed == "tabs"){
			PF.fn.show_tab(dataTab["data-tab"]);
		}
	});

	/**
	 * LISTING
	 * -------------------------------------------------------------------------------------------------
	 */

	// Stock the scroll position on list element click
	$(document).on("click", ".list-item a", function(e) {
		if($(this).attr("src") == "") return;
		History.replaceState({scrollTop: $(window).scrollTop()}, document.title, window.location.href);
	});

	// Load more (listing +1 page)
	$(document).on("click", "[data-action=load-more]", function(e){

		$(this).closest('.content-listing-more').hide();

		if(!PF.fn.is_listing() || $(this).closest(PF.obj.listing.selectors.content_listing).is(":hidden") || $(this).closest("#content-listing-template").exists() || PF.obj.listing.calling) return;

		PF.fn.listing.queryString.stock_new();

		// Page hack
		PF.obj.listing.query_string.page = $(PF.obj.listing.selectors.content_listing_visible).data("page");
		PF.obj.listing.query_string.page++;

		// Offset hack
		var offset = $(PF.obj.listing.selectors.content_listing_visible).data("offset");

		if(typeof offset !== "undefined") {
			PF.obj.listing.query_string.offset = offset;
			if(typeof PF.obj.listing.params_hidden == "undefined") {
				PF.obj.listing.params_hidden = {};
			}
			PF.obj.listing.params_hidden.offset = offset;
		} else {
			if(typeof PF.obj.listing.query_string.offset !== "undefined") {
				delete PF.obj.listing.query_string.offset;
			}
			if(PF.obj.listing.params_hidden && typeof PF.obj.listing.params_hidden.offset !== "undefined") {
				delete PF.obj.listing.params_hidden.offset;
			}
		}

		PF.fn.listing.ajax();
		e.preventDefault();

	});

	// List found on load html -> Do the columns!
	if($(PF.obj.listing.selectors.list_item).length > 0){
		PF.fn.listing.show();

		// Bind the infinte scroll
		$(window).scroll(function() {
			var $loadMore = $(PF.obj.listing.selectors.content_listing_pagination, PF.obj.listing.selectors.content_listing_visible).find("[data-action=load-more]");
			if($loadMore.length > 0 && (($(window).scrollTop() + $(window).innerHeight()) > ($(document).height() - 300)) && PF.obj.listing.calling == false) {
			   $loadMore.click();
			}
        });

	}

	// Multi-selection tools
	$(document).on("click", PF.obj.modal.selectors.root+ " [data-switch]", function(){
		var $this_modal = $(this).closest(PF.obj.modal.selectors.root);
		$("[data-view=switchable]", $this_modal).hide();
		$("#"+$(this).attr("data-switch"), $this_modal).show();
	});

	$(document).on("click", "[data-toggle]", function() {
		var $target = $("[data-content=" + $(this).data("toggle") + "]");
		var show = !$target.is(":visible");
		$(this).html($(this).data('html-' + (show ? 'on' : 'off')));
		$target.toggle();
	});

	// Cookie law thing
	$(document).on("click", "[data-action=cookie-law-close]", function(){
		var $cookie = $(this).closest("#cookie-law-banner");
		var cookieName = (typeof $cookie.data("cookie") !== typeof undefined) ? $cookie.data("cookie") : "PF_COOKIE_LAW_DISPLAY";
		Cookies.set(cookieName, 0, {expires: 365});
		$cookie.remove();
	});

	// One-click input copy
	Clipboard = new Clipboard("[data-action=copy]", {
		text: function(trigger) {
			var $target = $(trigger.getAttribute("data-action-target"));
			var text = $target.is(":input") ? $target.val() : $target.text();
			return text.trim();
		}
	});
	Clipboard.on('success', function(e) {
		var $target = $(e.trigger.getAttribute("data-action-target"));
		$target.highlight();
		e.clearSelection();
	});

});

/**
 * PEAFOWL OBJECT
 * -------------------------------------------------------------------------------------------------
 */
var PF = {fn: {}, str: {}, obj: {}};

/**
 * PEAFOWL CONFIG
 * -------------------------------------------------------------------------------------------------
 */
PF.obj.config = {
	base_url: "",
	json_api: "/json/",
	listing: {
		items_per_page: 24
	},
	animation: {
		easingFn: "ease",
		normal: 400,
		fast: 250
	}
};

/**
 * WINDOW VARS
 * -------------------------------------------------------------------------------------------------
 */

/**
 * LANGUAGE FUNCTIONS
 * -------------------------------------------------------------------------------------------------
 */
PF.obj.l10n = {};

/**
 * Get lang string by key
 * @argument string (lang key string)
 */
// pf: get_pf_lang
PF.fn._s = function(string, s){
	var string;
	if(typeof string == "undefined") {
		return string;
	}
	if(typeof PF.obj.l10n !== "undefined" && typeof PF.obj.l10n[string] !== "undefined") {
		string = PF.obj.l10n[string][0];
		if(typeof string == "undefined") {
			string = string;
		}
	} else {
		string = string;
	}
	string = string.toString();
	if(typeof s !== "undefined") {
		string = sprintf(string, s);
	}
	return string;
};

PF.fn._n = function(singular, plural, n){
	var string;
	if(typeof PF.obj.l10n !== "undefined" && typeof PF.obj.l10n[singular] !== "undefined") {
		string = PF.obj.l10n[singular][n == 1 ? 0 : 1];
	} else {
		string = n == 1 ? singular : plural;
	}
	string = typeof string == "undefined" ? singular : string.toString();
	if(typeof n !== "undefined") {
		string = sprintf(string, n);
	}
	return string;
};

/**
 * Extend Peafowl lang
 * Useful to add or replace strings
 * @argument strings obj
 */
// pf: extend_pf_lang
PF.fn.extend_lang = function(strings){
	$.each(PF.obj.lang_strings, function(i,v){
		if(typeof strings[i] !== "undefined") {
			$.extend(PF.obj.lang_strings[i], strings[i]);
		}
	});
};

/**
 * HELPER FUNCTIONS
 * -------------------------------------------------------------------------------------------------
 */

PF.fn.get_url_vars = function(){
	var match,
		pl     = /\+/g,  // Regex for replacing addition symbol with a space
		search = /([^&=]+)=?([^&]*)/g,
		decode = function (s) {
			return decodeURIComponent(escape(s.replace(pl, " ")));
		},
		query  = window.location.search.substring(1),
		urlParams = {};

	while(match = search.exec(query)){
		urlParams[decode(match[1])] = decode(match[2]);
	}

	return urlParams;

};

PF.fn.get_url_var = function(name){
	return PF.fn.get_url_vars()[name];
};

PF.fn.is_user_logged = function() {
	return $("#top-bar-user").exists(); // nota: default version
	// It should use backend conditional
};

PF.fn.generate_random_string = function(len){
	if(typeof len == "undefined") len = 5;
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for(var i=0; i < len; i++){
        text += possible.charAt(Math.floor(Math.random() * possible.length));
	}
    return text;
};

PF.fn.getDateTime = function() {
	var now     = new Date();
	var year    = now.getFullYear();
	var month   = now.getMonth()+1;
	var day     = now.getDate();
	var hour    = now.getHours();
	var minute  = now.getMinutes();
	var second  = now.getSeconds();
	if(month.toString().length == 1) {
	var month = '0'+month;
	}
	if(day.toString().length == 1) {
	var day = '0'+day;
	}
	if(hour.toString().length == 1) {
	var hour = '0'+hour;
	}
	if(minute.toString().length == 1) {
	var minute = '0'+minute;
	}
	if(second.toString().length == 1) {
	var second = '0'+second;
	}
	var dateTime = year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;
	return dateTime;
};

PF.fn.htmlEncode = function(value) {
  return $('<div/>').text($.trim(value)).html();
};

PF.fn.htmlDecode = function(value) {
  return $('<div/>').html($.trim(value)).text();
};

PF.fn.nl2br = function(str) {
    var breakTag = '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
};

// https://gist.github.com/alexey-bass/1115557
PF.fn.versionCompare = function(left, right) {
    if (typeof left + typeof right != 'stringstring')
        return false;

    var a = left.split('.')
    ,   b = right.split('.')
    ,   i = 0, len = Math.max(a.length, b.length);

    for (; i < len; i++) {
        if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
            return 1;
        } else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
            return -1;
        }
    }

    return 0;
}

/**
 * Basename
 * http://stackoverflow.com/questions/3820381/need-a-basename-function-in-javascript
 */
PF.fn.baseName = function(str) {
	var base = new String(str).substring(str.lastIndexOf('/') + 1);
	if(base.lastIndexOf(".") != -1) {
		base = base.substring(0, base.lastIndexOf("."));
	}
	return base;
}

// https://stackoverflow.com/a/8809472
PF.fn.guid = function() {
	var d = new Date().getTime();
	 if (typeof performance !== 'undefined' && typeof performance.now === 'function'){
	     d += performance.now(); //use high-precision timer if available
	 }
	 return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
	     var r = (d + Math.random() * 16) % 16 | 0;
	     d = Math.floor(d / 16);
	     return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
	 });
}

PF.fn.md5 = function(string) {
	return SparkMD5.hash(string);
}

/**
 * dataURI to BLOB
 * http://stackoverflow.com/questions/4998908/convert-data-uri-to-file-then-append-to-formdata
 */
PF.fn.dataURItoBlob = function(dataURI) {
	// convert base64/URLEncoded data component to raw binary data held in a string
	var byteString;
	if (dataURI.split(',')[0].indexOf('base64') >= 0) {
		byteString = atob(dataURI.split(',')[1]);
	} else {
		byteString = unescape(dataURI.split(',')[1]);
	}
	// separate out the mime component
	var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
	// write the bytes of the string to a typed array
	var ia = new Uint8Array(byteString.length);
	for (var i = 0; i < byteString.length; i++) {
		ia[i] = byteString.charCodeAt(i);
	}
	return new Blob([ia], {type:mimeString});
}

PF.fn.clean_facebook_hash = function() {
	if(window.location.hash == "#_=_") {
		window.location.hash = "";
	}
};
PF.fn.clean_facebook_hash();

/**
 * Get the min and max value from 1D array
 */
Array.min = function(array){
    return Math.min.apply(Math, array);
};
Array.max = function(array){
    return Math.max.apply(Math, array);
};

/**
 * Return the sum of all the values in a 1D array
 */
Array.sum = function(array){
	return array.reduce(function(pv, cv){ return cv + pv});
};

/**
 * Return the size of an object
 */
Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

/**
 * Flatten an object
 */
Object.flatten = function(obj, prefix) {

	if(typeof prefix == "undefined") var prefix = "";

    var result = {};

	$.each(obj, function(key, value) {
		if(!value) return;
		if(typeof value == "object") {
			result = $.extend({}, result, Object.flatten(value, prefix + key + '_'));
		} else {
			result[prefix + key] = value;
		}
	});

	return result;
};

/**
 * Tells if the string is a number or not
 */
String.prototype.isNumeric = function(){
	return !isNaN(parseFloat(this)) && isFinite(this);
};

/**
 * Repeats an string
 */
String.prototype.repeat = function(num){
	return new Array(num + 1).join(this);
};

/**
 * Ucfirst
 */
String.prototype.capitalizeFirstLetter = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}


/**
 * Tells if the string is a email or not
 */
String.prototype.isEmail = function(){
	var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test(this);
};

// http://phpjs.org/functions/round/
String.prototype.getRounded = function(precision, mode) {
	var m, f, isHalf, sgn; // helper variables
	precision |= 0; // making sure precision is integer
	m = Math.pow(10, precision);
	value = this;
	value *= m;
	sgn = (value > 0) | -(value < 0); // sign of the number
	isHalf = value % 1 === 0.5 * sgn;
	f = Math.floor(value);

	if(isHalf) {
		switch (mode) {
			case 'PHP_ROUND_HALF_DOWN':
				value = f + (sgn < 0); // rounds .5 toward zero
			break;
			case 'PHP_ROUND_HALF_EVEN':
				value = f + (f % 2 * sgn); // rouds .5 towards the next even integer
			break;
			case 'PHP_ROUND_HALF_ODD':
				value = f + !(f % 2); // rounds .5 towards the next odd integer
			break;
			default:
				value = f + (sgn > 0); // rounds .5 away from zero
		}
	}

	return (isHalf ? value : Math.round(value)) / m;
};

/**
 * Return bytes from Size + Suffix like "10 MB"
 */
String.prototype.getBytes = function(){
	var units = ["KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
		suffix = this.toUpperCase().substr(-2);
	if(units.indexOf(suffix) == -1) {
		return this;
	}
	var pow_factor = units.indexOf(suffix) + 1;
	return parseFloat(this) * Math.pow(1000, pow_factor);
};

/**
 * Return size formatted from size bytes
 */
String.prototype.formatBytes = function(round) {
	var bytes = parseInt(this),
		units = ["KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
	if(!$.isNumeric(this)) {
		return false;
	}
	if (bytes < 1000) return bytes + " B";
	if(typeof round == "undefined") var round = 2;
	for(var i=0; i<units.length; i++) {
		var multiplier = Math.pow(1000, i + 1),
			threshold = multiplier * 1000;
		if(bytes < threshold) {
			var size = bytes / multiplier;
			return this.getRounded.call(size, round) + " " + units[i];
		}
	}
};

/**
 * Returns the image url.matches (multiple)
 */
String.prototype.match_image_urls = function() {
	return this.match(/\b(?:(http[s]?|ftp[s]):\/\/)?([^:\/\s]+)(:[0-9]+)?((?:\/\w+)*\/)([\w\-\.]+[^#?\s]+)([^#\s]*)?(#[\w\-]+)?\.(?:jpe?g|gif|png|bmp)\b/gim);
};

String.prototype.match_urls = function() {
	return this.match(/\b(?:(http[s]?|ftp[s]):\/\/)?([^:\/\s]+)(:[0-9]+)?((?:\/\w+)*\/)([\w\-\.]+[^#?\s]+)([^#\s]*)?(#[\w\-]+)?\b/gim);
};


// Add ECMA262-5 Array methods if not supported natively
if (!("indexOf" in Array.prototype)) {
    Array.prototype.indexOf= function(find, i /*opt*/) {
        if(i===undefined) i = 0;
        if(i<0) i+= this.length;
        if(i<0) i = 0;
        for(var n = this.length; i<n; i++) {
            if(i in this && this[i]===find) {
                return i;
			}
		}
        return -1;
    };
}

/**
 * Removes all the array duplicates without loosing the array order.
 */
Array.prototype.array_unique = function(){
	var result = [];
    $.each(this, function(i, e) {
        if ($.inArray(e, result) == -1) result.push(e);
    });
    return result;
};

PF.fn.deparam = function(querystring) {
	if(typeof querystring == "undefined" || !querystring) return;
	var obj = {},
		pairs = querystring.replace(/^[\?|&]*/, "").replace(/[&|\?]*$/, "").split("&");
	for(var i=0; i<pairs.length; i++) {
		var split = pairs[i].split('=');
		var key = decodeURIComponent(split[0]);
		var value = decodeURIComponent(split[1]);
		// Aready in the object?
		if(obj.hasOwnProperty(key) && !value) {
			continue;
		}
		obj[key] = value;
	}
	return obj;
};

// http://stackoverflow.com/a/1634841/1145912
String.prototype.removeURLParameter = function(key) {
	var url = "",
		deparam = PF.fn.deparam(this);
	if(typeof deparam[key] !== "undefined") {
		delete deparam[key];
	}
	return decodeURIComponent($.param(deparam));
};

/**
 * Truncate the middle of the URL just like Firebug
 * From http://stackoverflow.com/questions/10903002/shorten-url-for-display-with-beginning-and-end-preserved-firebug-net-panel-st
 */
String.prototype.truncate_middle = function(l) {
	var l = typeof(l) != "undefined" ? l : 40,
		chunk_l = (l/2),
		url = this.replace(/https?:\/\//g,"");

	if(url.length <= l) {
		return url;
	}

	function shortString(s, l, reverse) {
		var stop_chars = [' ','/', '&'],
			acceptable_shortness = l * 0.80, // When to start looking for stop characters
			reverse = typeof(reverse) != "undefined" ? reverse : false,
			s = reverse ? s.split("").reverse().join("") : s,
			short_s = "";

		for(var i=0; i < l-1; i++){
			short_s += s[i];
			if(i >= acceptable_shortness && stop_chars.indexOf(s[i]) >= 0) {
				break;
			}
		};
		if(reverse){ return short_s.split("").reverse().join(""); }
		return short_s;
	};

	return shortString(url, chunk_l, false) + "..." + shortString(url, chunk_l, true);
};


/**
 * Compare 2 arrays/objects
 * http://stackoverflow.com/questions/1773069/using-jquery-to-compare-two-arrays
 */
jQuery.extend({
    compare: function (a,b) {
        var obj_str = '[object Object]',
            arr_str = '[object Array]',
            a_type  = Object.prototype.toString.apply(a),
            b_type  = Object.prototype.toString.apply(b);
            if(a_type !== b_type){
				return false;
			} else if(a_type === obj_str){
                return $.compareObject(a,b);
            } else if(a_type === arr_str){
                return $.compareArray(a,b);
            }
            return (a === b);
    },
	compareArray: function (arrayA, arrayB) {
        var a,b,i,a_type,b_type;
        if (arrayA === arrayB) { return true;}
        if (arrayA.length != arrayB.length) { return false; }
        a = jQuery.extend(true, [], arrayA);
        b = jQuery.extend(true, [], arrayB);
        a.sort();
        b.sort();
        for (i = 0, l = a.length; i < l; i+=1) {
            a_type = Object.prototype.toString.apply(a[i]);
            b_type = Object.prototype.toString.apply(b[i]);
            if(a_type !== b_type){
                return false;
            }
            if($.compare(a[i],b[i]) === false){
                return false;
            }
        }
        return true;
    },
	compareObject: function(objA,objB){
        var i,a_type,b_type;
        // Compare if they are references to each other
        if (objA === objB) { return true;}
        if (Object.keys(objA).length !== Object.keys(objB).length) { return false;}
        for (i in objA) {
            if (objA.hasOwnProperty(i)) {
                if(typeof objB[i] === 'undefined'){
                    return false;
                } else {
                    a_type = Object.prototype.toString.apply(objA[i]);
                    b_type = Object.prototype.toString.apply(objB[i]);
                    if (a_type !== b_type) {
                        return false;
                    }
                }
            }
            if($.compare(objA[i],objB[i]) === false){
                return false;
            }
        }
        return true;
    }
});

/**
 * Tells if a selector exits in the dom
 */
jQuery.fn.exists = function(){
	return this.length > 0;
};

/**
 * Replace .svg for .png
 */
jQuery.fn.replace_svg = function(){
	if(!this.attr("src")) return;
	$(this).each(function(){
		$(this).attr("src", $(this).attr("src").replace(".svg", ".png"));
	});
};

/**
 * Detect fluid layout
 * nota: deberia ir en PF
 */
jQuery.fn.is_fluid = function(){
	return true;
	return(this.hasClass("fluid") || this.css("width")=="100%");
};

/**
 * jQueryfy the form data
 * Bind the attributes and values of form data to be manipulated by DOM fn
 */
jQuery.fn.bindFormData = function() {
	$(":input", this).each(function() {
		var safeVal = PF.fn.htmlEncode($(this).val());

		if($(this).is("input")){
			this.setAttribute("value", this.value);
			if(this.checked) {
				this.setAttribute("checked", "checked");
			} else {
				this.removeAttribute("checked");
			}
		}
		if($(this).is("textarea")){
			$(this).html(safeVal);
		}
		if($(this).is("select")){
			var index = this.selectedIndex,
				i = 0;
			$(this).children("option").each(function() {
				if (i++ != index) {
					this.removeAttribute("selected");
				} else {
					this.setAttribute("selected","selected");
				}
			});
		}
	});
	return this;
};

/** jQuery.formValues: get or set all of the name/value pairs from child input controls
 * @argument data {array} If included, will populate all child controls.
 * @returns element if data was provided, or array of values if not
 * http://stackoverflow.com/questions/1489486/jquery-plugin-to-serialize-a-form-and-also-restore-populate-the-form
 */
jQuery.fn.formValues = function(data) {
    var els = $(":input", this);
    if(typeof data != "object"){
        data = {};
        $.each(els, function(){
            if(this.name && !this.disabled && (this.checked || /select|textarea/i.test(this.nodeName) || /color|date|datetime|datetime-local|email|month|range|search|tel|time|url|week|text|number|hidden|password/i.test(this.type))){
				if(this.name.match(/^.*\[\]$/) && this.checked) {
					if(typeof data[this.name] == "undefined") {
						data[this.name] = [];
					}
					data[this.name].push($(this).val());
				} else {
					data[this.name] = $(this).val();
				}
            }
        });
        return data;
    } else {
        $.each(els, function() {
			if(this.name.match(/^.*\[\]$/) && typeof data[this.name] == "object") {
				$(this).prop("checked", data[this.name].indexOf($(this).val()) !== -1);
			} else  {
				if(this.name && data[this.name]){
					if(/checkbox|radio/i.test(this.type)) {
						$(this).prop("checked", (data[this.name] == $(this).val()));
					} else {
						$(this).val(data[this.name]);
					}
				} else if(/checkbox|radio/i.test(this.type)){
					$(this).removeProp("checked");
				}
			}
        });
        return $(this);
    }
};

jQuery.fn.storeformData = function(dataname){
	if(typeof dataname == "undefined" && typeof $(this).attr("id") !== "undefined"){
		dataname = $(this).attr("id");
	}
	if(typeof dataname !== "undefined") $(this).data(dataname, $(this).formValues());
	return this;
};

/**
 * Compare the $.data values against the current DOM values
 * It relies in using $.data to store the previous value
 * Data must be stored using $.formValues()
 *
 * @argument dataname string name for the data key
 */
jQuery.fn.is_sameformData = function(dataname){
	var $this = $(this);
	if(typeof dataname == "undefined") dataname = $this.attr("id");
	return jQuery.compare($this.formValues(), $this.data(dataname));
};

/**
 * Prevent non-numeric keydown
 * Allows only numeric keys to be entered on the target event
 */
jQuery.Event.prototype.keydown_numeric = function(){
	var e = this;

	if(e.shiftKey) {
		e.preventDefault();
		return false;
	}

	var key = e.charCode || e.keyCode,
		target = e.target,
		value = ($(target).val()=="") ? 0 : parseInt($(target).val());

	if(key == 13) { // Allow enter key
		return true;
	}

	if(key == 46 || key == 8 || key == 9 || key == 27 ||
		// Allow: Ctrl+A
		(key == 65 && e.ctrlKey === true) ||
		// Allow: home, end, left, right
		(key >= 35 && key <= 40)){
		// let it happen, don't do anything
		return true;
	} else {
		// Ensure that it is a number and stop the keypress
		if ((key < 48 || key > 57) && (key < 96 || key > 105 )){
			e.preventDefault();
		}
	}
};

/**
 * Detect canvas support
 */
PF.fn.is_canvas_supported = function(){
	var elem = document.createElement("canvas");
	return !!(elem.getContext && elem.getContext("2d"));
};

/**
 * Detect validity support
 */
PF.fn.is_validity_supported = function(){
	var i = document.createElement("input");
	return typeof i.validity === "object";
};

PF.fn.getScrollBarWidth = function() {
	var inner = document.createElement('p');
	inner.style.width = "100%";
	inner.style.height = "200px";

	var outer = document.createElement('div');
	outer.style.position = "absolute";
	outer.style.top = "0px";
	outer.style.left = "0px";
	outer.style.visibility = "hidden";
	outer.style.width = "200px";
	outer.style.height = "150px";
	outer.style.overflow = "hidden";
	outer.appendChild (inner);

	document.body.appendChild (outer);
	var w1 = inner.offsetWidth;
	outer.style.overflow = 'scroll';
	var w2 = inner.offsetWidth;
	if (w1 == w2) w2 = outer.clientWidth;

	document.body.removeChild (outer);

	return (w1 - w2);
};

PF.str.ScrollBarWidth = PF.fn.getScrollBarWidth();

/**
 * Updates the notifications button
 */
PF.fn.top_notifications_viewed = function(){
	var $top_bar_notifications = $("[data-action=top-bar-notifications]"),
		$notifications_lists = $(".top-bar-notifications-list", $top_bar_notifications),
		$notifications_count = $(".top-btn-number", $top_bar_notifications);

	if($(".persistent", $top_bar_notifications).exists()){
		$notifications_count.text($(".persistent", $top_bar_notifications).length).addClass("on");
	} else {
		$notifications_count.removeClass("on");
	}
};

/**
 * bind tipTip for the $target with options
 * @argument $target selector or jQuery obj
 * @argument options obj
 */
PF.fn.bindtipTip = function($target, options) {
	if(typeof $target == "undefined") $target = $("body");
	if($target instanceof jQuery == false) $target = $($target);
	var bindtipTipoptions = {
			delay: 0,
			content: false,
			fadeIn: 0
		};
	if(typeof options !== "undefined"){
		if(typeof options.delay !== "undefined") bindtipTipoptions.delay = options.delay;
		if(typeof options.content !== "undefined") bindtipTipoptions.content = options.content;
		if(typeof options.content !== "undefined") bindtipTipoptions.fadeIn = options.fadeIn;
	}
	if($target.attr("rel") !== "tooltip") $target = $("[rel=tooltip]", $target);

	$target.each(function(){
		if((typeof $(this).attr("href") !== "undefined" || typeof $(this).data("href") !== "undefined") && PF.fn.isDevice(["phone", "phablet", "tablet"])) {
			return true;
		}
		var position = typeof $(this).data("tiptip") == "undefined" ? "bottom" : $(this).data("tiptip");
		if(PF.fn.isDevice(["phone", "phablet"])) {
			position = "top";
		}
		$(this).tipTip({delay: bindtipTipoptions.delay, defaultPosition: position, content: bindtipTipoptions.content, fadeIn: bindtipTipoptions.fadeIn, fadeOut: 0});
	});
};

/**
 * form modal changed
 * Detects if the form modal (fullscreen) has changed or not
 * Note: It relies in that you save a serialized data to the
 */
PF.fn.form_modal_has_changed = function() {
	if($(PF.obj.modal.selectors.root).is(":hidden")) return;
	if(typeof $("html").data("modal-form-values") == typeof undefined) return;

	var data_stored = $("html").data("modal-form-values");
	var data_modal = PF.fn.deparam($(":input:visible", PF.obj.modal.selectors.root).serialize());
	var has_changed = false;
	var keys = $.extend({}, data_stored, data_modal);

	for(var k in keys) {
		if(data_stored[k] !== data_modal[k]) {
			has_changed = true;
			break;
		}
	}

	return has_changed;
};

/**
 * PEAFOWL CONDITIONALS
 * -------------------------------------------------------------------------------------------------
 */

PF.fn.is_listing = function(){
	return $(PF.obj.listing.selectors.content_listing).exists();
};

PF.fn.is_tabs = function(){
	return $(".content-tabs").exists();
};

/**
 * PEAFOWL EFFECTS
 * -------------------------------------------------------------------------------------------------
 */

/**
 * Shake effect
 * Shakes the element using CSS animations.
 * @argument callback fn
 */
jQuery.fn.shake = function(callback){
	this.each(function(init){
        var jqNode = $(this),
			jqNode_position = jqNode.css("position");

		if(!jqNode_position.match("relative|absolute|fixed")) jqNode.css({position: "relative"});

		var jqNode_left = parseInt(jqNode.css("left"));

		if(!jqNode_left.toString().isNumeric()) jqNode_left = 0;

		if(!jqNode.is(":animated")){
			for(var x = 1; x <= 2; x++){
				jqNode.animate({
					left: jqNode_left-10
				}, 0).animate({
					left: jqNode_left
				}, 30).animate({
					left: jqNode_left+10
				}, 30).animate({
					left: jqNode_left
				}, 30);
			};
			if(jqNode_position!=="static") jqNode.css({position: jqNode_position});
		};
    });
	if(typeof callback == "function") callback();
	return this;
};

/**
 * Highlight effect
 * Changes the background of the element to a highlight color and revert to original
 * @argument string (yellow|red|hex-color)
 */
jQuery.fn.highlight = function(color){
	if(this.is(":animated") || !this.exists()) return this;
	if(typeof color == "undefined") color = "yellow";

	var fadecolor = color;

	switch(color){
		case "yellow":
			fadecolor = "#FFFBA2";
		break;
		case "red":
			fadecolor = "#FF7F7F";
		break;
		default:
			fadecolor = color;
		break;
	};
	var base_background_color = $(this).css("background-color"),
		base_background = $(this).css("background");

	$(this).css({background: "", backgroundColor: fadecolor}).animate({backgroundColor: base_background_color }, 800, function(){
		$(this).css("background", "");
	});
	return this;
};

/**
 * Peafowl slidedown effect
 * Bring the element using slideDown-type effect
 * @argument speed (fast|normal|slow|int)
 * @argument callback fn
 */
jQuery.fn.pf_slideDown = function(speed, callback){

	var default_speed = "normal",
		this_length = $(this).length,
		css_prechanges, css_animation, animation_speed;

	if(typeof speed == "function"){
		callback = speed;
		speed = default_speed;
	}
	if(typeof speed == "undefined"){
		speed = default_speed;
	}

	$(this).each(function(index){
		var this_css_top = parseInt($(this).css("top")),
			to_top = this_css_top > 0 ? this_css_top : 0;

		if(speed == 0){
			css_prechanges = {display: "block", opacity: 0},
			css_animation = {opacity: 1},
			animation_speed = jQuery.speed("fast").duration;
		} else {
			css_prechanges = {top: -$(this).outerHeight(true), opacity: 1, display: "block"};
			css_animation = {top: to_top};
			animation_speed = jQuery.speed(speed).duration;
		}

		$(this).data("originalTop", $(this).css("top"));
		$(this).css(css_prechanges).animate(css_animation, animation_speed, function(){
			if (index == this_length - 1){
				if(typeof callback == "function"){
					callback();
				}
			}
		});
	});

	return this;
};

/**
 * Peafowl slideUp effect
 * Move the element using slideUp-type effect
 * @argument speed (fast|normal|slow|int)
 * @argument callback fn
 */
jQuery.fn.pf_slideUp = function(speed, callback){

	var default_speed = "normal",
		this_length = $(this).length;

	if(typeof speed == "function"){
		callback = speed;
		speed = default_speed;
	}
	if(typeof speed == "undefined"){
		speed = default_speed;
	}

	$(this).each(function(index){
		$(this).animate({top: -$(this).outerHeight(true)}, jQuery.speed(speed).duration, function(){
			$(this).css({display: "none", top: $(this).data("originalTop")});
			if(index == this_length - 1){
				if(typeof callback == "function"){
					callback();
				}
			}
		});
	});

	return this;
};

/**
 * Peafowl visible on viewport
 */
jQuery.fn.is_in_viewport = function(){
	var rect = $(this)[0].getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document. documentElement.clientHeight) && /*or $(window).height() */
        rect.right <= (window.innerWidth || document. documentElement.clientWidth) /*or $(window).width() */
    );
};

/**
 * Visible on current window stuff
 */
jQuery.fn.getWindowCutoff = function() {
	var rect = {
		top: $(this).offset().top,
		left: $(this).offset().left,
		width: $(this).outerWidth(),
		height: $(this).outerHeight(),
	};
	rect.right = rect.left + rect.width;
	rect.bottom = rect.top + rect.height;
	var detected = false;
	var cutoff = {
			top: rect.top > 0 ? 0 : rect.top,
			right: document.body.clientWidth - rect.right,
			bottom: document.body.clientHeight - rect.bottom,
			left: rect.left > 0 ? 0 : rect.left,
	};
	for(var key in cutoff) {
		if(cutoff[key] < 0) {
				detected = true;
		} else {
			cutoff[key] = 0;
		}
	}
	if(!detected) return null;
	return cutoff;
};

/**
 * Scroll the window to the target.
 * @argument target selector
 * @argument callback fn
 */
PF.fn.scroll = function(target, callback){
	if(typeof target == "function") {
		var callback = target,
			target = "";
	}

	var pxtop = parseInt($("body").css("margin-top"));
	if(pxtop==0 && $(".top-bar-placeholder").exists()) {
		pxtop = $(".top-bar-placeholder").height();
	}

	if(!$(target).exists()) target = "html";
	$("body,html").animate({scrollTop: $(target).offset().top - pxtop}, "normal", function(){
		if(typeof callback == "function") callback();
	});
};

PF.fn.close_pops = function(e){
	$(".pop-box:visible").each(function(){
		$(this).closest(".pop-btn").click();
	});
};

/**
 * Bring up a nice growl-like alert
 */
PF.fn.growl = {

	selectors: {
		root: "#growl"
	},

	str: {
		timeout: null,
		timeoutcall: false
	},

	/**
	 * Fires the growl
	 * @argument options object
	 */
	call: function(options){
		if(typeof options == "undefined") return;
		if(typeof options == "string"){
			options = {message: options};
		}
		if(typeof options.message == "undefined") return;

		var growl_options, $growl, growl_class, growl_color;

		growl_options = {
			message: options.message,
			insertTo: "body",
			where: "before",
			color: "default",
			css: {},
			classes: "",
			expires: 0,
			callback: function(){}
		};

		for(key in growl_options) {
			if(typeof options[key] !== "undefined") {
				if(key.match("/^(callback)$/")) {
					if(typeof options[key] == "function") {
						growl_options[key] = options[key];
					}
				} else {
					growl_options[key] = options[key];
				}

			}
		}

		if(!$(growl_options.insertTo).exists()){
			growl_options.insertTo = "body";
		}

		if($(PF.fn.growl.selectors.root).exists()){
			if($(PF.fn.growl.selectors.root).text() == growl_options.message){
				$(PF.fn.growl.selectors.root).shake();
				return;
			}
			$(PF.fn.growl.selectors.root).remove();
		}

		$growl = $('<div id="'+ PF.fn.growl.selectors.root.replace("#", "") +'" class="growl">'+growl_options.message+'<span class="icon icon-close" data-action="close"></span></div>').css(growl_options.css).addClass(growl_options.classes);

		growl_class = growl_options.insertTo !== "body" ? "static" : "";

		switch(growl_options.color){
			case "dark":
				growl_color = "dark";
			break;
			default:
				growl_color = "";
			break;
		}

		$growl.addClass(growl_class+" "+growl_color);

		if(growl_options.where == "before"){
			$(growl_options.insertTo).prepend($growl.hide());
		} else {
			$(growl_options.insertTo).append($growl.hide());
		}

		if($(".fullscreen").is(":visible")){
			$growl.css({"z-index": parseInt($(".fullscreen").css("z-index"))+1});
		}

		if($(PF.obj.modal.selectors.root).is(":visible")){
			var $modal_box = $(PF.obj.modal.selectors.box, PF.obj.modal.selectors.root);
			$growl.show();
			$growl.css("top", ($("#top-bar").outerHeight(true) - $growl.outerHeight(true))/2);

			PF.fn.growl.fixPosition();

			$growl.hide();
		}


		$growl.pf_slideDown(growl_class == "static" ? 0 : 200, function(){
			if(typeof growl_options.callback == "function"){
				growl_options.callback();
			}
		});

		$(document).on("click", ".growl", function(e){
			if(PF.fn.isDevice(["phone", "phablet"]) || $(e.target).is("[data-action=close]")) {
				PF.fn.growl.close(true);
			}
		});

		if(growl_options.expires > 0){
			if(typeof this.str.timeout == "number"){
				clearTimeout(this.str.timeout);
			}
			this.str.timeout = setTimeout(function(){
				PF.fn.growl.str.timeoutcall = true;
				PF.fn.growl.close();
			}, growl_options.expires);
		}

	},

	/**
	 * Fires an expirable growl (will close after time)
	 * @argument msg string
	 * @argument time int (ms)
	 */
	expirable: function(msg, time){
		if(typeof msg == "undefined") return;
		if(typeof time == "undefined") time = 5000;
		PF.fn.growl.call({message: msg, expires: time});
	},

	/**
	 * Closes the growl
	 * @argument callback fn
	 */
	close: function(forced, callback){
		var $growl = $(PF.fn.growl.selectors.root);

		if(forced) {
			this.str.timeout = null;
			this.str.timeoutcall = false;
			clearTimeout(this.str.timeout);
		}

		if(!$growl.exists() || (typeof this.str.timeout == "number" && !this.str.timeoutcall)) {
			return;
		}

		$growl.fadeOut("fast", function(){
			$(this).remove();
			if(typeof callback == "function"){
				callback();
			}
		});
	},

	fixPosition: function() {

		var $growl = $(PF.fn.growl.selectors.root);

		if(!$growl.exists() || !$(PF.obj.modal.selectors.root).exists()) {
			return;
		}

		if($growl.data("fixedPosition") == "scrollbar" && $(PF.obj.modal.selectors.root).hasScrollBar().vertical) {
			return;
		}

		var	offsetX = {
				modal: $(PF.obj.modal.selectors.box).offset().left,
				growl: $growl.offset().left
			},
			growlCompensate = offsetX.modal - offsetX.growl,
			marginLeft = growlCompensate < 0 ? ("-=" + Math.abs(growlCompensate)) : "-" + parseInt($growl.css("width"))/2;

		if(!PF.fn.isDevice(["phone", "phablet"])) {
			$growl.css("marginLeft", marginLeft + "px");
		}

		$growl.data("fixedPosition", $(PF.obj.modal.selectors.root).hasScrollBar().vertical ? "scrollbar" : "no-scrollbar");

	}

};

/**
 * Bring up a nice fullscreen modal
 */
PF.obj.modal = {
	type: "",
	selectors: {
		root: "#fullscreen-modal",
		box: "#fullscreen-modal-box",
		body: "#fullscreen-modal-body",
		login: "[data-modal=login]",
		changes_confirm: "#fullscreen-changes-confirm",
		btn_container: ".btn-container",
		close_buttons: ".close-modal,.cancel-modal,[data-action=cancel],[data-action-close]",
		submit_button: "[data-action=submit]",
		growl_placeholder: "#fullscreen-growl-placeholder"
	},
	ajax: {
		url: "",
		deferred: {}
	},
	locked: false,
	form_data: {},
	XHR: {},
	prevented: false
};
PF.obj.modal.$close_buttons = $(PF.obj.modal.selectors.close_buttons, PF.obj.modal.selectors.root);
PF.obj.modal.$submit_button = $(PF.obj.modal.selectors.submit_button, PF.obj.modal.selectors.root);

PF.fn.modal = {

	str: {
		transition: "all " + PF.obj.config.animation.fast + "ms ease"
	},

	/**
	 * Fires the modal
	 * @argument options object
	 */
	call:
		function(options){
			var modal_options, modal_base_template, modal_message;

			if(typeof options == "undefined") return;
			if(typeof options.template !== "undefined" && typeof options.type == "undefined") options.type = "html";
			if((typeof options.title == "undefined" || typeof options.message == "undefined") && (options.type !== "login" && options.type !== "html")) return;

			PF.fn.growl.close();

			modal_options = {
				forced: false,
				type: "confirm",
				title: options.title,
				message: options.message,
				html: false,
				template: options.template,
				buttons: true,
				button_submit: PF.fn._s("Submit"),
				txt_or: PF.fn._s("or"),
				button_cancel: PF.fn._s("cancel"),
				ajax: {url: null, data: null, deferred: {}},
				confirm: function() {},
				cancel: function(){
					PF.fn.modal.close();
				},
				load: function() {},
				callback: function() {}
			};

			for(key in modal_options) {
				if(typeof options[key] !== "undefined") {
					if((/^cancel|confirm|callback$/).test(key)) {
						if(typeof options[key] == "function") {
							modal_options[key] = options[key];
						}
					} else {
						modal_options[key] = options[key];
					}

				}
			}

			if(typeof options.ajax !== "undefined" && !options.ajax.url && options.ajax.deferred) {
				modal_options.ajax.url = PF.obj.config.json_api;
			}

			if(modal_options.type == "login"){
				modal_options.buttons = false;
			}

			if(modal_options.type == "confirm") {
				modal_options.button_submit = PF.fn._s("Confirm");
			}

			var overlay_background = "soft-black";
			if($("html").hasClass("tone-dark")) {
				overlay_background = "black";
			}

			var modal_base_template = [
				'<div id="', PF.obj.modal.selectors.root.replace("#", ""),
				'"class="fullscreen '+overlay_background+'"><div id="',
				PF.obj.modal.selectors.box.replace("#", ""),
				'"class="clickable"><div id="', PF.obj.modal.selectors.body.replace("#", ""),
				'">%MODAL_BODY%</div>%MODAL_BUTTONS%<span class="close-modal icon-close" data-action="close-modal"></span></div></div>'
			].join("");

			var modal_buttons = modal_options.buttons ? ['<div class="', PF.obj.modal.selectors.btn_container.replace(".", ""), '"><button class="btn btn-input default" data-action="submit" type="submit">', modal_options.button_submit, '</button> <span class="btn-alt">', modal_options.txt_or, '<a class="cancel" data-action="cancel">', modal_options.button_cancel, '</a></span></div>'].join("") : "";

			if(modal_options.type == "login"){
				modal_options.template = typeof modal_options.template == "undefined" ? $(PF.obj.modal.selectors.login).html() : modal_options.template;
			}

			var modalBodyHTML;

			switch(modal_options.type){
				case "html":
				case "login":
					modalBodyHTML = modal_options.template;
				break;
				case "confirm": default:
					modal_message = modal_options.message;
					if(!modal_options.html){
						modal_message = '<p>'+modal_message+'</p>';
					}
					modalBodyHTML = '<h1>'+modal_options.title+'</h1>'+modal_message;
				break;
			}

			if(typeof modalBodyHTML == "undefined") {
				console.log("PF Error: Modal content is empty");
				return;
			}

			modal_base_template = modal_base_template
				.replace("%MODAL_BODY%", modalBodyHTML)
				.replace("%MODAL_BUTTONS%", modal_buttons)
				.replace(/template-tooltip/g, "tooltip");

			$(PF.obj.modal.selectors.root).remove();

            $("body").data("overflow-hidden", $("body").hasClass("overflow-hidden"));
            $("body").prepend(modal_base_template).addClass("overflow-hidden");

            this.fixScrollbars();

            $("[rel=tooltip]", PF.obj.modal.selectors.root).each(function(){
                PF.fn.bindtipTip(this, {content:$(this).data("title")});
            });

			if($(":button, input[type=submit], input[type=reset]", PF.obj.modal.selectors.root).length > 0) {
				var $form = $("form", PF.obj.modal.selectors.root);
				if($form.exists()) {
					$form.append($($(PF.obj.modal.selectors.btn_container, PF.obj.modal.selectors.root).html()).wrapInner(PF.obj.modal.selectors.btn_container.replace(".", "")));
					$(PF.obj.modal.selectors.btn_container, PF.obj.modal.selectors.root).each(function() {
						if(!$(this).closest("form").exists()) {
							$(this).remove();
						}
					});
				} else {
					$(PF.obj.modal.selectors.box, PF.obj.modal.selectors.root).wrapInner('<form />');
				}
			}

			modal_options.callback();

			$(PF.obj.modal.selectors.box).css({transform: "scale(0.7)", opacity: 0, transition: PF.fn.modal.str.transition});
            $(PF.obj.modal.selectors.root).css({display: "block"});
            setTimeout(function() {
                $(PF.obj.modal.selectors.root).css({opacity: 1});
                $(PF.obj.modal.selectors.box).css({transform: "scale(1)", opacity: 1});
				if(typeof PFrecaptchaCallback !== typeof undefined) {
					PFrecaptchaCallback();
				}
                setTimeout(function() {
                    if(typeof modal_options.load == "function") {
                        modal_options.load();
                    }
                    // Stock default modal values
                    $("html").data("modal-form-values", PF.fn.deparam($(":input:visible", PF.obj.modal.selectors.root).serialize()));

                }, PF.obj.config.animation.fast);
            }, 1);

            // Bind the modal events
			$(PF.obj.modal.selectors.root).click(function(e){

				var $this = $(e.target),
					_this = this;

				if(PF.obj.modal.locked) {
					return;
				}

				// Changes confirm?
				if($this.closest(PF.obj.modal.selectors.changes_confirm).exists() && ($this.is(PF.obj.modal.selectors.close_buttons) || $this.is(PF.obj.modal.selectors.submit_button))) {

					$(PF.obj.modal.selectors.changes_confirm).remove();

					if($this.is(PF.obj.modal.selectors.close_buttons)) {
						$(PF.obj.modal.selectors.box, _this).fadeIn("fast", function() {
							$(this).css("transition", PF.fn.modal.str.transition);
						});
					} else {
						PF.fn.modal.close();
					}

				// Modal
				} else {

					if(!$this.closest(".clickable").exists() || $this.is(PF.obj.modal.selectors.close_buttons)) {
						PF.fn.growl.close();
						modal_options.cancel();
					}

					if($this.is(PF.obj.modal.selectors.submit_button)) {

						if(modal_options.confirm() === false) {
							return;
						}

						var modal_submit_continue = true;
						if($("input, textarea, select", PF.obj.modal.selectors.root).not(":input[type=button], :input[type=submit], :input[type=reset]").length > 0 && !PF.fn.form_modal_has_changed() && !modal_options.forced) {
							modal_submit_continue = false;
						}

						if(modal_submit_continue) {

							if(modal_options.ajax.url) {

								var $btn_container = $(PF.obj.modal.selectors.btn_container, PF.obj.modal.selectors.root);
								PF.obj.modal.locked = true;

								$btn_container.first().clone().height($btn_container.height()).html("").addClass("loading").appendTo(PF.obj.modal.selectors.root + " form");
								$btn_container.hide();

								PF.obj.modal.$close_buttons.hide();

								var modal_loading_msg;

								switch(PF.obj.modal.type) {
									case "edit":
										modal_loading_msg = PF.fn._s("Saving");
									break;
									case "confirm":
									case "form":
									default:
										modal_loading_msg = PF.fn._s("Sending");
									break;
								}

								PF.fn.loading.inline($(PF.obj.modal.selectors.btn_container+".loading", PF.obj.modal.selectors.root), {size: "small", message: modal_loading_msg, valign: "center"});

								$(PF.obj.modal.selectors.root).disableForm();

								if(!$.isEmptyObject(PF.obj.modal.form_data) || (typeof options.ajax !== "undefined" && typeof options.ajax.data == "undefined")) {
									modal_options.ajax.data = PF.obj.modal.form_data;
								}

								PF.obj.modal.XHR = $.ajax({
									url: modal_options.ajax.url,
									type: "POST",
									data: modal_options.ajax.data //PF.obj.modal.form_data // $.param ?
								}).complete(function(XHR){

									PF.obj.modal.locked = false;

									if(XHR.status == 200) {

										var success_fn = typeof modal_options.ajax.deferred !== "undefined" && typeof modal_options.ajax.deferred.success !== "undefined" ? modal_options.ajax.deferred.success : null;

										if(typeof success_fn == "function") {
											PF.fn.modal.close(function() {
												if(typeof success_fn == "function") {
													success_fn(XHR);
												}
											});
										} else if(typeof success_fn == "object") {

											if(typeof success_fn.before == "function") {
												success_fn.before(XHR);
											}
											if(typeof success_fn.done == "function") {
												success_fn.done(XHR);
											}
										}

									} else {

										$(PF.obj.modal.selectors.root).enableForm();
										$(PF.obj.modal.selectors.btn_container+".loading", PF.obj.modal.selectors.root).remove();
										$btn_container.css("display", "");

										if(typeof modal_options.ajax.deferred !== "undefined" && typeof modal_options.ajax.deferred.error == "function") {
											modal_options.ajax.deferred.error(XHR);
										} else {
											var message = PF.fn._s("An error occurred. Please try again later.");
											/*
											if(XHR.responseJSON.error.message) {
												message = XHR.responseJSON.error.message;
											}
											*/
											PF.fn.growl.call(message);
										}

									}
								});

							} else {
								// No ajax behaviour
								PF.fn.modal.close(modal_options.callback());
							}

						}
					}
				}
			});
		},

	/**
	 * Fires a confirm modal
	 * @argument options object
	 */
	confirm:
		function(options){
			options.type = "confirm";
			if(typeof options.title == "undefined"){
				options.title = PF.fn._s("Confirm action");
			}
			PF.fn.modal.call(options);
		},

	/**
	 * Fires a simple info modal
	 */
	simple:
		function(options){
			if(typeof options == "string") options = {message: options};
			if(typeof options.buttons == "undefined") options.buttons = false;
			if(typeof options.title == "undefined") options.title = PF.fn._s("information");
			PF.fn.modal.call(options);
		},

	fixScrollbars:
        function() {
            if(!$(PF.obj.modal.selectors.root).exists()) {
                return;
            }
            var $targets = {
                    padding: $(".top-bar, .fixed, .position-fixed"),
                    margin: $("html"),
                }
            var properties = {}
            if(PF.str.ScrollBarWidth > 0 && $("html").hasScrollBar().vertical && !$("body").data("overflow-hidden")) {
                properties.padding = PF.str.ScrollBarWidth + "px";
                properties.margin = PF.str.ScrollBarWidth + "px";
            } else {
                properties.padding = "";
                properties.margin = "";
            }
            $targets.padding.css({paddingRight: properties.padding});
            $targets.margin.css({marginRight: properties.margin});
        },

	/**
	 * Closes the modal
	 * @argument callback fn
	 */
	close:
		function(callback){
            if(!$(PF.obj.modal.selectors.root).exists()) {
                return;
            }
			PF.fn.growl.close(true);
			$("[rel=tooltip]", PF.obj.modal.selectors.root).tipTip("hide");
			$(PF.obj.modal.selectors.box).css({transform: "scale(0.5)", opacity: 0});
			$(PF.obj.modal.selectors.root).css({opacity: 0});
            setTimeout(function() {
                if(PF.str.ScrollBarWidth > 0 && $("html").hasScrollBar().vertical) {
					$(".top-bar, .fixed, .position-fixed").css({paddingRight: ""});
				}
				$("html").css({marginRight: ""});
                if(!$("body").data("overflow-hidden")) {
				    $("body").removeClass("overflow-hidden");
                }
                $("body").removeData("overflow-hidden");
				$(PF.obj.modal.selectors.root).remove();
				if(typeof callback == "function") callback();
            }, PF.obj.config.animation.normal);
		},

};

/**
 * Peafowlesque popups
 */
PF.fn.popup = function(options){

	var settings = {
			height: options.height || 500,
			width: options.width || 650,
			scrollTo: 0,
			resizable: 0,
			scrollbars: 0,
			location: 0
		};

	settings.top = (screen.height/2) - (settings.height/2);
	settings.left = (screen.width/2) - (settings.width/2);

	var settings_ = "";
	for(var key in settings){
		settings_ += key + "=" + settings[key] + ",";
	}
	settings_ = settings_.slice(0, -1); // remove the last comma

	window.open(options.href, "Popup", settings_);
	return;
};

/**
 * PEAFOWL FLUID WIDTH FIXER
 * -------------------------------------------------------------------------------------------------
 */
PF.fn.list_fluid_width = function() {
	if(!$("body").is_fluid()) return;

	var $content_listing = $(PF.obj.listing.selectors.content_listing_visible),
		$pad_content_listing = $(PF.obj.listing.selectors.pad_content, $content_listing),
		$list_item = $(PF.obj.listing.selectors.list_item, $content_listing),
		list_item_width = $list_item.outerWidth(true),
		list_item_gutter = $list_item.outerWidth(true) - $list_item.width();

	PF.obj.listing.content_listing_ratio = parseInt(($content_listing.width()+list_item_gutter) / list_item_width);

	if($list_item.length < PF.obj.listing.content_listing_ratio) {
		$pad_content_listing.css("width", "100%");
		return;
	}

	if(PF.fn.isDevice(["tablet", "laptop", "desktop"])) {
	//	$pad_content_listing.width((PF.obj.listing.content_listing_ratio * list_item_width) - list_item_gutter);
	}


	if(PF.obj.follow_scroll.$node.hasClass("position-fixed")) {
		PF.obj.follow_scroll.$node.width($(".content-width").first().width());
	}

};

/**
 * PEAFOWL TABS
 * -------------------------------------------------------------------------------------------------
 */

PF.obj.tabs = {
	hashdata: {}
};

PF.fn.show_tab = function(tab) {

	if(typeof tab == "undefined") return;
	var $this = $("a[data-tab=" + tab + "]", ".content-tabs");

	$("li", $this.closest("ul")).removeClass("current");
	$this.closest("li").addClass("current");

	var $tab_content_group = $("#tabbed-content-group");
	$target = $("#"+$this.data("tab"));

	$(".tabbed-content", $tab_content_group).removeClass("visible").hide();
	$($target, $tab_content_group).addClass("visible").show();

	// Show/hide the listing sorting
	$("[data-content=list-selection]").removeClass("visible").addClass("hidden");
	$("[data-content=list-selection][data-tab="+$this.data("tab")+"]").removeClass("hidden").addClass("visible");

	if($tab_content_group.exists()){

		var $list_item_target = $(PF.obj.listing.selectors.list_item+":not(.jsly)", $target),
			target_fade = !$target.hasClass("jsly");

		if($target.data("load") == "ajax" && $target.data("empty") !== "true" && !$(PF.obj.listing.selectors.list_item, $target).exists()){
			PF.fn.listing.queryString.stock_load();
			$target.html(PF.obj.listing.template.fill);
			PF.fn.loading.inline($(PF.obj.listing.selectors.content_listing_loading, $target));
			PF.fn.listing.queryString.stock_new();
			PF.fn.listing.ajax();
		} else {
			PF.fn.listing.queryString.stock_current();
			PF.fn.listing.columnizer(false, 0, false);
			$list_item_target[target_fade ? "fadeIn" : "show"]();
		}

	}

	PF.fn.listing.columnizerQueue();

	if($(PF.obj.listing.selectors.content_listing_visible).data("queued") == true) {
		PF.fn.listing.columnizer(true, 0);
	}

};

/**
 * PEAFOWL LISTINGS
 * -------------------------------------------------------------------------------------------------
 */
PF.obj.listing = {
	columns: "",
	columns_number: 1,
	current_column: "",
	current_column: "",
	XHR: {},
	query_string: PF.fn.get_url_vars(),
	calling: false,
	content_listing_ratio: 1,
	selectors: {
		sort: ".sort-listing .current [data-sort]",
		content_listing: ".content-listing",
		content_listing_visible: ".content-listing:visible",
		content_listing_loading: ".content-listing-loading",
		content_listing_load_more: ".content-listing-more",
		content_listing_pagination: ".content-listing-pagination",
		empty_icon: ".icon icon-drawer",
		pad_content: ".pad-content-listing",
		list_item: ".list-item",
	},
	template: {
		fill: $("[data-template=content-listing]").html(),
		empty: $("[data-template=content-listing-empty]").html(),
		loading: $("[data-template=content-listing-loading]").html()
	}
};

PF.fn.listing = {};

PF.fn.listing.show = function(response, callback) {

	$content_listing = $("#content-listing-tabs").exists() ? $(PF.obj.listing.selectors.content_listing_visible, "#content-listing-tabs") : $(PF.obj.listing.selectors.content_listing);

	PF.fn.loading.inline(PF.obj.listing.selectors.content_listing_loading);

	$(PF.obj.listing.selectors.list_item+":not(.jsly)", $content_listing).each(function() {

		$(this).imagesLoaded(function(i) {

			var items = PF.obj.listing.selectors.list_item,
				$subjects = $(items+":visible", PF.obj.listing.selectors.content_listing_visible),
				$targets = $(i.elements);

			if((typeof response !== "undefined" && $(response.html).length < PF.obj.config.listing.items_per_page) || $(PF.obj.listing.selectors.list_item, $content_listing).length < PF.obj.config.listing.items_per_page) {
				PF.fn.listing.removeLoader($content_listing);
			}

			if($(PF.obj.listing.selectors.content_listing_pagination, $content_listing).is("[data-type=classic]") || !$("[data-action=load-more]", $content_listing).exists()) {
				$(PF.obj.listing.selectors.content_listing_loading, $content_listing).remove();
			}

			if($subjects.length == 0) {
				$targets.show();
				PF.fn.listing.columnizer(false, 0);
				PF.obj.listing.recolumnize = true;
			}

			//var animation_time = $subjects.length == 0 ? 0 : null;
			var animation_time = 0;

			PF.fn.listing.columnizer(PF.obj.listing.recolumnize, animation_time, $subjects.length == 0);

			$targets.hide();
			PF.obj.listing.recolumnize = false;

			if(PF.fn.isDevice(["laptop", "desktop"])) {

				$targets.each(function() { // too much CPU for this

					$(this).show().find(".image-container").hide();

					var callTime =  $.now();
					var $this = $(this);
					var $target = $(".image-container", $this);

					$(".image-container", this).imagesLoaded(function(){
						var loadTime = $.now() - callTime;

						if($subjects.length == 0) {
							if(loadTime > PF.obj.config.animation.normal) {
								$target.fadeIn(PF.obj.config.animation.normal);
							} else {
								$target.show();
							}
						} else {
							$target.fadeIn(PF.obj.config.animation.normal);
						}
					});
				});
			} else {
				$targets.show();
			}

			PF.obj.listing.calling = false;

			var visible_loading = $(PF.obj.listing.selectors.content_listing_loading, $content_listing).exists() && ($(PF.obj.listing.selectors.content_listing_loading, $content_listing).is_in_viewport());
			if(typeof PF.obj.listing.show_load_more == typeof undefined) {
				PF.obj.listing.show_load_more = visible_loading;
			}

			$(PF.obj.listing.selectors.content_listing_loading, $content_listing)[(visible_loading ? "add" : "remove") + "Class"]("visibility-hidden");
			$(PF.obj.listing.selectors.content_listing_load_more, $content_listing)[(PF.obj.listing.show_load_more ? "show" : "hide")]();

			var State = History.getState();
			if(State.data && typeof State.data.scrollTop !== "undefined") {
				if($(window).scrollTop() !== State.data.scrollTop) {
					//$(window).scrollTop(State.data.scrollTop);
				}
			}

			if(typeof callback == "function") {
				callback();
			}
		})
	});
};

PF.fn.listing.removeLoader = function(obj) {

	var remove = [PF.obj.listing.selectors.content_listing_load_more, PF.obj.listing.selectors.content_listing_loading];

	if($(PF.obj.listing.selectors.content_listing_pagination, $content_listing).is("[data-type=endless]")) {
		remove.push(PF.obj.listing.selectors.content_listing_pagination);
	}

	$.each(remove, function(i,v) {
		$(v, obj).remove();
	});
};

PF.fn.listing.queryString = {

	// Stock the querystring values from initial load
	stock_load: function() {

		var $content_listing = $(PF.obj.listing.selectors.content_listing_visible),
			params = PF.fn.deparam($content_listing.data("params"));

		PF.obj.listing.params_hidden = typeof $content_listing.data("params-hidden") !== "undefined" ?  PF.fn.deparam($content_listing.data("params-hidden")) : null;

		if(typeof PF.obj.listing.query_string.action == "undefined") {
			PF.obj.listing.query_string.action = $content_listing.data("action") || "list";
		}
		if(typeof PF.obj.listing.query_string.list == "undefined") {
			PF.obj.listing.query_string.list = $content_listing.data("list");
		}
		if(typeof PF.obj.listing.query_string.sort == "undefined") {
			if(typeof params !== "undefined" && typeof params.sort !== "undefined") {
				PF.obj.listing.query_string.sort = params.sort;
			} else {
				PF.obj.listing.query_string.sort = $(":visible"+PF.obj.listing.selectors.sort).data("sort");
			}
		}
		if(typeof PF.obj.listing.query_string.page == "undefined") {
			PF.obj.listing.query_string.page = 1;
		}
		$content_listing.data("page", PF.obj.listing.query_string.page);

		// Stock the real ajaxed hrefs for ajax loads
		$(PF.obj.listing.selectors.content_listing+"[data-load=ajax]").each(function(){

		var $sortable_switch = $("[data-tab="+$(this).attr("id")+"]"+PF.obj.listing.selectors.sort);
		var dataParams = PF.fn.deparam($(this).data("params")),
			dataParamsHidden = PF.fn.deparam($(this).data("params-hidden")),
			params = {
				   q: dataParams && dataParams.q ? dataParams.q : null,
				list: $(this).data("list"),
				sort: $sortable_switch.exists() ? $sortable_switch.data("sort") : (dataParams && dataParams.sort ? dataParams.sort: null),
				page: dataParams && dataParams.page ? dataParams.page : 1
			};

			if(dataParamsHidden && dataParamsHidden.list) {
				delete params.list;
			}

			for(var k in params) {
				if(!params[k]) delete params[k];
			}

		});

		// The additional params setted in data-params=""
		for(var k in params) {
			if(/action|list|sort|page/.test(k) == false) {
				PF.obj.listing.query_string[k] = params[k];
			}
		}

		if(typeof PF.obj.listing.params_hidden !== typeof undefined) {
			// The additional params setted in data-hidden-params=""
			for(var k in PF.obj.listing.params_hidden) {
				if(/action|list|sort|page/.test(k) == false) {
					PF.obj.listing.query_string[k] = PF.obj.listing.params_hidden[k];
				}
			}
			PF.obj.listing.query_string['params_hidden'] = PF.obj.listing.params_hidden;
			PF.obj.listing.params_hidden['params_hidden'] = null; // Add this key for legacy, params_hidden v3.9.0 intro*
		}
	},

	// Stock new querystring values for initial ajax call
	stock_new: function(){
		var $content_listing = $(PF.obj.listing.selectors.content_listing_visible),
			params = PF.fn.deparam($content_listing.data("params"));

		if($content_listing.data("offset")) {
			PF.obj.listing.query_string.offset = $content_listing.data("offset");
		} else {
			delete PF.obj.listing.query_string.offset;
		}

		PF.obj.listing.query_string.action = $content_listing.data("action") || "list";
		PF.obj.listing.query_string.list = $content_listing.data("list");

		if(typeof params !== "undefined" && typeof params.sort !== "undefined") {
			PF.obj.listing.query_string.sort = params.sort;
		} else {
			PF.obj.listing.query_string.sort = $(":visible"+PF.obj.listing.selectors.sort).data("sort");
		}

		PF.obj.listing.query_string.page = 1;
	},

	// Stock querystring values for static tab change
	stock_current: function(){
		this.stock_new();
		PF.obj.listing.query_string.page = $(PF.obj.listing.selectors.content_listing_visible).data("page");
	}
};

// Initial load -> Stock the current querystring
PF.fn.listing.queryString.stock_load();

PF.fn.listing.ajax = function() {

	if(PF.obj.listing.calling == true) {
		return;
	}

	PF.obj.listing.calling = true;

	var $content_listing = $(PF.obj.listing.selectors.content_listing_visible),
		$pad_content_listing = $(PF.obj.listing.selectors.pad_content, $content_listing);

	$(PF.obj.listing.selectors.content_listing_load_more, $content_listing).hide();
	$(PF.obj.listing.selectors.content_listing_loading, $content_listing).removeClass("visibility-hidden").show();

	PF.obj.listing.XHR = $.ajax({
		type: "POST",
		data: $.param($.extend({}, PF.obj.listing.query_string, $.ajaxSettings.data))
	}).complete(function(XHR) {

		var response = XHR.responseJSON;
		var removePagination = function() {
				$(PF.obj.listing.selectors.content_listing_loading+","+PF.obj.listing.selectors.content_listing_pagination+":not([data-visibility=visible])", $content_listing).remove();
			},
			setEmptyTemplate = function() {
				$content_listing.data("empty", "true").html(PF.obj.listing.template.empty);
				$("[data-content=list-selection][data-tab="+$content_listing.attr("id")+"]").addClass("disabled");
			};

		if(XHR.readyState == 4 && typeof response !== "undefined") {

			$("[data-content=list-selection][data-tab="+$content_listing.attr("id")+"]").removeClass("disabled");

			// Bad Request Bad Request what you gonna do when they come for ya?
			if(XHR.status !== 200) {
				// This is here to inherit the emptys
				var response_output = typeof response.error !== "undefined" && typeof response.error.message !== "undefined" ? response.error.message : "Bad request";
				PF.fn.growl.call("Error: "+response_output);
				$content_listing.data("load", "");
			}
			// Empty HTML
			if((typeof response.html == "undefined" || response.html == "") && $(PF.obj.listing.selectors.list_item, $content_listing).length == 0) {
				setEmptyTemplate();
			}
			// End of the line
			if(typeof response.html == "undefined" || response.html == "") {
				removePagination();
				PF.obj.listing.calling = false;
				if(typeof PF.fn.listing_end == "function") {
					PF.fn.listing_end();
				}
				return;
			}

			// Listing stuff
			$content_listing.data({
				"load": "",
				"page": PF.obj.listing.query_string.page
			});

			var url_object = $.extend({}, PF.obj.listing.query_string);
			for(var k in PF.obj.listing.params_hidden) {
				if(typeof url_object[k] !== "undefined") {
					delete url_object[k];
				}
			}

			delete url_object["action"];

			for(var k in url_object) {
				if(!url_object[k]) delete url_object[k];
			}

			// get the fancy URL with scrollTop attached
			if(document.URL.indexOf("?" + $.param(url_object)) == -1) {
				var url = window.location.href;
				url = url.split("?")[0].replace(/\/$/, "") + "/?" + $.param(url_object);
				if(window.location.hash) {
					url = url.replace(window.location.hash, '');
				}
				History.pushState({pushed: "pagination", scrollTop: $(window).scrollTop()}, document.title, url);
			}

			$("a[data-tab="+$content_listing.attr("id")+"]").attr("href", document.URL);

			$pad_content_listing.append(response.html);

			PF.fn.listing.show(response, function() {
				$(PF.obj.listing.selectors.content_listing_loading, $content_listing).addClass("visibility-hidden");
			});

		} else {
			// Network error, abort or something similar
			PF.obj.listing.calling = false;
			$content_listing.data("load", "");
			removePagination();
			if($(PF.obj.listing.selectors.list_item, $content_listing).length == 0) {
				setEmptyTemplate();
			}
			if(XHR.readyState !== 0) {
				PF.fn.growl.call(PF.fn._s("An error occurred. Please try again later."));
			}
		}

		if(typeof PF.fn.listing.ajax.callback == "function") {
			PF.fn.listing.ajax.callback(XHR);
		}

	});

};

PF.fn.listing.columnizerQueue = function() {
	$(PF.obj.listing.selectors.content_listing+":hidden").data("queued", true);
};

PF.fn.listing.refresh = function(animation_time) {
	PF.fn.listing.columnizer(true, animation_time, false);
	$(PF.obj.listing.selectors.list_item).show();
};

// Peafowl's masonry approach... Just because godlike.
var width = $(window).width();
PF.fn.listing.columnizer = function(forced, animation_time, hard_forced) {

	var device_to_columns = { // default
			phone: 1,
			phablet: 3,
			tablet: 4,
			laptop: 5,
			desktop: 6,
            largescreen: 7,
		};

	if(typeof forced !== "boolean") var forced = false;
	if(typeof PF.obj.listing.mode == "undefined") forced = true;
	if(typeof hard_forced !== "boolean") {
		var hard_forced = false,
			default_hard_forced = true;
	} else {
		var default_hard_forced = false;
	}
	if(!hard_forced && default_hard_forced) {
		if(width !== $(window).width() || forced) {
			hard_forced = true;
		}
	}

	if(typeof animation_time == typeof undefined) var animation_time = PF.obj.config.animation.normal;

	//animation_time = 0;

	var $container = $("#content-listing-tabs").exists() ? $(PF.obj.listing.selectors.content_listing_visible, "#content-listing-tabs") : $(PF.obj.listing.selectors.content_listing),
		$pad_content_listing = $(PF.obj.listing.selectors.pad_content, $container),
		list_mode = "responsive",
		$list_item = $(forced || hard_forced ? PF.obj.listing.selectors.list_item : PF.obj.listing.selectors.list_item+":not(.jsly)", $container);

	$container.addClass("jsly");

	// Get the device columns from global config
	if(typeof PF.obj.config.listing.device_to_columns !== "undefined") {
		device_to_columns = $.extend({}, device_to_columns, PF.obj.config.listing.device_to_columns);
	}

	// Get the device columns from the dom
	if($container.data("device-columns")) {
		device_to_columns = $.extend({}, device_to_columns, $container.data("device-columns"));
	}

	PF.obj.listing.mode = list_mode;
	PF.obj.listing.device = PF.fn.getDeviceName();

	if(!$list_item.exists()) return;

	if(typeof $container.data("columns") !== "undefined" && !forced && !hard_forced){
		PF.obj.listing.columns = $container.data("columns");
		PF.obj.listing.columns_number = $container.data("columns").length - 1;
		PF.obj.listing.current_column = $container.data("current_column");
	} else {
		var $list_item_1st =  $list_item.first();
		$list_item_1st.css("width", "");
		PF.obj.listing.columns = new Array();
		PF.obj.listing.columns_number = device_to_columns[PF.fn.getDeviceName()];
		for(i=0; i<PF.obj.listing.columns_number; i++){
			PF.obj.listing.columns[i+1] = 0;
		}
		PF.obj.listing.current_column = 1;
	}

	var special_margin = PF.obj.listing.columns_number == 1 ? "-10px" : "";

	$("#tabbed-content-group").css({marginLeft: special_margin, marginRight: special_margin});

	$container.removeClass("small-cols").addClass(PF.obj.listing.columns_number > 6 ? "small-cols" : "");

	$pad_content_listing.css("width", "100%");

	var delay = 0;

	$list_item.each(function(index) {

		$(this).addClass("jsly");

		var $list_item_img = $(".list-item-image", this),
			$list_item_src = $(".list-item-image img", this),
			$list_item_thumbs = $(".list-item-thumbs", this),
			isJslyLoaded = $list_item_src.hasClass("jsly-loaded");

		$list_item_src.show();

		if(hard_forced) {
			$(this).css({top: "", left: "", height: "", position: ""});
			$list_item_img.css({maxHeight: "", height: ""});
			$list_item_src.removeClass("jsly").css({width: "", height: ""}).parent().css({
				marginLeft: "",
				marginTop: ""
			});
			$("li", $list_item_thumbs).css({width: "", height: ""});
		}

		var width_responsive = PF.obj.listing.columns_number == 1 ? "100%" : parseInt((1/PF.obj.listing.columns_number)*($container.width() - (10 * (PF.obj.listing.columns_number - 1))) + "px");
		$(this).css("width", width_responsive);

		if(PF.obj.listing.current_column > PF.obj.listing.columns_number){
			PF.obj.listing.current_column = 1
		}

		$(this).attr("data-col", PF.obj.listing.current_column);

		if(!$list_item_src.exists()){
			var empty = true;
			$list_item_src = $(".image-container .empty", this);
		}

		var already_shown = $(this).is(":visible");
		$list_item.show();

		var isFixed = $list_item_img.hasClass("fixed-size");

		var image = {
				w: parseInt($list_item_src.attr("width")),
				h: parseInt($list_item_src.attr("height"))
			};
			image.ratio = image.w / image.h;

		//$list_item_src.removeAttr("width height"); // para fixed

		if(hard_forced && PF.obj.listing.columns_number > 1) {
			$list_item_src.css({width: "auto", height: "auto"});
			$(".image-container:not(.list-item-avatar-cover)", this).css({width: "", height: "auto"});
		} else {
			if(image.w > $container.width()) {
				$(".image-container:not(.list-item-avatar-cover)", this).css(image.ratio < 1 ? {maxWidth: "100%", height: "auto"} : {height: "100%", width: "auto"});
				$list_item_src.css(image.ratio < 1 ? {maxWidth: "100%", height: "auto"} : {height: "100%", width: "auto"});
			}
		}

		// Meet the minHeight?

		if(empty || ($list_item_img.css("min-height") && !$list_item_src.hasClass("jsly"))) {

			var	list_item_img_min_height = parseInt($list_item_img.css("height")),
				col = {
					w: $(this).width(),
					h: isFixed ? $(this).width() : null
				},
				magicWidth = Math.min(image.w, image.w < col.w ? image.w : col.w);

			if(isFixed){
				$list_item_img.css({height: col.w}); // Sets the item container height
				if(image.ratio <= 3 && (image.ratio > 1 || image.ratio==1)) { // Landscape or square
					image.h = Math.min(image.h, image.w < col.w ? image.w : col.w);
					image.w = image.h * image.ratio;
				} else { // Portrait
					image.w = magicWidth;
					image.h = image.w / image.ratio;
				}
				var list_item_img_min_h = parseInt($list_item_img.css("min-height"));
				$list_item_img.css("min-height", 0);
			} else { // Fluid height
				image.w = magicWidth;
				if(image.ratio >= 3 || image.ratio < 1 || image.ratio==1){ // Portrait or square
					image.h = image.w / image.ratio;
				} else { // Landscape
					image.h = Math.min(image.h, image.w);
					image.w = image.h * image.ratio;
				}
				if(empty) {
					image.h = col.w;
				}
				$list_item_img.css({height: image.h}); // Fill some gaps
			}
			$list_item_src.css({width: image.w, height: image.h});

			if($list_item_src.width() == 0) {
				$list_item_src.css({width: magicWidth, height: magicWidth / image.ratio});
			}

			if($(".image-container", this).is(".list-item-avatar-cover")) {
				$list_item_src.css(isFixed ? {width: "auto", height: "100%"} : {width: "100%", height: "auto"});
			}
			if($list_item_src.height() !== 0 && ($list_item_img.height() > $list_item_src.height() || isFixed)){
				$list_item_src.parent().css({
					"marginTop": ($list_item_img.outerHeight() - $list_item_src.height())/2
				});
			}
			if($list_item_img.width() < $list_item_src.width()){
				$list_item_src.parent().css({
					"marginLeft": - (($list_item_src.outerWidth()-$list_item_img.width())/2) + "px"
				});
			}

			var list_item_src_pitfall_x = Math.max($list_item_src.position().left * 2, 0),
				list_item_src_pitfall_y = Math.max($list_item_src.position().top * 2, 0);

			// Do we need upscale? It is safe to upscale?
			if(PF.obj.listing.columns_number > 6 && (list_item_src_pitfall_x > 0 || list_item_src_pitfall_y > 0)){
				var pitfall_ratio_x = list_item_src_pitfall_x/$list_item_img.width(),
					pitfall_ratio_y = list_item_src_pitfall_y/$list_item_img.height(),
					pitfall = {};
				if(pitfall_ratio_x <= .25 && pitfall_ratio_y <= .25){
					if(pitfall_ratio_x > pitfall_ratio_y){
						pitfall.width = list_item_src_pitfall_x + $list_item_img.width();
						pitfall.height = pitfall.width / image.ratio;
					} else {
						pitfall.height = list_item_src_pitfall_y + $list_item_src.height();
						pitfall.width = pitfall.height * image.ratio;
					}
					$list_item_src.css(pitfall);
					$list_item_src.parent().css({
						"marginLeft": -(($list_item_src.width()-$list_item_img.width())/2),
						"marginTop": 0
					});
				}
			}

			if($list_item_thumbs.exists()) {
				$("li", $list_item_thumbs).css({width: 100/$("li", $list_item_thumbs).length + "%"}).css({height: $("li", $list_item_thumbs).width()});
			}

			if(!already_shown) {
				$list_item.hide();
			}

		}

		//$pad_content_listing.css("visibility", "visible");

		if(!$list_item_src.hasClass("jsly") && $(this).is(":hidden")) {
			$(this).css('top', "100%");
		}

		PF.obj.listing.columns[PF.obj.listing.current_column] += $(this).outerHeight(true);

		if(PF.obj.listing.columns_number == 1) {
			$(this).removeClass("position-absolute");
		} else {
			if($(this).is(":animated")) {
				animation_time = 0;
			}
			$(this).addClass("position-absolute");

			var new_left = $(this).outerWidth(true)*(PF.obj.listing.current_column - 1);
			var must_change_left = parseInt($(this).css("left")) != new_left;
			if(must_change_left) {
				animate_grid = true;
				$(this).animate({
					left: new_left
				}, animation_time);
			}

			var new_top = PF.obj.listing.columns[PF.obj.listing.current_column] - $(this).outerHeight(true);
			if(parseInt($(this).css("top")) != new_top) {
				animate_grid = true;
				$(this).animate({
					top: new_top
				}, animation_time);
				if(must_change_left) {
					delay = 1;
				}
			}
		}

		if(already_shown) {
			$list_item.show();
		}

		if(!isJslyLoaded) {
			$list_item_src.addClass("jsly").hide().imagesLoaded(function(i){
				$(i.elements).show().addClass("jsly-loaded");
			});
		}

		// Fill the shortest column (fluid view only)
		if(!isFixed) {
			var minCol, minH, currentH;
			for(var i=1; i<=PF.obj.listing.columns_number; i++){
				currentH = PF.obj.listing.columns[i];

				if(typeof minH == "undefined") {
					minH = currentH;
					minCol = i;
				}

				if(PF.obj.listing.columns[i] == 0) {
					minCol = i;
					break;
				}
				if(currentH < minH) {
					minH = PF.obj.listing.columns[i];
					minCol = i;
				}
			}

			PF.obj.listing.current_column = minCol;
		} else {
			PF.obj.listing.current_column++;
		}

	});

	$container.data({"columns": PF.obj.listing.columns, "current_column": PF.obj.listing.current_column});

	var content_listing_height = 0;
	$.each(PF.obj.listing.columns, function(i, v){
		if(v>content_listing_height) {
			content_listing_height = v;
		}
	});

	if(content_listing_height > 10) {
		content_listing_height -= 10;
	}

	PF.obj.listing.width = $container.width();

	if(typeof PF.obj.listing.height !== typeof undefined) {
		var old_listing_height = PF.obj.listing.height;
	}
	PF.obj.listing.height = content_listing_height;

	var do_listing_h_resize = typeof old_listing_height !== typeof undefined && old_listing_height !== PF.obj.listing.height;

	if(!do_listing_h_resize) {
		$pad_content_listing.height(content_listing_height);
		PF.fn.list_fluid_width();
	}

	// Magic!
	if(do_listing_h_resize) {
		$pad_content_listing.height(old_listing_height);
		setTimeout(function() {
			$pad_content_listing.animate({height: content_listing_height}, animation_time, function() {
				PF.fn.list_fluid_width();
			});
		}, animation_time * delay);
	}

	$container.data("list-mode", PF.obj.listing.mode);
	$(PF.obj.listing.selectors.content_listing_visible).data("queued", false);

};


/**
 * PEAFOWL LOADERS
 * -------------------------------------------------------------------------------------------------
 */
PF.fn.loading = {
	spin: {
		small: {lines: 11, length: 0, width: 3, radius: 7, speed: 1, trail: 45, blocksize: 20}, // 20x20
		normal: {lines: 11, length: 0, width: 5, radius: 10, speed: 1, trail: 45, blocksize: 30}, // 30x30
		big: {lines: 11, length: 0, width: 7, radius: 13, speed: 1, trail: 45, blocksize: 40}, // 40x40
		huge: {lines: 11, length: 0, width: 9, radius: 16, speed: 1, trail: 45, blocksize: 50} // 50x50
	},
	inline: function($target, options) {

		if(typeof $target == "undefined") return;

		if($target instanceof jQuery == false) {
			var $target = $($target);
		}

		var defaultoptions = {
				size: "normal",
				color: $("body").css("color"),
				center: false,
				position: "absolute",
				shadow: false,
				valign: "top"
			};

		if(typeof options == "undefined"){
			options = defaultoptions;
		} else {
			for(var k in defaultoptions) {
				if(typeof options[k] == "undefined") {
					options[k] = defaultoptions[k];
				}
			}
		}

		var size = PF.fn.loading.spin[options.size];

		PF.fn.loading.spin[options.size].color = options.color;
		PF.fn.loading.spin[options.size].shadow = options.shadow;

		$target.html('<span class="loading-indicator"></span>' + (typeof options.message !== "undefined" ? '<span class="loading-text">'+ options.message +'</span>' : '')).css({"line-height": PF.fn.loading.spin[options.size].blocksize + "px"});

		$(".loading-indicator", $target).css({width: PF.fn.loading.spin[options.size].blocksize, height: PF.fn.loading.spin[options.size].blocksize}).spin(PF.fn.loading.spin[options.size]);

		if(options.center){
			$(".loading-indicator", $target.css("textAlign", "center")).css({
				position: options.position,
				top: "50%",
				left: "50%",
				marginTop: -(PF.fn.loading.spin[options.size].blocksize/2),
				marginLeft: -(PF.fn.loading.spin[options.size].blocksize/2)
			});
		}
		if(options.valign == "center") {
			$(".loading-indicator,.loading-text", $target).css("marginTop", ($target.height()-PF.fn.loading.spin[options.size].blocksize)/2 + "px");
		}

		$(".spinner", $target).css({top: PF.fn.loading.spin[options.size].blocksize/2 + "px", left: PF.fn.loading.spin[options.size].blocksize/2 + "px"});

	},
	fullscreen: function(){
		$("body").append('<div class="fullscreen" id="pf-fullscreen-loader"><div class="fullscreen-loader black-bkg"><span class="loading-txt">' + PF.fn._s("loading") + '</span></div></div>');
		$(".fullscreen-loader", "#pf-fullscreen-loader").spin(PF.fn.loading.spin.huge);
		$("#pf-fullscreen-loader").css("opacity", 1);
	},
	destroy : function($target){
		var $loader_fs = $("#pf-fullscreen-loader"),
			$loader_os = $("#pf-onscreen-loader");

		if($target == "fullscreen") $target = $loader_fs;
		if($target == "onscreen") $target = $loader_os;

		if(typeof $target !== "undefined"){
			$target.remove();
		} else {
			$loader_fs.remove();
			$loader_os.remove();
		}
	}
};

/**
 * PEAFOWL FORM HELPERS
 * -------------------------------------------------------------------------------------------------
 */
jQuery.fn.disableForm = function(){
	$(this).data("disabled", true);
	$(":input", this).each(function(){
		$(this).attr("disabled", true);
	});
	return this;
};
jQuery.fn.enableForm = function(){
	$(this).data("disabled", false);
	$(":input", this).removeAttr("disabled");
	return this;
};

/**
 * PEAFOWL FOLLOW SCROLL
 * -------------------------------------------------------------------------------------------------
 */
PF.obj.follow_scroll = {
	Y: 0,
	y: 0,
	$node: $(".follow-scroll"),
	node_h: 0,
	base_h: $(".follow-scroll").outerHeight(),
	set: function(reset) {
		if(reset) {
			PF.obj.follow_scroll.base_h = $(".follow-scroll").outerHeight();
		}
		var exists = PF.obj.follow_scroll.$node.closest(".follow-scroll-wrapper").exists();
		if(exists) {
			PF.obj.follow_scroll.$node.closest(".follow-scroll-wrapper").css("position", "static");
		}
		PF.obj.follow_scroll.y = PF.obj.follow_scroll.$node.exists() ? PF.obj.follow_scroll.$node.offset().top : null;
		PF.obj.follow_scroll.node_h = PF.obj.follow_scroll.$node.outerHeight();
		if(exists) {
			PF.obj.follow_scroll.$node.closest(".follow-scroll-wrapper").css("position", "");
		}
	},
	checkDocumentHeight: function(){
		var lastHeight = document.body.clientHeight, newHeight, timer;
		(function run(){
			newHeight = document.body.clientHeight;
			if(lastHeight != newHeight) {
				PF.obj.follow_scroll.set();
			}
			lastHeight = newHeight;
			timer = setTimeout(run, 200);
		})();
	}
};
PF.obj.follow_scroll.set();
//PF.obj.follow_scroll.checkDocumentHeight();

PF.obj.follow_scroll.process = function(forced) {

	if(forced) {
		PF.obj.follow_scroll.node_h = PF.obj.follow_scroll.base_h;
	}

	if(!PF.obj.follow_scroll.$node.exists()) return; // Nothing to do here

	var $parent = PF.obj.follow_scroll.$node.closest("[data-content=follow-scroll-parent]");
	if(!$parent.exists()) {
		$parent = PF.obj.follow_scroll.$node.closest(".content-width");
	}

	var $wrapper = PF.obj.follow_scroll.$node.closest('.follow-scroll-wrapper');

	var top = PF.obj.follow_scroll.node_h;
	var	cond = $(window).scrollTop() > PF.obj.follow_scroll.y - top;

	if($("#top-bar").css("position") !== "fixed") {
		PF.obj.follow_scroll.Y -= $(window).scrollTop();
		if(PF.obj.follow_scroll.Y < 0) PF.obj.follow_scroll.Y = 0;
		cond = cond && $(window).scrollTop() > PF.obj.follow_scroll.y;
	}

	if((cond && $wrapper.hasClass("position-fixed")) || (!cond && !$wrapper.hasClass("position-fixed"))) {
		return;
	}

	if(!$wrapper.exists()) {
		PF.obj.follow_scroll.$node.wrapAll('<div class="follow-scroll-wrapper" />');
		$wrapper = PF.obj.follow_scroll.$node.closest('.follow-scroll-wrapper');
	}

	$wrapper.css("min-height", PF.obj.follow_scroll.node_h);

	PF.obj.follow_scroll.Y = $("#top-bar").outerHeight(true) + parseFloat($("#top-bar").css("top"));

	if(cond) {
		var placeholderHeight = PF.obj.follow_scroll.node_h;
		$wrapper
			.addClass("position-fixed")
			.css({top: PF.obj.follow_scroll.Y});
		if(!$wrapper.next().is(".follow-scroll-placeholder")) {
			$wrapper.after($('<div class="follow-scroll-placeholder" />').css("min-height", placeholderHeight));
		} else {
			$wrapper.parent().find(".follow-scroll-placeholder").show();
		}
	} else {
		$wrapper.removeClass("position-fixed").css({top: "", width: "", minHeight: ""});
		$wrapper.parent().find(".follow-scroll-placeholder").hide();
	}

	$("[data-show-on=follow-scroll]")[(cond ? "remove" : "add") + "Class"]("hidden soft-hidden");

	if(!$("html").data("top-bar-box-shadow-prevent")) {
		$("html")[(cond ? "add" : "remove") + "Class"]("top-bar-box-shadow-none");
	}

	PF.obj.follow_scroll.$node[(cond ? "add" : "remove") + "Class"]("content-width");

};

PF.fn.isDevice = function(device) {
	if(typeof device == "object") {
		var device = '.' + device.join(",.");
	} else {
		var device = '.' + device;
	}
	return $("html").is(device);
};

PF.fn.getDeviceName = function() {
	var current_device;
	$.each(PF.obj.devices, function(i,v) {
		if(PF.fn.isDevice(v)) {
			current_device = v;
			return true;
		}
	});
	return current_device;
};

PF.fn.topMenu = {
	vars : {
		$button : $("[data-action=top-bar-menu-full]", "#top-bar"),
		menu : "#menu-fullscreen",
		speed: PF.obj.config.animation.fast,
        menu_top: (parseInt($("#top-bar").outerHeight()) + parseInt($("#top-bar").css("top")) + parseInt($("#top-bar").css("margin-top")) + parseInt($("#top-bar").css("margin-bottom")) - parseInt($("#top-bar").css("border-bottom-width"))) + "px"
	},
	show: function(speed) {

		if($("body").is(":animated")) return;

		if(typeof speed == "undefined") {
			var speed = this.vars.speed;
		}

		this.vars.$button.addClass("current");
		//$("html").addClass("menu-fullscreen-visible");
		$("#top-bar").css("position", "fixed").append($("<div/>", {
			id: "menu-fullscreen",
			"class": "touch-scroll",
			html: $('<ul/>', {
				html: $(".top-bar-left").html() + $(".top-bar-right").html()
			})
		}).css({
			borderTopWidth: this.vars.menu_top,
			left: "-100%",
			//height: $(window).height(), // aca
		}));

		var $menu = $(this.vars.menu);

		$("li.phone-hide, li > .top-btn-text, li > .top-btn-text > span, li > a > .top-btn-text > span", $menu).each(function() {
			$(this).removeClass("phone-hide");
		});
		$("[data-action=top-bar-menu-full]", $menu).remove();
		$(".btn.black, .btn.default, .btn.blue, .btn.green, .btn.orange, .btn.red, .btn.transparent", $menu).removeClass("btn black default blue green orange red transparent");

        setTimeout(function() {
            $menu.css({transform: "translate(100%, 0)"});
        }, 1);
        setTimeout(function() {
			$("html").css({backgroundColor: ""});
		}, this.vars.speed);
	},
	hide: function(speed) {

		if($("body").is(":animated")) return;

		if(!$(this.vars.menu).is(":visible")) return;

		if(typeof speed == "undefined") {
			var speed = this.vars.speed;
		}

        $("#top-bar").css("position", "");

		this.vars.$button.removeClass("current");
		//$("html").removeClass("menu-fullscreen-visible");
		var $menu = $(this.vars.menu);
		$menu.css({
			transform: "none"
		});
        setTimeout(function() {
            $menu.remove();
        }, speed);
	}
};

/**
 * JQUERY PLUGINS (strictly needed plugins)
 * -------------------------------------------------------------------------------------------------
 */

 // http://phpjs.org/functions/sprintf/
function sprintf(){var e=/%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;var t=arguments;var n=0;var r=t[n++];var i=function(e,t,n,r){if(!n){n=" "}var i=e.length>=t?"":(new Array(1+t-e.length>>>0)).join(n);return r?e+i:i+e};var s=function(e,t,n,r,s,o){var u=r-e.length;if(u>0){if(n||!s){e=i(e,r,o,n)}else{e=e.slice(0,t.length)+i("",u,"0",true)+e.slice(t.length)}}return e};var o=function(e,t,n,r,o,u,a){var f=e>>>0;n=n&&f&&{2:"0b",8:"0",16:"0x"}[t]||"";e=n+i(f.toString(t),u||0,"0",false);return s(e,n,r,o,a)};var u=function(e,t,n,r,i,o){if(r!=null){e=e.slice(0,r)}return s(e,"",t,n,i,o)};var a=function(e,r,a,f,l,c,h){var p,d,v,m,g;if(e==="%%"){return"%"}var y=false;var b="";var w=false;var E=false;var S=" ";var x=a.length;for(var T=0;a&&T<x;T++){switch(a.charAt(T)){case" ":b=" ";break;case"+":b="+";break;case"-":y=true;break;case"'":S=a.charAt(T+1);break;case"0":w=true;S="0";break;case"#":E=true;break}}if(!f){f=0}else if(f==="*"){f=+t[n++]}else if(f.charAt(0)=="*"){f=+t[f.slice(1,-1)]}else{f=+f}if(f<0){f=-f;y=true}if(!isFinite(f)){throw new Error("sprintf: (minimum-)width must be finite")}if(!c){c="fFeE".indexOf(h)>-1?6:h==="d"?0:undefined}else if(c==="*"){c=+t[n++]}else if(c.charAt(0)=="*"){c=+t[c.slice(1,-1)]}else{c=+c}g=r?t[r.slice(0,-1)]:t[n++];switch(h){case"s":return u(String(g),y,f,c,w,S);case"c":return u(String.fromCharCode(+g),y,f,c,w);case"b":return o(g,2,E,y,f,c,w);case"o":return o(g,8,E,y,f,c,w);case"x":return o(g,16,E,y,f,c,w);case"X":return o(g,16,E,y,f,c,w).toUpperCase();case"u":return o(g,10,E,y,f,c,w);case"i":case"d":p=+g||0;p=Math.round(p-p%1);d=p<0?"-":b;g=d+i(String(Math.abs(p)),c,"0",false);return s(g,d,y,f,w);case"e":case"E":case"f":case"F":case"g":case"G":p=+g;d=p<0?"-":b;v=["toExponential","toFixed","toPrecision"]["efg".indexOf(h.toLowerCase())];m=["toString","toUpperCase"]["eEfFgG".indexOf(h)%2];g=d+Math.abs(p)[v](c);return s(g,d,y,f,w)[m]();default:return e}};return r.replace(e,a)};

/*!
 * imagesLoaded PACKAGED v4.1.0
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */

!function(t,e){"function"==typeof define&&define.amd?define("ev-emitter/ev-emitter",e):"object"==typeof module&&module.exports?module.exports=e():t.EvEmitter=e()}(this,function(){function t(){}var e=t.prototype;return e.on=function(t,e){if(t&&e){var i=this._events=this._events||{},n=i[t]=i[t]||[];return-1==n.indexOf(e)&&n.push(e),this}},e.once=function(t,e){if(t&&e){this.on(t,e);var i=this._onceEvents=this._onceEvents||{},n=i[t]=i[t]||[];return n[e]=!0,this}},e.off=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=i.indexOf(e);return-1!=n&&i.splice(n,1),this}},e.emitEvent=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=0,o=i[n];e=e||[];for(var r=this._onceEvents&&this._onceEvents[t];o;){var s=r&&r[o];s&&(this.off(t,o),delete r[o]),o.apply(this,e),n+=s?0:1,o=i[n]}return this}},t}),function(t,e){"use strict";"function"==typeof define&&define.amd?define(["ev-emitter/ev-emitter"],function(i){return e(t,i)}):"object"==typeof module&&module.exports?module.exports=e(t,require("ev-emitter")):t.imagesLoaded=e(t,t.EvEmitter)}(window,function(t,e){function i(t,e){for(var i in e)t[i]=e[i];return t}function n(t){var e=[];if(Array.isArray(t))e=t;else if("number"==typeof t.length)for(var i=0;i<t.length;i++)e.push(t[i]);else e.push(t);return e}function o(t,e,r){return this instanceof o?("string"==typeof t&&(t=document.querySelectorAll(t)),this.elements=n(t),this.options=i({},this.options),"function"==typeof e?r=e:i(this.options,e),r&&this.on("always",r),this.getImages(),h&&(this.jqDeferred=new h.Deferred),void setTimeout(function(){this.check()}.bind(this))):new o(t,e,r)}function r(t){this.img=t}function s(t,e){this.url=t,this.element=e,this.img=new Image}var h=t.jQuery,a=t.console;o.prototype=Object.create(e.prototype),o.prototype.options={},o.prototype.getImages=function(){this.images=[],this.elements.forEach(this.addElementImages,this)},o.prototype.addElementImages=function(t){"IMG"==t.nodeName&&this.addImage(t),this.options.background===!0&&this.addElementBackgroundImages(t);var e=t.nodeType;if(e&&d[e]){for(var i=t.querySelectorAll("img"),n=0;n<i.length;n++){var o=i[n];this.addImage(o)}if("string"==typeof this.options.background){var r=t.querySelectorAll(this.options.background);for(n=0;n<r.length;n++){var s=r[n];this.addElementBackgroundImages(s)}}}};var d={1:!0,9:!0,11:!0};return o.prototype.addElementBackgroundImages=function(t){var e=getComputedStyle(t);if(e)for(var i=/url\((['"])?(.*?)\1\)/gi,n=i.exec(e.backgroundImage);null!==n;){var o=n&&n[2];o&&this.addBackground(o,t),n=i.exec(e.backgroundImage)}},o.prototype.addImage=function(t){var e=new r(t);this.images.push(e)},o.prototype.addBackground=function(t,e){var i=new s(t,e);this.images.push(i)},o.prototype.check=function(){function t(t,i,n){setTimeout(function(){e.progress(t,i,n)})}var e=this;return this.progressedCount=0,this.hasAnyBroken=!1,this.images.length?void this.images.forEach(function(e){e.once("progress",t),e.check()}):void this.complete()},o.prototype.progress=function(t,e,i){this.progressedCount++,this.hasAnyBroken=this.hasAnyBroken||!t.isLoaded,this.emitEvent("progress",[this,t,e]),this.jqDeferred&&this.jqDeferred.notify&&this.jqDeferred.notify(this,t),this.progressedCount==this.images.length&&this.complete(),this.options.debug&&a&&a.log("progress: "+i,t,e)},o.prototype.complete=function(){var t=this.hasAnyBroken?"fail":"done";if(this.isComplete=!0,this.emitEvent(t,[this]),this.emitEvent("always",[this]),this.jqDeferred){var e=this.hasAnyBroken?"reject":"resolve";this.jqDeferred[e](this)}},r.prototype=Object.create(e.prototype),r.prototype.check=function(){var t=this.getIsImageComplete();return t?void this.confirm(0!==this.img.naturalWidth,"naturalWidth"):(this.proxyImage=new Image,this.proxyImage.addEventListener("load",this),this.proxyImage.addEventListener("error",this),this.img.addEventListener("load",this),this.img.addEventListener("error",this),void(this.proxyImage.src=this.img.src))},r.prototype.getIsImageComplete=function(){return this.img.complete&&void 0!==this.img.naturalWidth},r.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.img,e])},r.prototype.handleEvent=function(t){var e="on"+t.type;this[e]&&this[e](t)},r.prototype.onload=function(){this.confirm(!0,"onload"),this.unbindEvents()},r.prototype.onerror=function(){this.confirm(!1,"onerror"),this.unbindEvents()},r.prototype.unbindEvents=function(){this.proxyImage.removeEventListener("load",this),this.proxyImage.removeEventListener("error",this),this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype=Object.create(r.prototype),s.prototype.check=function(){this.img.addEventListener("load",this),this.img.addEventListener("error",this),this.img.src=this.url;var t=this.getIsImageComplete();t&&(this.confirm(0!==this.img.naturalWidth,"naturalWidth"),this.unbindEvents())},s.prototype.unbindEvents=function(){this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.element,e])},o.makeJQueryPlugin=function(e){e=e||t.jQuery,e&&(h=e,h.fn.imagesLoaded=function(t,e){var i=new o(this,t,e);return i.jqDeferred.promise(h(this))})},o.makeJQueryPlugin(),o});

/**
 * TipTip
 * Copyright 2010 Drew Wilson
 * code.drewwilson.com/entry/tiptip-jquery-plugin
 *
 * Version 1.3(modified) - Updated: Jun. 23, 2011
 * http://drew.tenderapp.com/discussions/tiptip/70-updated-tiptip-with-new-features
 *
 * This TipTip jQuery plug-in is dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
(function($){$.fn.tipTip=function(options){var defaults={activation:"hover",keepAlive:false,maxWidth:"200px",edgeOffset:6,defaultPosition:"bottom",delay:400,fadeIn:200,fadeOut:200,attribute:"title",content:false,enter:function(){},afterEnter:function(){},exit:function(){},afterExit:function(){},cssClass:""};if($("#tiptip_holder").length<=0){var tiptip_holder=$('<div id="tiptip_holder"></div>');var tiptip_content=$('<div id="tiptip_content"></div>');var tiptip_arrow=$('<div id="tiptip_arrow"></div>');$("body").append(tiptip_holder.html(tiptip_content).prepend(tiptip_arrow.html('<div id="tiptip_arrow_inner"></div>')))}else{var tiptip_holder=$("#tiptip_holder");var tiptip_content=$("#tiptip_content");var tiptip_arrow=$("#tiptip_arrow")}return this.each(function(){var org_elem=$(this),data=org_elem.data("tipTip"),opts=data&&data.options||$.extend(defaults,options),callback_data={holder:tiptip_holder,content:tiptip_content,arrow:tiptip_arrow,options:opts};if(data){switch(options){case"show":active_tiptip();break;case"hide":deactive_tiptip();break;case"destroy":org_elem.unbind(".tipTip").removeData("tipTip");break}}else{var timeout=false;org_elem.data("tipTip",{options:opts});if(opts.activation=="hover"){org_elem.bind("mouseenter.tipTip",function(){active_tiptip()}).bind("mouseleave.tipTip",function(){if(!opts.keepAlive){deactive_tiptip()}else{tiptip_holder.one("mouseleave.tipTip",function(){deactive_tiptip()})}})}else{if(opts.activation=="focus"){org_elem.bind("focus.tipTip",function(){active_tiptip()}).bind("blur.tipTip",function(){deactive_tiptip()})}else{if(opts.activation=="click"){org_elem.bind("click.tipTip",function(e){e.preventDefault();active_tiptip();return false}).bind("mouseleave.tipTip",function(){if(!opts.keepAlive){deactive_tiptip()}else{tiptip_holder.one("mouseleave.tipTip",function(){deactive_tiptip()})}})}else{if(opts.activation=="manual"){}}}}}function active_tiptip(){if(opts.enter.call(org_elem,callback_data)===false){return}var org_title;if(opts.content){org_title=$.isFunction(opts.content)?opts.content.call(org_elem,callback_data):opts.content}else{org_title=opts.content=org_elem.attr(opts.attribute);org_elem.removeAttr(opts.attribute)}if(!org_title){return}tiptip_content.html(org_title);tiptip_holder.hide().removeAttr("class").css({margin:"0px","max-width":opts.maxWidth});if(opts.cssClass){tiptip_holder.addClass(opts.cssClass)}tiptip_arrow.removeAttr("style");var top=parseInt(org_elem.offset()["top"]),left=parseInt(org_elem.offset()["left"]),org_width=parseInt(org_elem.outerWidth()),org_height=parseInt(org_elem.outerHeight()),tip_w=tiptip_holder.outerWidth(),tip_h=tiptip_holder.outerHeight(),w_compare=Math.round((org_width-tip_w)/2),h_compare=Math.round((org_height-tip_h)/2),marg_left=Math.round(left+w_compare),marg_top=Math.round(top+org_height+opts.edgeOffset),t_class="",arrow_top="",arrow_left=Math.round(tip_w-12)/2;if(opts.defaultPosition=="bottom"){t_class="_bottom"}else{if(opts.defaultPosition=="top"){t_class="_top"}else{if(opts.defaultPosition=="left"){t_class="_left"}else{if(opts.defaultPosition=="right"){t_class="_right"}}}}var right_compare=(w_compare+left)<parseInt($(window).scrollLeft()),left_compare=(tip_w+left)>parseInt($(window).width());if((right_compare&&w_compare<0)||(t_class=="_right"&&!left_compare)||(t_class=="_left"&&left<(tip_w+opts.edgeOffset+5))){t_class="_right";arrow_top=Math.round(tip_h-13)/2;arrow_left=-12;marg_left=Math.round(left+org_width+opts.edgeOffset);marg_top=Math.round(top+h_compare)}else{if((left_compare&&w_compare<0)||(t_class=="_left"&&!right_compare)){t_class="_left";arrow_top=Math.round(tip_h-13)/2;arrow_left=Math.round(tip_w);marg_left=Math.round(left-(tip_w+opts.edgeOffset+5));marg_top=Math.round(top+h_compare)}}var top_compare=(top+org_height+opts.edgeOffset+tip_h+8)>parseInt($(window).height()+$(window).scrollTop()),bottom_compare=((top+org_height)-(opts.edgeOffset+tip_h+8))<0;if(top_compare||(t_class=="_bottom"&&top_compare)||(t_class=="_top"&&!bottom_compare)){if(t_class=="_top"||t_class=="_bottom"){t_class="_top"}else{t_class=t_class+"_top"}arrow_top=tip_h;marg_top=Math.round(top-(tip_h+5+opts.edgeOffset))}else{if(bottom_compare|(t_class=="_top"&&bottom_compare)||(t_class=="_bottom"&&!top_compare)){if(t_class=="_top"||t_class=="_bottom"){t_class="_bottom"}else{t_class=t_class+"_bottom"}arrow_top=-12;marg_top=Math.round(top+org_height+opts.edgeOffset)}}if(t_class=="_right_top"||t_class=="_left_top"){marg_top=marg_top+5}else{if(t_class=="_right_bottom"||t_class=="_left_bottom"){marg_top=marg_top-5}}if(t_class=="_left_top"||t_class=="_left_bottom"){marg_left=marg_left+5}tiptip_arrow.css({"margin-left":arrow_left+"px","margin-top":arrow_top+"px"});tiptip_holder.css({"margin-left":marg_left+"px","margin-top":marg_top+"px"}).addClass("tip"+t_class);if(timeout){clearTimeout(timeout)}timeout=setTimeout(function(){tiptip_holder.stop(true,true).fadeIn(opts.fadeIn)},opts.delay);opts.afterEnter.call(org_elem,callback_data)}function deactive_tiptip(){if(opts.exit.call(org_elem,callback_data)===false){return}if(timeout){clearTimeout(timeout)}tiptip_holder.fadeOut(opts.fadeOut);opts.afterExit.call(org_elem,callback_data)}})}})(jQuery);

/**
 * jQuery UI Touch Punch 0.2.2
 * Copyright 2011, Dave Furfero
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * Depends: jquery.ui.widget jquery.ui.mouse
 */
(function(b){b.support.touch="ontouchend" in document;if(!b.support.touch){return;}var c=b.ui.mouse.prototype,e=c._mouseInit,a;function d(g,h){if(g.originalEvent.touches.length>1){return;}g.preventDefault();var i=g.originalEvent.changedTouches[0],f=document.createEvent("MouseEvents");f.initMouseEvent(h,true,true,window,1,i.screenX,i.screenY,i.clientX,i.clientY,false,false,false,false,0,null);g.target.dispatchEvent(f);}c._touchStart=function(g){var f=this;if(a||!f._mouseCapture(g.originalEvent.changedTouches[0])){return;}a=true;f._touchMoved=false;d(g,"mouseover");d(g,"mousemove");d(g,"mousedown");};c._touchMove=function(f){if(!a){return;}this._touchMoved=true;d(f,"mousemove");};c._touchEnd=function(f){if(!a){return;}d(f,"mouseup");d(f,"mouseout");if(!this._touchMoved){d(f,"click");}a=false;};c._mouseInit=function(){var f=this;f.element.bind("touchstart",b.proxy(f,"_touchStart")).bind("touchmove",b.proxy(f,"_touchMove")).bind("touchend",b.proxy(f,"_touchEnd"));e.call(f);};})(jQuery);

/**
 * fileOverview TouchSwipe - jQuery Plugin
 * version 1.6.5
*/
(function(a){if(typeof define==="function"&&define.amd&&define.amd.jQuery){define(["jquery"],a)}else{a(jQuery)}}(function(e){var o="left",n="right",d="up",v="down",c="in",w="out",l="none",r="auto",k="swipe",s="pinch",x="tap",i="doubletap",b="longtap",A="horizontal",t="vertical",h="all",q=10,f="start",j="move",g="end",p="cancel",a="ontouchstart" in window,y="TouchSwipe";var m={fingers:1,threshold:75,cancelThreshold:null,pinchThreshold:20,maxTimeThreshold:null,fingerReleaseThreshold:250,longTapThreshold:500,doubleTapThreshold:200,swipe:null,swipeLeft:null,swipeRight:null,swipeUp:null,swipeDown:null,swipeStatus:null,pinchIn:null,pinchOut:null,pinchStatus:null,click:null,tap:null,doubleTap:null,longTap:null,triggerOnTouchEnd:true,triggerOnTouchLeave:false,allowPageScroll:"auto",fallbackToMouseEvents:true,excludedElements:"label, button, input, select, textarea, a, .noSwipe"};e.fn.swipe=function(D){var C=e(this),B=C.data(y);if(B&&typeof D==="string"){if(B[D]){return B[D].apply(this,Array.prototype.slice.call(arguments,1))}else{e.error("Method "+D+" does not exist on jQuery.swipe")}}else{if(!B&&(typeof D==="object"||!D)){return u.apply(this,arguments)}}return C};e.fn.swipe.defaults=m;e.fn.swipe.phases={PHASE_START:f,PHASE_MOVE:j,PHASE_END:g,PHASE_CANCEL:p};e.fn.swipe.directions={LEFT:o,RIGHT:n,UP:d,DOWN:v,IN:c,OUT:w};e.fn.swipe.pageScroll={NONE:l,HORIZONTAL:A,VERTICAL:t,AUTO:r};e.fn.swipe.fingers={ONE:1,TWO:2,THREE:3,ALL:h};function u(B){if(B&&(B.allowPageScroll===undefined&&(B.swipe!==undefined||B.swipeStatus!==undefined))){B.allowPageScroll=l}if(B.click!==undefined&&B.tap===undefined){B.tap=B.click}if(!B){B={}}B=e.extend({},e.fn.swipe.defaults,B);return this.each(function(){var D=e(this);var C=D.data(y);if(!C){C=new z(this,B);D.data(y,C)}})}function z(a0,aq){var av=(a||!aq.fallbackToMouseEvents),G=av?"touchstart":"mousedown",au=av?"touchmove":"mousemove",R=av?"touchend":"mouseup",P=av?null:"mouseleave",az="touchcancel";var ac=0,aL=null,Y=0,aX=0,aV=0,D=1,am=0,aF=0,J=null;var aN=e(a0);var W="start";var T=0;var aM=null;var Q=0,aY=0,a1=0,aa=0,K=0;var aS=null;try{aN.bind(G,aJ);aN.bind(az,a5)}catch(ag){e.error("events not supported "+G+","+az+" on jQuery.swipe")}this.enable=function(){aN.bind(G,aJ);aN.bind(az,a5);return aN};this.disable=function(){aG();return aN};this.destroy=function(){aG();aN.data(y,null);return aN};this.option=function(a8,a7){if(aq[a8]!==undefined){if(a7===undefined){return aq[a8]}else{aq[a8]=a7}}else{e.error("Option "+a8+" does not exist on jQuery.swipe.options")}return null};function aJ(a9){if(ax()){return}if(e(a9.target).closest(aq.excludedElements,aN).length>0){return}var ba=a9.originalEvent?a9.originalEvent:a9;var a8,a7=a?ba.touches[0]:ba;W=f;if(a){T=ba.touches.length}else{a9.preventDefault()}ac=0;aL=null;aF=null;Y=0;aX=0;aV=0;D=1;am=0;aM=af();J=X();O();if(!a||(T===aq.fingers||aq.fingers===h)||aT()){ae(0,a7);Q=ao();if(T==2){ae(1,ba.touches[1]);aX=aV=ap(aM[0].start,aM[1].start)}if(aq.swipeStatus||aq.pinchStatus){a8=L(ba,W)}}else{a8=false}if(a8===false){W=p;L(ba,W);return a8}else{ak(true)}return null}function aZ(ba){var bd=ba.originalEvent?ba.originalEvent:ba;if(W===g||W===p||ai()){return}var a9,a8=a?bd.touches[0]:bd;var bb=aD(a8);aY=ao();if(a){T=bd.touches.length}W=j;if(T==2){if(aX==0){ae(1,bd.touches[1]);aX=aV=ap(aM[0].start,aM[1].start)}else{aD(bd.touches[1]);aV=ap(aM[0].end,aM[1].end);aF=an(aM[0].end,aM[1].end)}D=a3(aX,aV);am=Math.abs(aX-aV)}if((T===aq.fingers||aq.fingers===h)||!a||aT()){aL=aH(bb.start,bb.end);ah(ba,aL);ac=aO(bb.start,bb.end);Y=aI();aE(aL,ac);if(aq.swipeStatus||aq.pinchStatus){a9=L(bd,W)}if(!aq.triggerOnTouchEnd||aq.triggerOnTouchLeave){var a7=true;if(aq.triggerOnTouchLeave){var bc=aU(this);a7=B(bb.end,bc)}if(!aq.triggerOnTouchEnd&&a7){W=ay(j)}else{if(aq.triggerOnTouchLeave&&!a7){W=ay(g)}}if(W==p||W==g){L(bd,W)}}}else{W=p;L(bd,W)}if(a9===false){W=p;L(bd,W)}}function I(a7){var a8=a7.originalEvent;if(a){if(a8.touches.length>0){C();return true}}if(ai()){T=aa}a7.preventDefault();aY=ao();Y=aI();if(a6()){W=p;L(a8,W)}else{if(aq.triggerOnTouchEnd||(aq.triggerOnTouchEnd==false&&W===j)){W=g;L(a8,W)}else{if(!aq.triggerOnTouchEnd&&a2()){W=g;aB(a8,W,x)}else{if(W===j){W=p;L(a8,W)}}}}ak(false);return null}function a5(){T=0;aY=0;Q=0;aX=0;aV=0;D=1;O();ak(false)}function H(a7){var a8=a7.originalEvent;if(aq.triggerOnTouchLeave){W=ay(g);L(a8,W)}}function aG(){aN.unbind(G,aJ);aN.unbind(az,a5);aN.unbind(au,aZ);aN.unbind(R,I);if(P){aN.unbind(P,H)}ak(false)}function ay(bb){var ba=bb;var a9=aw();var a8=aj();var a7=a6();if(!a9||a7){ba=p}else{if(a8&&bb==j&&(!aq.triggerOnTouchEnd||aq.triggerOnTouchLeave)){ba=g}else{if(!a8&&bb==g&&aq.triggerOnTouchLeave){ba=p}}}return ba}function L(a9,a7){var a8=undefined;if(F()||S()){a8=aB(a9,a7,k)}else{if((M()||aT())&&a8!==false){a8=aB(a9,a7,s)}}if(aC()&&a8!==false){a8=aB(a9,a7,i)}else{if(al()&&a8!==false){a8=aB(a9,a7,b)}else{if(ad()&&a8!==false){a8=aB(a9,a7,x)}}}if(a7===p){a5(a9)}if(a7===g){if(a){if(a9.touches.length==0){a5(a9)}}else{a5(a9)}}return a8}function aB(ba,a7,a9){var a8=undefined;if(a9==k){aN.trigger("swipeStatus",[a7,aL||null,ac||0,Y||0,T]);if(aq.swipeStatus){a8=aq.swipeStatus.call(aN,ba,a7,aL||null,ac||0,Y||0,T);if(a8===false){return false}}if(a7==g&&aR()){aN.trigger("swipe",[aL,ac,Y,T]);if(aq.swipe){a8=aq.swipe.call(aN,ba,aL,ac,Y,T);if(a8===false){return false}}switch(aL){case o:aN.trigger("swipeLeft",[aL,ac,Y,T]);if(aq.swipeLeft){a8=aq.swipeLeft.call(aN,ba,aL,ac,Y,T)}break;case n:aN.trigger("swipeRight",[aL,ac,Y,T]);if(aq.swipeRight){a8=aq.swipeRight.call(aN,ba,aL,ac,Y,T)}break;case d:aN.trigger("swipeUp",[aL,ac,Y,T]);if(aq.swipeUp){a8=aq.swipeUp.call(aN,ba,aL,ac,Y,T)}break;case v:aN.trigger("swipeDown",[aL,ac,Y,T]);if(aq.swipeDown){a8=aq.swipeDown.call(aN,ba,aL,ac,Y,T)}break}}}if(a9==s){aN.trigger("pinchStatus",[a7,aF||null,am||0,Y||0,T,D]);if(aq.pinchStatus){a8=aq.pinchStatus.call(aN,ba,a7,aF||null,am||0,Y||0,T,D);if(a8===false){return false}}if(a7==g&&a4()){switch(aF){case c:aN.trigger("pinchIn",[aF||null,am||0,Y||0,T,D]);if(aq.pinchIn){a8=aq.pinchIn.call(aN,ba,aF||null,am||0,Y||0,T,D)}break;case w:aN.trigger("pinchOut",[aF||null,am||0,Y||0,T,D]);if(aq.pinchOut){a8=aq.pinchOut.call(aN,ba,aF||null,am||0,Y||0,T,D)}break}}}if(a9==x){if(a7===p||a7===g){clearTimeout(aS);if(V()&&!E()){K=ao();aS=setTimeout(e.proxy(function(){K=null;aN.trigger("tap",[ba.target]);if(aq.tap){a8=aq.tap.call(aN,ba,ba.target)}},this),aq.doubleTapThreshold)}else{K=null;aN.trigger("tap",[ba.target]);if(aq.tap){a8=aq.tap.call(aN,ba,ba.target)}}}}else{if(a9==i){if(a7===p||a7===g){clearTimeout(aS);K=null;aN.trigger("doubletap",[ba.target]);if(aq.doubleTap){a8=aq.doubleTap.call(aN,ba,ba.target)}}}else{if(a9==b){if(a7===p||a7===g){clearTimeout(aS);K=null;aN.trigger("longtap",[ba.target]);if(aq.longTap){a8=aq.longTap.call(aN,ba,ba.target)}}}}}return a8}function aj(){var a7=true;if(aq.threshold!==null){a7=ac>=aq.threshold}return a7}function a6(){var a7=false;if(aq.cancelThreshold!==null&&aL!==null){a7=(aP(aL)-ac)>=aq.cancelThreshold}return a7}function ab(){if(aq.pinchThreshold!==null){return am>=aq.pinchThreshold}return true}function aw(){var a7;if(aq.maxTimeThreshold){if(Y>=aq.maxTimeThreshold){a7=false}else{a7=true}}else{a7=true}return a7}function ah(a7,a8){if(aq.allowPageScroll===l||aT()){a7.preventDefault()}else{var a9=aq.allowPageScroll===r;switch(a8){case o:if((aq.swipeLeft&&a9)||(!a9&&aq.allowPageScroll!=A)){a7.preventDefault()}break;case n:if((aq.swipeRight&&a9)||(!a9&&aq.allowPageScroll!=A)){a7.preventDefault()}break;case d:if((aq.swipeUp&&a9)||(!a9&&aq.allowPageScroll!=t)){a7.preventDefault()}break;case v:if((aq.swipeDown&&a9)||(!a9&&aq.allowPageScroll!=t)){a7.preventDefault()}break}}}function a4(){var a8=aK();var a7=U();var a9=ab();return a8&&a7&&a9}function aT(){return !!(aq.pinchStatus||aq.pinchIn||aq.pinchOut)}function M(){return !!(a4()&&aT())}function aR(){var ba=aw();var bc=aj();var a9=aK();var a7=U();var a8=a6();var bb=!a8&&a7&&a9&&bc&&ba;return bb}function S(){return !!(aq.swipe||aq.swipeStatus||aq.swipeLeft||aq.swipeRight||aq.swipeUp||aq.swipeDown)}function F(){return !!(aR()&&S())}function aK(){return((T===aq.fingers||aq.fingers===h)||!a)}function U(){return aM[0].end.x!==0}function a2(){return !!(aq.tap)}function V(){return !!(aq.doubleTap)}function aQ(){return !!(aq.longTap)}function N(){if(K==null){return false}var a7=ao();return(V()&&((a7-K)<=aq.doubleTapThreshold))}function E(){return N()}function at(){return((T===1||!a)&&(isNaN(ac)||ac===0))}function aW(){return((Y>aq.longTapThreshold)&&(ac<q))}function ad(){return !!(at()&&a2())}function aC(){return !!(N()&&V())}function al(){return !!(aW()&&aQ())}function C(){a1=ao();aa=event.touches.length+1}function O(){a1=0;aa=0}function ai(){var a7=false;if(a1){var a8=ao()-a1;if(a8<=aq.fingerReleaseThreshold){a7=true}}return a7}function ax(){return !!(aN.data(y+"_intouch")===true)}function ak(a7){if(a7===true){aN.bind(au,aZ);aN.bind(R,I);if(P){aN.bind(P,H)}}else{aN.unbind(au,aZ,false);aN.unbind(R,I,false);if(P){aN.unbind(P,H,false)}}aN.data(y+"_intouch",a7===true)}function ae(a8,a7){var a9=a7.identifier!==undefined?a7.identifier:0;aM[a8].identifier=a9;aM[a8].start.x=aM[a8].end.x=a7.pageX||a7.clientX;aM[a8].start.y=aM[a8].end.y=a7.pageY||a7.clientY;return aM[a8]}function aD(a7){var a9=a7.identifier!==undefined?a7.identifier:0;var a8=Z(a9);a8.end.x=a7.pageX||a7.clientX;a8.end.y=a7.pageY||a7.clientY;return a8}function Z(a8){for(var a7=0;a7<aM.length;a7++){if(aM[a7].identifier==a8){return aM[a7]}}}function af(){var a7=[];for(var a8=0;a8<=5;a8++){a7.push({start:{x:0,y:0},end:{x:0,y:0},identifier:0})}return a7}function aE(a7,a8){a8=Math.max(a8,aP(a7));J[a7].distance=a8}function aP(a7){if(J[a7]){return J[a7].distance}return undefined}function X(){var a7={};a7[o]=ar(o);a7[n]=ar(n);a7[d]=ar(d);a7[v]=ar(v);return a7}function ar(a7){return{direction:a7,distance:0}}function aI(){return aY-Q}function ap(ba,a9){var a8=Math.abs(ba.x-a9.x);var a7=Math.abs(ba.y-a9.y);return Math.round(Math.sqrt(a8*a8+a7*a7))}function a3(a7,a8){var a9=(a8/a7)*1;return a9.toFixed(2)}function an(){if(D<1){return w}else{return c}}function aO(a8,a7){return Math.round(Math.sqrt(Math.pow(a7.x-a8.x,2)+Math.pow(a7.y-a8.y,2)))}function aA(ba,a8){var a7=ba.x-a8.x;var bc=a8.y-ba.y;var a9=Math.atan2(bc,a7);var bb=Math.round(a9*180/Math.PI);if(bb<0){bb=360-Math.abs(bb)}return bb}function aH(a8,a7){var a9=aA(a8,a7);if((a9<=45)&&(a9>=0)){return o}else{if((a9<=360)&&(a9>=315)){return o}else{if((a9>=135)&&(a9<=225)){return n}else{if((a9>45)&&(a9<135)){return v}else{return d}}}}}function ao(){var a7=new Date();return a7.getTime()}function aU(a7){a7=e(a7);var a9=a7.offset();var a8={left:a9.left,right:a9.left+a7.outerWidth(),top:a9.top,bottom:a9.top+a7.outerHeight()};return a8}function B(a7,a8){return(a7.x>a8.left&&a7.x<a8.right&&a7.y>a8.top&&a7.y<a8.bottom)}}}));

/*
 * JavaScript Load Image v2.7.0* (added S.originalWidth and S.originalHeight)
 * https://github.com/blueimp/JavaScript-Load-Image
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
!function(e){"use strict";function t(e,i,a){var o,r=document.createElement("img");if(r.onerror=function(o){return t.onerror(r,o,e,i,a)},r.onload=function(o){return t.onload(r,o,e,i,a)},t.isInstanceOf("Blob",e)||t.isInstanceOf("File",e))o=r._objectURL=t.createObjectURL(e);else{if("string"!=typeof e)return!1;o=e,a&&a.crossOrigin&&(r.crossOrigin=a.crossOrigin)}return o?(r.src=o,r):t.readFile(e,function(e){var t=e.target;t&&t.result?r.src=t.result:i&&i(e)})}function i(e,i){!e._objectURL||i&&i.noRevoke||(t.revokeObjectURL(e._objectURL),delete e._objectURL)}var a=window.createObjectURL&&window||window.URL&&URL.revokeObjectURL&&URL||window.webkitURL&&webkitURL;t.isInstanceOf=function(e,t){return Object.prototype.toString.call(t)==="[object "+e+"]"},t.transform=function(e,i,a,o,r){a(t.scale(e,i,r),r)},t.onerror=function(e,t,a,o,r){i(e,r),o&&o.call(e,t)},t.onload=function(e,a,o,r,n){i(e,n),r&&t.transform(e,n,r,o,{})},t.transformCoordinates=function(){},t.getTransformedOptions=function(e,t){var i,a,o,r,n=t.aspectRatio;if(!n)return t;i={};for(a in t)t.hasOwnProperty(a)&&(i[a]=t[a]);return i.crop=!0,o=e.naturalWidth||e.width,r=e.naturalHeight||e.height,o/r>n?(i.maxWidth=r*n,i.maxHeight=r):(i.maxWidth=o,i.maxHeight=o/n),i},t.renderImageToCanvas=function(e,t,i,a,o,r,n,s,l,d){return e.getContext("2d").drawImage(t,i,a,o,r,n,s,l,d),e},t.hasCanvasOption=function(e){return e.canvas||e.crop||!!e.aspectRatio},t.scale=function(e,i,a){function o(){var e=Math.max((l||v)/v,(d||P)/P);e>1&&(v*=e,P*=e)}function r(){var e=Math.min((n||v)/v,(s||P)/P);e<1&&(v*=e,P*=e)}i=i||{};var n,s,l,d,u,c,f,g,h,m,p,S=document.createElement("canvas"),b=e.getContext||t.hasCanvasOption(i)&&S.getContext,x=e.naturalWidth||e.width,y=e.naturalHeight||e.height,v=x,P=y;S.originalWidth=x;S.originalHeight=y;if(b&&(i=t.getTransformedOptions(e,i,a),f=i.left||0,g=i.top||0,i.sourceWidth?(u=i.sourceWidth,void 0!==i.right&&void 0===i.left&&(f=x-u-i.right)):u=x-f-(i.right||0),i.sourceHeight?(c=i.sourceHeight,void 0!==i.bottom&&void 0===i.top&&(g=y-c-i.bottom)):c=y-g-(i.bottom||0),v=u,P=c),n=i.maxWidth,s=i.maxHeight,l=i.minWidth,d=i.minHeight,b&&n&&s&&i.crop?(v=n,P=s,p=u/c-n/s,p<0?(c=s*u/n,void 0===i.top&&void 0===i.bottom&&(g=(y-c)/2)):p>0&&(u=n*c/s,void 0===i.left&&void 0===i.right&&(f=(x-u)/2))):((i.contain||i.cover)&&(l=n=n||l,d=s=s||d),i.cover?(r(),o()):(o(),r())),b){if(h=i.pixelRatio,h>1&&(S.style.width=v+"px",S.style.height=P+"px",v*=h,P*=h,S.getContext("2d").scale(h,h)),m=i.downsamplingRatio,m>0&&m<1&&v<u&&P<c)for(;u*m>v;)S.width=u*m,S.height=c*m,t.renderImageToCanvas(S,e,f,g,u,c,0,0,S.width,S.height),u=S.width,c=S.height,e=document.createElement("canvas"),e.width=u,e.height=c,t.renderImageToCanvas(e,S,0,0,u,c,0,0,u,c);return S.width=v,S.height=P,t.transformCoordinates(S,i),t.renderImageToCanvas(S,e,f,g,u,c,0,0,v,P)}return e.width=v,e.height=P,e},t.createObjectURL=function(e){return!!a&&a.createObjectURL(e)},t.revokeObjectURL=function(e){return!!a&&a.revokeObjectURL(e)},t.readFile=function(e,t,i){if(window.FileReader){var a=new FileReader;if(a.onload=a.onerror=t,i=i||"readAsDataURL",a[i])return a[i](e),a}return!1},"function"==typeof define&&define.amd?define(function(){return t}):"object"==typeof module&&module.exports?module.exports=t:e.loadImage=t}(window),function(e){"use strict";"function"==typeof define&&define.amd?define(["./load-image"],e):e("object"==typeof module&&module.exports?require("./load-image"):window.loadImage)}(function(e){"use strict";var t=window.Blob&&(Blob.prototype.slice||Blob.prototype.webkitSlice||Blob.prototype.mozSlice);e.blobSlice=t&&function(){var e=this.slice||this.webkitSlice||this.mozSlice;return e.apply(this,arguments)},e.metaDataParsers={jpeg:{65505:[]}},e.parseMetaData=function(t,i,a,o){a=a||{},o=o||{};var r=this,n=a.maxMetaDataSize||262144,s=!(window.DataView&&t&&t.size>=12&&"image/jpeg"===t.type&&e.blobSlice);!s&&e.readFile(e.blobSlice.call(t,0,n),function(t){if(t.target.error)return console.log(t.target.error),void i(o);var n,s,l,d,u=t.target.result,c=new DataView(u),f=2,g=c.byteLength-4,h=f;if(65496===c.getUint16(0)){for(;f<g&&(n=c.getUint16(f),n>=65504&&n<=65519||65534===n);){if(s=c.getUint16(f+2)+2,f+s>c.byteLength){console.log("Invalid meta data: Invalid segment size.");break}if(l=e.metaDataParsers.jpeg[n])for(d=0;d<l.length;d+=1)l[d].call(r,c,f,s,o,a);f+=s,h=f}!a.disableImageHead&&h>6&&(u.slice?o.imageHead=u.slice(0,h):o.imageHead=new Uint8Array(u).subarray(0,h))}else console.log("Invalid JPEG file: Missing JPEG marker.");i(o)},"readAsArrayBuffer")||i(o)},e.hasMetaOption=function(e){return e.meta};var i=e.transform;e.transform=function(t,a,o,r,n){e.hasMetaOption(a||{})?e.parseMetaData(r,function(n){i.call(e,t,a,o,r,n)},a,n):i.apply(e,arguments)}}),function(e){"use strict";"function"==typeof define&&define.amd?define(["./load-image","./load-image-meta"],e):"object"==typeof module&&module.exports?e(require("./load-image"),require("./load-image-meta")):e(window.loadImage)}(function(e){"use strict";e.ExifMap=function(){return this},e.ExifMap.prototype.map={Orientation:274},e.ExifMap.prototype.get=function(e){return this[e]||this[this.map[e]]},e.getExifThumbnail=function(e,t,i){var a,o,r;if(!i||t+i>e.byteLength)return void console.log("Invalid Exif data: Invalid thumbnail data.");for(a=[],o=0;o<i;o+=1)r=e.getUint8(t+o),a.push((r<16?"0":"")+r.toString(16));return"data:image/jpeg,%"+a.join("%")},e.exifTagTypes={1:{getValue:function(e,t){return e.getUint8(t)},size:1},2:{getValue:function(e,t){return String.fromCharCode(e.getUint8(t))},size:1,ascii:!0},3:{getValue:function(e,t,i){return e.getUint16(t,i)},size:2},4:{getValue:function(e,t,i){return e.getUint32(t,i)},size:4},5:{getValue:function(e,t,i){return e.getUint32(t,i)/e.getUint32(t+4,i)},size:8},9:{getValue:function(e,t,i){return e.getInt32(t,i)},size:4},10:{getValue:function(e,t,i){return e.getInt32(t,i)/e.getInt32(t+4,i)},size:8}},e.exifTagTypes[7]=e.exifTagTypes[1],e.getExifValue=function(t,i,a,o,r,n){var s,l,d,u,c,f,g=e.exifTagTypes[o];if(!g)return void console.log("Invalid Exif data: Invalid tag type.");if(s=g.size*r,l=s>4?i+t.getUint32(a+8,n):a+8,l+s>t.byteLength)return void console.log("Invalid Exif data: Invalid data offset.");if(1===r)return g.getValue(t,l,n);for(d=[],u=0;u<r;u+=1)d[u]=g.getValue(t,l+u*g.size,n);if(g.ascii){for(c="",u=0;u<d.length&&(f=d[u],"\0"!==f);u+=1)c+=f;return c}return d},e.parseExifTag=function(t,i,a,o,r){var n=t.getUint16(a,o);r.exif[n]=e.getExifValue(t,i,a,t.getUint16(a+2,o),t.getUint32(a+4,o),o)},e.parseExifTags=function(e,t,i,a,o){var r,n,s;if(i+6>e.byteLength)return void console.log("Invalid Exif data: Invalid directory offset.");if(r=e.getUint16(i,a),n=i+2+12*r,n+4>e.byteLength)return void console.log("Invalid Exif data: Invalid directory size.");for(s=0;s<r;s+=1)this.parseExifTag(e,t,i+2+12*s,a,o);return e.getUint32(n,a)},e.parseExifData=function(t,i,a,o,r){if(!r.disableExif){var n,s,l,d=i+10;if(1165519206===t.getUint32(i+4)){if(d+8>t.byteLength)return void console.log("Invalid Exif data: Invalid segment size.");if(0!==t.getUint16(i+8))return void console.log("Invalid Exif data: Missing byte alignment offset.");switch(t.getUint16(d)){case 18761:n=!0;break;case 19789:n=!1;break;default:return void console.log("Invalid Exif data: Invalid byte alignment marker.")}if(42!==t.getUint16(d+2,n))return void console.log("Invalid Exif data: Missing TIFF marker.");s=t.getUint32(d+4,n),o.exif=new e.ExifMap,s=e.parseExifTags(t,d,d+s,n,o),s&&!r.disableExifThumbnail&&(l={exif:{}},s=e.parseExifTags(t,d,d+s,n,l),l.exif[513]&&(o.exif.Thumbnail=e.getExifThumbnail(t,d+l.exif[513],l.exif[514]))),o.exif[34665]&&!r.disableExifSub&&e.parseExifTags(t,d,d+o.exif[34665],n,o),o.exif[34853]&&!r.disableExifGps&&e.parseExifTags(t,d,d+o.exif[34853],n,o)}}},e.metaDataParsers.jpeg[65505].push(e.parseExifData)}),function(e){"use strict";"function"==typeof define&&define.amd?define(["./load-image","./load-image-exif"],e):"object"==typeof module&&module.exports?e(require("./load-image"),require("./load-image-exif")):e(window.loadImage)}(function(e){"use strict";e.ExifMap.prototype.tags={256:"ImageWidth",257:"ImageHeight",34665:"ExifIFDPointer",34853:"GPSInfoIFDPointer",40965:"InteroperabilityIFDPointer",258:"BitsPerSample",259:"Compression",262:"PhotometricInterpretation",274:"Orientation",277:"SamplesPerPixel",284:"PlanarConfiguration",530:"YCbCrSubSampling",531:"YCbCrPositioning",282:"XResolution",283:"YResolution",296:"ResolutionUnit",273:"StripOffsets",278:"RowsPerStrip",279:"StripByteCounts",513:"JPEGInterchangeFormat",514:"JPEGInterchangeFormatLength",301:"TransferFunction",318:"WhitePoint",319:"PrimaryChromaticities",529:"YCbCrCoefficients",532:"ReferenceBlackWhite",306:"DateTime",270:"ImageDescription",271:"Make",272:"Model",305:"Software",315:"Artist",33432:"Copyright",36864:"ExifVersion",40960:"FlashpixVersion",40961:"ColorSpace",40962:"PixelXDimension",40963:"PixelYDimension",42240:"Gamma",37121:"ComponentsConfiguration",37122:"CompressedBitsPerPixel",37500:"MakerNote",37510:"UserComment",40964:"RelatedSoundFile",36867:"DateTimeOriginal",36868:"DateTimeDigitized",37520:"SubSecTime",37521:"SubSecTimeOriginal",37522:"SubSecTimeDigitized",33434:"ExposureTime",33437:"FNumber",34850:"ExposureProgram",34852:"SpectralSensitivity",34855:"PhotographicSensitivity",34856:"OECF",34864:"SensitivityType",34865:"StandardOutputSensitivity",34866:"RecommendedExposureIndex",34867:"ISOSpeed",34868:"ISOSpeedLatitudeyyy",34869:"ISOSpeedLatitudezzz",37377:"ShutterSpeedValue",37378:"ApertureValue",37379:"BrightnessValue",37380:"ExposureBias",37381:"MaxApertureValue",37382:"SubjectDistance",37383:"MeteringMode",37384:"LightSource",37385:"Flash",37396:"SubjectArea",37386:"FocalLength",41483:"FlashEnergy",41484:"SpatialFrequencyResponse",41486:"FocalPlaneXResolution",41487:"FocalPlaneYResolution",41488:"FocalPlaneResolutionUnit",41492:"SubjectLocation",41493:"ExposureIndex",41495:"SensingMethod",41728:"FileSource",41729:"SceneType",41730:"CFAPattern",41985:"CustomRendered",41986:"ExposureMode",41987:"WhiteBalance",41988:"DigitalZoomRatio",41989:"FocalLengthIn35mmFilm",41990:"SceneCaptureType",41991:"GainControl",41992:"Contrast",41993:"Saturation",41994:"Sharpness",41995:"DeviceSettingDescription",41996:"SubjectDistanceRange",42016:"ImageUniqueID",42032:"CameraOwnerName",42033:"BodySerialNumber",42034:"LensSpecification",42035:"LensMake",42036:"LensModel",42037:"LensSerialNumber",0:"GPSVersionID",1:"GPSLatitudeRef",2:"GPSLatitude",3:"GPSLongitudeRef",4:"GPSLongitude",5:"GPSAltitudeRef",6:"GPSAltitude",7:"GPSTimeStamp",8:"GPSSatellites",9:"GPSStatus",10:"GPSMeasureMode",11:"GPSDOP",12:"GPSSpeedRef",13:"GPSSpeed",14:"GPSTrackRef",15:"GPSTrack",16:"GPSImgDirectionRef",17:"GPSImgDirection",18:"GPSMapDatum",19:"GPSDestLatitudeRef",20:"GPSDestLatitude",21:"GPSDestLongitudeRef",22:"GPSDestLongitude",23:"GPSDestBearingRef",24:"GPSDestBearing",25:"GPSDestDistanceRef",26:"GPSDestDistance",27:"GPSProcessingMethod",28:"GPSAreaInformation",29:"GPSDateStamp",30:"GPSDifferential",31:"GPSHPositioningError"},e.ExifMap.prototype.stringValues={ExposureProgram:{0:"Undefined",1:"Manual",2:"Normal program",3:"Aperture priority",4:"Shutter priority",5:"Creative program",6:"Action program",7:"Portrait mode",8:"Landscape mode"},MeteringMode:{0:"Unknown",1:"Average",2:"CenterWeightedAverage",3:"Spot",4:"MultiSpot",5:"Pattern",6:"Partial",255:"Other"},LightSource:{0:"Unknown",1:"Daylight",2:"Fluorescent",3:"Tungsten (incandescent light)",4:"Flash",9:"Fine weather",10:"Cloudy weather",11:"Shade",12:"Daylight fluorescent (D 5700 - 7100K)",13:"Day white fluorescent (N 4600 - 5400K)",14:"Cool white fluorescent (W 3900 - 4500K)",15:"White fluorescent (WW 3200 - 3700K)",17:"Standard light A",18:"Standard light B",19:"Standard light C",20:"D55",21:"D65",22:"D75",23:"D50",24:"ISO studio tungsten",255:"Other"},Flash:{0:"Flash did not fire",1:"Flash fired",5:"Strobe return light not detected",7:"Strobe return light detected",9:"Flash fired, compulsory flash mode",13:"Flash fired, compulsory flash mode, return light not detected",15:"Flash fired, compulsory flash mode, return light detected",16:"Flash did not fire, compulsory flash mode",24:"Flash did not fire, auto mode",25:"Flash fired, auto mode",29:"Flash fired, auto mode, return light not detected",31:"Flash fired, auto mode, return light detected",32:"No flash function",65:"Flash fired, red-eye reduction mode",69:"Flash fired, red-eye reduction mode, return light not detected",71:"Flash fired, red-eye reduction mode, return light detected",73:"Flash fired, compulsory flash mode, red-eye reduction mode",77:"Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected",79:"Flash fired, compulsory flash mode, red-eye reduction mode, return light detected",89:"Flash fired, auto mode, red-eye reduction mode",93:"Flash fired, auto mode, return light not detected, red-eye reduction mode",95:"Flash fired, auto mode, return light detected, red-eye reduction mode"},SensingMethod:{1:"Undefined",2:"One-chip color area sensor",3:"Two-chip color area sensor",4:"Three-chip color area sensor",5:"Color sequential area sensor",7:"Trilinear sensor",8:"Color sequential linear sensor"},SceneCaptureType:{0:"Standard",1:"Landscape",2:"Portrait",3:"Night scene"},SceneType:{1:"Directly photographed"},CustomRendered:{0:"Normal process",1:"Custom process"},WhiteBalance:{0:"Auto white balance",1:"Manual white balance"},GainControl:{0:"None",1:"Low gain up",2:"High gain up",3:"Low gain down",4:"High gain down"},Contrast:{0:"Normal",1:"Soft",2:"Hard"},Saturation:{0:"Normal",1:"Low saturation",2:"High saturation"},Sharpness:{0:"Normal",1:"Soft",2:"Hard"},SubjectDistanceRange:{0:"Unknown",1:"Macro",2:"Close view",3:"Distant view"},FileSource:{3:"DSC"},ComponentsConfiguration:{0:"",1:"Y",2:"Cb",3:"Cr",4:"R",5:"G",6:"B"},Orientation:{1:"top-left",2:"top-right",3:"bottom-right",4:"bottom-left",5:"left-top",6:"right-top",7:"right-bottom",8:"left-bottom"}},e.ExifMap.prototype.getText=function(e){var t=this.get(e);switch(e){case"LightSource":case"Flash":case"MeteringMode":case"ExposureProgram":case"SensingMethod":case"SceneCaptureType":case"SceneType":case"CustomRendered":case"WhiteBalance":case"GainControl":case"Contrast":case"Saturation":case"Sharpness":case"SubjectDistanceRange":case"FileSource":case"Orientation":return this.stringValues[e][t];case"ExifVersion":case"FlashpixVersion":if(!t)return;return String.fromCharCode(t[0],t[1],t[2],t[3]);case"ComponentsConfiguration":if(!t)return;return this.stringValues[e][t[0]]+this.stringValues[e][t[1]]+this.stringValues[e][t[2]]+this.stringValues[e][t[3]];case"GPSVersionID":if(!t)return;return t[0]+"."+t[1]+"."+t[2]+"."+t[3]}return String(t)},function(e){var t,i=e.tags,a=e.map;for(t in i)i.hasOwnProperty(t)&&(a[i[t]]=t)}(e.ExifMap.prototype),e.ExifMap.prototype.getAll=function(){var e,t,i={};for(e in this)this.hasOwnProperty(e)&&(t=this.tags[e],t&&(i[t]=this.getText(t)));return i}}),function(e){"use strict";"function"==typeof define&&define.amd?define(["./load-image"],e):e("object"==typeof module&&module.exports?require("./load-image"):window.loadImage)}(function(e){"use strict";var t=e.hasCanvasOption,i=e.hasMetaOption,a=e.transformCoordinates,o=e.getTransformedOptions;e.hasCanvasOption=function(i){return!!i.orientation||t.call(e,i)},e.hasMetaOption=function(t){return t.orientation===!0||i.call(e,t)},e.transformCoordinates=function(t,i){a.call(e,t,i);var o=t.getContext("2d"),r=t.width,n=t.height,s=t.style.width,l=t.style.height,d=i.orientation;if(d&&!(d>8))switch(d>4&&(t.width=n,t.height=r,t.style.width=l,t.style.height=s),d){case 2:o.translate(r,0),o.scale(-1,1);break;case 3:o.translate(r,n),o.rotate(Math.PI);break;case 4:o.translate(0,n),o.scale(1,-1);break;case 5:o.rotate(.5*Math.PI),o.scale(1,-1);break;case 6:o.rotate(.5*Math.PI),o.translate(0,-n);break;case 7:o.rotate(.5*Math.PI),o.translate(r,-n),o.scale(-1,1);break;case 8:o.rotate(-.5*Math.PI),o.translate(-r,0)}},e.getTransformedOptions=function(t,i,a){var r,n,s=o.call(e,t,i),l=s.orientation;if(l===!0&&a&&a.exif&&(l=a.exif.get("Orientation")),!l||l>8||1===l)return s;r={};for(n in s)s.hasOwnProperty(n)&&(r[n]=s[n]);switch(r.orientation=l,l){case 2:r.left=s.right,r.right=s.left;break;case 3:r.left=s.right,r.top=s.bottom,r.right=s.left,r.bottom=s.top;break;case 4:r.top=s.bottom,r.bottom=s.top;break;case 5:r.left=s.top,r.top=s.left,r.right=s.bottom,r.bottom=s.right;break;case 6:r.left=s.top,r.top=s.right,r.right=s.bottom,r.bottom=s.left;break;case 7:r.left=s.bottom,r.top=s.right,r.right=s.top,r.bottom=s.left;break;case 8:r.left=s.bottom,r.top=s.left,r.right=s.top,r.bottom=s.right}return s.orientation>4&&(r.maxWidth=s.maxHeight,r.maxHeight=s.maxWidth,r.minWidth=s.minHeight,r.minHeight=s.minWidth,r.sourceWidth=s.sourceHeight,r.sourceHeight=s.sourceWidth),r}});

/**
 * History.js Core
 * @author Benjamin Arthur Lupton <contact@balupton.com>
 * @copyright 2010-2011 Benjamin Arthur Lupton <contact@balupton.com>
 * @license New BSD License <http://creativecommons.org/licenses/BSD/>
 */
typeof JSON!="object"&&(JSON={}),function(){"use strict";function f(e){return e<10?"0"+e:e}function quote(e){return escapable.lastIndex=0,escapable.test(e)?'"'+e.replace(escapable,function(e){var t=meta[e];return typeof t=="string"?t:"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4)})+'"':'"'+e+'"'}function str(e,t){var n,r,i,s,o=gap,u,a=t[e];a&&typeof a=="object"&&typeof a.toJSON=="function"&&(a=a.toJSON(e)),typeof rep=="function"&&(a=rep.call(t,e,a));switch(typeof a){case"string":return quote(a);case"number":return isFinite(a)?String(a):"null";case"boolean":case"null":return String(a);case"object":if(!a)return"null";gap+=indent,u=[];if(Object.prototype.toString.apply(a)==="[object Array]"){s=a.length;for(n=0;n<s;n+=1)u[n]=str(n,a)||"null";return i=u.length===0?"[]":gap?"[\n"+gap+u.join(",\n"+gap)+"\n"+o+"]":"["+u.join(",")+"]",gap=o,i}if(rep&&typeof rep=="object"){s=rep.length;for(n=0;n<s;n+=1)typeof rep[n]=="string"&&(r=rep[n],i=str(r,a),i&&u.push(quote(r)+(gap?": ":":")+i))}else for(r in a)Object.prototype.hasOwnProperty.call(a,r)&&(i=str(r,a),i&&u.push(quote(r)+(gap?": ":":")+i));return i=u.length===0?"{}":gap?"{\n"+gap+u.join(",\n"+gap)+"\n"+o+"}":"{"+u.join(",")+"}",gap=o,i}}typeof Date.prototype.toJSON!="function"&&(Date.prototype.toJSON=function(e){return isFinite(this.valueOf())?this.getUTCFullYear()+"-"+f(this.getUTCMonth()+1)+"-"+f(this.getUTCDate())+"T"+f(this.getUTCHours())+":"+f(this.getUTCMinutes())+":"+f(this.getUTCSeconds())+"Z":null},String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(e){return this.valueOf()});var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={"\b":"\\b","	":"\\t","\n":"\\n","\f":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},rep;typeof JSON.stringify!="function"&&(JSON.stringify=function(e,t,n){var r;gap="",indent="";if(typeof n=="number")for(r=0;r<n;r+=1)indent+=" ";else typeof n=="string"&&(indent=n);rep=t;if(!t||typeof t=="function"||typeof t=="object"&&typeof t.length=="number")return str("",{"":e});throw new Error("JSON.stringify")}),typeof JSON.parse!="function"&&(JSON.parse=function(text,reviver){function walk(e,t){var n,r,i=e[t];if(i&&typeof i=="object")for(n in i)Object.prototype.hasOwnProperty.call(i,n)&&(r=walk(i,n),r!==undefined?i[n]=r:delete i[n]);return reviver.call(e,t,i)}var j;text=String(text),cx.lastIndex=0,cx.test(text)&&(text=text.replace(cx,function(e){return"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4)}));if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))return j=eval("("+text+")"),typeof reviver=="function"?walk({"":j},""):j;throw new SyntaxError("JSON.parse")})}(),function(e,t){"use strict";var n=e.History=e.History||{},r=e.jQuery;if(typeof n.Adapter!="undefined")throw new Error("History.js Adapter has already been loaded...");n.Adapter={bind:function(e,t,n){r(e).bind(t,n)},trigger:function(e,t,n){r(e).trigger(t,n)},extractEventData:function(e,n,r){var i=n&&n.originalEvent&&n.originalEvent[e]||r&&r[e]||t;return i},onDomLoad:function(e){r(e)}},typeof n.init!="undefined"&&n.init()}(window),function(e,t){"use strict";var n=e.document,r=e.setTimeout||r,i=e.clearTimeout||i,s=e.setInterval||s,o=e.History=e.History||{};if(typeof o.initHtml4!="undefined")throw new Error("History.js HTML4 Support has already been loaded...");o.initHtml4=function(){if(typeof o.initHtml4.initialized!="undefined")return!1;o.initHtml4.initialized=!0,o.enabled=!0,o.savedHashes=[],o.isLastHash=function(e){var t=o.getHashByIndex(),n;return n=e===t,n},o.isHashEqual=function(e,t){return e=encodeURIComponent(e).replace(/%25/g,"%"),t=encodeURIComponent(t).replace(/%25/g,"%"),e===t},o.saveHash=function(e){return o.isLastHash(e)?!1:(o.savedHashes.push(e),!0)},o.getHashByIndex=function(e){var t=null;return typeof e=="undefined"?t=o.savedHashes[o.savedHashes.length-1]:e<0?t=o.savedHashes[o.savedHashes.length+e]:t=o.savedHashes[e],t},o.discardedHashes={},o.discardedStates={},o.discardState=function(e,t,n){var r=o.getHashByState(e),i;return i={discardedState:e,backState:n,forwardState:t},o.discardedStates[r]=i,!0},o.discardHash=function(e,t,n){var r={discardedHash:e,backState:n,forwardState:t};return o.discardedHashes[e]=r,!0},o.discardedState=function(e){var t=o.getHashByState(e),n;return n=o.discardedStates[t]||!1,n},o.discardedHash=function(e){var t=o.discardedHashes[e]||!1;return t},o.recycleState=function(e){var t=o.getHashByState(e);return o.discardedState(e)&&delete o.discardedStates[t],!0},o.emulated.hashChange&&(o.hashChangeInit=function(){o.checkerFunction=null;var t="",r,i,u,a,f=Boolean(o.getHash());return o.isInternetExplorer()?(r="historyjs-iframe",i=n.createElement("iframe"),i.setAttribute("id",r),i.setAttribute("src","#"),i.style.display="none",n.body.appendChild(i),i.contentWindow.document.open(),i.contentWindow.document.close(),u="",a=!1,o.checkerFunction=function(){if(a)return!1;a=!0;var n=o.getHash(),r=o.getHash(i.contentWindow.document);return n!==t?(t=n,r!==n&&(u=r=n,i.contentWindow.document.open(),i.contentWindow.document.close(),i.contentWindow.document.location.hash=o.escapeHash(n)),o.Adapter.trigger(e,"hashchange")):r!==u&&(u=r,f&&r===""?o.back():o.setHash(r,!1)),a=!1,!0}):o.checkerFunction=function(){var n=o.getHash()||"";return n!==t&&(t=n,o.Adapter.trigger(e,"hashchange")),!0},o.intervalList.push(s(o.checkerFunction,o.options.hashChangeInterval)),!0},o.Adapter.onDomLoad(o.hashChangeInit)),o.emulated.pushState&&(o.onHashChange=function(t){var n=t&&t.newURL||o.getLocationHref(),r=o.getHashByUrl(n),i=null,s=null,u=null,a;return o.isLastHash(r)?(o.busy(!1),!1):(o.doubleCheckComplete(),o.saveHash(r),r&&o.isTraditionalAnchor(r)?(o.Adapter.trigger(e,"anchorchange"),o.busy(!1),!1):(i=o.extractState(o.getFullUrl(r||o.getLocationHref()),!0),o.isLastSavedState(i)?(o.busy(!1),!1):(s=o.getHashByState(i),a=o.discardedState(i),a?(o.getHashByIndex(-2)===o.getHashByState(a.forwardState)?o.back(!1):o.forward(!1),!1):(o.pushState(i.data,i.title,encodeURI(i.url),!1),!0))))},o.Adapter.bind(e,"hashchange",o.onHashChange),o.pushState=function(t,n,r,i){r=encodeURI(r).replace(/%25/g,"%");if(o.getHashByUrl(r))throw new Error("History.js does not support states with fragment-identifiers (hashes/anchors).");if(i!==!1&&o.busy())return o.pushQueue({scope:o,callback:o.pushState,args:arguments,queue:i}),!1;o.busy(!0);var s=o.createStateObject(t,n,r),u=o.getHashByState(s),a=o.getState(!1),f=o.getHashByState(a),l=o.getHash(),c=o.expectedStateId==s.id;return o.storeState(s),o.expectedStateId=s.id,o.recycleState(s),o.setTitle(s),u===f?(o.busy(!1),!1):(o.saveState(s),c||o.Adapter.trigger(e,"statechange"),!o.isHashEqual(u,l)&&!o.isHashEqual(u,o.getShortUrl(o.getLocationHref()))&&o.setHash(u,!1),o.busy(!1),!0)},o.replaceState=function(t,n,r,i){r=encodeURI(r).replace(/%25/g,"%");if(o.getHashByUrl(r))throw new Error("History.js does not support states with fragment-identifiers (hashes/anchors).");if(i!==!1&&o.busy())return o.pushQueue({scope:o,callback:o.replaceState,args:arguments,queue:i}),!1;o.busy(!0);var s=o.createStateObject(t,n,r),u=o.getHashByState(s),a=o.getState(!1),f=o.getHashByState(a),l=o.getStateByIndex(-2);return o.discardState(a,s,l),u===f?(o.storeState(s),o.expectedStateId=s.id,o.recycleState(s),o.setTitle(s),o.saveState(s),o.Adapter.trigger(e,"statechange"),o.busy(!1)):o.pushState(s.data,s.title,s.url,!1),!0}),o.emulated.pushState&&o.getHash()&&!o.emulated.hashChange&&o.Adapter.onDomLoad(function(){o.Adapter.trigger(e,"hashchange")})},typeof o.init!="undefined"&&o.init()}(window),function(e,t){"use strict";var n=e.console||t,r=e.document,i=e.navigator,s=!1,o=e.setTimeout,u=e.clearTimeout,a=e.setInterval,f=e.clearInterval,l=e.JSON,c=e.alert,h=e.History=e.History||{},p=e.history;try{s=e.sessionStorage,s.setItem("TEST","1"),s.removeItem("TEST")}catch(d){s=!1}l.stringify=l.stringify||l.encode,l.parse=l.parse||l.decode;if(typeof h.init!="undefined")throw new Error("History.js Core has already been loaded...");h.init=function(e){return typeof h.Adapter=="undefined"?!1:(typeof h.initCore!="undefined"&&h.initCore(),typeof h.initHtml4!="undefined"&&h.initHtml4(),!0)},h.initCore=function(d){if(typeof h.initCore.initialized!="undefined")return!1;h.initCore.initialized=!0,h.options=h.options||{},h.options.hashChangeInterval=h.options.hashChangeInterval||100,h.options.safariPollInterval=h.options.safariPollInterval||500,h.options.doubleCheckInterval=h.options.doubleCheckInterval||500,h.options.disableSuid=h.options.disableSuid||!1,h.options.storeInterval=h.options.storeInterval||1e3,h.options.busyDelay=h.options.busyDelay||250,h.options.debug=h.options.debug||!1,h.options.initialTitle=h.options.initialTitle||r.title,h.options.html4Mode=h.options.html4Mode||!1,h.options.delayInit=h.options.delayInit||!1,h.intervalList=[],h.clearAllIntervals=function(){var e,t=h.intervalList;if(typeof t!="undefined"&&t!==null){for(e=0;e<t.length;e++)f(t[e]);h.intervalList=null}},h.debug=function(){(h.options.debug||!1)&&h.log.apply(h,arguments)},h.log=function(){var e=typeof n!="undefined"&&typeof n.log!="undefined"&&typeof n.log.apply!="undefined",t=r.getElementById("log"),i,s,o,u,a;e?(u=Array.prototype.slice.call(arguments),i=u.shift(),typeof n.debug!="undefined"?n.debug.apply(n,[i,u]):n.log.apply(n,[i,u])):i="\n"+arguments[0]+"\n";for(s=1,o=arguments.length;s<o;++s){a=arguments[s];if(typeof a=="object"&&typeof l!="undefined")try{a=l.stringify(a)}catch(f){}i+="\n"+a+"\n"}return t?(t.value+=i+"\n-----\n",t.scrollTop=t.scrollHeight-t.clientHeight):e||c(i),!0},h.getInternetExplorerMajorVersion=function(){var e=h.getInternetExplorerMajorVersion.cached=typeof h.getInternetExplorerMajorVersion.cached!="undefined"?h.getInternetExplorerMajorVersion.cached:function(){var e=3,t=r.createElement("div"),n=t.getElementsByTagName("i");while((t.innerHTML="<!--[if gt IE "+ ++e+"]><i></i><![endif]-->")&&n[0]);return e>4?e:!1}();return e},h.isInternetExplorer=function(){var e=h.isInternetExplorer.cached=typeof h.isInternetExplorer.cached!="undefined"?h.isInternetExplorer.cached:Boolean(h.getInternetExplorerMajorVersion());return e},h.options.html4Mode?h.emulated={pushState:!0,hashChange:!0}:h.emulated={pushState:!Boolean(e.history&&e.history.pushState&&e.history.replaceState&&!/ Mobile\/([1-7][a-z]|(8([abcde]|f(1[0-8]))))/i.test(i.userAgent)&&!/AppleWebKit\/5([0-2]|3[0-2])/i.test(i.userAgent)),hashChange:Boolean(!("onhashchange"in e||"onhashchange"in r)||h.isInternetExplorer()&&h.getInternetExplorerMajorVersion()<8)},h.enabled=!h.emulated.pushState,h.bugs={setHash:Boolean(!h.emulated.pushState&&i.vendor==="Apple Computer, Inc."&&/AppleWebKit\/5([0-2]|3[0-3])/.test(i.userAgent)),safariPoll:Boolean(!h.emulated.pushState&&i.vendor==="Apple Computer, Inc."&&/AppleWebKit\/5([0-2]|3[0-3])/.test(i.userAgent)),ieDoubleCheck:Boolean(h.isInternetExplorer()&&h.getInternetExplorerMajorVersion()<8),hashEscape:Boolean(h.isInternetExplorer()&&h.getInternetExplorerMajorVersion()<7)},h.isEmptyObject=function(e){for(var t in e)if(e.hasOwnProperty(t))return!1;return!0},h.cloneObject=function(e){var t,n;return e?(t=l.stringify(e),n=l.parse(t)):n={},n},h.getRootUrl=function(){var e=r.location.protocol+"//"+(r.location.hostname||r.location.host);if(r.location.port||!1)e+=":"+r.location.port;return e+="/",e},h.getBaseHref=function(){var e=r.getElementsByTagName("base"),t=null,n="";return e.length===1&&(t=e[0],n=t.href.replace(/[^\/]+$/,"")),n=n.replace(/\/+$/,""),n&&(n+="/"),n},h.getBaseUrl=function(){var e=h.getBaseHref()||h.getBasePageUrl()||h.getRootUrl();return e},h.getPageUrl=function(){var e=h.getState(!1,!1),t=(e||{}).url||h.getLocationHref(),n;return n=t.replace(/\/+$/,"").replace(/[^\/]+$/,function(e,t,n){return/\./.test(e)?e:e+"/"}),n},h.getBasePageUrl=function(){var e=h.getLocationHref().replace(/[#\?].*/,"").replace(/[^\/]+$/,function(e,t,n){return/[^\/]$/.test(e)?"":e}).replace(/\/+$/,"")+"/";return e},h.getFullUrl=function(e,t){var n=e,r=e.substring(0,1);return t=typeof t=="undefined"?!0:t,/[a-z]+\:\/\//.test(e)||(r==="/"?n=h.getRootUrl()+e.replace(/^\/+/,""):r==="#"?n=h.getPageUrl().replace(/#.*/,"")+e:r==="?"?n=h.getPageUrl().replace(/[\?#].*/,"")+e:t?n=h.getBaseUrl()+e.replace(/^(\.\/)+/,""):n=h.getBasePageUrl()+e.replace(/^(\.\/)+/,"")),n.replace(/\#$/,"")},h.getShortUrl=function(e){var t=e,n=h.getBaseUrl(),r=h.getRootUrl();return h.emulated.pushState&&(t=t.replace(n,"")),t=t.replace(r,"/"),h.isTraditionalAnchor(t)&&(t="./"+t),t=t.replace(/^(\.\/)+/g,"./").replace(/\#$/,""),t},h.getLocationHref=function(e){return e=e||r,e.URL===e.location.href?e.location.href:e.location.href===decodeURIComponent(e.URL)?e.URL:e.location.hash&&decodeURIComponent(e.location.href.replace(/^[^#]+/,""))===e.location.hash?e.location.href:e.URL.indexOf("#")==-1&&e.location.href.indexOf("#")!=-1?e.location.href:e.URL||e.location.href},h.store={},h.idToState=h.idToState||{},h.stateToId=h.stateToId||{},h.urlToId=h.urlToId||{},h.storedStates=h.storedStates||[],h.savedStates=h.savedStates||[],h.normalizeStore=function(){h.store.idToState=h.store.idToState||{},h.store.urlToId=h.store.urlToId||{},h.store.stateToId=h.store.stateToId||{}},h.getState=function(e,t){typeof e=="undefined"&&(e=!0),typeof t=="undefined"&&(t=!0);var n=h.getLastSavedState();return!n&&t&&(n=h.createStateObject()),e&&(n=h.cloneObject(n),n.url=n.cleanUrl||n.url),n},h.getIdByState=function(e){var t=h.extractId(e.url),n;if(!t){n=h.getStateString(e);if(typeof h.stateToId[n]!="undefined")t=h.stateToId[n];else if(typeof h.store.stateToId[n]!="undefined")t=h.store.stateToId[n];else{for(;;){t=(new Date).getTime()+String(Math.random()).replace(/\D/g,"");if(typeof h.idToState[t]=="undefined"&&typeof h.store.idToState[t]=="undefined")break}h.stateToId[n]=t,h.idToState[t]=e}}return t},h.normalizeState=function(e){var t,n;if(!e||typeof e!="object")e={};if(typeof e.normalized!="undefined")return e;if(!e.data||typeof e.data!="object")e.data={};return t={},t.normalized=!0,t.title=e.title||"",t.url=h.getFullUrl(e.url?e.url:h.getLocationHref()),t.hash=h.getShortUrl(t.url),t.data=h.cloneObject(e.data),t.id=h.getIdByState(t),t.cleanUrl=t.url.replace(/\??\&_suid.*/,""),t.url=t.cleanUrl,n=!h.isEmptyObject(t.data),(t.title||n)&&h.options.disableSuid!==!0&&(t.hash=h.getShortUrl(t.url).replace(/\??\&_suid.*/,""),/\?/.test(t.hash)||(t.hash+="?"),t.hash+="&_suid="+t.id),t.hashedUrl=h.getFullUrl(t.hash),(h.emulated.pushState||h.bugs.safariPoll)&&h.hasUrlDuplicate(t)&&(t.url=t.hashedUrl),t},h.createStateObject=function(e,t,n){var r={data:e,title:t,url:n};return r=h.normalizeState(r),r},h.getStateById=function(e){e=String(e);var n=h.idToState[e]||h.store.idToState[e]||t;return n},h.getStateString=function(e){var t,n,r;return t=h.normalizeState(e),n={data:t.data,title:e.title,url:e.url},r=l.stringify(n),r},h.getStateId=function(e){var t,n;return t=h.normalizeState(e),n=t.id,n},h.getHashByState=function(e){var t,n;return t=h.normalizeState(e),n=t.hash,n},h.extractId=function(e){var t,n,r,i;return e.indexOf("#")!=-1?i=e.split("#")[0]:i=e,n=/(.*)\&_suid=([0-9]+)$/.exec(i),r=n?n[1]||e:e,t=n?String(n[2]||""):"",t||!1},h.isTraditionalAnchor=function(e){var t=!/[\/\?\.]/.test(e);return t},h.extractState=function(e,t){var n=null,r,i;return t=t||!1,r=h.extractId(e),r&&(n=h.getStateById(r)),n||(i=h.getFullUrl(e),r=h.getIdByUrl(i)||!1,r&&(n=h.getStateById(r)),!n&&t&&!h.isTraditionalAnchor(e)&&(n=h.createStateObject(null,null,i))),n},h.getIdByUrl=function(e){var n=h.urlToId[e]||h.store.urlToId[e]||t;return n},h.getLastSavedState=function(){return h.savedStates[h.savedStates.length-1]||t},h.getLastStoredState=function(){return h.storedStates[h.storedStates.length-1]||t},h.hasUrlDuplicate=function(e){var t=!1,n;return n=h.extractState(e.url),t=n&&n.id!==e.id,t},h.storeState=function(e){return h.urlToId[e.url]=e.id,h.storedStates.push(h.cloneObject(e)),e},h.isLastSavedState=function(e){var t=!1,n,r,i;return h.savedStates.length&&(n=e.id,r=h.getLastSavedState(),i=r.id,t=n===i),t},h.saveState=function(e){return h.isLastSavedState(e)?!1:(h.savedStates.push(h.cloneObject(e)),!0)},h.getStateByIndex=function(e){var t=null;return typeof e=="undefined"?t=h.savedStates[h.savedStates.length-1]:e<0?t=h.savedStates[h.savedStates.length+e]:t=h.savedStates[e],t},h.getCurrentIndex=function(){var e=null;return h.savedStates.length<1?e=0:e=h.savedStates.length-1,e},h.getHash=function(e){var t=h.getLocationHref(e),n;return n=h.getHashByUrl(t),n},h.unescapeHash=function(e){var t=h.normalizeHash(e);return t=decodeURIComponent(t),t},h.normalizeHash=function(e){var t=e.replace(/[^#]*#/,"").replace(/#.*/,"");return t},h.setHash=function(e,t){var n,i;return t!==!1&&h.busy()?(h.pushQueue({scope:h,callback:h.setHash,args:arguments,queue:t}),!1):(h.busy(!0),n=h.extractState(e,!0),n&&!h.emulated.pushState?h.pushState(n.data,n.title,n.url,!1):h.getHash()!==e&&(h.bugs.setHash?(i=h.getPageUrl(),h.pushState(null,null,i+"#"+e,!1)):r.location.hash=e),h)},h.escapeHash=function(t){var n=h.normalizeHash(t);return n=e.encodeURIComponent(n),h.bugs.hashEscape||(n=n.replace(/\%21/g,"!").replace(/\%26/g,"&").replace(/\%3D/g,"=").replace(/\%3F/g,"?")),n},h.getHashByUrl=function(e){var t=String(e).replace(/([^#]*)#?([^#]*)#?(.*)/,"$2");return t=h.unescapeHash(t),t},h.setTitle=function(e){var t=e.title,n;t||(n=h.getStateByIndex(0),n&&n.url===e.url&&(t=n.title||h.options.initialTitle));try{r.getElementsByTagName("title")[0].innerHTML=t.replace("<","&lt;").replace(">","&gt;").replace(" & "," &amp; ")}catch(i){}return r.title=t,h},h.queues=[],h.busy=function(e){typeof e!="undefined"?h.busy.flag=e:typeof h.busy.flag=="undefined"&&(h.busy.flag=!1);if(!h.busy.flag){u(h.busy.timeout);var t=function(){var e,n,r;if(h.busy.flag)return;for(e=h.queues.length-1;e>=0;--e){n=h.queues[e];if(n.length===0)continue;r=n.shift(),h.fireQueueItem(r),h.busy.timeout=o(t,h.options.busyDelay)}};h.busy.timeout=o(t,h.options.busyDelay)}return h.busy.flag},h.busy.flag=!1,h.fireQueueItem=function(e){return e.callback.apply(e.scope||h,e.args||[])},h.pushQueue=function(e){return h.queues[e.queue||0]=h.queues[e.queue||0]||[],h.queues[e.queue||0].push(e),h},h.queue=function(e,t){return typeof e=="function"&&(e={callback:e}),typeof t!="undefined"&&(e.queue=t),h.busy()?h.pushQueue(e):h.fireQueueItem(e),h},h.clearQueue=function(){return h.busy.flag=!1,h.queues=[],h},h.stateChanged=!1,h.doubleChecker=!1,h.doubleCheckComplete=function(){return h.stateChanged=!0,h.doubleCheckClear(),h},h.doubleCheckClear=function(){return h.doubleChecker&&(u(h.doubleChecker),h.doubleChecker=!1),h},h.doubleCheck=function(e){return h.stateChanged=!1,h.doubleCheckClear(),h.bugs.ieDoubleCheck&&(h.doubleChecker=o(function(){return h.doubleCheckClear(),h.stateChanged||e(),!0},h.options.doubleCheckInterval)),h},h.safariStatePoll=function(){var t=h.extractState(h.getLocationHref()),n;if(!h.isLastSavedState(t))return n=t,n||(n=h.createStateObject()),h.Adapter.trigger(e,"popstate"),h;return},h.back=function(e){return e!==!1&&h.busy()?(h.pushQueue({scope:h,callback:h.back,args:arguments,queue:e}),!1):(h.busy(!0),h.doubleCheck(function(){h.back(!1)}),p.go(-1),!0)},h.forward=function(e){return e!==!1&&h.busy()?(h.pushQueue({scope:h,callback:h.forward,args:arguments,queue:e}),!1):(h.busy(!0),h.doubleCheck(function(){h.forward(!1)}),p.go(1),!0)},h.go=function(e,t){var n;if(e>0)for(n=1;n<=e;++n)h.forward(t);else{if(!(e<0))throw new Error("History.go: History.go requires a positive or negative integer passed.");for(n=-1;n>=e;--n)h.back(t)}return h};if(h.emulated.pushState){var v=function(){};h.pushState=h.pushState||v,h.replaceState=h.replaceState||v}else h.onPopState=function(t,n){var r=!1,i=!1,s,o;return h.doubleCheckComplete(),s=h.getHash(),s?(o=h.extractState(s||h.getLocationHref(),!0),o?h.replaceState(o.data,o.title,o.url,!1):(h.Adapter.trigger(e,"anchorchange"),h.busy(!1)),h.expectedStateId=!1,!1):(r=h.Adapter.extractEventData("state",t,n)||!1,r?i=h.getStateById(r):h.expectedStateId?i=h.getStateById(h.expectedStateId):i=h.extractState(h.getLocationHref()),i||(i=h.createStateObject(null,null,h.getLocationHref())),h.expectedStateId=!1,h.isLastSavedState(i)?(h.busy(!1),!1):(h.storeState(i),h.saveState(i),h.setTitle(i),h.Adapter.trigger(e,"statechange"),h.busy(!1),!0))},h.Adapter.bind(e,"popstate",h.onPopState),h.pushState=function(t,n,r,i){if(h.getHashByUrl(r)&&h.emulated.pushState)throw new Error("History.js does not support states with fragement-identifiers (hashes/anchors).");if(i!==!1&&h.busy())return h.pushQueue({scope:h,callback:h.pushState,args:arguments,queue:i}),!1;h.busy(!0);var s=h.createStateObject(t,n,r);return h.isLastSavedState(s)?h.busy(!1):(h.storeState(s),h.expectedStateId=s.id,p.pushState(s.id,s.title,s.url),h.Adapter.trigger(e,"popstate")),!0},h.replaceState=function(t,n,r,i){if(h.getHashByUrl(r)&&h.emulated.pushState)throw new Error("History.js does not support states with fragement-identifiers (hashes/anchors).");if(i!==!1&&h.busy())return h.pushQueue({scope:h,callback:h.replaceState,args:arguments,queue:i}),!1;h.busy(!0);var s=h.createStateObject(t,n,r);return h.isLastSavedState(s)?h.busy(!1):(h.storeState(s),h.expectedStateId=s.id,p.replaceState(s.id,s.title,s.url),h.Adapter.trigger(e,"popstate")),!0};if(s){try{h.store=l.parse(s.getItem("History.store"))||{}}catch(m){h.store={}}h.normalizeStore()}else h.store={},h.normalizeStore();h.Adapter.bind(e,"unload",h.clearAllIntervals),h.saveState(h.storeState(h.extractState(h.getLocationHref(),!0))),s&&(h.onUnload=function(){var e,t,n;try{e=l.parse(s.getItem("History.store"))||{}}catch(r){e={}}e.idToState=e.idToState||{},e.urlToId=e.urlToId||{},e.stateToId=e.stateToId||{};for(t in h.idToState){if(!h.idToState.hasOwnProperty(t))continue;e.idToState[t]=h.idToState[t]}for(t in h.urlToId){if(!h.urlToId.hasOwnProperty(t))continue;e.urlToId[t]=h.urlToId[t]}for(t in h.stateToId){if(!h.stateToId.hasOwnProperty(t))continue;e.stateToId[t]=h.stateToId[t]}h.store=e,h.normalizeStore(),n=l.stringify(e);try{s.setItem("History.store",n)}catch(i){if(i.code!==DOMException.QUOTA_EXCEEDED_ERR)throw i;s.length&&(s.removeItem("History.store"),s.setItem("History.store",n))}},h.intervalList.push(a(h.onUnload,h.options.storeInterval)),h.Adapter.bind(e,"beforeunload",h.onUnload),h.Adapter.bind(e,"unload",h.onUnload));if(!h.emulated.pushState){h.bugs.safariPoll&&h.intervalList.push(a(h.safariStatePoll,h.options.safariPollInterval));if(i.vendor==="Apple Computer, Inc."||(i.appCodeName||"")==="Mozilla")h.Adapter.bind(e,"hashchange",function(){h.Adapter.trigger(e,"popstate")}),h.getHash()&&h.Adapter.onDomLoad(function(){h.Adapter.trigger(e,"hashchange")})}},(!h.options||!h.options.delayInit)&&h.init()}(window);

/*
 * jQuery Iframe Transport Plugin 1.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint unparam: true, nomen: true */
/*global define, window, document */

(function(factory){if(typeof define==="function"&&define.amd){define(["jquery"],factory)}else{factory(window.jQuery)}}(function($){var counter=0;$.ajaxTransport("iframe",function(options){if(options.async){var form,iframe,addParamChar;return{send:function(_,completeCallback){form=$('<form style="display:none;"></form>');form.attr("accept-charset",options.formAcceptCharset);addParamChar=/\?/.test(options.url)?"&":"?";if(options.type==="DELETE"){options.url=options.url+addParamChar+"_method=DELETE";options.type="POST"}else{if(options.type==="PUT"){options.url=options.url+addParamChar+"_method=PUT";options.type="POST"}else{if(options.type==="PATCH"){options.url=options.url+addParamChar+"_method=PATCH";options.type="POST"}}}counter+=1;iframe=$('<iframe src="javascript:false;" name="iframe-transport-'+counter+'"></iframe>').bind("load",function(){var fileInputClones,paramNames=$.isArray(options.paramName)?options.paramName:[options.paramName];iframe.unbind("load").bind("load",function(){var response;try{response=iframe.contents();if(!response.length||!response[0].firstChild){throw new Error()}}catch(e){response=undefined}completeCallback(200,"success",{iframe:response});$('<iframe src="javascript:false;"></iframe>').appendTo(form);window.setTimeout(function(){form.remove()},0)});form.prop("target",iframe.prop("name")).prop("action",options.url).prop("method",options.type);if(options.formData){$.each(options.formData,function(index,field){$('<input type="hidden"/>').prop("name",field.name).val(field.value).appendTo(form)})}if(options.fileInput&&options.fileInput.length&&options.type==="POST"){fileInputClones=options.fileInput.clone();options.fileInput.after(function(index){return fileInputClones[index]});if(options.paramName){options.fileInput.each(function(index){$(this).prop("name",paramNames[index]||options.paramName)})}form.append(options.fileInput).prop("enctype","multipart/form-data").prop("encoding","multipart/form-data")}form.submit();if(fileInputClones&&fileInputClones.length){options.fileInput.each(function(index,input){var clone=$(fileInputClones[index]);$(input).prop("name",clone.prop("name"));clone.replaceWith(input)})}});form.append(iframe).appendTo(document.body)},abort:function(){if(iframe){iframe.unbind("load").prop("src","javascript".concat(":false;"))}if(form){form.remove()}}}}});$.ajaxSetup({converters:{"iframe text":function(iframe){return iframe&&$(iframe[0].body).text()},"iframe json":function(iframe){return iframe&&$.parseJSON($(iframe[0].body).text())},"iframe html":function(iframe){return iframe&&$(iframe[0].body).html()},"iframe xml":function(iframe){var xmlDoc=iframe&&iframe[0];return xmlDoc&&$.isXMLDoc(xmlDoc)?xmlDoc:$.parseXML((xmlDoc.XMLDocument&&xmlDoc.XMLDocument.xml)||$(xmlDoc.body).html())},"iframe script":function(iframe){return iframe&&$.globalEval($(iframe[0].body).text())}}})}));

/**
 * Copyright (c) 2011-2013 Felix Gnass
 * Licensed under the MIT license
 */
//fgnass.github.com/spin.js#v1.3.2
(function(root,factory){if(typeof exports=="object"){module.exports=factory()}else{if(typeof define=="function"&&define.amd){define(factory)}else{root.Spinner=factory()}}}(this,function(){var prefixes=["webkit","Moz","ms","O"],animations={},useCssAnimations;function createEl(tag,prop){var el=document.createElement(tag||"div"),n;for(n in prop){el[n]=prop[n]}return el}function ins(parent){for(var i=1,n=arguments.length;i<n;i++){parent.appendChild(arguments[i])}return parent}var sheet=(function(){var el=createEl("style",{type:"text/css"});ins(document.getElementsByTagName("head")[0],el);return el.sheet||el.styleSheet}());function addAnimation(alpha,trail,i,lines){var name=["opacity",trail,~~(alpha*100),i,lines].join("-"),start=0.01+i/lines*100,z=Math.max(1-(1-alpha)/trail*(100-start),alpha),prefix=useCssAnimations.substring(0,useCssAnimations.indexOf("Animation")).toLowerCase(),pre=prefix&&"-"+prefix+"-"||"";if(!animations[name]){sheet.insertRule("@"+pre+"keyframes "+name+"{0%{opacity:"+z+"}"+start+"%{opacity:"+alpha+"}"+(start+0.01)+"%{opacity:1}"+(start+trail)%100+"%{opacity:"+alpha+"}100%{opacity:"+z+"}}",sheet.cssRules.length);animations[name]=1}return name}function vendor(el,prop){var s=el.style,pp,i;prop=prop.charAt(0).toUpperCase()+prop.slice(1);for(i=0;i<prefixes.length;i++){pp=prefixes[i]+prop;if(s[pp]!==undefined){return pp}}if(s[prop]!==undefined){return prop}}function css(el,prop){for(var n in prop){el.style[vendor(el,n)||n]=prop[n]}return el}function merge(obj){for(var i=1;i<arguments.length;i++){var def=arguments[i];for(var n in def){if(obj[n]===undefined){obj[n]=def[n]}}}return obj}function pos(el){var o={x:el.offsetLeft,y:el.offsetTop};while((el=el.offsetParent)){o.x+=el.offsetLeft,o.y+=el.offsetTop}return o}function getColor(color,idx){return typeof color=="string"?color:color[idx%color.length]}var defaults={lines:12,length:7,width:5,radius:10,rotate:0,corners:1,color:"#000",direction:1,speed:1,trail:100,opacity:1/4,fps:20,zIndex:"auto",className:"spinner",top:"auto",left:"auto",position:"relative"};function Spinner(o){if(typeof this=="undefined"){return new Spinner(o)}this.opts=merge(o||{},Spinner.defaults,defaults)}Spinner.defaults={};merge(Spinner.prototype,{spin:function(target){this.stop();var self=this,o=self.opts,el=self.el=css(createEl(0,{className:o.className}),{position:o.position,width:0,zIndex:o.zIndex}),mid=o.radius+o.length+o.width,ep,tp;if(target){target.insertBefore(el,target.firstChild||null);tp=pos(target);ep=pos(el);css(el,{left:(o.left=="auto"?tp.x-ep.x+(target.offsetWidth>>1):parseInt(o.left,10)+mid)+"px",top:(o.top=="auto"?tp.y-ep.y+(target.offsetHeight>>1):parseInt(o.top,10)+mid)+"px"})}el.setAttribute("role","progressbar");self.lines(el,self.opts);if(!useCssAnimations){var i=0,start=(o.lines-1)*(1-o.direction)/2,alpha,fps=o.fps,f=fps/o.speed,ostep=(1-o.opacity)/(f*o.trail/100),astep=f/o.lines;(function anim(){i++;for(var j=0;j<o.lines;j++){alpha=Math.max(1-(i+(o.lines-j)*astep)%f*ostep,o.opacity);self.opacity(el,j*o.direction+start,alpha,o)}self.timeout=self.el&&setTimeout(anim,~~(1000/fps))})()}return self},stop:function(){var el=this.el;if(el){clearTimeout(this.timeout);if(el.parentNode){el.parentNode.removeChild(el)}this.el=undefined}return this},lines:function(el,o){var i=0,start=(o.lines-1)*(1-o.direction)/2,seg;function fill(color,shadow){return css(createEl(),{position:"absolute",width:(o.length+o.width)+"px",height:o.width+"px",background:color,boxShadow:shadow,transformOrigin:"left",transform:"rotate("+~~(360/o.lines*i+o.rotate)+"deg) translate("+o.radius+"px,0)",borderRadius:(o.corners*o.width>>1)+"px"})}for(;i<o.lines;i++){seg=css(createEl(),{position:"absolute",top:1+~(o.width/2)+"px",transform:o.hwaccel?"translate3d(0,0,0)":"",opacity:o.opacity,animation:useCssAnimations&&addAnimation(o.opacity,o.trail,start+i*o.direction,o.lines)+" "+1/o.speed+"s linear infinite"});if(o.shadow){ins(seg,css(fill("rgba(0,0,0,.25)","0 0 4px rgba(0,0,0,.5)"),{top:2+"px"}))}ins(el,ins(seg,fill(getColor(o.color,i),"0 0 1px rgba(0,0,0,.1)")))}return el},opacity:function(el,i,val){if(i<el.childNodes.length){el.childNodes[i].style.opacity=val}}});function initVML(){function vml(tag,attr){return createEl("<"+tag+' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">',attr)}sheet.addRule(".spin-vml","behavior:url(#default#VML)");Spinner.prototype.lines=function(el,o){var r=o.length+o.width,s=2*r;function grp(){return css(vml("group",{coordsize:s+" "+s,coordorigin:-r+" "+-r}),{width:s,height:s})}var margin=-(o.width+o.length)*2+"px",g=css(grp(),{position:"absolute",top:margin,left:margin}),i;function seg(i,dx,filter){ins(g,ins(css(grp(),{rotation:360/o.lines*i+"deg",left:~~dx}),ins(css(vml("roundrect",{arcsize:o.corners}),{width:r,height:o.width,left:o.radius,top:-o.width>>1,filter:filter}),vml("fill",{color:getColor(o.color,i),opacity:o.opacity}),vml("stroke",{opacity:0}))))}if(o.shadow){for(i=1;i<=o.lines;i++){seg(i,-2,"progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)")}}for(i=1;i<=o.lines;i++){seg(i)}return ins(el,g)};Spinner.prototype.opacity=function(el,i,val,o){var c=el.firstChild;o=o.shadow&&o.lines||0;if(c&&i+o<c.childNodes.length){c=c.childNodes[i+o];c=c&&c.firstChild;c=c&&c.firstChild;if(c){c.opacity=val}}}}var probe=css(createEl("group"),{behavior:"url(#default#VML)"});if(!vendor(probe,"transform")&&probe.adj){initVML()}else{useCssAnimations=vendor(probe,"animation")}return Spinner}));
(function(e){if(typeof exports=="object"){e(require("jquery"),require("spin"))}else if(typeof define=="function"&&define.amd){define(["jquery","spin"],e)}else{if(!window.Spinner)throw new Error("Spin.js not present");e(window.jQuery,window.Spinner)}})(function(e,t){e.fn.spin=function(n,r){return this.each(function(){var i=e(this),s=i.data();if(s.spinner){s.spinner.stop();delete s.spinner}if(n!==false){n=e.extend({color:r||i.css("color")},e.fn.spin.presets[n]||n);s.spinner=(new t(n)).spin(this)}})};e.fn.spin.presets={tiny:{lines:8,length:2,width:2,radius:3},small:{lines:8,length:4,width:3,radius:5},large:{lines:10,length:8,width:4,radius:8}}});

// http://stackoverflow.com/a/21422049
(function(e){e.fn.hasScrollBar=function(){var e={},t=this.get(0);e.vertical=t.scrollHeight>t.clientHeight?true:false;e.horizontal=t.scrollWidth>t.clientWidth?true:false;return e}})(jQuery);

/**
 * Antiscroll
 * https://github.com/LearnBoost/antiscroll
 */
(function($){$.fn.antiscroll=function(options){return this.each(function(){if($(this).data("antiscroll"))$(this).data("antiscroll").destroy();$(this).data("antiscroll",new $.Antiscroll(this,options))})};$.Antiscroll=Antiscroll;function Antiscroll(el,opts){this.el=$(el);this.options=opts||{};this.x=false!==this.options.x||this.options.forceHorizontal;this.y=false!==this.options.y||this.options.forceVertical;this.autoHide=false!==this.options.autoHide;this.padding=undefined==this.options.padding?2:
this.options.padding;this.inner=this.el.find(".antiscroll-inner");this.inner.css({"width":"+="+(this.y?scrollbarSize():0),"height":"+="+(this.x?scrollbarSize():0)});this.refresh()}Antiscroll.prototype.refresh=function(){var needHScroll=this.inner.get(0).scrollWidth>this.el.width()+(this.y?scrollbarSize():0),needVScroll=this.inner.get(0).scrollHeight>this.el.height()+(this.x?scrollbarSize():0);if(this.x)if(!this.horizontal&&needHScroll)this.horizontal=new Scrollbar.Horizontal(this);else if(this.horizontal&&
!needHScroll){this.horizontal.destroy();this.horizontal=null}else if(this.horizontal)this.horizontal.update();if(this.y)if(!this.vertical&&needVScroll)this.vertical=new Scrollbar.Vertical(this);else if(this.vertical&&!needVScroll){this.vertical.destroy();this.vertical=null}else if(this.vertical)this.vertical.update()};Antiscroll.prototype.destroy=function(){if(this.horizontal){this.horizontal.destroy();this.horizontal=null}if(this.vertical){this.vertical.destroy();this.vertical=null}return this};
Antiscroll.prototype.rebuild=function(){this.destroy();this.inner.attr("style","");Antiscroll.call(this,this.el,this.options);return this};function Scrollbar(pane){this.pane=pane;this.pane.el.append(this.el);this.innerEl=this.pane.inner.get(0);this.dragging=false;this.enter=false;this.shown=false;this.pane.el.mouseenter($.proxy(this,"mouseenter"));this.pane.el.mouseleave($.proxy(this,"mouseleave"));this.el.mousedown($.proxy(this,"mousedown"));this.innerPaneScrollListener=$.proxy(this,"scroll");this.pane.inner.scroll(this.innerPaneScrollListener);
this.innerPaneMouseWheelListener=$.proxy(this,"mousewheel");this.pane.inner.bind("mousewheel",this.innerPaneMouseWheelListener);var initialDisplay=this.pane.options.initialDisplay;if(initialDisplay!==false){this.show();if(this.pane.autoHide)this.hiding=setTimeout($.proxy(this,"hide"),parseInt(initialDisplay,10)||3E3)}}Scrollbar.prototype.destroy=function(){this.el.remove();this.pane.inner.unbind("scroll",this.innerPaneScrollListener);this.pane.inner.unbind("mousewheel",this.innerPaneMouseWheelListener);
return this};Scrollbar.prototype.mouseenter=function(){this.enter=true;this.show()};Scrollbar.prototype.mouseleave=function(){this.enter=false;if(!this.dragging)if(this.pane.autoHide)this.hide()};Scrollbar.prototype.scroll=function(){if(!this.shown){this.show();if(!this.enter&&!this.dragging)if(this.pane.autoHide)this.hiding=setTimeout($.proxy(this,"hide"),1500)}this.update()};Scrollbar.prototype.mousedown=function(ev){ev.preventDefault();this.dragging=true;this.startPageY=ev.pageY-parseInt(this.el.css("top"),
10);this.startPageX=ev.pageX-parseInt(this.el.css("left"),10);this.el[0].ownerDocument.onselectstart=function(){return false};var pane=this.pane,move=$.proxy(this,"mousemove"),self=this;$(this.el[0].ownerDocument).mousemove(move).mouseup(function(){self.dragging=false;this.onselectstart=null;$(this).unbind("mousemove",move);if(!self.enter)self.hide()})};Scrollbar.prototype.show=function(duration){if(!this.shown&&this.update()){this.el.addClass("antiscroll-scrollbar-shown");if(this.hiding){clearTimeout(this.hiding);
this.hiding=null}this.shown=true}};Scrollbar.prototype.hide=function(){if(this.pane.autoHide!==false&&this.shown){this.el.removeClass("antiscroll-scrollbar-shown");this.shown=false}};Scrollbar.Horizontal=function(pane){this.el=$('<div class="antiscroll-scrollbar antiscroll-scrollbar-horizontal">',pane.el);Scrollbar.call(this,pane)};inherits(Scrollbar.Horizontal,Scrollbar);Scrollbar.Horizontal.prototype.update=function(){var paneWidth=this.pane.el.width(),trackWidth=paneWidth-this.pane.padding*2,innerEl=
this.pane.inner.get(0);this.el.css("width",trackWidth*paneWidth/innerEl.scrollWidth).css("left",trackWidth*innerEl.scrollLeft/innerEl.scrollWidth);return paneWidth<innerEl.scrollWidth};Scrollbar.Horizontal.prototype.mousemove=function(ev){var trackWidth=this.pane.el.width()-this.pane.padding*2,pos=ev.pageX-this.startPageX,barWidth=this.el.width(),innerEl=this.pane.inner.get(0);var y=Math.min(Math.max(pos,0),trackWidth-barWidth);innerEl.scrollLeft=(innerEl.scrollWidth-this.pane.el.width())*y/(trackWidth-
barWidth)};Scrollbar.Horizontal.prototype.mousewheel=function(ev,delta,x,y){if(x<0&&0==this.pane.inner.get(0).scrollLeft||x>0&&this.innerEl.scrollLeft+Math.ceil(this.pane.el.width())==this.innerEl.scrollWidth){ev.preventDefault();return false}};Scrollbar.Vertical=function(pane){this.el=$('<div class="antiscroll-scrollbar antiscroll-scrollbar-vertical">',pane.el);Scrollbar.call(this,pane)};inherits(Scrollbar.Vertical,Scrollbar);Scrollbar.Vertical.prototype.update=function(){var paneHeight=this.pane.el.height(),
trackHeight=paneHeight-this.pane.padding*2,innerEl=this.innerEl;var scrollbarHeight=trackHeight*paneHeight/innerEl.scrollHeight;scrollbarHeight=scrollbarHeight<20?20:scrollbarHeight;var topPos=trackHeight*innerEl.scrollTop/innerEl.scrollHeight;if(topPos+scrollbarHeight>trackHeight){var diff=topPos+scrollbarHeight-trackHeight;topPos=topPos-diff-3}this.el.css("height",scrollbarHeight).css("top",topPos);return paneHeight<innerEl.scrollHeight};Scrollbar.Vertical.prototype.mousemove=function(ev){var paneHeight=
this.pane.el.height(),trackHeight=paneHeight-this.pane.padding*2,pos=ev.pageY-this.startPageY,barHeight=this.el.height(),innerEl=this.innerEl;var y=Math.min(Math.max(pos,0),trackHeight-barHeight);innerEl.scrollTop=(innerEl.scrollHeight-paneHeight)*y/(trackHeight-barHeight)};Scrollbar.Vertical.prototype.mousewheel=function(ev,delta,x,y){if(y>0&&0==this.innerEl.scrollTop||y<0&&this.innerEl.scrollTop+Math.ceil(this.pane.el.height())==this.innerEl.scrollHeight){ev.preventDefault();return false}};function inherits(ctorA,
ctorB){function f(){}f.prototype=ctorB.prototype;ctorA.prototype=new f}var size;function scrollbarSize(){if(size===undefined){var div=$('<div class="antiscroll-inner" style="width:50px;height:50px;overflow-y:scroll;'+'position:absolute;top:-200px;left:-200px;"><div style="height:100px;width:100%">'+"</div>");$("body").append(div);var w1=$(div).innerWidth();var w2=$("div",div).innerWidth();$(div).remove();size=w1-w2}return size}})(jQuery);


/**
 * jQuery Mousewheel
 * ! Copyright (c) 2013 Brandon Aaron (http://brandonaaron.net)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Thanks to: http://adomas.org/javascript-mouse-wheel/ for some pointers.
 * Thanks to: Mathias Bank(http://www.mathias-bank.de) for a scope bug fix.
 * Thanks to: Seamus Leahy for adding deltaX and deltaY
 *
 * Version: 3.1.3
 *
 * Requires: 1.2.2+
 */
(function(factory){if(typeof define==="function"&&define.amd)define(["jquery"],factory);else if(typeof exports==="object")module.exports=factory;else factory(jQuery)})(function($){var toFix=["wheel","mousewheel","DOMMouseScroll","MozMousePixelScroll"];var toBind="onwheel"in document||document.documentMode>=9?["wheel"]:["mousewheel","DomMouseScroll","MozMousePixelScroll"];var lowestDelta,lowestDeltaXY;if($.event.fixHooks)for(var i=toFix.length;i;)$.event.fixHooks[toFix[--i]]=$.event.mouseHooks;$.event.special.mousewheel=
{setup:function(){if(this.addEventListener)for(var i=toBind.length;i;)this.addEventListener(toBind[--i],handler,false);else this.onmousewheel=handler},teardown:function(){if(this.removeEventListener)for(var i=toBind.length;i;)this.removeEventListener(toBind[--i],handler,false);else this.onmousewheel=null}};$.fn.extend({mousewheel:function(fn){return fn?this.bind("mousewheel",fn):this.trigger("mousewheel")},unmousewheel:function(fn){return this.unbind("mousewheel",fn)}});function handler(event){var orgEvent=
event||window.event,args=[].slice.call(arguments,1),delta=0,deltaX=0,deltaY=0,absDelta=0,absDeltaXY=0,fn;event=$.event.fix(orgEvent);event.type="mousewheel";if(orgEvent.wheelDelta)delta=orgEvent.wheelDelta;if(orgEvent.detail)delta=orgEvent.detail*-1;if(orgEvent.deltaY){deltaY=orgEvent.deltaY*-1;delta=deltaY}if(orgEvent.deltaX){deltaX=orgEvent.deltaX;delta=deltaX*-1}if(orgEvent.wheelDeltaY!==undefined)deltaY=orgEvent.wheelDeltaY;if(orgEvent.wheelDeltaX!==undefined)deltaX=orgEvent.wheelDeltaX*-1;absDelta=
Math.abs(delta);if(!lowestDelta||absDelta<lowestDelta)lowestDelta=absDelta;absDeltaXY=Math.max(Math.abs(deltaY),Math.abs(deltaX));if(!lowestDeltaXY||absDeltaXY<lowestDeltaXY)lowestDeltaXY=absDeltaXY;fn=delta>0?"floor":"ceil";delta=Math[fn](delta/lowestDelta);deltaX=Math[fn](deltaX/lowestDeltaXY);deltaY=Math[fn](deltaY/lowestDeltaXY);args.unshift(event,delta,deltaX,deltaY);return($.event.dispatch||$.event.handle).apply(this,args)}});

/**
Delayed action
http://stackoverflow.com/a/7150496
**/
(function($) {
    $.fn.delayedAction = function(options)
    {
        var settings = $.extend(
            {},
            {
                delayedAction : function(){},
                cancelledAction: function(){},
                hoverTime: 1000
            },
            options);

        return this.each(function(){
           var $this = $(this);
            $this.hover(function(){
               $this.data('timerId',
                          setTimeout(function(){
                                      $this.data('hover',false);
                                      settings.delayedAction($this);
                                      },settings.hoverTime));
                $this.data('hover',true);
            },
            function(){
                if($this.data('hover')){
                    clearTimeout($this.data('timerId'));
                    settings.cancelledAction($this);
                }
                $this.data('hover',false);
            } );
        });
    }
})(jQuery);

/**
Created: 20060120
Author:  Steve Moitozo <god at zilla dot us> -- geekwisdom.com
License: MIT License (see below)
Copyright (c) 2006 Steve Moitozo <god at zilla dot us>

Slightly modified for Peafowl

*/
function testPassword(e){var t=0,n="weak",r="",i=0;if(e.length<5){t=t+3;r=r+"3 points for length ("+e.length+")\n"}else if(e.length>4&&e.length<8){t=t+6;r=r+"6 points for length ("+e.length+")\n"}else if(e.length>7&&e.length<16){t=t+12;r=r+"12 points for length ("+e.length+")\n"}else if(e.length>15){t=t+18;r=r+"18 point for length ("+e.length+")\n"}if(e.match(/[a-z]/)){t=t+1;r=r+"1 point for at least one lower case char\n"}if(e.match(/[A-Z]/)){t=t+5;r=r+"5 points for at least one upper case char\n"}if(e.match(/\d+/)){t=t+5;r=r+"5 points for at least one number\n"}if(e.match(/(.*[0-9].*[0-9].*[0-9])/)){t=t+5;r=r+"5 points for at least three numbers\n"}if(e.match(/.[!,@,#,$,%,^,&,*,?,_,~]/)){t=t+5;r=r+"5 points for at least one special char\n"}if(e.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)){t=t+5;r=r+"5 points for at least two special chars\n"}if(e.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)){t=t+2;r=r+"2 combo points for upper and lower letters\n"}if(e.match(/([a-zA-Z])/)&&e.match(/([0-9])/)){t=t+2;r=r+"2 combo points for letters and numbers\n"}if(e.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,?,_,~])|([!,@,#,$,%,^,&,*,?,_,~].*[a-zA-Z0-9])/)){t=t+2;r=r+"2 combo points for letters, numbers and special chars\n"}if(e.length==0){t=0}if(t<16){n="very weak"}else if(t>15&&t<25){n="weak"}else if(t>24&&t<35){n="average"}else if(t>34&&t<45){n="strong"}else{n="stronger"}i=Math.round(Math.min(100,100*t/45))/100;return{score:t,ratio:i,percent:i*100+"%",verdict:n,log:r}}

// SparkMD5
(function(factory){if(typeof exports==="object"){module.exports=factory()}else if(typeof define==="function"&&define.amd){define(factory)}else{var glob;try{glob=window}catch(e){glob=self}glob.SparkMD5=factory()}})(function(undefined){"use strict";var add32=function(a,b){return a+b&4294967295},hex_chr=["0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f"];function cmn(q,a,b,x,s,t){a=add32(add32(a,q),add32(x,t));return add32(a<<s|a>>>32-s,b)}function ff(a,b,c,d,x,s,t){return cmn(b&c|~b&d,a,b,x,s,t)}function gg(a,b,c,d,x,s,t){return cmn(b&d|c&~d,a,b,x,s,t)}function hh(a,b,c,d,x,s,t){return cmn(b^c^d,a,b,x,s,t)}function ii(a,b,c,d,x,s,t){return cmn(c^(b|~d),a,b,x,s,t)}function md5cycle(x,k){var a=x[0],b=x[1],c=x[2],d=x[3];a=ff(a,b,c,d,k[0],7,-680876936);d=ff(d,a,b,c,k[1],12,-389564586);c=ff(c,d,a,b,k[2],17,606105819);b=ff(b,c,d,a,k[3],22,-1044525330);a=ff(a,b,c,d,k[4],7,-176418897);d=ff(d,a,b,c,k[5],12,1200080426);c=ff(c,d,a,b,k[6],17,-1473231341);b=ff(b,c,d,a,k[7],22,-45705983);a=ff(a,b,c,d,k[8],7,1770035416);d=ff(d,a,b,c,k[9],12,-1958414417);c=ff(c,d,a,b,k[10],17,-42063);b=ff(b,c,d,a,k[11],22,-1990404162);a=ff(a,b,c,d,k[12],7,1804603682);d=ff(d,a,b,c,k[13],12,-40341101);c=ff(c,d,a,b,k[14],17,-1502002290);b=ff(b,c,d,a,k[15],22,1236535329);a=gg(a,b,c,d,k[1],5,-165796510);d=gg(d,a,b,c,k[6],9,-1069501632);c=gg(c,d,a,b,k[11],14,643717713);b=gg(b,c,d,a,k[0],20,-373897302);a=gg(a,b,c,d,k[5],5,-701558691);d=gg(d,a,b,c,k[10],9,38016083);c=gg(c,d,a,b,k[15],14,-660478335);b=gg(b,c,d,a,k[4],20,-405537848);a=gg(a,b,c,d,k[9],5,568446438);d=gg(d,a,b,c,k[14],9,-1019803690);c=gg(c,d,a,b,k[3],14,-187363961);b=gg(b,c,d,a,k[8],20,1163531501);a=gg(a,b,c,d,k[13],5,-1444681467);d=gg(d,a,b,c,k[2],9,-51403784);c=gg(c,d,a,b,k[7],14,1735328473);b=gg(b,c,d,a,k[12],20,-1926607734);a=hh(a,b,c,d,k[5],4,-378558);d=hh(d,a,b,c,k[8],11,-2022574463);c=hh(c,d,a,b,k[11],16,1839030562);b=hh(b,c,d,a,k[14],23,-35309556);a=hh(a,b,c,d,k[1],4,-1530992060);d=hh(d,a,b,c,k[4],11,1272893353);c=hh(c,d,a,b,k[7],16,-155497632);b=hh(b,c,d,a,k[10],23,-1094730640);a=hh(a,b,c,d,k[13],4,681279174);d=hh(d,a,b,c,k[0],11,-358537222);c=hh(c,d,a,b,k[3],16,-722521979);b=hh(b,c,d,a,k[6],23,76029189);a=hh(a,b,c,d,k[9],4,-640364487);d=hh(d,a,b,c,k[12],11,-421815835);c=hh(c,d,a,b,k[15],16,530742520);b=hh(b,c,d,a,k[2],23,-995338651);a=ii(a,b,c,d,k[0],6,-198630844);d=ii(d,a,b,c,k[7],10,1126891415);c=ii(c,d,a,b,k[14],15,-1416354905);b=ii(b,c,d,a,k[5],21,-57434055);a=ii(a,b,c,d,k[12],6,1700485571);d=ii(d,a,b,c,k[3],10,-1894986606);c=ii(c,d,a,b,k[10],15,-1051523);b=ii(b,c,d,a,k[1],21,-2054922799);a=ii(a,b,c,d,k[8],6,1873313359);d=ii(d,a,b,c,k[15],10,-30611744);c=ii(c,d,a,b,k[6],15,-1560198380);b=ii(b,c,d,a,k[13],21,1309151649);a=ii(a,b,c,d,k[4],6,-145523070);d=ii(d,a,b,c,k[11],10,-1120210379);c=ii(c,d,a,b,k[2],15,718787259);b=ii(b,c,d,a,k[9],21,-343485551);x[0]=add32(a,x[0]);x[1]=add32(b,x[1]);x[2]=add32(c,x[2]);x[3]=add32(d,x[3])}function md5blk(s){var md5blks=[],i;for(i=0;i<64;i+=4){md5blks[i>>2]=s.charCodeAt(i)+(s.charCodeAt(i+1)<<8)+(s.charCodeAt(i+2)<<16)+(s.charCodeAt(i+3)<<24)}return md5blks}function md5blk_array(a){var md5blks=[],i;for(i=0;i<64;i+=4){md5blks[i>>2]=a[i]+(a[i+1]<<8)+(a[i+2]<<16)+(a[i+3]<<24)}return md5blks}function md51(s){var n=s.length,state=[1732584193,-271733879,-1732584194,271733878],i,length,tail,tmp,lo,hi;for(i=64;i<=n;i+=64){md5cycle(state,md5blk(s.substring(i-64,i)))}s=s.substring(i-64);length=s.length;tail=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(i=0;i<length;i+=1){tail[i>>2]|=s.charCodeAt(i)<<(i%4<<3)}tail[i>>2]|=128<<(i%4<<3);if(i>55){md5cycle(state,tail);for(i=0;i<16;i+=1){tail[i]=0}}tmp=n*8;tmp=tmp.toString(16).match(/(.*?)(.{0,8})$/);lo=parseInt(tmp[2],16);hi=parseInt(tmp[1],16)||0;tail[14]=lo;tail[15]=hi;md5cycle(state,tail);return state}function md51_array(a){var n=a.length,state=[1732584193,-271733879,-1732584194,271733878],i,length,tail,tmp,lo,hi;for(i=64;i<=n;i+=64){md5cycle(state,md5blk_array(a.subarray(i-64,i)))}a=i-64<n?a.subarray(i-64):new Uint8Array(0);length=a.length;tail=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(i=0;i<length;i+=1){tail[i>>2]|=a[i]<<(i%4<<3)}tail[i>>2]|=128<<(i%4<<3);if(i>55){md5cycle(state,tail);for(i=0;i<16;i+=1){tail[i]=0}}tmp=n*8;tmp=tmp.toString(16).match(/(.*?)(.{0,8})$/);lo=parseInt(tmp[2],16);hi=parseInt(tmp[1],16)||0;tail[14]=lo;tail[15]=hi;md5cycle(state,tail);return state}function rhex(n){var s="",j;for(j=0;j<4;j+=1){s+=hex_chr[n>>j*8+4&15]+hex_chr[n>>j*8&15]}return s}function hex(x){var i;for(i=0;i<x.length;i+=1){x[i]=rhex(x[i])}return x.join("")}if(hex(md51("hello"))!=="5d41402abc4b2a76b9719d911017c592"){add32=function(x,y){var lsw=(x&65535)+(y&65535),msw=(x>>16)+(y>>16)+(lsw>>16);return msw<<16|lsw&65535}}function toUtf8(str){if(/[\u0080-\uFFFF]/.test(str)){str=unescape(encodeURIComponent(str))}return str}function utf8Str2ArrayBuffer(str,returnUInt8Array){var length=str.length,buff=new ArrayBuffer(length),arr=new Uint8Array(buff),i;for(i=0;i<length;i++){arr[i]=str.charCodeAt(i)}return returnUInt8Array?arr:buff}function arrayBuffer2Utf8Str(buff){return String.fromCharCode.apply(null,new Uint8Array(buff))}function concatenateArrayBuffers(first,second,returnUInt8Array){var result=new Uint8Array(first.byteLength+second.byteLength);result.set(new Uint8Array(first));result.set(new Uint8Array(second),first.byteLength);return returnUInt8Array?result:result.buffer}function SparkMD5(){this.reset()}SparkMD5.prototype.append=function(str){this.appendBinary(toUtf8(str));return this};SparkMD5.prototype.appendBinary=function(contents){this._buff+=contents;this._length+=contents.length;var length=this._buff.length,i;for(i=64;i<=length;i+=64){md5cycle(this._hash,md5blk(this._buff.substring(i-64,i)))}this._buff=this._buff.substring(i-64);return this};SparkMD5.prototype.end=function(raw){var buff=this._buff,length=buff.length,i,tail=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],ret;for(i=0;i<length;i+=1){tail[i>>2]|=buff.charCodeAt(i)<<(i%4<<3)}this._finish(tail,length);ret=!!raw?this._hash:hex(this._hash);this.reset();return ret};SparkMD5.prototype.reset=function(){this._buff="";this._length=0;this._hash=[1732584193,-271733879,-1732584194,271733878];return this};SparkMD5.prototype.getState=function(){return{buff:this._buff,length:this._length,hash:this._hash}};SparkMD5.prototype.setState=function(state){this._buff=state.buff;this._length=state.length;this._hash=state.hash;return this};SparkMD5.prototype.destroy=function(){delete this._hash;delete this._buff;delete this._length};SparkMD5.prototype._finish=function(tail,length){var i=length,tmp,lo,hi;tail[i>>2]|=128<<(i%4<<3);if(i>55){md5cycle(this._hash,tail);for(i=0;i<16;i+=1){tail[i]=0}}tmp=this._length*8;tmp=tmp.toString(16).match(/(.*?)(.{0,8})$/);lo=parseInt(tmp[2],16);hi=parseInt(tmp[1],16)||0;tail[14]=lo;tail[15]=hi;md5cycle(this._hash,tail)};SparkMD5.hash=function(str,raw){return SparkMD5.hashBinary(toUtf8(str),raw)};SparkMD5.hashBinary=function(content,raw){var hash=md51(content);return!!raw?hash:hex(hash)};SparkMD5.ArrayBuffer=function(){this.reset()};SparkMD5.ArrayBuffer.prototype.append=function(arr){var buff=concatenateArrayBuffers(this._buff.buffer,arr,true),length=buff.length,i;this._length+=arr.byteLength;for(i=64;i<=length;i+=64){md5cycle(this._hash,md5blk_array(buff.subarray(i-64,i)))}this._buff=i-64<length?buff.subarray(i-64):new Uint8Array(0);return this};SparkMD5.ArrayBuffer.prototype.end=function(raw){var buff=this._buff,length=buff.length,tail=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],i,ret;for(i=0;i<length;i+=1){tail[i>>2]|=buff[i]<<(i%4<<3)}this._finish(tail,length);ret=!!raw?this._hash:hex(this._hash);this.reset();return ret};SparkMD5.ArrayBuffer.prototype.reset=function(){this._buff=new Uint8Array(0);this._length=0;this._hash=[1732584193,-271733879,-1732584194,271733878];return this};SparkMD5.ArrayBuffer.prototype.getState=function(){var state=SparkMD5.prototype.getState.call(this);state.buff=arrayBuffer2Utf8Str(state.buff);return state};SparkMD5.ArrayBuffer.prototype.setState=function(state){state.buff=utf8Str2ArrayBuffer(state.buff,true);return SparkMD5.prototype.setState.call(this,state)};SparkMD5.ArrayBuffer.prototype.destroy=SparkMD5.prototype.destroy;SparkMD5.ArrayBuffer.prototype._finish=SparkMD5.prototype._finish;SparkMD5.ArrayBuffer.hash=function(arr,raw){var hash=md51_array(new Uint8Array(arr));return!!raw?hash:hex(hash)};return SparkMD5});

/*!
 * clipboard.js v1.5.8
 * https://zenorocha.github.io/clipboard.js
 *
 * Licensed MIT © Zeno Rocha
 */
!function(t){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=t();else if("function"==typeof define&&define.amd)define([],t);else{var e;e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,e.Clipboard=t()}}(function(){var t,e,n;return function t(e,n,r){function o(a,s){if(!n[a]){if(!e[a]){var c="function"==typeof require&&require;if(!s&&c)return c(a,!0);if(i)return i(a,!0);var u=new Error("Cannot find module '"+a+"'");throw u.code="MODULE_NOT_FOUND",u}var l=n[a]={exports:{}};e[a][0].call(l.exports,function(t){var n=e[a][1][t];return o(n?n:t)},l,l.exports,t,e,n,r)}return n[a].exports}for(var i="function"==typeof require&&require,a=0;a<r.length;a++)o(r[a]);return o}({1:[function(t,e,n){var r=t("matches-selector");e.exports=function(t,e,n){for(var o=n?t:t.parentNode;o&&o!==document;){if(r(o,e))return o;o=o.parentNode}}},{"matches-selector":5}],2:[function(t,e,n){function r(t,e,n,r,i){var a=o.apply(this,arguments);return t.addEventListener(n,a,i),{destroy:function(){t.removeEventListener(n,a,i)}}}function o(t,e,n,r){return function(n){n.delegateTarget=i(n.target,e,!0),n.delegateTarget&&r.call(t,n)}}var i=t("closest");e.exports=r},{closest:1}],3:[function(t,e,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.fn=function(t){var e=Object.prototype.toString.call(t);return"[object Function]"===e}},{}],4:[function(t,e,n){function r(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!s.string(e))throw new TypeError("Second argument must be a String");if(!s.fn(n))throw new TypeError("Third argument must be a Function");if(s.node(t))return o(t,e,n);if(s.nodeList(t))return i(t,e,n);if(s.string(t))return a(t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function o(t,e,n){return t.addEventListener(e,n),{destroy:function(){t.removeEventListener(e,n)}}}function i(t,e,n){return Array.prototype.forEach.call(t,function(t){t.addEventListener(e,n)}),{destroy:function(){Array.prototype.forEach.call(t,function(t){t.removeEventListener(e,n)})}}}function a(t,e,n){return c(document.body,t,e,n)}var s=t("./is"),c=t("delegate");e.exports=r},{"./is":3,delegate:2}],5:[function(t,e,n){function r(t,e){if(i)return i.call(t,e);for(var n=t.parentNode.querySelectorAll(e),r=0;r<n.length;++r)if(n[r]==t)return!0;return!1}var o=Element.prototype,i=o.matchesSelector||o.webkitMatchesSelector||o.mozMatchesSelector||o.msMatchesSelector||o.oMatchesSelector;e.exports=r},{}],6:[function(t,e,n){function r(t){var e;if("INPUT"===t.nodeName||"TEXTAREA"===t.nodeName)t.focus(),t.setSelectionRange(0,t.value.length),e=t.value;else{t.hasAttribute("contenteditable")&&t.focus();var n=window.getSelection(),r=document.createRange();r.selectNodeContents(t),n.removeAllRanges(),n.addRange(r),e=n.toString()}return e}e.exports=r},{}],7:[function(t,e,n){function r(){}r.prototype={on:function(t,e,n){var r=this.e||(this.e={});return(r[t]||(r[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){function r(){o.off(t,r),e.apply(n,arguments)}var o=this;return r._=e,this.on(t,r,n)},emit:function(t){var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),r=0,o=n.length;for(r;o>r;r++)n[r].fn.apply(n[r].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),r=n[t],o=[];if(r&&e)for(var i=0,a=r.length;a>i;i++)r[i].fn!==e&&r[i].fn._!==e&&o.push(r[i]);return o.length?n[t]=o:delete n[t],this}},e.exports=r},{}],8:[function(t,e,n){"use strict";function r(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}n.__esModule=!0;var i=function(){function t(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,r.key,r)}}return function(e,n,r){return n&&t(e.prototype,n),r&&t(e,r),e}}(),a=t("select"),s=r(a),c=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=e.action,this.emitter=e.emitter,this.target=e.target,this.text=e.text,this.trigger=e.trigger,this.selectedText=""},t.prototype.initSelection=function t(){if(this.text&&this.target)throw new Error('Multiple attributes declared, use either "target" or "text"');if(this.text)this.selectFake();else{if(!this.target)throw new Error('Missing required attributes, use either "target" or "text"');this.selectTarget()}},t.prototype.selectFake=function t(){var e=this,n="rtl"==document.documentElement.getAttribute("dir");this.removeFake(),this.fakeHandler=document.body.addEventListener("click",function(){return e.removeFake()}),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.fontSize="12pt",this.fakeElem.style.border="0",this.fakeElem.style.padding="0",this.fakeElem.style.margin="0",this.fakeElem.style.position="absolute",this.fakeElem.style[n?"right":"left"]="-9999px",this.fakeElem.style.top=(window.pageYOffset||document.documentElement.scrollTop)+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,document.body.appendChild(this.fakeElem),this.selectedText=s.default(this.fakeElem),this.copyText()},t.prototype.removeFake=function t(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function t(){this.selectedText=s.default(this.target),this.copyText()},t.prototype.copyText=function t(){var e=void 0;try{e=document.execCommand(this.action)}catch(n){e=!1}this.handleResult(e)},t.prototype.handleResult=function t(e){e?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function t(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function t(){this.removeFake()},i(t,[{key:"action",set:function t(){var e=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=e,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function t(){return this._action}},{key:"target",set:function t(e){if(void 0!==e){if(!e||"object"!=typeof e||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');this._target=e}},get:function t(){return this._target}}]),t}();n.default=c,e.exports=n.default},{select:6}],9:[function(t,e,n){"use strict";function r(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function i(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}function a(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}n.__esModule=!0;var s=t("./clipboard-action"),c=r(s),u=t("tiny-emitter"),l=r(u),f=t("good-listener"),d=r(f),h=function(t){function e(n,r){o(this,e),t.call(this),this.resolveOptions(r),this.listenClick(n)}return i(e,t),e.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText},e.prototype.listenClick=function t(e){var n=this;this.listener=d.default(e,"click",function(t){return n.onClick(t)})},e.prototype.onClick=function t(e){var n=e.delegateTarget||e.currentTarget;this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new c.default({action:this.action(n),target:this.target(n),text:this.text(n),trigger:n,emitter:this})},e.prototype.defaultAction=function t(e){return a("action",e)},e.prototype.defaultTarget=function t(e){var n=a("target",e);return n?document.querySelector(n):void 0},e.prototype.defaultText=function t(e){return a("text",e)},e.prototype.destroy=function t(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(l.default);n.default=h,e.exports=n.default},{"./clipboard-action":8,"good-listener":4,"tiny-emitter":7}]},{},[9])(9)});
