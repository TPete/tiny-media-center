<?php
namespace API;

class MovieController extends Controller{
	
	private $picturePath;
	
	public function __construct($path, $alias, $dbConfig, $apiKey){
		$scraper = new TMDBWrapper($path, $this->picturePath, $apiKey);
		$store = new MovieStoreDB($dbConfig, $alias, $alias."pictures/");
		parent::__construct($path, $alias, $store, $scraper);
		$this->picturePath = $path."pictures/";
	}
	
	public function getCategories(){
		//TODO: replace hard coded values
		$categories = array("movies/" => "Filme");
		
		return $categories;
	}
	
	/**
	 * Get movies matching the given criteria.
	 * 
	 * @param sort the sort criteria (name|date|year)
	 * @param order the sort order (asc|desc)
	 * @param filter search terms
	 * @param genre genres
	 * @param cnt pagination control; maximum number of results
	 * @param offset pagination control; offset
	 * 
	 * @return the movies (array of arrays)
	 * 
	 */
	public function getMovies($sort, $order, $filter, $genre, $cnt, $offset){
		return $this->store->getMovies($sort, $order, $filter, $genre, $cnt, $offset);
	}
	
	/**
	 * Get movies for the given collection id.
	 * Results are ordered by release date.
	 *
	 * @param collectionID the collection id
	 * @param cnt pagination control; maximum number of results
	 * @param offset pagination control; offset
	 *
	 * @return the movies (array of arrays)
	 *
	 */
	public function getMoviesForCollection($collectionID, $cnt, $offset){
		return $this->store->getMoviesForCollection($collectionID, $cnt, $offset);
	}
	
	/**
	 * Get movies for the given list id.
	 * Results are ordered by release date.
	 *
	 * @param listId the list id
	 * @param cnt pagination control; maximum number of results
	 * @param offset pagination control; offset
	 *
	 * @return the movies (array of arrays)
	 *
	 */
	public function getMoviesForList($listId, $cnt, $offset){
		return $this->store->getMoviesForList($listId, $cnt, $offset);
	}
	
	/**
	 * Get details for the given id (database id).
	 * 
	 * @param id the id of the movie.
	 * 
	 * @return the movie details, an error message if the movie was not found
	 */
	public function getMovieDetails($id){
		$movie = $this->store->getMovieById($id);
		if (isset($movie["error"])){
			return $movie;
		}
		$actors = explode(",", $movie["actors"]);
		$movie["actors"] = array_slice($actors, 0, 4);
		$movie["countries"] = explode(",", $movie["countries"]);
		$movie["genres"] = explode(",", $movie["genres"]);
		$movie["year"] = substr($movie["release_date"], 0, 4);
		
		return $movie;
	}
	
	public function updateFromScraper($dbid, $movieDBID, $filename){
		$movie = $this->scraper->getMovieInfo($movieDBID, $this->path, $filename);
		if ($movie !== null){
			$this->scraper->downloadPoster($movie->getId(), $movie->getPosterPath());
			$this->store->updateMovieById($movie->toArray(), $dbid, $this->path);
			$this->resizeMoviePics($this->picturePath);
		}
		
		return $movie;
	}
		
	public function lookupMovie($id){
		$movie = $this->scraper->getMovieInfo($id);
		
		return $movie->toArray();
	}
		
	private function searchMovie($title, $filename){
		$movie = $this->scraper->searchMovie($title, $filename);
		if ($movie !== null){
			$this->scraper->downloadPoster($movie->getId(), $movie->getPosterPath());
			$this->store->updateMovie($movie->toArray(), $this->path);

			return "OK:".$movie;
		}
		else{
			return "No Match";
		}
	}
	
	public function getGenres(){
		return $this->store->getGenres();
	}
	
	public function getLists(){
		return $this->store->getLists();
	}
	
	public function getCollections(){
		return $this->store->getCollections();
	}
	
