<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<div id="powered-by" class="footer">Powered by <a href="https://chevereto.com" rel="generator">Chevereto</a> image hosting</div>

<?php if (!is_maintenance()) {
    G\Render\include_theme_file('snippets/embed_tpl');
} ?>

<?php
if (is_upload_allowed()) {
    G\Render\include_theme_file('snippets/anywhere_upload');
}
?>

<?php
if (!CHV\Login::isLoggedUser()) {
    G\Render\include_theme_file('snippets/modal_login');
}
?>

<?php G\Render\include_theme_file('custom_hooks/footer'); ?>

<?php CHV\Render\include_peafowl_foot(); ?>

<?php CHV\Render\show_theme_inline_code('snippets/footer.js'); ?>

<?php CHV\Render\showQueuePixel(); ?>

<?php CHV\Render\showPingPixel(); ?>

<?php echo CHV\getSetting('analytics_code'); ?>

<?php
if (CHV\Login::isAdmin()) {
    ?>

<script>
	$(document).ready(function() {
		$(document).on("click", "[data-action=upgrade]", function() {
			PF.fn.modal.call({
				template: $("[data-modal=form-upgrade]").html(),
				buttons: false,
				button_submit: "Upgrade now",
				ajax: {
					data: {action: 'upgrade'},
					deferred: {
						success: function(XHR) {
							window.location.href = XHR.responseJSON.redir.url;
						},
						error: function(XHR) {
							PF.fn.growl.call(XHR.responseJSON.error.message);
						}
					}
				},
			});
		});
	});
</script>

<div data-modal="form-upgrade" class="hidden" data-is-xhr data-submit-fn="CHV.fn.submit_upgradeToPaid" data-ajax-deferred="CHV.fn.complete_upgradeToPaid">
	<span class="modal-box-title">Upgrade to premium</span>
	<div class="text-align-center margin-top-30 margin-bottom-30" style="font-size: 90px;">💎💖👏</div>
	<p>Upgrading to Chevereto premium edition not only allows you to get more features and early access to all new additions and fixes. It also helps to keep development ongoing which is the most important asset of your purchase.</p>
	<p>Chevereto <a href="https://chevereto.com/features" target="_blank">premium features</a> include support for multiple external storage servers, bulk content importer, manage banners, content likes, user followers, social login signup and more. Of course, we don't charge any time-based fees.</p>
	<p>You will need a <a href="https://chevereto.com/panel/license" target="_blank">license key</a> for this process. If you don't have a license key, you can <a href="https://chevereto.com/pricing" target="_blank">purchase</a> it right now.</p>
	<div class="btn-container text-align-center"><button class="btn btn-input green" data-action="submit" type="submit">Upgrade now</button> <span class="btn-alt"><?php _se('or'); ?><a class="cancel" data-action="cancel">maybe later</a></span></div>
</div>
<?php
} ?>

</body>
</html>