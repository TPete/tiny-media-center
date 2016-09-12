<?php

set_time_limit(900);
require "vendor/autoload.php";
require "vendor/james-heinrich/getid3/getid3/getid3.php";

use TinyMediaCenter\API;

$app = new Slim\Slim(array(
    'templates.path' => 'templates/'
));

$config = API\Util::readJSONFile("config.json");
$db = array("host" => $config["dbHost"],
			"name" => $config["dbName"],
			"user" => $config["dbUser"],
			"password" => $config["dbPassword"]);

function initGET($var, $default = "", $toInt = false){
	$res = isset($_GET[$var]) ? $_GET[$var] : $default;
	$res = trim($res);
	if ($toInt){
		$res = intval($res, 10);
	}

	return $res;
}

function handleException(Exception $exception){
	$error = array("error" => $exception->getMessage(), "trace" => $exception->getTrace());
	echo json_encode($error);
};

$app->get('/', function(){
		echo "nothing to see here";
});

$app->get('/config', 
		function(){
			$file = "config.json";
			if (!file_exists($file)){
				$file = "example_config.json";
			}
			$config = API\Util::readJSONFile($file);
			echo json_encode($config);
		});

$app->get('/config/check/:type',
		function($type){
			if ($type === "db"){
				$res = array();
				try{
					$db = new PDO("mysql:host=".$_GET["host"].";dbname=".$_GET["name"], $_GET["user"], $_GET["password"]);
					$res["dbAccess"] = "Ok";
					$db = array("host" => $_GET["host"],
							"name" => $_GET["name"],
							"user" => $_GET["user"],
							"password" => $_GET["password"]);
					$ShowStore = new API\ShowStoreDB($db);
					$checkShows = $ShowStore->checkSetup();
					$MovieStore = new API\MovieStoreDB($db, "", "");
					$checkMovies = $MovieStore->checkSetup();
					if ($checkShows && $checkMovies){
						$res["dbSetup"] = "Ok";
					}
					else{
						$res["dbSetup"] =  "Error";
					}
				}
				catch (PDOException $e){
					$res["dbAccess"] =  "Error: ".$e->getMessage();
					$res["dbSetup"] =  "Error";
				}
				echo json_encode($res);
			}
			if ($type === "movies"){
				if (is_dir($_GET["pathMovies"]) and is_writable($_GET["pathMovies"]) 
					and API\Util::checkUrl($_GET["aliasMovies"])){
					$res["result"] = "Ok";
				}
				else{
					$res["result"] =  "Error";
				}
				echo json_encode($res);
			}
			if ($type === "shows"){
				if (is_dir($_GET["pathShows"]) and is_writable($_GET["pathShows"]) 
					and API\Util::checkUrl($_GET["aliasShows"])){
					$res["result"] = "Ok";
					$folders = API\Util::getFolders($_GET["pathShows"]);
					$res["folders"] = $folders;
				}
				else{
					$res["result"] =  "Error";
				}
				echo json_encode($res);
			}
		});

$app->post('/config', 
		function() use ($app){
			$config = array();
			$config["pathMovies"] = $_POST["pathMovies"];
			$config["aliasMovies"] = $_POST["aliasMovies"];
			$config["pathShows"] = $_POST["pathShows"];
			$config["aliasShows"] = $_POST["aliasShows"];
			$config["dbHost"] = $_POST["dbHost"];
			$config["dbName"] = $_POST["dbName"];
			$config["dbUser"] = $_POST["dbUser"];
			$config["dbPassword"] = $_POST["dbPassword"];
			$config["TMDBApiKey"] = $_POST["TMDBApiKey"];
			$config["TTVDBApiKey"] = $_POST["TTVDBApiKey"];
			
			API\Util::writeJSONFile("config.json", $config);
			
			$app->redirect('/install');
		});

$app->post('/config/db',
		function() use($db){
			try{
				$ShowStore = new API\ShowStoreDB($db);
				$checkShows = $ShowStore->checkSetup();
				$MovieStore = new API\MovieStoreDB($db, "", "");
				$checkMovies = $MovieStore->checkSetup();
				if (!$checkShows && !$checkMovies){
					$ShowStore->setupDB();
					$MovieStore->setupDB();
					echo "Ok";
				}
				else{
					echo "Error";
				}
			}
			catch(Exception $e){
				handleException($e);
			}
		});

