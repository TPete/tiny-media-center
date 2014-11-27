<?php
namespace API;

class ShowStoreDB{

	private $host;
	private $db;
	private $user;
	private $password;
	private $tables = array("shows", "show_episodes");

	public function __construct($config){
		$this->host = $config["host"];
		$this->db = $config["name"];
		$this->user = $config["user"];
		$this->password = $config["password"];
	}
	
	private function connect(){
		$db = new \PDO("mysql:host=".$this->host.";dbname=".$this->db, $this->user, $this->password);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		return $db;
	}
	
	public function checkSetup(){
		$db = $this->connect();
		$result = true;
		foreach ($this->tables as $table){
			try{
				$sql = "SELECT 1 FROM ".$table." LIMIT 1;";
				$stmt = $db->prepare($sql);
				$stmt->execute();
				$result = $result && true;
			}
			catch (\PDOException $e){
				$result = false;
			}
		}
		return $result;
	}
	
	public function setupDB(){
		$db = $this->connect();
		foreach ($this->tables as $table){
			$sql = file_get_contents("sql/".$table.".sql");
			$stmt = $db->prepare($sql);
			$stmt->execute();
		}
	}
	
	public function getShows($category){
		$db = $this->connect();
		$sql = "Select id, title, folder, tvdb_id
				From shows
				Where category = :category 
				order by title";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$shows = array();
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$shows[] = $row;
		}
		
		return $shows;
	}
	
	public function getShowDetails($category, $folder){
		$db = $this->connect();
		$sql = "Select id, title, folder, tvdb_id
				From shows
				Where category = :category and folder = :folder 
				order by title";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
		$stmt->execute();
		$show = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		return $show;
	}
	
	public function getEpisodes($category, $folder){
		$db = $this->connect();
		$sql = "Select ep.season_no, ep.episode_no, ep.title, ep.id
				From shows sh
				Join show_episodes ep on sh.id = ep.show_id
				Where sh.category = :category and sh.folder = :folder 
				Order by ep.season_no, ep.episode_no";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
		$stmt->execute();
		$episodes = array();
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
			$episodes[] = $row;
		}
	
		return $episodes;
	}
	
	public function getEpisodeDescription($category, $id){
		$db = $this->connect();
		$sql = "Select concat( season_no, 'x', lpad(episode_no, 2, '0'), ' ', title ) title, description
				From show_episodes
				Where id = :id
				order by title";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":id", $id, \PDO::PARAM_STR);
		$stmt->execute();
		$desc = $stmt->fetch(\PDO::FETCH_ASSOC);

		return $desc;
	}
	
	public function updateDetails($category, $folder, $title, $tvdbId){
		$db = $this->connect();
		$sql = "Update shows
				set Title = :title,
				TVDB_ID = :tvdb_id
				Where category = :category and folder = :folder";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":title", $title, \PDO::PARAM_STR);
		$stmt->bindValue(":tvdb_id", $tvdbId, \PDO::PARAM_INT);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
		$stmt->execute();
	}
	
	public function createIfMissing($category, $folder){
		$db = $this->connect();
		$sql = "Select count(*) cnt
				From shows
				Where category = :category and folder = :folder";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if ($row["cnt"] === "0"){
			$title = str_replace("-", " ", $folder);
			$sql = "Insert into shows(category, folder, title)
					Values (:category, :folder, :title)";
			$stmt = $db->prepare($sql);
			$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
			$stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
			$stmt->bindValue(":title", $title, \PDO::PARAM_STR);
			$stmt->execute();
			echo "Added ".$folder."<br>";
		}
	}
	
	public function removeIfObsolete($category, $folders){
		$db = $this->connect();
		$sql = "Select folder
				From shows
				Where category = :category";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(":category", $category, \PDO::PARAM_STR);
		$stmt->execute();
		$dbFolders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$sqlShows = "Delete 
				From shows
				Where category = :category and folder = :folder";
		$stmtShows = $db->prepare($sqlShows);
		$stmtShows->bindValue(":category", $category, \PDO::PARAM_STR);
		foreach($dbFolders as $row){
			if (!in_array($row["folder"], $folders)){
				$stmtShows->bindValue(":folder", $row["folder"], \PDO::PARAM_STR);
				$stmtShows->execute();
				echo "Removed ".$row["folder"]."<br>";
			}
		}
	}
	
	public function updateEpisodes($showId, $seasons){
		$sqlDelete = "Delete
					From show_episodes
					Where show_id = :showId";
		$sqlInsert = "Insert into show_episodes(show_id, season_no, episode_no, title, description)
					Values(:showId, :seasonNo, :episodeNo, :title, :description)";
		$db = $this->connect();
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->bindValue(":showId", $showId);
		$stmtDelete->execute();
		$stmtInsert = $db->prepare($sqlInsert);
		$seasonCnt = 1;
		foreach($seasons as $episodes){
			$episodeCnt = 1;
			foreach($episodes as $episode){				
				$stmtInsert->bindValue(":showId", $showId, \PDO::PARAM_INT);
				$stmtInsert->bindValue(":seasonNo", $seasonCnt, \PDO::PARAM_INT);
				$stmtInsert->bindValue(":episodeNo", $episodeCnt, \PDO::PARAM_INT);
				$stmtInsert->bindValue(":title", $episode["title"], \PDO::PARAM_STR);
				$stmtInsert->bindValue(":description", $episode["description"], \PDO::PARAM_STR);
				$stmtInsert->execute();
				$episodeCnt++;
			}
			$seasonCnt++;
		}
	}
}