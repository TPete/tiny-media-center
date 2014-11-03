<?php
require 'lib/Slim/Slim.php';
require 'classes/RestApi.php';
require 'classes/RemoteException.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
		'templates.path' => 'templates/'
));

$config = getConfig();
$api = new RestAPI($config["restUrl"]);

function getConfig(){
	$config = readJSONFile("config.json");
	$dir = $_SERVER["SCRIPT_NAME"];
	$dir = substr($dir, 0, strrpos($dir, "/"));
	$config["host"] = $_SERVER["HTTP_HOST"].$dir;
	
	return $config;
}

function readJSONFile($file){
	$fileData = file_get_contents($file);
	if(!mb_check_encoding($fileData, 'UTF-8')){
		$fileData = utf8_encode($fileData);
	}
	$res = json_decode($fileData, true);

	return $res;
}

function initGET($var, $default = "", $toInt = false){
	$res = isset($_GET[$var]) ? $_GET[$var] : $default;
	$res = trim($res);
	if ($toInt){
		$res = intval($res, 10);
	}
	
	return $res;
}

function getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list){
	if ($offset > 0){
		$offsetPrev = $offset - $cnt;
		if ($offsetPrev < 0){
			$offsetPrev = 0;
		}
		if ($collection === 0 and $list === 0){
			$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres,	"offset" => $offsetPrev);
		}
		else{
			if ($collection > 0){
				$tmp = array("collection" => $collection, "offset" => $offsetPrev);
			}
			if ($list > 0){
				$tmp = array("list" => $list, "offset" => $offsetPrev);
			}
		}
		$previous = http_build_query($tmp);
		$previousClass = "";
	}
	else{
		$previous = "javascript: void(0);";
		$previousClass = "disabled";
	}
	
	return array("link" => $previous, "class" => $previousClass);
}

function getNextLink($offset, $cnt, $moviesCnt, $sort, $filter, $genres, $collection, $list){
	if ($offset + 2*$cnt <= $moviesCnt){
		$offsetNext = $offset + $cnt;
		if ($collection === 0 and $list === 0){
			$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres, "offset" => $offsetNext);
		}
		else{
			if ($collection > 0){
				$tmp = array("collection" => $collection, "offset" => $offsetNext);
			}
			if ($list > 0){
				$tmp = array("list" => $list, "offset" => $offsetNext);
			}
		}
		$next = http_build_query($tmp);
		$nextClass = "";
	}
	else{
		if ($moviesCnt - $cnt > $offset){
			$offsetNext = $moviesCnt - $cnt;
			if ($collection === 0 and $list === 0){
				$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres, "offset" => $offsetNext);
			}
			else{
				if ($collection > 0){
					$tmp = array("collection" => $collection, "offset" => $offsetNext);
				}
				if ($list > 0){
					$tmp = array("list" => $list, "offset" => $offsetNext);
				}
			}
			$next = http_build_query($tmp);
			$nextClass = "";
		}
		else{
			$next = "javascript: void(0);";
			$nextClass = "disabled";
		}
	}
	
	return array("link" => $next, "class" => $nextClass);
}

function renderException($exp, $config, $app){
	$header = "Error";
	$app->render("pageHeader.php", array("pageTitle" => $header, "host" => $config["host"]));
	$app->render("headerBarShows.php", array("header" => $header, "showEditButton" => false));
	$app->render("error.php", array("message" => $exp->getMessage(), "trace" => $exp->getStackTrace()));
	$app->render("pageFooter.php", array("host" => $config["host"]));
}

$app->get('/',
		function () use($app, $config){
			$pageTitle = "Main Index";
			$header = "TV";
			$app->render("pageHeader.php", array("pageTitle" => $header." Index", "host" => $config["host"]));
			$app->render("headerBarMovies.php", array("header" => $header));
			$categories = array("shows/serien/" => "Serien", "shows/kinder/" => "Kinder", "movies/" => "Filme");
			$app->render("categorySelection.php", array("categories" => $categories));
			$app->render("pageFooter.php", array("host" => $config["host"]));
		});

$app->get('/install', 
		function() use($app, $config){
			$header = "Install";
			$app->render("pageHeader.php", array("pageTitle" => $header." Index", "host" => $config["host"]));
			$app->render("headerBarMovies.php", array("header" => $header));
			$file = "config.json";
			if (!file_exists($file)){
				$file = "example_config.json";
			}
			$config = readJSONFile($file);
			$app->render("install.php", $config);
			$app->render("pageFooter.php", array("host" => $config["host"]));
		});

