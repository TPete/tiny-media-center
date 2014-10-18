<div id="show-edit-box">
	<form method="post" action="edit/<?php echo $id;?>">
		<label for="title">Titel</label>
		<input type="text" name="title" id="title" placeholder="Titel" value="<?php echo $title;?>">		
		<br>
		<label for="tvdbId">TVDB ID</label>
		<input type="text" name="tvdbId" id="tvdbId" value="<?php echo $tvdbId;?>">
		<br>
		<button type="submit">Los</button>
	</form>
</div>