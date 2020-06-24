<?php if (!defined('access') or !access) {
    die('This file cannot be directly accessed.');
} ?>
<script>
	$(document).ready(function() {
		if(typeof CHV == "undefined") {
			CHV = {obj: {}, fn: {}, str:{}};
		} else {
			if(typeof CHV.obj.embed_tpl == "undefined") {
				CHV.obj.embed_tpl = {};
			}
		}
		CHV.obj.embed_tpl = <?php $embed_tpl = G\get_global('embed_tpl'); echo json_encode($embed_tpl); ?>;
	});
</script>
<div data-modal="form-embed-codes" class="hidden">
	<span class="modal-box-title"><?php _se('Embed codes'); ?></span>
	<div class="input-label margin-bottom-0 copy-hover-display">
		<div class="c7 margin-bottom-10">
			<select name="form-embed-toggle" id="form-embed-toggle" class="text-input" data-combo="form-embed-toggle-combo">
				<?php
                    foreach (G\get_global('embed_tpl') as $key => $value) {
                        echo '<optgroup label="'.$value['label'].'">'."\n";
                        foreach ($value['options'] as $k => $v) {
                            echo '	<option value="'.$k.'" data-size="'.$v["size"].'">'.$v["label"].'</option>'."\n";
                        }
                        echo '</optgroup>';
                    }
                ?>
			</select>
		</div>
		<div id="form-embed-toggle-combo">
			<?php
                $i=0;
                foreach (G\get_global('embed_tpl') as $key => $value) {
                    foreach ($value['options'] as $k => $v) {
                        echo '<div data-combo-value="'.$k.'" class="switch-combo'.($i>0 ? " soft-hidden" : "").'">
							<textarea id="modal-embed-code-'.$i.'" class="r3 resize-vertical" name="'.$k.'" data-size="'.$v["size"].'" data-focus="select-all"></textarea>
							<button class="input-action" data-action="copy" data-action-target="#modal-embed-code-'.$i.'">'._s('copy').'</button>
						</div>'."\n";
                        $i++;
                    }
                }
            ?>
		</div>
	</div>
</div>