$app->group('/shows', function() use ($app, $config, $api) {

	$app->get('/:category/edit/:id',
			function($category, $id) use ($app, $api){
				try{
					$details = $api->getShowDetails($category, $id);
					$app->render("showEdit.php", array("category" => $category, "id" => $id, 
							"title" => $details["title"], "tvdbId" => $details["tvdbId"]));
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->post('/:category/edit/:id',
			function($category, $id) use ($app, $api, $config){
				try{
					$api->updateShowDetails($category, $id, $_POST["title"], $_POST["tvdbId"]);
					
					$app->redirect("http://".$config["host"].'/shows/'.$category.'/'.$id);
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->get('/:category/episodes/:id',
			function($category, $id) use ($app, $api){
				try{
					$data = $api->getEpisodeDescription($category, $id);
					
					$app->render("episodeDetails.php", $data);
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->get('/:category/(:id)',
			function ($category, $id = "") use ($app, $config, $api) {
				try{
					if (strlen($id) === 0){
						$data = $api->getCategoryOverview($category);
						$header = ucfirst($category);
						$target = $config["host"];
						$content = "categoryOverview.php";
						$contentParams = array("overview" => $data);
						$showEditButton = false;
					}
					else{
						$data = $api->getShowDetails($category, $id);
						$header = $data["title"];
						$target = $config["host"]."/shows/".$category."/";
						$content = "episodesList.php";
						$contentParams = array("showData" => $data["seasons"], "imageUrl" => $data["imageUrl"]);
						$showEditButton = true;
					}
					$app->render("pageHeader.php", array("pageTitle" => $header, "host" => $config["host"]));
					$app->render("headerBarShows.php", array("header" => $header, "target" => $target, "showEditButton" => $showEditButton));
					$app->render($content, $contentParams);
					$app->render("pageFooter.php", array("host" => $config["host"]));
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	
});

$app->group('/movies', function() use ($app, $config, $api) {

	$app->get('/', 
		function() use ($app, $config, $api){
			try{
				$sort = initGET("sort", "name_asc");
				$filter = initGET("filter");
				$genres = initGET("genres");
				$offset = initGET("offset", 0, true);
				$collection = initGET("collection", 0, true);
				$list = initGET("list", 0, true);
				$display = initGET("display", "all");
				$cnt = 6;
				
				if ($collection > 0 or $list > 0){
					$filter = "";
					$genres = "";
					$sort = "name_asc";
				}
				
				$movies = $api->getMovies($sort, $cnt, $offset, $filter, $genres, $collection, $list);
				
				$previous = getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list);
				$next = getNextLink($offset, $cnt, $movies["cnt"], $sort, $filter, $genres, $collection, $list);
							
				$header = "Filme";
				if ($display === "all"){
					$app->render("pageHeader.php", array("pageTitle" => $header." Index", 
							"host" => $config["host"]));
					$app->render("headerBarMovies.php", array("header" => $header, "target" => $config["host"], 
							"searchButtons" => true, "sort" => $sort, "filter" => $filter, "genres" => $genres, 
							"collection" => $collection, "list" => $list));
					$view = $app->view();
					$view->setTemplatesDirectory("templates/");
					$view->clear();
					$view->set("movies", $movies["list"]);
					$view->set("previous", $previous["link"]);
					$view->set("next", $next["link"]);
					$view->set("previousClass", $previous["class"]);
					$view->set("nextClass", $next["class"]);
					$movieOverview = $view->fetch("movieOverview.php");
					$app->render("movieWrapper.php", array("movieOverview" => $movieOverview, 
							"sort" => $sort, "filter" => $filter, "genres" => $genres, "offset" => $offset, 
							"collection" => $collection, "list" => $list));
					$app->render("pageFooter.php", array("host" => $config["host"]));
				}
				if ($display === "overview"){
					$app->render("movieOverview.php", array("movies" => $movies["list"], 
							"previous" => $previous["link"], "next" => $next["link"], 
							"previousClass" => $previous["class"], "nextClass" => $next["class"]));
				}
			}
			catch(RemoteException $exp){
				renderException($exp, $config, $app);
			}
		});
	
	$app->get('/search/',
			function() use ($app, $api){
				try{
					$comp = $api->getCompilations();
					$app->render("movieSearch.php", array("lists" => $comp["lists"], "collections" => $comp["collections"]));
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->get('/lookup/:id',
			function($id) use($app, $api){
				try{
					$movie = $api->lookupMovie($_GET["movieDBID"]);
					if ($movie !== null){
						$app->render("movieDetailsDialog.php", array("data" => $movie, "movie_db_id" => $_GET["movieDBID"]));
					}
					else{
						echo "No Match";
					}
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->get('/genres/',
			function() use($api){
				try{
					$term = initGET("term", "");
					$res = $api->getGenres($term);
					
					echo json_encode($res);
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	$app->get('/:id',
			function($id) use ($app, $config, $api){
				try{
					$movie = $api->getMovie($id);
					$output = initGET("output", "html");
					if ($output === "html"){
						$movie["path"] = $movie["filename"];
						$movie["filename"] = substr($movie["filename"], strrpos($movie["filename"], "/") + 1);
						$app->render("movieDetails.php", $movie);
					}
					if ($output === "edit"){
						$movie_db_id = $movie["movie_db_id"];
						$app->render("movieDetailsDialog.php", array("data" => $movie, "movie_db_id" => $movie_db_id));
					}
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
	
	$app->post('/:dbid',
			function($dbid) use ($api){
				try{
					echo $api->updateMovie($dbid, $_POST["movieDBID"], $_POST["filename"]);
					echo "OK";
				}
				catch(RemoteException $exp){
					renderException($exp, $config, $app);
				}
			});
	
});

$app->run();