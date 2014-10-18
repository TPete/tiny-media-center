<h2>Error</h2>
<strong><?php echo $message;?></strong>
<br>
in
<br>
<?php foreach($trace as $frame){?>
	<?php if (isset($frame["file"])){?>
		<em><?php echo $frame["file"];?>(<?php echo $frame["line"];?>) : </em>
	<?php }?>
	<?php if (isset($frame["function"])){?>
		<em><?php echo $frame["function"];?></em>
	<?php }?>
	<br>
<?php }?>