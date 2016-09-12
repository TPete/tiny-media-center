<?php
namespace TinyMediaCenter\API;

abstract class Store{
	
	private $host;
	private $db;
	private $user;
	private $password;
	private $tables;
	
	public function __construct($config, $tables){
		$this->host = $config["host"];
		$this->db = $config["name"];
		$this->user = $config["user"];
		$this->password = $config["password"];
		$this->tables = $tables;
	}
	
	protected function connect(){
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
}