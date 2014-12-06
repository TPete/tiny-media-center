<div class='category-overview'>
<?php foreach($overview as $data){?>
	<a href="<?php echo $data["folder"];?>" class="sub-category" title="<?php echo $data["title"];?>">
		<img class='thumbnail' src='<?php echo $data["thumbUrl"];?>'>
		<span><?php echo $data["title"];?></span>
	</a>
<?php }?>
<br class="clear">
</div>