<?php
namespace API;

class MovieStoreDB extends Store{
		
	public function __construct($config){
		$tables = array("movies", "lists", "list_parts", "collections", "collection_parts");
		parent::__construct($config, $tables);
	}
	
	public function getMovies($category, $sort, $order, $filter, $genres, $cnt, $offset){
		$db = $this->connect();
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.title_sort, mov.added_date, mov.release_date";
		$sqlCnt = "Select count(*) Cnt";
		$sql = "
				From movies mov
				Where mov.category = '".$category."' ";
		
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
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMoviesForCollection($category, $collectionId, $cnt, $offset){
		$db = $this->connect();
		$sqlCnt = "Select count(*) Cnt";
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, mov.collection_id";
		$sql = "
				From collections col
				Join collection_parts parts on col.ID = parts.COLLECTION_ID
				Join movies mov on parts.MOVIE_ID = mov.MOVIE_DB_ID and col.CATEGORY = mov.CATEGORY
				Where col.MOVIE_DB_ID = :collectionId
				  and mov.CATEGORY = :category 
				Order By mov.RELEASE_DATE";
		
		$stmt = $db->prepare($sqlCnt.$sql);
		$stmt->bindValue(":collectionId", $collectionId, \PDO::PARAM_INT);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
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
			$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
			$stmt->execute();
			$list = array();
			while($row =$stmt->fetch(\PDO::FETCH_ASSOC)){
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMoviesForList($category, $listId, $cnt, $offset){
		$db = $this->connect();
		$sqlCnt = "Select count(*) Cnt";
		$sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
							mov.countries, mov.actors, mov.director, mov.info, mov.original_title";
		$sql = "
				FROM list_parts lp
				JOIN lists li on lp.LIST_ID = li.ID
				JOIN movies mov ON lp.MOVIE_ID = mov.MOVIE_DB_ID and li.CATEGORY = mov.CATEGORY
				WHERE lp.LIST_ID = :listId
				  and mov.CATEGORY = :category 
				Order By mov.RELEASE_DATE";
		$stmt = $db->prepare($sqlCnt.$sql);
		$stmt->bindValue(":listId", $listId, \PDO::PARAM_INT);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
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
			$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
			$stmt->execute();
			$list = array();
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
				$list[] = $row;
			}
			return array("list" => $list, "cnt" => $rowCount);
		}
		else{
			return array("list" => array(), "cnt" => 0);
		}
	}
	
	public function getMovieById($category, $id){
		$sql = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.collection_id, ifnull(col.name, '') collection_name
				From movies mov
				Left Join collections col on mov.COLLECTION_ID = col.MOVIE_DB_ID
				Where mov.category = :category and mov.id = :id";
		$sqlLists = "Select li.ID list_id, li.NAME list_name
					From movies mov
					Join list_parts lp on mov.MOVIE_DB_ID = lp.MOVIE_ID	
					Join lists li on li.ID = lp.LIST_ID
					Where mov.category = :category and mov.ID = :id";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		$stmt = $db->prepare($sqlLists);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->execute();
		$row["lists"] = array();
		while ($tmp = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$row["lists"][] = array("list_id" => $tmp["list_id"], "list_name" => $tmp["list_name"]);
		}
						
		return $row;
	}
			
	public function updateMovie($category, $movie, $dir, $id = ""){
		$db = $this->connect();
		if ($id === ""){
			$sql = "Select ID
					From movies
					Where FILENAME = :filename
					and CATEGORY = :category";
			$stmt = $db->prepare($sql);
			$stmt->bindValue(":filename", $movie["filename"], \PDO::PARAM_STR);
			$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			if ($row !== false){
				$id = $row["ID"];
			}
		}
		if ($id === ""){
			$sql = "Insert into movies(MOVIE_DB_ID, TITLE, FILENAME, OVERVIEW, RELEASE_DATE, GENRES, 
					COUNTRIES, ACTORS, DIRECTOR, INFO, ORIGINAL_TITLE, COLLECTION_ID, ADDED_DATE, TITLE_SORT, CATEGORY)
					Values (:movieDBId, :title, :filename, :overview, :releaseDate, :genres, 
					:countries, :actors, :director, :info, :originalTitle, :collectionId, :addedDate, :titleSort, :category)";
		}
		else{
			$sql = "Update movies
					set MOVIE_DB_ID = :movieDBId, TITLE = :title, FILENAME = :filename, OVERVIEW = :overview, 
					RELEASE_DATE = :releaseDate, GENRES = :genres, COUNTRIES = :countries, ACTORS = :actors,  
					DIRECTOR = :director, INFO = :info, ORIGINAL_TITLE = :originalTitle, COLLECTION_ID = :collectionId, 
					ADDED_DATE = :addedDate, TITLE_SORT = :titleSort, CATEGORY = :category
					Where ID = ".$id;
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
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
	}
		
	public function updateCollectionById($category, $collection, $id){
		$db = $this->connect();
		$sql = "Select ID, MOVIE_DB_ID
				From collections
				Where MOVIE_DB_ID = :id
				  And CATEGORY = :category";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":id", $id, \PDO::PARAM_INT);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if ($row === false){
			$sql = "Insert into collections(MOVIE_DB_ID, NAME, OVERVIEW, CATEGORY)
				Values (:movieDBId, :name, :overview, :category)";
		}
		else{
			$sqlOld = "Delete
						From collection_parts
						Where COLLECTION_ID = ".$row["ID"];
			$result = $db->query($sqlOld);
			$sql = "Update collections
					set MOVIE_DB_ID = :movieDBId,
					NAME = :name,
					OVERVIEW = :overview,
					CATEGORY = :category
					Where ID = ".$row["ID"];
		}
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":movieDBId", $collection["id"], \PDO::PARAM_INT);
		$stmt->bindValue(":name", $collection["name"], \PDO::PARAM_STR);
		$stmt->bindValue(":overview", $collection["overview"], \PDO::PARAM_STR);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		if ($row === false){
			$id = $db->lastInsertId();
		}
		else{
			$id = $row["ID"];
		}
		
