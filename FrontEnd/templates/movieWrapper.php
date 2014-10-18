<input type="hidden" id="hidden-sort" value="<?php echo $sort;?>">
<input type="hidden" id="hidden-filter" value="<?php echo $filter;?>">
<input type="hidden" id="hidden-genres" value="<?php echo $genres;?>">
<input type="hidden" id="hidden-offset" value="<?php echo $offset;?>">
<input type="hidden" id="hidden-collection" value="<?php echo $collection;?>">
<input type="hidden" id="hidden-list" value="<?php echo $list;?>">

<div id='nm-movie-details-wrapper'>

</div>
<div id='movie-overview'>
<?php if (isset($movieOverview)){
	echo $movieOverview;
}?>
</div>