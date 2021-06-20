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

<div data-modal="form-upgrade" class="hidden" data-is-xhr data-submit-fn="CHV.fn.submit_upgradeToPaid" data-ajax-deferred="CHV.fn.complete_upgradeToPaid">
	<span class="modal-box-title">Upgrade to paid edition</span>
	<p>Upgrading to paid Chevereto enables access to all <a href="https://chevereto.com/features" target="_blank">features</a>. Tech Support included.</p>
	<div class="btn-container text-align-center"><button class="btn btn-input green" data-action="submit" type="submit">Upgrade now</button></div>
</div>
<?php
} ?>

</body>
</html>