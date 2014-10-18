<div id="nm-movie-search-box">
	<form method="get" action="">
		<label for="filter">Suche</label>
		<input type="text" name="filter" id="filter" placeholder="Titel, Darsteller, Regie, ...">		
		<br>
		<label for="genres">Genres</label>
		<input type="text" name="genres" id="genres">
		<br>
		<label for="sort">Sortierung</label>
		<select name="sort" id="sort">
			<option value="name_asc">Name</option>
			<option value="date_desc">Datum (neueste zuerst)</option>
			<option value="year_desc">Jahr (neueste zuerst)</option>
			<option value="year_asc">Jahr (älteste zuerst)</option>
		</select>
		<br>
		<label for="list">Listen</label>
		<select name="list" id="list">
			<option value=""></option>
			<?php foreach($lists as $row){?>
				<option value="<?php echo $row["id"];?>"><?php echo $row["name"];?></option>
			<?php }?>
		</select>
		<br>
		<label for="collection">Reihen</label>
		<select name="collection" id="collection">
			<option value=""></option>
			<?php foreach($collections as $row){?>
				<option value="<?php echo $row["id"];?>"><?php echo $row["name"];?></option>
			<?php }?>
		</select>
		<br>
		<button type="submit">Los</button>
	</form>
</div>