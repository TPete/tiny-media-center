<div id="movie-details-dialog">
<span class="movie-details-label">Movie DB ID: </span><br>
<input type="text" id="movie-id" value="<?php echo $movie_db_id;?>">
<br>

<span class="movie-details-label">Title</span><br>
<span><?php echo $data["title"];?></span><br>
<span class="movie-details-label">Overview</span><br>
<span><?php echo $data["overview"];?></span><br>
<span class="movie-details-label">Release Date</span><br>
<span><?php echo $data["release_date"];?></span><br>
</div>