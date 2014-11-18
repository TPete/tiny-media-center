<form method="post" action="">
<div>
<h2>FrontEnd</h2>
<?php foreach ($config as $name => $value){?>
	<label class="config">
	<?php echo $name;?>
	<input type="text" name="<?php echo $name;?>" id="<?php echo $name;?>" value="<?php echo $value;?>" required="required">
	</label>
<?php }?>
</div>

<div>
<h2>API</h2>
<?php if (count($apiConfig) === 0){?>
	API config not available. Please provide/check url to API.
<?php }?>
<?php foreach ($apiConfig as $name => $value){?>
	<label class="config">
	<?php echo $name;?>
	<input type="text" name="<?php echo $name;?>" id="<?php echo $name;?>" value="<?php echo $value;?>" required="required">
	</label>
<?php }?>
</div>
<button type="submit">Ok</button>
</form>