<?php

namespace API;

class TTVDBWrapper extends DBAPIWrapper{

	
	private $apiKey;
	private $imageBaseUrl = "http://thetvdb.com/banners/fanart/original/";
	
	public function __construct($apiKey){
		parent::__construct("http://thetvdb.com/api/");
		$this->apiKey = $apiKey;
	}
	
	public function getSeriesId($name){
		$url = "GetSeries.php?language=de&seriesname=".$name;
		$raw = $this->curlDownload($url);
		$xml = new \SimpleXMLElement($raw);
		$id = $xml->Series[0]->id;
		
		return (string)$id;
	}
	
	public function getSeriesInfoById($id){
		$url = $this->apiKey."/series/".$id."/all/de.xml";
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