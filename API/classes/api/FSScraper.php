<?php
namespace API;
//Scraper for fernsehserien.de

class FSScraper{
	
	private $descriptions;
	
	private function curl_download($url){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.google.de");
		$headers = array(
				"Accept: text/html",
				"Host: www.fernsehserien.de"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/25.0");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
	
		return $output;
	}
	
	private function getEpisodeDescriptions($url){
		$url = "http://www.fernsehserien.de/".$url;	
		$raw = $this->curl_download($url);
		$dom = new \DOMDocument();
		@$dom->loadHTML($raw); //html5 elements, like header, are not supported, so errors are surpressed
		$xpath = new \DOMXPath($dom);
		$divs = $xpath->query("//div[@class='episode-output-inhalt']");
		
		$descriptions = array();
		for($i = 0; $i < $divs->length; $i++){
			$ps = $xpath->query("p", $divs->item($i));
			
			$text = "";
			foreach($ps as $p){
				$text .= "<p>".$p->nodeValue."</p>";
			}
			$descriptions[] = $text;
		}
		
		return $descriptions;
	}
	
	/**
	 * Parse episode list. Result will be empty array if url is wrong.
	 * 
	 * @param unknown $data
	 * @return multitype:multitype:
	 */
	private function parseData($data){
		$dom = new \DOMDocument();
		@$dom->loadHTML($data); //html5 elements, like header, are not supported, so errors are surpressed
		$xpath = new \DOMXPath($dom);
		$tbodies = $xpath->query("//table[@class='episodenliste']/tbody[@itemtype='http://schema.org/TVSeason']");
		
		$seasons = array();
		$seasonCnt = 0;
		foreach($tbodies as $tbody){
			$trs = $xpath->query("tr", $tbody);
			$first = true;
			$episodes = array();
			$label = "";
			$episodeCnt = 0;
			$this->descriptions = null;
			foreach($trs as $tr){
				if ($first){
					$first = false;
					$label = trim($tr->nodeValue);
					if (substr($label, 0, strlen("Staffel")) !== "Staffel"){
						//ignore specials
						break;
					}
					else{
						$seasonNo = substr($label, strpos($label, " ") + 1);
						$seasonNo = intval(trim($seasonNo), 10);
						if ($seasonNo < $seasonCnt){
							break; //ignore web series and stuff
						}
					}
				}
				else{
					$td = $xpath->query("td[@class='episodenliste-titel']", $tr);
					if ($td->length > 0){
						$attr = $xpath->query("@data-href", $td->item(0));
						$descriptionUrl = $attr->item(0)->nodeValue;
						$descriptionUrl = substr($descriptionUrl, 0, strpos($descriptionUrl, "#"));
						if ($this->descriptions === null){
							$this->descriptions = $this->getEpisodeDescriptions($descriptionUrl);
						}
						
						$span = $xpath->query("span[@itemprop='name']", $td->item(0));
						if ($span->length > 0){
							$title = trim($span->item(0)->nodeValue);
						}
						else{
							$title = "unbekannt";
						}
						if ($episodeCnt >= count($this->descriptions)){
							$desc = "";
						}
						else{
							$desc = $this->descriptions[$episodeCnt];
						}
						$episodes[] = array("title" => $title, "description" => $desc);
						$episodeCnt++;
					}
					else{
						//ignore (empty tr spacer)
					}
				}
			}
			if (count($episodes) > 0){
				if (isset($seasons[$label])){
					$label = "Extra ".$label;
				}
				$seasons[$label] = $episodes;
				$seasonCnt++;
			}
		}
		
		return $seasons;
	}
	
	public function scrape($url){
		$raw = $this->curl_download($url);
		$data = $this->parseData($raw);
		
		return $data;
	}
}