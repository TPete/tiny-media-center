<div class='series-title'>
	<div class="header-caption"><?php echo $header;?></div>
	<div class='header-buttons-left'>
<?php if (isset($target)){?>
	<a href="http://<?php echo $target;?>" class="backBn">&lt; Zurück</a>
<?php }	?>
	<br class='clear'>
	</div>
	<div class='header-buttons-right'>
<?php if (isset($searchButtons)){?>
	<?php 
	$class1 = ($filter === "" and $genres === "" and $collection === 0 and $list === 0 and $sort === "date_desc") ? " class='header-buttons-active'" : "";
	$class2 = ($filter === "" and $genres === "" and $collection === 0 and $list === 0 and $sort === "name_asc") ? " class='header-buttons-active'" : "";
	$class3 = ($filter !== "" or $genres !== "" or $collection !== 0 or $list !== 0) ? " class='header-buttons-active'" : "";
	?>
	<a href="?sort=date_desc" <?php echo $class1;?>>Datum</a>
	<a href="?sort=name_asc" <?php echo $class2;?>>Name</a>
	<a href="javascript:void(0)" id='search-box-link' <?php echo $class3;?>>Suche</a>
<?php }	?>
	<br class='clear'>
	</div>
	<br class='clear'>
</div>