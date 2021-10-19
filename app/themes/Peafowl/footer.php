<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>

<?php if (!is_maintenance()) {
    G\Render\include_theme_file('snippets/embed_tpl');
} ?>

<?php
if (is_upload_allowed() && (CHV\getSetting('upload_gui') == 'js' || G\is_route('upload'))) {
    G\Render\include_theme_file('snippets/anywhere_upload');
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

<div data-modal="form-upgrade" class="hidden">
	<span class="modal-box-title">Upgrade</span>
	<p>Upgrading to paid Chevereto enables access to all features and latest updates. This process is automated, you will only need to paste your license key.</p>
    <p>All data will remain, and you can count on support assistance.</p>
	<div class="btn-container"><button class="btn blue" data-action="submit" type="submit"><span class="btn-icon icon-next3"></span> Upgrade now</button> <a class="btn orange outline" href="https://chevereto.com/pricing" target="_blank"><span class="btn-icon icon-key3"></span> Purchase license</a></div>
</div>
<?php
} ?>

</body>
</html>