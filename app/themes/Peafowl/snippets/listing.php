<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>

<?php
$list = function_exists('get_list') ? get_list() : G\get_global('list');
$tabs = (array) (G\get_global('tabs') ? G\get_global('tabs') : (function_exists('get_tabs') ? get_tabs() : NULL));

$classic = isset($_GET['pagination']) || CHV\getSetting('listing_pagination_mode') == 'classic';
$do_pagination = !isset($list->pagination) OR $list->pagination == true ? true : false;
foreach($tabs as $tab) {
	if($tab['list'] === FALSE) continue;
	if($tab['current']) {
?>
<div id="<?php echo $tab["id"]; ?>" class="tabbed-content content-listing visible list-<?php echo $tab["type"]; ?>" data-action="list" data-list="<?php echo $tab["type"]; ?>" data-params="<?php echo $tab["params"]; ?>" data-params-hidden="<?php echo $tab["params_hidden"]; ?>">
	<?php
		if($list->output && count($list->output) > 0) {
	?>
	<div class="pad-content-listing"><?php echo $list->htmlOutput($list->output_tpl ?: NULL); ?></div>
	<?php
		if($do_pagination) {
	?>
	<div class="content-listing-more">
		<button class="btn btn-big grey" data-action="load-more"><?php _se('Load more'); ?></button>
	</div>
	<?php
	}

		if(count($list->output) >= $list->limit) {
	?>
	<div class="content-listing-loading"></div>
	<?php
		}

		if($do_pagination and ($classic or count($list->output) >= $list->limit)) { // pagination
	?>
	<?php
		if($classic) {
		}
		if($list->has_page_prev || $list->has_page_next) {
	?>
	<ul class="content-listing-pagination<?php if($classic) { ?> visible<?php } ?>" data-visibility="<?php echo $classic ? 'visible' : 'hidden'; ?>" data-content="listing-pagination" data-type="<?php echo $classic ? 'classic' : 'endless'; ?>">
	<?php
				$current_url = G\add_ending_slash(preg_replace('/\?.*/', '', G\get_current_url()));
				$current_url .= '?' . $tab["params"] . '&' . 'pagination';

				preg_match('/page=([0-9]+)/', $tab["params"], $matches);
				$current_page_qs = $matches[0];

				$page = intval($_GET['page'] ? $_GET['page'] : $matches[1]);
				$pages = [];

				$pages['prev'] = [
					'label'		=> '<span class="icon icon-arrow-left7"></span>',
					'url'		=> $list->has_page_prev ? str_replace($current_page_qs, 'page='.($page - 1), $current_url) : NULL,
					'disabled'	=> !$list->has_page_prev
				];

				$pages[] = [
					'label'		=> $page,
					'url'		=> str_replace($current_page_qs, 'page='.$page, $current_url),
					'current'	=> TRUE
				];

				$pages['next'] = [
					'label'		=> '<span class="icon icon-arrow-right7"></span>',
					'url'		=> $list->has_page_next ? str_replace($current_page_qs, 'page='.($page + 1), $current_url) : NULL,
					'load-more' => !$classic,
					'disabled'	=> !$list->has_page_next
				];

				foreach($pages as $k => $page) {
					if(is_numeric($k)) {
						$li_class = 'pagination-page';
					} else {
						$li_class = 'pagination-' . $k;
					}
					if($page['current']) {
						$li_class .= ' pagination-current';
					}
					if($page['disabled']) {
						$li_class .= ' pagination-disabled';
					}
		?>
		<li class="<?php echo $li_class; ?>"><a data-pagination="<?php echo $k; ?>" <?php if($page['load-more']) { ?>data-action="load-more" <?php } if(!is_null($page['url'])) { ?>href="<?php echo $page['url']; ?>"<?php } ?>><?php echo $page['label']; ?></a></li>
		<?php
				}
		?>
		<script>
			$(document).ready(function() {
				$("a[href]", "[data-content=listing-pagination]").each(function() {
					$(this).attr("href", $(this).attr("href").removeURLParameter("pagination"));
				});
			});
		</script>
	</ul>
	<?php
		}
		if($classic) {
		}
	?>
	<?php
			} // pagination?
		} else { // Results?
			G\Render\include_theme_file("snippets/template_content_empty");
		}
	?>
</div>
<?php
	} else { // !current
?>

<div id="<?php echo $tab["id"]; ?>" class="tabbed-content content-listing hidden list-<?php echo $tab["type"]; ?>" data-action="list" data-list="<?php echo $tab["type"]; ?>" data-params="<?php echo $tab["params"]; ?>" data-params-hidden="<?php echo $tab["params_hidden"]; ?>" data-load="<?php echo $classic ? 'classic' : 'ajax'; ?>">
</div>

<?php
	}
} // for
?>

<?php G\Render\include_theme_file("snippets/viewer_template"); ?>

<?php G\Render\include_theme_file("snippets/templates_content_listing"); ?>