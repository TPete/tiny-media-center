<?php

namespace API;

//Wrapper for themoviedb.org API
class TMDBWrapper{
	
	private $apiKey;
	private $baseUrl = "http://api.themoviedb.org/3/";
	private $movieDir;
	private $moviePics;
	private $config;
	
	
	public function __construct($movieDir, $moviePics, $apiKey){
		$this->movieDir = $movieDir;
		$this->moviePics = $moviePics;
		$this->apiKey = $apiKey;
	}
	
	private function curlDownload($url, $args = array()){
		$url = $this->baseUrl.$url."?api_key=".$this->apiKey."&language=de";
		foreach($args as $argName => $argVal){
			$url .= "&".$argName."=".urlencode($argVal);
		}
		
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $output;
	}
	
	private function downloadImage($url, $file){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$raw = curl_exec($ch);
		curl_close ($ch);
		if(file_exists($file)){
			unlink($file);
		}
		$fp = fopen($file,'x');
		fwrite($fp, $raw);
		fclose($fp);
	}
	
	private function fetchConfiguration(){
		$url = "configuration";
		$tmp = $this->curlDownload($url);
		$this->config = json_decode($tmp, true);
	}
	
	public function downloadPoster($id, $path){
		$this->fetchConfiguration();
		$url = $this->config["images"]["base_url"]."original".$path;
		$this->downloadImage($url, $this->moviePics.$id."_big.jpg");
	}
		
	public function getCollectionInfo($id){
		$url = "collection/".$id;
		$data = $this->curlDownload($url);
		$data = json_decode($data, true);
		
		return $data;
	}
	
	public function getMovieInfo($id, $movieDir = "", $filename = ""){
		$url = "movie/".$id;
		$args = array("append_to_response" => "credits");
		$data = $this->curlDownload($url, $args);
		$data = json_decode($data, true);
		$tmp = $data["genres"];
		$genres = array();
		foreach($tmp as $ele){
			$genres[] = $ele["name"];
		}
		$tmp = $data["production_countries"];
		$countries = array();
		foreach($tmp as $ele){
			$countries[] = $ele["iso_3166_1"];
		}
		$credits = $data["credits"];
		$tmp = $credits["cast"];
		$actors = array();
		foreach($tmp as $ele){
			$actors[] = $ele["name"];
		}
		$tmp = $credits["crew"];
		$director = "";
		foreach($tmp as $ele){
			if ($ele["job"] === "Director"){
				$director = $ele["name"];
				break;
			}
		}
		$collection_id = $data["belongs_to_collection"]["id"];
		
		$movieData = array("id" => $id, "title" => $data["title"], "filename" => $filename,
			"overview" => $data["overview"], "poster" => $id."_big.jpg", "poster_path" => $data["poster_path"], 
			"release_date" => $data["release_date"], "genres" => $genres, "countries" => $countries, 
			"actors" => $actors, "director" => $director, "collection_id" => $collection_id,
			"original_title" => $data["original_title"]);
		
		
		$mov = new \Movie($movieData, $movieDir);
		
		return $mov;
	}
	
	/**
	 * Search for a movie using the provided title. If sth. was found fetch the info for the
	 * first id in the result (using getMovieInfo).
	 * 
	 * @param String $title the title to search for
	 * @param String $filename the name of the movie file
	 * @return Ambigous <NULL, \API\Movie>
	 */
	public function searchMovie($title, $filename){
		$url = "search/movie";
		$args = array("query" => $title);
		
		$data = $this->curlDownload($url, $args);
		$data = json_decode($data, true);
		$result = null;
		if (isset($data["results"][0])){
			$id = $data["results"][0]["id"];
			$result = $this->getMovieInfo($id, $this->movieDir, $filename);
		}
		
		
		return $result;
	}
	
}