<?php

use API;
require "classes/Movie.php";
require "classes/getID3/getid3/getid3.php";

require "classes/api/Util.php";
require "classes/api/TTVDBWrapper.php";
require "classes/api/ShowController.php";
require "classes/api/ShowStoreDB.php";
require "classes/api/TMDBWrapper.php";
require "classes/api/MovieController.php";
require "classes/api/MovieStoreDB.php";

require 'lib/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
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

function handleException($exception){
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

$app->post('/config', 
		function() use ($app){
			$config = array();
			$config["pathMovies"] = $_POST["pathMovies"];
			$config["aliasMovies"] = $_POST["aliasMovies"];
			$config["moviePics"] = $_POST["moviePics"];
			$config["aliasMoviePics"] = $_POST["aliasMoviePics"];
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

$app->group('/shows', function() use ($app, $config, $db){

	$ShowController = new API\ShowController($config["pathShows"], $config["aliasShows"], 
						$db, $config["TTVDBApiKey"]);
	
	$app->get('/maintenance/',
			function() use ($ShowController){
				try{
					$ShowController->maintenance("Serien");
					$ShowController->maintenance("Kinder");
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
					echo $ShowController->updateDetails($category, $id, $_POST["title"], $_POST["tvdbId"]);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
		
});

$app->group('/movies', function() use ($app, $config, $db){
	
	$MovieController = new API\MovieController($config["pathMovies"], $config["aliasMovies"], 
			$config["moviePics"], $config["aliasMoviePics"], $db, $config["TMDBApiKey"]);
	
	$app->get('/', 
			function() use ($MovieController) {
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
						$movieList = $MovieController->getMoviesForCollection($collection, $cnt, $offset);
					}
					if ($list > 0){
						$movieList = $MovieController->getMoviesForList($list, $cnt, $offset);
					}
					if ($list === 0 and $collection === 0){
						$movieList = $MovieController->getMovies($sort, $order, $filter, $genre, $cnt, $offset);
					}				
					
					echo json_encode($movieList);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/genres/',
			function() use ($MovieController){
				try{
					$genres = $MovieController->getGenres();
					
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
	
	$app->get('/compilations/',
			function() use ($MovieController){
				try{
					$lists = $MovieController->getLists();
					$collections = $MovieController->getCollections();
					$comp = array("lists" => $lists, "collections" => $collections);
					
					echo json_encode($comp);
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/maintenance',
			function() use ($MovieController){
				try{
					$MovieController->maintenance();
				}
				catch(Exception $e){
					handleException($e);
				}
			});
	
	$app->get('/:id',
			function($id) use ($MovieController) {
				try{
					$id = intval($id, 10);
					$details = $MovieController->getMovieDetails($id);
					
					echo json_encode($details);
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
		
	$app->post('/:id',
			function($id) use ($MovieController) {
				try{
					$id = intval($id, 10);
					$movie = $MovieController->updateFromScraper($id, $_POST["movieDBID"], $_POST["filename"]);
		
					if ($movie !== null){
						echo "OK:".$movie;
					}
					else{
						echo "Error";
					}
				}
				catch(Exception $e){
					handleException($e);
				}
			});
});

$app->run();