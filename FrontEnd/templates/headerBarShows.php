<div class='series-title'>
	<div class="header-caption"><?php echo $header;?></div>
	<div class='header-buttons-left'>
<?php if (isset($target)){?>
	<a href="http://<?php echo $target;?>" class="backBn">&lt; Zurück</a>
<?php }	?>
	<br class='clear'>
	</div>
	<div class='header-buttons-right'>
	<?php if ($showEditButton){?>
		<a href="javascript:void(0)" id='edit-box-link'>Edit</a>
	<?php }?>
		<br class='clear'>
	</div>
	<br class='clear'>
</div>