		$sqlParts = "Insert into collection_parts(COLLECTION_ID, MOVIE_ID)
					Values (:collectionId, :movieId)";
		$stmtParts = $db->prepare($sqlParts);
		
		foreach($collection["parts"] as $part){
			$stmtParts->bindValue(":collectionId", $id, \PDO::PARAM_INT);
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
	
	public function checkRemovedFiles($category, $dir){
		$sql = "Select ID, MOVIE_DB_ID, FILENAME
				From movies
				Where CATEGORY = :category
				Order by id";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$list = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			if (!file_exists($dir.$row["FILENAME"])){
				$list[] = $row;
			}
		}
		$sql = "Delete From movies
				Where ID = :id";
		$stmt = $db->prepare($sql);
		$protocol = "";
		foreach($list as $toRemove){
			$protocol .= "Removing ".$toRemove["FILENAME"]."<br>";
			$stmt->bindValue(":id", $toRemove["ID"], \PDO::PARAM_INT);
			$stmt->execute();
		}
		
		return $protocol;
	}
	
	public function checkExisting($category, $dir){
		$db = $this->connect();
		$sql = "Select count(*) cnt
				From movies
				Where FILENAME = :filename
				  and CATEGORY = :category";
		$stmt = $db->prepare($sql);
		$files = glob($dir."*.avi");
		$missing = array();
		foreach($files as $file){
			$filename = substr($file, strrpos($file, "/") + 1);
			$stmt->bindValue(":filename", $filename, \PDO::PARAM_STR);
			$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			$cnt = intval($row["cnt"], 10);
			if ($cnt === 0){
				$missing[] = $filename;
			}
		}
		return $missing;
	}
	
	public function checkDuplicates($category){
		$db = $this->connect();
		$sql = "Select Title
				From movies
				Where CATEGORY = :category
				Group By Title
				Having count(*) > 1";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$duplicates = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$duplicates[] = $row["Title"];
		}
		
		return $duplicates;
	}
	
	public function checkCollections($category){
		$db = $this->connect();
		$sql = "SELECT mov.collection_id id
				FROM movies mov
				LEFT JOIN collections col ON mov.collection_id = col.movie_db_id AND mov.CATEGORY = col.CATEGORY
				WHERE mov.collection_id IS NOT NULL
				AND col.name IS NULL 
				AND mov.CATEGORY = :category";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$missing = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$missing[] = $row["id"];
		}
		
		$sql = "SELECT col.id As id
				FROM collections col 
				LEFT JOIN movies mov ON mov.collection_id = col.movie_db_id AND mov.CATEGORY = col.CATEGORY
				WHERE col.id IS NOT NULL
				AND mov.collection_id IS NULL 
				AND col.CATEGORY = :category";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$obsolete = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$obsolete[] = $row["id"];
		}
		return array("missing" => $missing, "obsolete" => $obsolete);
	}
	
	public function getMissingPics($category, $dir){
		$sql = "Select ID, MOVIE_DB_ID
				From movies
				Where CATEGORY = :category
				Order by id";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$movieDBIDS = array();
		$missing = array();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$movieDBIDS[] = $row["MOVIE_DB_ID"];
			$big = $dir.$row["MOVIE_DB_ID"]."_big.jpg";
			if (!file_exists($big)){
				$missing[] = $row;
			}
		}		
		return array("missing" => $missing, "all" => $movieDBIDS);
	}
		
	public function getGenres($category){
		$sql = "Select genres
				From movies
				Where category = :category";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
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
	
	public function getLists($category){
		$sql = "SELECT id, name
				FROM lists
				Where category = :category
				ORDER BY name";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$lists = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		return $lists;
	}
	
	public function getCollections($category){
		$sql = "SELECT MOVIE_DB_ID id, name
				FROM collections
				Where category = :category
				ORDER BY name";
		$db = $this->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$collections = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
		return $collections;
	}
}