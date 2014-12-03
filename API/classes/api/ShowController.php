<?php
namespace API;

class ShowController extends Controller{
		
	public function __construct($path, $alias, $dbConfig, $apiKey){
		$store = new ShowStoreDB($dbConfig);
		$scraper = new TTVDBWrapper($apiKey);
		parent::__construct($path, $alias, $store, $scraper);
	}
	
	public function getCategories(){
		$folders = Util::getFolders($this->path);
		$shows = array();
		foreach($folders as $folder){
			$shows["shows/".strtolower($folder)."/"] = $folder;
		}
		
		return $shows;
	}
	
	public function getList($category){
		$overview = $this->store->getShows($category);
		$result = array();
		foreach($overview as $show){
			$result[] = array("folder" => $show["folder"], "title" => $show["title"],
					"tvdb_id" => $show["tvdb_id"],  
					"thumbUrl" => $this->alias.$category."/".$show["folder"]."/thumb_200.jpg");
		}
		
		return $result;
	}
	
	public function getDetails($category, $id){
		$episodesData = $this->store->getEpisodes($category, $id);
		$showDetails = $this->store->getShowDetails($category, $id);
		$base = $this->path.$category."/".$id."/";
		$files = glob($base."*.avi");
		
		$episodesArray = array();
		$season = array();
		$current = 0;
		foreach($episodesData as $ep){
			if ($current !== $ep["season_no"]){
				if ($current > 0){
					$episodesArray["Staffel ".$current] = $season;
				}
				$current = $ep["season_no"];
				$season = array();
			}
			$season[$ep["episode_no"]] = array("title" => $ep["title"], "id" => $ep["id"]); 	
		}
		$episodesArray["Staffel ".$current] = $season;
		
		$showData = array();
		$seasonData = array();
		$seasonNo = 0;
		$lastSeason = "";
		foreach ($episodesArray as $season => $episodes){
			if (count($seasonData) > 0){
				$showData[] = array("title" => $lastSeason, "episodes" => $seasonData);
			}
			$lastSeason = $season;
			$seasonData = array();
			$seasonNo++;
			$episodeNo = 0;
			foreach($episodes as $episode){
				$episodeNo++;
				$link = $this->getFileLink($seasonNo, $episodeNo, $files, $this->path);
				$label = sprintf("%02d", $episodeNo)." - ".$episode["title"];
				if ($link !== false){
					$seasonData[] = array("link" => $this->alias.$link, "label" => $label, "id" => $episode["id"]);
				}
				else{
					$seasonData[] = array("link" => "", "label" => $label, "id" => $episode["id"]);
				}
			}
		}
		if (count($seasonData) > 0){
			$showData[] = array("title" => $lastSeason, "episodes" => $seasonData);;
		}
		
		$result = array("title" => $showDetails["title"], "seasons" => $showData,
				"tvdbId" => $showDetails["tvdb_id"], "imageUrl" => $this->alias.$category."/".$id."/bg.jpg");
		
		return $result;
	}
	
	public function getEpisodeDescription($category, $id){
		return $this->store->getEpisodeDescription($category, $id);
	}
	
	public function updateDetails($category, $id, $title, $tvdbId){
		return $this->store->updateDetails($category, $id, $title, $tvdbId);
	}
	
	public function updateData(){
		$protocol = "";
		$folders = Util::getFolders($this->path);
		foreach($folders as $folder){
			$protocol .= $this->maintenance($folder);
		}
		
		return array("result" => "Ok", "protocol" => $protocol);
	}
	
	private function maintenance($category){
		$protocol = "";
		$protocol .= "<h2>Maintenance ".$category."</h2>";
		$protocol .= "<h3>Check missing show entries (new shows)</h3>";
		$protocol .= $this->addMissingShows($category);
		
		$protocol .= "<h3>Check obsolete show entries (removed shows)</h3>";
		$protocol .= $this->removeObsoleteShows($category);
		
		$protocol .= "<h3>Update episodes</h3>";
		$protocol .= $this->updateEpisodes($category);
		
		$protocol .= "<h3>Update thumbnails</h3>";
		$this->updateThumbs($category);
		
		return $protocol;
	}
	
	private function addMissingShows($category){
		$protocol = "";
		$folders = Util::getFolders($this->path.$category."/");
		foreach($folders as $folder){
			$protocol .= $this->store->createIfMissing($category, $folder);
		}
		
		return $protocol;
	}
	
	private function removeObsoleteShows($category){
		$folders = Util::getFolders($this->path.$category."/");
		return $this->store->removeIfObsolete($category, $folders);
	}
	
	private function updateEpisodes($category){
		$protocol = "";
		$shows = $this->store->getShows($category);
		foreach($shows as $show){
			$protocol .= "Updating ".$show["title"]." ... ";
			if ($show["tvdb_id"] === null){
				$search = urlencode($show["title"]);
				$id = $this->scraper->getSeriesId($search);
				$path = $this->path.$category."/".$show["folder"]."/bg.jpg";
				$this->scraper->downloadBG($id, $path);
				$this->store->updateDetails($category, $show["folder"], $show["title"], $id);
			}
			else{
				$id = $show["tvdb_id"];
			}
			$protocol .= "Scraping ... ";
			$seasons = $this->scraper->getSeriesInfoById($id);
			if (count($seasons) > 0){
				$this->store->updateEpisodes($show["id"], $seasons);
				$protocol .= "Done";
			}
			else{
				$protocol .= "Scraping failed (check ID)";
			}
			
			$protocol .= "<br>";
		}
		
		return $protocol;
	}
	
	private function updateThumbs($category){
		$protocol = "";
		$folders = Util::getFolders($this->path.$category."/");
		foreach($folders as $folder){
			$path = $this->path.$category."/".$folder."/";
			$protocol .= $path;
			$dim = 200;
			Util::resizeImage($path."bg.jpg", $path."thumb_".$dim.".jpg", $dim, $dim);
			$protocol .= "done";
			$protocol .= "<br>";
		}
		
		return $protocol;
	}
		
	private function getFileLink($seasonNo, $episodeNo, $files, $baseDir){
		if (strlen($episodeNo) === 1){
			$episodeNo = "0".$episodeNo;
		}
		foreach($files as $file){
			if (strpos($file, "_".$seasonNo."x".$episodeNo) !== false){
				$link = str_replace($baseDir, "", $file);
				$link = str_replace("\\", "/", $link);
				return $link;
			}
		}
	
		return false;
	}
	
}