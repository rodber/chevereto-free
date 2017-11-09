<?php
/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
			<inbox@rodolfoberrios.com>

  Copyright (C) Rodolfo Berrios A. All rights reserved.
  
  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */
  
if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $doctitle; ?></title>
<link rel="stylesheet" href="<?php echo G\absolute_to_url(CHV_PATH_PEAFOWL . 'peafowl.css'); ?>">
<link rel="stylesheet" href="<?php echo G\absolute_to_url(CHV_APP_PATH_CONTENT_SYSTEM . 'style.css'); ?>">
<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic&subset=latin,greek,cyrillic">
<link rel="shortcut icon" href="<?php echo G\absolute_to_url(CHV_APP_PATH_CONTENT_SYSTEM . 'favicon.png'); ?>">
<script>(function(w,d,u){w.readyQ=[];w.bindReadyQ=[];function p(x,y){if(x=="ready"){w.bindReadyQ.push(y);}else{w.readyQ.push(x);}};var a={ready:p,bind:p};w.$=w.jQuery=function(f){if(f===d||f===u){return a}else{p(f)}}})(window,document);</script>
</head>

<body>
	<div class="c20 center-box">
		<header id="header">
			<div id="logo"><img src="<?php echo G\absolute_to_url(CHV_APP_PATH_CONTENT_SYSTEM . 'chevereto.png'); ?>" alt=""></div>
		</header>
		<div id="content">
			<?php echo $html; ?>
		</div>
		<div id="powered">&copy; <a href="http://chevereto.com">Chevereto image hosting script</a></div>
	</div>
</body>

<script src="<?php echo G\absolute_to_url(CHV_PATH_PEAFOWL . 'js/jquery.min.js'); ?>"></script>
<script src="<?php echo G\absolute_to_url(CHV_PATH_PEAFOWL . 'js/scripts.js'); ?>"></script>
<script>(function($,d){$.each(readyQ,function(i,f){$(f)});$.each(bindReadyQ,function(i,f){$(d).bind("ready",f)})})(jQuery,document)</script>
<script src="<?php echo G\absolute_to_url(CHV_PATH_PEAFOWL . 'peafowl.js'); ?>"></script>
<script>
PF.obj.config.base_url = "<?php echo G\get_base_url(); ?>";
PF.obj.l10n = <?php echo json_encode(CHV\get_translation_table()) ;?>;
</script>
<?php
if(method_exists('CHV\Settings','getChevereto')) {
	echo '<script>var CHEVERETO = ' . json_encode(CHV\Settings::getChevereto()) . '</script>';
}
?>
<script src="<?php echo CHV\Render\versionize_src(G\Render\get_app_lib_file_url('chevereto.js')); ?>"></script>

</html>