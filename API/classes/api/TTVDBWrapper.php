<?php

namespace API;

class TTVDBWrapper{

	
	private $apiKey = "2F0A44BB473DF27C";
	private $baseUrl = "http://thetvdb.com/api/";
	private $imageBaseUrl = "http://thetvdb.com/banners/fanart/original/";
	
	public function __construct(){
		
	}
	
	private function curlDownload($url, $args = array()){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
	
		return $output;
	}
	
	private function downloadImage($url, $file){
		var_dump($url);
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
	
	public function getServerTime(){
		$url = $this->baseUrl."Updates.php?type=none";
		$raw = $this->curlDownload($url);
		$xml = new \SimpleXMLElement($raw);
		$time = $xml->Time;
		
		return (string)$time;
	}
	
	public function getSeriesId($name){
		$url = $this->baseUrl."GetSeries.php?language=de&seriesname=".$name;
		$raw = $this->curlDownload($url);
		$xml = new \SimpleXMLElement($raw);
		$id = $xml->Series[0]->id;
		
		return (string)$id;
	}
	
	public function getSeriesInfoById($id){
		$url = $this->baseUrl.$this->apiKey."/series/".$id."/all/de.xml";
		$raw = $this->curlDownload($url);
		$xml = new \SimpleXMLElement($raw);
		$rawEpisodes = $xml->Episode;
		$seasons = array();
		foreach($rawEpisodes as $re){
			$seasonNumber = (int)$re->SeasonNumber;
			if ($seasonNumber === 0){//skip specials
				continue;
			}
			if (!isset($seasons[$seasonNumber])){
				$seasons[$seasonNumber] = array();
			}
			$episodeNumber = (int)$re->EpisodeNumber;
			$seasons[$seasonNumber][$episodeNumber] = array("title" => (string)$re->EpisodeName, "description" => (string)$re->Overview);
		}
		
		return $seasons;
	}
	
	public function getSeriesInfoByName($name){
		$id = $this->getSeriesId($name);
		$info = $this->getSeriesInfoById($id);
		
		return $info;
	}
	
	public function downloadBG($seriesId, $path){
		$url = $this->imageBaseUrl.$seriesId."-1.jpg";
		$this->downloadImage($url, $path);
	}

}