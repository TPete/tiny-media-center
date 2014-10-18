<div id="nm-movie-poster">
	<img src='<?php echo $poster;?>' alt='Poster'>
	<div>
		<a id='movie-play-link' href='<?php echo $path;?>' target='_blank' title='Play'>
			&#9654;
		</a>
		<a id='movie-edit-link' data-id='<?php echo $id;?>' data-movieDBID='<?php echo $movie_db_id;?>' 
			data-filename='<?php echo $filename;?>' href='#' title='Edit'>
			Edit
		</a>
	</div>
</div>
<div id="nm-movie-details">
	<div id='nm-movie-details-info'>
		<span class="movie-details-label">Jahr/Land: </span><br>
		<span><?php echo $year."/".implode(", ", array_slice($countries, 0, 3));?></span><br>
		<span class="movie-details-label">Genres: </span><br>
		<span><?php echo implode(", ", array_slice($genres, 0, 3));?></span><br>
		<span class="movie-details-label">Darsteller: </span><br>
		<span>
			<?php $last = end($actors);?>
			<?php foreach ($actors as $actor){?>
				<a href="?filter=<?php echo urlencode(str_replace("&nbsp;", " ", $actor));?>">
				<?php echo $actor;?>
				</a>
				<?php if ($actor !== $last){?>
					<br>
				<?php }?>
			<?php }?>
		</span><br>
		<span class="movie-details-label">Regie: </span><br>
		<span><a href="?filter=<?php echo urlencode($director);?>"><?php echo $director;?></a></span><br>
		<span class="movie-details-label">Info: </span><br>
		<span><?php echo $info;?></span><br>
	</div>
	<div class="nm-movie-details-overview">
	<h2 class="nm-movie-details-title">
	<?php echo $title;?>
	</h2>
	<?php echo $overview;?>
	<?php if (strlen($collection_name) > 0){?>
	<br><br>
	Teil der <a href="?collection=<?php echo $collection_id;?>"><?php echo $collection_name;?></a>
	<?php }?>
	<?php foreach($lists as $list){?>
	<br><br>
	Teil der <a href="?list=<?php echo $list["list_id"];?>"><?php echo $list["list_name"];?></a> Liste
	<?php }?>
	</div>
	<br class="clear">
</div>
<br class="clear">