<?php

class RestAPI{
	
	private $baseUrl;
	
	public function __construct($baseUrl){
		$this->baseUrl = $baseUrl;
	}
	
	private function raiseException($e){
		$exp = new RemoteException($e["error"], $e["trace"]);
		throw $exp;
	}
	
	private function curlDownload($url, $args = array()){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		
		$url = $this->baseUrl.$url;
		$queryString = "?";
		foreach($args as $argName => $argVal){
			$queryString .= $argName."=".urlencode($argVal)."&";
		}
		$queryString = substr($queryString, 0, strlen($queryString) - 1);
		$url .= $queryString;		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($output, true);
		if (isset($response["error"])){
			$this->raiseException($response);
		}
	
		return $response;
	}
	
	private function curlPost($url, $args){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		
		$url = $this->baseUrl.$url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($output, true);
		if (isset($response["error"])){
			$this->raiseException($response);
		}
		
		return $response;
	}
	
//SHOWS
	
	public function getCategoryOverview($category){
		$url = "/shows/".$category;
		$list = $this->curlDownload($url);
		
		return $list;
	}
	
	public function getShowDetails($category, $id){
		$url = "/shows/".$category."/".$id;
		$details = $this->curlDownload($url);
		
		return $details;
	}
	
	public function getEpisodeDescription($category, $id){
		$url = "/shows/".$category."/episodes/".$id;
		$description = $this->curlDownload($url);
	
		return $description;
	}
	
	public function updateShowDetails($category, $id, $title, $tvdbId){
		$url = "/shows/".$category."/edit/".$id;
		$args = array("title" => $title, "tvdbId" => $tvdbId);
		$result = $this->curlPost($url, $args);
	
		return $result;
	}

//MOVIES
	
	public function getMovie($id){
		$url = "/movies/".$id;
		$res = $this->curlDownload($url);
		
		return $res;
	}
	
	public function lookupMovie($id){
		$url = "/movies/lookup/".$id;
		$res = $this->curlDownload($url);
	
		return $res;
	}
	
	public function getMovies($sort, $cnt, $offset, $filter = "", $genres = "", $collection = "0", $list = "0"){
		$url = "/movies/";
		$args = array("sort" => $sort, "cnt" => $cnt, "offset" => $offset, "filter" => $filter, 
				"genre" => $genres, "collection" => $collection, "list" => $list);
				
		$res = $this->curlDownload($url, $args);
	
		return $res;
	}
	
	public function updateMovie($dbID, $movieDBID, $filename){
		$url = "/movies/".$dbID;
		$args = array("movieDBID" => $movieDBID, "filename" => $filename);
		
		$res = $this->curlPost($url, $args);
		
		return $res;
	}
	
	public function getGenres($term){
		$url = "/movies/genres/";
		$args = array("term" => $term);
		
		$res = $this->curlDownload($url, $args);
		
		return $res;
	}
	
	public function getCompilations(){
		$url = "/movies/compilations/";
		$res = $this->curlDownload($url);
	
		return $res;
	}
}