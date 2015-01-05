<a href="?<?php echo $previous;?>" id="movie-overview-prev" class="movie-overview-nav <?php echo $previousClass;?>">&lt;</a>
<?php foreach($movies as $row){?>
	<img class='movie-overview-poster' src='<?php echo $row["poster"];?>' 
	alt='Poster' data-id='<?php echo $row["id"];?>'>
<?php }?>
<a href="?<?php echo $next;?>" id="movie-overview-next" class="movie-overview-nav <?php echo $nextClass;?>">&gt;</a>
<br class="clear">