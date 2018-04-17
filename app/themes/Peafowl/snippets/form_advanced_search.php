<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<form action="<?php echo G\get_base_url("search/images"); ?>/" method="get" data-beforeunload="continue">
	<?php
		foreach([
			'as_q' => [
				'label'			=> _s('All these words'),
				'placeholder'	=> _s('Type the important words: tri-colour rat terrier')
			],
			'as_epq' => [
				'label' 		=> _s('This exact word or phrase'),
				'placeholder'	=> _s('Put exact words in quotes: "rat terrier"')
			],
			/*
			'as_oq'	=> [
				'label'			=> _s('Any of these words'),
				'placeholder'	=> _s('Type OR between all the words you want: miniature OR standard')
			],
			*/
			'as_eq'	=> [
				'label'			=> _s('None of these words'),
				'placeholder'	=> _s('Put a minus sign just before words you don\'t want: -rodent -"Jack Russell"')
			]
		] as $k => $v) {
	?>
	<div class="input-label">
		<label for="<?php echo $k; ?>"><?php echo $v['label']; ?></label>
		<input type="text" id="<?php echo $k; ?>" name="<?php echo $k; ?>" class="text-input" placeholder="<?php echo G\safe_html($v['placeholder']); ?>">
	</div>
	<?php } ?>
	<?php
		// Category selector
		$categories = get_categories();
		if(count($categories) > 0) {
			array_unshift($categories, [
				'id'		=> NULL,
				'url_key'		=> NULL,
				'name'		=> _s('All'),
			]);
	?>
	<div class="c7 input-label">
		<label for="as_cat"><?php _se('Category'); ?></label>
		<select name="as_cat" id="as_cat" class="text-input">
	<?php
			foreach($categories as $category) {
	?>
	<option value="<?php echo $category['url_key']; ?>"><?php echo $category['name']; ?></option>
	<?php
			}
	?>
		</select>
	</div>
	<?php
		} // categories
	?>
	<?php
		if(is_admin()) {
			$storages = CHV\getStorages();

			if($storages) {
				array_unshift($storages, [
					'id'		=> NULL,
					'name'		=> _s('All')
				]);
	?>
	<div class="c7 input-label">
		<label for="as_stor"><?php _se('Storage'); ?></label>
		<select name="as_stor" id="as_stor" class="text-input">
	<?php
				foreach($storages as $storage) {
	?>
			<option value="<?php echo $storage['id']; ?>"><?php echo $storage['name']; ?></option>
	<?php
				}
	?>
		</select>
	</div>
	<?php
			}
	?>
	<div class="c7 input-label">
		<label for="as_ip"><?php _se('IP address'); ?></label>
		<input type="text" id="as_ip" name="as_ip" class="text-input" placeholder="<?php echo G\get_client_ip(); ?>">
	</div>
	<?php
		} //admin
	?>
</form>