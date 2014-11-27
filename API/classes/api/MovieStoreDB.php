<?php
namespace API;

class MovieStoreDB extends Store{
	
	private $alias;
	private $pictureAlias;
	
	public function __construct($config, $alias, $pictureAlias){
		$tables = array("movies", "lists", "list_parts", "collections", "collection_parts");
		parent::__construct($config, $tables);
		$this->alias = $alias;
		$this->pictureAlias = $pictureAlias;
	}
	
	public function getMovies($sort, $order, $filter, $genres, $cnt, $offset){
		$db = $this->connect();
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.title_sort, mov.added_date, mov.release_date";
		$sqlCnt = "Select count(*) Cnt";
		$sql = "
				From movies mov
				Where 1 = 1 ";
		
		if (strlen($genres) > 0){
			$whereGenres = "";
			$genres = strtolower($genres);
			$genresArray = explode(",", $genres);
			foreach ($genresArray as $gen){
				$whereGenres .= "and Lower(mov.GENRES) like '%".$gen."%' ";
			}
			$sql .= $whereGenres;
		}
		
		if (strlen($filter) > 0){
			$whereTitle = "";
			$whereTitleSort = "";
			$whereOriginalTitle = "";
			$whereActors = "";
			$whereDirector = "";
			$filter = strtolower($filter);
			$filterArray = explode(" ", $filter);
			foreach ($filterArray as $fil){
				$whereTitle .= "
						Lower(mov.TITLE) like '%".$fil."%' and ";
				$whereTitleSort .= "
						Lower(mov.TITLE_SORT) like '%".$fil."%' and ";
				$whereOriginalTitle .= "
						Lower(mov.ORIGINAL_TITLE) like '%".$fil."%' and ";
				$whereActors .= "
						Lower(mov.ACTORS) like '%".$fil."%' and ";
				$whereDirector .= "
						Lower(mov.DIRECTOR) like '%".$fil."%' and ";
			} 
			$whereTitle = substr($whereTitle, 0, -4);
			$whereTitleSort = substr($whereTitleSort, 0, -4);
			$whereOriginalTitle = substr($whereOriginalTitle, 0, -4);
			$whereActors = substr($whereActors, 0, -4);
			$whereDirector = substr($whereDirector, 0, -4);
			
			$sqlCnt = $sqlCnt.$sql. "
						and (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle."
							or ".$whereActors." or ".$whereDirector.")";
			
			$sqlAll = $sqlCols.", 1 sorter, levenshtein(mov.TITLE, '".$filter."') dist ".$sql." and ".$whereTitle;
			$sqlAll .= "
					 Union ";
			$sqlAll .= $sqlCols.", 2 sorter, levenshtein(mov.TITLE_SORT, '".$filter."') dist ".$sql." and ".$whereTitleSort." and NOT(".$whereTitle.")";
			$sqlAll .= "
					 Union ";
			$sqlAll .= $sqlCols.", 3 sorter, levenshtein(mov.ORIGINAL_TITLE, '".$filter."') dist ".$sql." and ".$whereOriginalTitle." and NOT(".$whereTitle." or ".$whereTitleSort.")";
			$sqlAll .= "
					 Union ";
			$sqlAll .= $sqlCols.", 4 sorter, levenshtein(mov.ACTORS, '".$filter."') dist ".$sql." and ".$whereActors." and NOT (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle.")";
			$sqlAll .= "
					 Union ";
			$sqlAll .= $sqlCols.", 5 sorter, levenshtein(mov.DIRECTOR, '".$filter."') dist ".$sql." and ".$whereDirector." and NOT (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle." or ".$whereActors.")";
		}
		else{
			$sqlCnt = $sqlCnt.$sql;
			$sqlAll = $sqlCols.", 1 sorter, 1 dist ".$sql;
		}

		$stmt = $db->query($sqlCnt);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$rowCount = $row["Cnt"];
		
		if ($rowCount > 0){
			$sql = $sqlAll;
			if ($sort === "name"){
				$sql .= "
						Order by sorter, dist, TITLE_SORT ".$order;
			}
			if ($sort === "date"){
				$sql .= "
						Order by sorter, dist, ADDED_DATE ".$order;
			}
			if ($sort === "year"){
				$sql .= "
						Order by sorter, dist, RELEASE_DATE ".$order;
			}
			if ($cnt > -1){
				$sql .= "
						Limit ".$offset.", ".$cnt;
			}

			$stmt = $db->query($sql);
			$list = array();
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
				$row["poster"] = $this->pictureAlias.$row["movie_db_id"]."_333x500.jpg";
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMoviesForCollection($collectionId, $cnt, $offset){
		$db = $this->connect();
		$sqlCnt = "Select count(*) Cnt";
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, mov.collection_id";
		$sql = "
				From collections col
				Join collection_parts parts on col.MOVIE_DB_ID = parts.COLLECTION_ID
				Join movies mov on parts.MOVIE_ID = mov.MOVIE_DB_ID
				Where col.MOVIE_DB_ID = :collectionId
				Order By mov.RELEASE_DATE";
		
		$stmt = $db->prepare($sqlCnt.$sql);
		$stmt->bindValue(":collectionId", $collectionId, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$rowCount = $row["Cnt"];
		
		if ($rowCount > 0){
			$sql = $sqlCols.$sql;
			if ($cnt > -1){
				$sql .= "
						Limit ".$offset.", ".$cnt;
			}
			$stmt = $db->prepare($sql);
			$stmt->bindValue(":collectionId", $collectionId, \PDO::PARAM_INT);
			$stmt->execute();
			$list = array();
			while($row =$stmt->fetch(\PDO::FETCH_ASSOC)){
				$row["poster"] = $this->pictureAlias.$row["movie_db_id"]."_333x500.jpg";
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMoviesForList($listId, $cnt, $offset){
		$db = $this->connect();
		$sqlCnt = "Select count(*) Cnt";
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
							mov.countries, mov.actors, mov.director, mov.info, mov.original_title";
		$sql = "
				FROM list_parts lp
				JOIN movies mov ON lp.MOVIE_ID = mov.MOVIE_DB_ID
				WHERE lp.LIST_ID = :listId
				Order By mov.RELEASE_DATE";
		$stmt = $db->prepare($sqlCnt.$sql);
		$stmt->bindValue(":listId", $listId, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$rowCount = $row["Cnt"];
		
		if ($rowCount > 0){
			$sql = $sqlCols.$sql;
			if ($cnt > -1){
				$sql .= "
						Limit ".$offset.", ".$cnt;
			}
			$stmt = $db->prepare($sql);
			$stmt->bindValue(":listId", $listId, \PDO::PARAM_INT);
			$stmt->execute();
			$list = array();
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
				$row["poster"] = $this->pictureAlias.$row["movie_db_id"]."_333x500.jpg";
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMovieById($id){
		$sql = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.collection_id, ifnull(col.name, '') collection_name
				From movies mov
				Left Join collections col on mov.COLLECTION_ID = col.MOVIE_DB_ID
				Where mov.id = :id";
		$sqlLists = "Select li.ID list_id, li.NAME list_name
					From movies mov
					Join list_parts lp on mov.MOVIE_DB_ID = lp.MOVIE_ID	
					Join lists li on li.ID = lp.LIST_ID
					Where mov.ID = :id";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$row["filename"] = $this->alias.$row["filename"];
		$row["poster"] = $this->pictureAlias.$row["movie_db_id"]."_333x500.jpg";
		
		$stmt = $db->prepare($sqlLists);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->execute();
		$row["lists"] = array();
		while ($tmp = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$row["lists"][] = array("list_id" => $tmp["list_id"], "list_name" => $tmp["list_name"]);
		}
						
		return $row;
	}
			
	public function updateMovie($movie, $dir){
		$sql = "Select ID
				From movies
				Where FILENAME = :filename";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":filename", $movie["filename"], \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if ($row === false){
			$sql = "Insert into movies(MOVIE_DB_ID, TITLE, FILENAME, OVERVIEW, RELEASE_DATE, GENRES, 
					COUNTRIES, ACTORS, DIRECTOR, INFO, ORIGINAL_TITLE, COLLECTION_ID, ADDED_DATE, TITLE_SORT)
					Values (:movieDBId, :title, :filename, :overview, :releaseDate, :genres, 
					:countries, :actors, :director, :info, :originalTitle, :collectionId, :addedDate, :titleSort)";
		}
		else{
			$sql = "Update movies
					set MOVIE_DB_ID = :movieDBId, TITLE = :title, FILENAME = :filename, OVERVIEW = :overview, 
					RELEASE_DATE = :releaseDate, GENRES = :genres, COUNTRIES = :countries, ACTORS = :actors,  
					DIRECTOR = :director, INFO = :info, ORIGINAL_TITLE = :originalTitle, COLLECTION_ID = :collectionId, 
					ADDED_DATE = :addedDate, TITLE_SORT = :titleSort
					Where ID = ".$row["ID"];
		}
		$actors = array_slice($movie["actors"], 0, 10);
		$filename = $dir.$movie["filename"];
		$added = date("Y-m-d", filemtime($filename));
		$titleSort = $movie["title"];
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":movieDBId", $movie["id"], \PDO::PARAM_INT);
		$stmt->bindValue(":title", $movie["title"], \PDO::PARAM_STR);
		$stmt->bindValue(":filename", $movie["filename"], \PDO::PARAM_STR);
		$stmt->bindValue(":overview", $movie["overview"], \PDO::PARAM_STR);
		$stmt->bindValue(":releaseDate", $movie["release_date"], \PDO::PARAM_STR);
		$stmt->bindValue(":genres", implode(",", $movie["genres"]), \PDO::PARAM_STR);
		$stmt->bindValue(":countries", implode(",", $movie["countries"]), \PDO::PARAM_STR);
		$stmt->bindValue(":actors", implode(",", $actors), \PDO::PARAM_STR);
		$stmt->bindValue(":director", $movie["director"], \PDO::PARAM_STR);
		$stmt->bindValue(":info", $movie["info"], \PDO::PARAM_STR);
		$stmt->bindValue(":originalTitle", $movie["original_title"], \PDO::PARAM_STR);
		$stmt->bindValue(":collectionId", $movie["collection_id"], \PDO::PARAM_INT);
		$stmt->bindValue(":addedDate", $added, \PDO::PARAM_STR);
		$stmt->bindValue(":titleSort", $titleSort, \PDO::PARAM_STR);
		$stmt->execute();
	}
	
	public function updateMovieById($movie, $id, $dir){
		$db = $this->connect();
		$sql = "Update movies
				set MOVIE_DB_ID = :movieDBId, TITLE = :title, FILENAME = :filename, OVERVIEW = :overview, 
				RELEASE_DATE = :releaseDate, GENRES = :genres, COUNTRIES = :countries, ACTORS = :actors,  
				DIRECTOR = :director, INFO = :info, ORIGINAL_TITLE = :originalTitle, COLLECTION_ID = :collectionId, 
				ADDED_DATE = :addedDate, TITLE_SORT = :titleSort
				Where ID = ".$id;
		$actors = array_slice($movie["actors"], 0, 10);
		$filename = $dir.$movie["filename"];
		$added = date("Y-m-d", filemtime($filename));
		$titleSort = $movie["title"];
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":movieDBId", $movie["id"], \PDO::PARAM_INT);
		$stmt->bindValue(":title", $movie["title"], \PDO::PARAM_STR);
		$stmt->bindValue(":filename", $movie["filename"], \PDO::PARAM_STR);
		$stmt->bindValue(":overview", $movie["overview"], \PDO::PARAM_STR);
		$stmt->bindValue(":releaseDate", $movie["release_date"], \PDO::PARAM_STR);
		$stmt->bindValue(":genres", implode(",", $movie["genres"]), \PDO::PARAM_STR);
		$stmt->bindValue(":countries", implode(",", $movie["countries"]), \PDO::PARAM_STR);
		$stmt->bindValue(":actors", implode(",", $actors), \PDO::PARAM_STR);
		$stmt->bindValue(":director", $movie["director"], \PDO::PARAM_STR);
		$stmt->bindValue(":info", $movie["info"], \PDO::PARAM_STR);
		$stmt->bindValue(":originalTitle", $movie["original_title"], \PDO::PARAM_STR);
		$stmt->bindValue(":collectionId", $movie["collection_id"], \PDO::PARAM_INT);
		$stmt->bindValue(":addedDate", $added, \PDO::PARAM_STR);
		$stmt->bindValue(":titleSort", $titleSort, \PDO::PARAM_STR);
		$stmt->execute();
	}
	
	public function updateCollectionById($collection, $id){
		$db = $this->connect();
		$sql = "Select ID, MOVIE_DB_ID
				From collections
				Where MOVIE_DB_ID = :id";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if ($row === false){
			$sql = "Insert into collections(MOVIE_DB_ID, NAME, OVERVIEW)
				Values (:movieDBId, :name, :overview)";
		}
		else{
			$sqlOld = "Delete
						From collection_parts
						Where COLLECTION_ID = ".$row["MOVIE_DB_ID"];
			$result = $db->query($sqlOld);
			$sql = "Update collections
					set MOVIE_DB_ID = :movieDBId,
					NAME = :name,
					OVERVIEW = :overview
					Where ID = ".$row["ID"];
		}
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":movieDBId", $collection["id"], \PDO::PARAM_INT);
		$stmt->bindValue(":name", $collection["name"], \PDO::PARAM_STR);
		$stmt->bindValue(":overview", $collection["overview"], \PDO::PARAM_STR);
		$stmt->execute();
		
		$sqlParts = "Insert into collection_parts(COLLECTION_ID, MOVIE_ID)
					Values (:collectionId, :movieId)";
		$stmtParts = $db->prepare($sqlParts);
		
		foreach($collection["parts"] as $part){
			$stmtParts->bindValue(":collectionId", $collection["id"], \PDO::PARAM_INT);
			$stmtParts->bindValue(":movieId", $part["id"], \PDO::PARAM_INT);
			$stmtParts->execute();
		}
	}
	
	public function removeObsoleteCollection($collectionId){
		$sql = "Delete
				From collections
				Where id = :collectionId";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":collectionId", $collectionId);
		$stmt->execute();
	}
	
	public function checkRemovedFiles($dir){
		$sql = "Select ID, MOVIE_DB_ID, FILENAME
				From movies
				Order by id";
		$db = $this->connect();
		$stmt = $db->query($sql);
		$list = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			if (!file_exists($dir.$row["FILENAME"])){
				$list[] = $row;
			}
		}
		$sql = "Delete From movies
				Where ID = :id";
		$stmt = $db->prepare($sql);
		foreach($list as $toRemove){
			echo "Removing ".$toRemove["FILENAME"]."<br>";
			$stmt->bindValue(":id", $toRemove["ID"], \PDO::PARAM_INT);
			$stmt->execute();
		}
	}
	
	public function checkExisting($dir){
		$db = $this->connect();
		$sql = "Select count(*) cnt
				From movies
				Where FILENAME = :filename";
		$stmt = $db->prepare($sql);
		$files = glob($dir."*.avi");
		$missing = array();
		$duplicates = array();
		foreach($files as $file){
			$filename = substr($file, strrpos($file, "/") + 1);
			$stmt->bindValue(":filename", $filename, \PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			$cnt = intval($row["cnt"], 10);
			if ($cnt === 0){
				$missing[] = $filename;
			}
			if ($cnt === 2){
				$duplicates[] = $filename;
			}
		}
		return array("missing" => $missing, "duplicates" => $duplicates);
	}
	
	public function checkCollections(){
		$db = $this->connect();
		$sql = "SELECT mov.collection_id id
				FROM movies mov
				LEFT JOIN collections col ON mov.collection_id = col.movie_db_id
				WHERE mov.collection_id IS NOT NULL
				AND col.name IS NULL ";
		$stmt = $db->query($sql);
		$missing = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$missing[] = $row["id"];
		}
		
		$sql = "SELECT col.id As id
				FROM collections col 
				LEFT JOIN movies mov ON mov.collection_id = col.movie_db_id
				WHERE col.id IS NOT NULL
				AND mov.collection_id IS NULL ";
		$stmt = $db->query($sql);
		$obsolete = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$obsolete[] = $row["id"];
		}
		return array("missing" => $missing, "obsolete" => $obsolete);
	}
	
	public function getMissingPics($picsDir){
		$sql = "Select ID, MOVIE_DB_ID
				From movies
				Order by id";
		$db = $this->connect();
		$stmt = $db->query($sql);
		$movieDBIDS = array();
		$missing = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$movieDBIDS[] = $row["MOVIE_DB_ID"];
			$big = $picsDir.$row["MOVIE_DB_ID"]."_big.jpg";
			if (!file_exists($big)){
				$missing[] = $row;
			}
		}		
		return array("missing" => $missing, "all" => $movieDBIDS);
	}
		
	public function getGenres(){
		$sql = "Select genres
				From movies";
		$db = $this->connect();
		$stmt = $db->query($sql);
		$genres = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$tmp = explode(",", $row["genres"]);
			foreach($tmp as $val){
				if (!in_array($val, $genres)){
					$genres[] = $val;
				}
			}
		}
		sort($genres);
		
		return $genres;
		
	}
	
	public function getLists(){
		$sql = "SELECT id, name
				FROM lists
				ORDER BY name";
		$db = $this->connect();
		$stmt = $db->query($sql);
		$lists = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		return $lists;
	}
	
	public function getCollections(){
		$sql = "SELECT MOVIE_DB_ID id, name
				FROM collections
				ORDER BY name";
		$db = $this->connect();
		$stmt = $db->query($sql);
		$collections = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
		return $collections;
	}
}