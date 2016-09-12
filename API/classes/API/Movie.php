<?php
namespace TinyMediaCenter\API;

class Movie{

	private $filename;
	private $title;
	private $original_title;
	private $id;
	private $overview;
	private $poster;
	private $poster_path;
	private $release_date;
	private $countries;
	private $genres;
	private $actors;
	private $director;
	private $info;
	private $collection_id;
	private $empty;
	
	private $movieDir;
	
	public function __construct($movieData = array(), $movieDir){
		$this->movieDir = $movieDir;
		if (count($movieData) > 0){
			$this->filename = $movieData["filename"];
			$this->title = $movieData["title"];
			$this->original_title = $movieData["original_title"];
			$this->id = $movieData["id"];
			$this->overview = $movieData["overview"];
			$this->poster = $movieData["poster"];
			$this->poster_path = $movieData["poster_path"];
			$this->release_date = $movieData["release_date"];
			$this->countries = $movieData["countries"];
			$this->genres = $movieData["genres"];
			$this->director = $movieData["director"];
			$this->collection_id = $movieData["collection_id"];
			$this->actors = array();
			foreach($movieData["actors"] as $act){
				$this->actors[] = str_replace(" ", "&nbsp;", $act);
			}
			if (isset($movieData["info"])){
				$this->info = $movieData["info"];
			}
			else{
				$this->info = "";
			}
			$this->empty = false;
		}
		else{
			$this->empty = true;
		}
	}
		
	public function getTitle(){
		return $this->title;
	}
	
	public function getFilename(){
		return $this->filename;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getOverview(){
		return $this->overview;
	}
	
	public function getPoster(){
		return $this->poster;
	}
	
	public function getPosterPath(){
		return $this->poster_path;
	}
	
	public function getCollectionId(){
		return $this->collection_id;
	}
	
	public function getReleaseDate(){
		return $this->release_date;
	}
	
	public function getGenres(){
		return $this->genres;
	}
	
	public function getDirector(){
		return $this->director;
	}
	
	public function getActors($limit = 0){
		$res = array();
		if ($limit > 0){
			foreach($this->actors as $actor){
				$limit--;
				if ($limit >= 0){
					$res[] = $actor;
				}
			}
		}
		else{
			$res = $this->actors;
		}
		
		return $res;
	}
	
	private function getInfo(){
		if ($this->info === "" and $this->filename !== ""){
			$getID3 = new \getID3();
			
			$fileInfo = $getID3->analyze($this->movieDir.$this->filename);
			$duration = $fileInfo["playtime_string"];
			$tmp = substr($fileInfo["playtime_string"], 0, strrpos($duration, ":"));
			if (strpos($tmp, ":") !== false){
				$duration = $tmp;
			}
			else{
				$duration = "0:".$tmp;
			}
			$duration .= "&nbsp;h";
			$resolution = $fileInfo["video"]["resolution_x"]."&nbsp;x&nbsp;".$fileInfo["video"]["resolution_y"];
			$sound = $fileInfo["audio"]["channels"];
			if ($sound === 2){
				$sound = "Stereo"; 
			}
			if ($sound === "5.1"){
				$sound = "DD&nbsp;5.1";
			}
			$this->info = $duration.", ".$resolution.", ".$sound;
		}
		
		return $this->info;
	}
	
	public function __toString(){
		$str = "Movie: [";
		$str .= "Title: ".$this->title.", ";
		$str .= "Id: ".$this->id.", ";
		$str .= "File: ".$this->filename."]";
		return $str;
	}
	
	public function toArray(){
		$res = array("id" => $this->id, "title" => $this->title, "filename" => $this->filename,
			"overview" => $this->overview, "poster" => $this->poster, 
			"release_date" => $this->release_date, "genres" => $this->genres, "countries" => $this->countries, 
			"actors" => $this->actors, "director" => $this->director, "info" => $this->getInfo(),
			"original_title" => $this->original_title, "collection_id" => $this->collection_id, "empty" => $this->empty);
		
		return $res;
	}
	
	public function getDetails(){
		$res = array("id" => $this->id, "title" => $this->title, "filename" => $this->filename,
				"overview" => $this->overview, "poster" => $this->poster,
				"year" => substr($this->release_date, 0, 4), "genres" => $this->genres, "countries" => $this->countries,
				"actors" => $this->getActors(4), "director" => $this->director, "info" => $this->getInfo());
		
		return $res;
	}
	
	public function toJSON(){
		$res = $this->toArray();
		
		return json_encode($res);
	}
	
	public function isEmpty(){
		return $this->empty;
	}
}