<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<h1>Connect to the database</h1>
<p>At this point you already have all the needed files uploaded and now you need to indicate where Chevereto will store the data (MySQL database).</p>
<p>To continue please provide your MySQL database details. <a data-modal="simple" data-target="modal-db-info">Learn more</a>.</p>
<div data-modal="modal-db-info" class="hidden">
	<span class="modal-box-title">Database info needed</span>
	<p>You must go to your hosting provider website panel (usually cPanel or Plesk) and get the required info. If you are running a server without panel you should be able to get this using SSH.</p>
	<p>Basically you need to create a database (this will be database name) and assign permissions to this database to a database user.</p>
	<p>Chevereto will connect to your database host using the database user credentials so this user must have access to the target database name.</p>
</div>
<?php if($error) { ?>
<p class="highlight padding-10"><?php echo $error_message; ?></p>
<?php } ?>
<form method="post" class="c9">
	<div class="input-label">
		<label for="db_host">Database host</label>
		<input type="text" name="db_host" id="db_host" class="text-input" value="<?php echo isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost'; ?>" placeholder="Usually localhost" title="Database server host (default: localhost)" rel="tooltip" data-tipTip="right" required>
	</div>
	<div class="input-label">
		<label for="db_name">Database name</label>
		<input type="text" name="db_name" id="db_name" class="text-input" value="<?php echo isset($_POST['db_name']) ? $_POST['db_name'] : ''; ?>" placeholder="Database name" title="Name of the database where you want to install Chevereto" rel="tooltip" data-tipTip="right" required>
	</div>
	<div class="input-label">
		<label for="db_user">Database user</label>
		<input type="text" name="db_user" id="db_user" class="text-input" value="<?php echo isset($_POST['db_user']) ? $_POST['db_user'] : ''; ?>" placeholder="Database user" title="User with access to the above database" rel="tooltip" data-tipTip="right" required>
	</div>
	<div class="input-label">
		<label for="db_pass">Database user password</label>
		<input type="text" name="db_pass" id="db_pass" class="text-input" value="<?php echo isset($_POST['db_pass']) ? $_POST['db_pass'] : ''; ?>" placeholder="Database user password" title="Password of the above user" rel="tooltip" data-tipTip="right">
	</div>
	<div class="input-label">
		<label for="db_table_prefix">Database table prefix</label>
		<input type="text" name="db_table_prefix" id="db_table_prefix" class="text-input" value="<?php echo isset($_POST['db_table_prefix']) ? $_POST['db_table_prefix'] : 'chv_'; ?>" placeholder="Usually chv_" title="Database table prefix. Use chv_ if you don't need this" rel="tooltip" data-tipTip="right">
	</div>
	<div class="btn-container margin-bottom-0">
		<button class="btn btn-input default" type="submit">Continue</button>
	</div>
</form>