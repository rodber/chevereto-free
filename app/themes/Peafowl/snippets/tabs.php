<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<?php
$tabs = (array) (G\get_global('tabs') ? G\get_global('tabs') : (function_exists('get_tabs') ? get_tabs() : null));
$current;
foreach ($tabs as $tab) {
    if ($tab["current"]) {
        $current = $tab;
        break;
    }
}
?>
<div class="phone-show hidden tab-menu current" data-action="tab-menu"><span data-content="current-tab-label"><?php echo $current["label"]; ?></span><span class="icon icon-menu4 margin-left-5"></span></div>
<ul class="content-tabs phone-hide">
	<?php
        foreach ($tabs as $tab) {
            $echo = [
                '<li class="' . (isset($tab['class']) ? $tab['class'] : '') . ' ' .($tab["current"] ? 'current' : '') . '">',
                '<a ',
                $tab['id'] ? ('id="' .  $tab['id'] . '-link" data-tab="' . $tab["id"] . '" ') : '',
                'href="' . $tab['url'] . '">',
                $tab["label"],
                '</a></li>'."\n"
            ];
            echo implode('', $echo);
        }
    ?>
</ul>