$app->get('/categories',
		function() use($config, $db){
			//expects tv shows to be in sub folders of $config["pathShows"]
			//where each sub folder will be listed as a different category
			
			//expects movies to be directly in $config["pathMovies"]
			//which will be listed as a single category
			//TODO: make this consistent and/or more flexible
			$ShowController = new API\ShowController($config["pathShows"], $config["aliasShows"],
								$db, $config["TTVDBApiKey"]);
			$shows = $ShowController->getCategories();
			$MovieController = new API\MovieController($config["pathMovies"], $config["aliasMovies"],
								$db, $config["TMDBApiKey"]);
			$movies = $MovieController->getCategories();
			$categories = array_merge($shows, $movies);
			
			echo json_encode($categories);
		});

$app->group('/shows', function() use ($app, $config, $db){

	$ShowController = new API\ShowController($config["pathShows"], $config["aliasShows"], 
						$db, $config["TTVDBApiKey"]);
	
	$app->post('/maintenance/',
			function() use ($ShowController){
				try{
					$result = $ShowController->updateData();
					echo json_encode($result);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/',
			function($category) use ($ShowController) {
				try{
					$list = $ShowController->getList($category);
					echo json_encode($list);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/episodes/:id/',
			function($category, $id) use ($ShowController) {
				try{
					$description = $ShowController->getEpisodeDescription($category, $id);
					echo json_encode($description);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/:id/',
			function($category, $id) use ($ShowController) {
				try{
					$details = $ShowController->getDetails($category, $id);
					echo json_encode($details);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->post('/:category/edit/:id/',
			function($category, $id) use ($ShowController) {
				try{
					$tvdbid = (int)$_POST["tvdbId"];
					echo $ShowController->updateDetails($category, $id, $_POST["title"], $tvdbid, $_POST["lang"]);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
		
});

$app->group('/movies', function() use ($app, $config, $db){
	
	$MovieController = new API\MovieController($config["pathMovies"], $config["aliasMovies"], 
			$db, $config["TMDBApiKey"]);
	
	$app->get('/:category/', 
			function($category) use ($MovieController) {
				try{
					$orgSort = initGET("sort", "name_asc");
					$split = explode("_", $orgSort);
					$sort = $split[0];
					$order = $split[1];
					$filter = initGET("filter");
					$genre = initGET("genre");
					$cnt = initGET("cnt", -1, true);
					$offset = initGET("offset", 0, true);
					$collection = initGET("collection", 0, true);
					$list = initGET("list", 0, true);
					
					if ($collection > 0){
						$movieList = $MovieController->getMoviesForCollection($category, $collection, $cnt, $offset);
					}
					if ($list > 0){
						$movieList = $MovieController->getMoviesForList($category, $list, $cnt, $offset);
					}
					if ($list === 0 and $collection === 0){
						$movieList = $MovieController->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset);
					}				
					
					echo json_encode($movieList);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/genres/',
			function($category) use ($MovieController){
				try{
					$genres = $MovieController->getGenres($category);
					
					$resp = array();
					$comp = initGET("term");
					$comp = mb_strtolower($comp);
					$l = strlen($comp);
					foreach($genres as $gen){
						if (substr(mb_strtolower($gen), 0, $l) === $comp){
							$resp[] = $gen;
						}
					}
						
					echo json_encode($resp);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/compilations/',
			function($category) use ($MovieController){
				try{
					$lists = $MovieController->getLists($category);
					$collections = $MovieController->getCollections($category);
					$comp = array("lists" => $lists, "collections" => $collections);
					
					echo json_encode($comp);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->post('/maintenance',
			function() use ($MovieController){
				try{
					$result = $MovieController->updateData();
					
					echo json_encode($result);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/lookup/:id',
			function($id) use ($MovieController) {
				try{
					$id = intval($id, 10);
					$details = $MovieController->lookupMovie($id);
	
					echo json_encode($details);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:category/:id',
			function($category, $id) use ($MovieController) {
				try{
					$id = intval($id, 10);
					$details = $MovieController->getMovieDetails($category, $id);
					
					echo json_encode($details);
				}
				catch(Exception $e){
					handleException($e);
				}
			});	
		
	$app->post('/:category/:id',
			function($category, $id) use ($MovieController) {
				echo "updating";
				try{
					$id = intval($id, 10);
					$res = $MovieController->updateFromScraper($category, $id, $_POST["movieDBID"], $_POST["filename"]);
					var_dump($res);
					echo $res;
				}
				catch(Exception $e){
					handleException($e);
				}
			});
});

$app->run();