	public function updateData(){
		echo "<h1>Maintenance</h1>";
		echo "<h2>Duplicate movie files</h2>";
		$res = $this->checkDuplicateFiles($this->path);
		foreach($res as $movie){
			echo $movie."<br>";
		}
				
		$res = $this->store->checkExisting($this->path);
		
		echo "<h2>Missing movie entries (new movies)</h2>";
		foreach($res["missing"] as $filename){
			$title = $this->getMovieTitle($filename);
			echo $title." (File: ".$filename.")<br>";
			echo $this->searchMovie($title, $filename);
			echo "<br>";
		}
		
		echo "<h2>DB duplicates</h2>";
		//TODO: handle duplicates
		foreach($res["duplicates"] as $dupe){
			var_dump($dupe);
		}
		
		echo "<h2>Obsolete movie entries</h2>";
		$this->store->checkRemovedFiles($this->path);
		
		echo "<h2>Missing collection entries</h2>";
		$res = $this->store->checkCollections();
		foreach($res["missing"] as $miss){
			$col = $this->updateCollectionFromScraper($miss);
			var_dump($col);
			echo "<br>";
		}
		
		echo "<h2>Obsolete collection entries</h2>";
		foreach($res["obsolete"] as $obs){
			echo $obs;
			echo $this->removeObsoleteCollection($obs);
			echo "<br>";
		}
		
		echo "<h2>Fetching missing Movie Pics</h2>";
		$res = $this->store->getMissingPics($this->picturePath);
		foreach($res["missing"] as $miss){
			echo "fetching ".$miss["MOVIE_DB_ID"]."<br>";
			$this->downloadMoviePic($miss["MOVIE_DB_ID"]);
		}
		echo "<h2>Remove obsolete Movie Pics</h2>";
		$this->removeObsoletePics($res["all"], $this->picturePath);
		
		echo "<h2>Resizing images</h2>";
		$this->resizeMoviePics($this->picturePath);
	}
	
	private function downloadMoviePic($id){
		$movie = $this->scraper->getMovieInfo($id);
		if ($movie !== null){
			$this->scraper->downloadPoster($movie->getId(), $movie->getPosterPath());
			return "OK";
		}
		return "No Match";
	}
	
	private function resizeMoviePics($picsDir){
		$images = Util::glob_recursive($picsDir."*big.jpg");
		foreach($images as $image){
			$id = substr($image, strrpos($image, "/") + 1);
			$id = substr($id, 0, strpos($id, "_"));
	
			$dest = $picsDir.$id."_big.jpg";
			$target = $picsDir.$id."_333x500.jpg";
	
			if (file_exists($target)){
				continue;
			}
			echo $dest." - ".$target."<br>";
			Util::resizeImage($dest, $target, 333, 500);
		}
	}
	
	private function removeObsoletePics($movieDBIDS, $picsDir){
		$files = glob($picsDir."*_big.jpg");
			
		foreach($files as $file){
			$id = substr($file, strlen($picsDir));
			$id = substr($id, 0, strpos($id, "_"));
			if (in_array($id, $movieDBIDS)){
				continue;
			}
			else{
				echo "removing ".$id."<br>";
				unlink($file);
				$small = $picsDir.$id."_333x500.jpg";
				if (file_exists($small)){
					unlink($small);
				}
			}
		}
	}
	
	private function checkDuplicateFiles($dir){
		$files = glob($dir."*.avi");
		$titles = array();
		foreach($files as $file){
			$titles[] = $this->getMovieTitle($file);
		}
		$cnts = array_count_values($titles);
		arsort($cnts);
		$result = array();
		foreach($cnts as $title => $cnt){
			if ($cnt > 1){
				$result[] = $title;
			}
			else{
				break;
			}
		}
		
		return $result;
	}
	
	private function getMovieTitle($file){
		$file = preg_replace("/\d{2}\.\d{2}\.\d{2}.*/i", "", $file); //match for yy.mm.dd
		$file = preg_replace("/\.mpg.*/i", "", $file); //match for .mpg
		$file = preg_replace("/\.hq.*/i", "", $file); //match for .hq
		$file = preg_replace("/\.avi.*/i", "", $file); //match for .avi
		$file = str_replace("_", " ", $file);
		$file = trim($file);
		
		return $file;
	}
	
	private function updateCollectionFromScraper($collectionId){
		$collectionData = $this->scraper->getCollectionInfo($collectionId);
		if ($collectionData !== null){
			$this->store->updateCollectionById($collectionData, $collectionId);
		}
		
		return $collectionData;
	}
	
	private function removeObsoleteCollection($collectionId){
		$this->store->removeObsoleteCollection($collectionId);
	}
	
}