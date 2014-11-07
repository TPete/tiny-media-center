<form method="post" action="http://192.168.2.2/tv/install">
<div>
<h2>FrontEnd</h2>
<?php foreach ($config as $name => $value){?>
	<label class="config">
	<?php echo $name;?>
	<input type="text" name="<?php echo $name;?>" value="<?php echo $value;?>" required="required">
	</label>
<?php }?>
</div>

<div>
<h2>API</h2>
<?php foreach ($apiConfig as $name => $value){?>
	<label class="config">
	<?php echo $name;?>
	<input type="text" name="<?php echo $name;?>" value="<?php echo $value;?>" required="required">
	</label>
<?php }?>
</div>
<button type="submit">Ok</button>
</form>