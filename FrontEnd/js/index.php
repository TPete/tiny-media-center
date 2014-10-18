<?php
require 'lib/Slim/Slim.php';
require 'classes/RestApi.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
		'templates.path' => 'templates/'
));

$config = array("host" => "192.168.1.1", 
			"pathMovies" => "D:/TV/", "aliasMovies" => "/tvmovies/",
			"pathShows" => "E:/Serien", "aliasShows" => "/tvshows/", 
			"baseUrl" => "/tv", "restUrl" => "tiny/tvapi", "thumbSize" => "200",
			"moviePics" => "/tvmovies/movie_pics/");
$api = new RestAPI($config["restUrl"]);

$app->get('/',
		function () use($app, $config){
			$pageTitle = "Main Index";
			$header = "TV";
			$app->render("pageHeader.php", array("pageTitle" => $header." Index", "host" => $config["host"].$config["baseUrl"]));
			$app->render("headerBar.php", array("header" => $header));
			$categories = array("shows/serien/" => "Serien", "shows/kinder/" => "Kinder", "movies/" => "Filme", "newmovies/" => "Filme Neu");
			$app->render("categorySelection.php", array("categories" => $categories));
			$app->render("pageFooter.php", array("host" => $config["host"].$config["baseUrl"]));
		});

$app->get('/shows/:category/(:id)',
		function ($category, $id = "") use ($app, $config, $api) {
			if (strlen($id) === 0){
				$thumbSize = (isset($_GET["thumbSize"])) ? $_GET["thumbSize"] : $config["thumbSize"];
				$data = $api->getCategoryOverview($category, $thumbSize);
				$header = ucfirst($category);
				$target = $config["baseUrl"];
				$scrape = false;
				$content = "categoryOverview.php";
				$contentParams = array("overview" => $data);
			}
			else{
				$data = $api->getShowDetails($category, $id);
				$header = $data["title"];
				$target = $config["baseUrl"]."/shows/".$category."/";
				$scrape = true;
				$content = "episodesList.php";
				$contentParams = array("showData" => $data["seasons"], 
						"imageUrl" => $data["imageUrl"], "scrapeUrl" => $data["scrapeUrl"]);
			}
			$app->render("pageHeader.php", array("pageTitle" => $header, "host" => $config["host"].$config["baseUrl"]));
			$app->render("headerBar.php", array("header" => $header, "target" => $target, "scrape" => $scrape));
			$app->render($content, $contentParams);
			$app->render("pageFooter.php", array("host" => $config["host"].$config["baseUrl"]));
		});

$app->post('/shows/:category/:id/', 
		function($category, $id) use ($api){
			$url = $_POST["url"];
			
			echo $api->updateShow($category, $id, $url);
		});

$app->get('/movies/', 
		function() use ($app, $config, $api){
			$orgSort = (isset($_GET["sort"])) ? $_GET["sort"] : "name_asc";
			$split = explode("_", $orgSort);
			$sort = $split[0];
			$order = $split[1];
			$filter = (isset($_GET["filter"])) ? $_GET["filter"] : "";
			$filter = trim($filter);
			$genre = (isset($_GET["genre"])) ? $_GET["genre"] : "";
			$genre = trim($genre);
			
			$header = "Filme";
			$sortOptions = array("name_asc" => "Name aufsteigend", "name_desc" => "Name absteigend",
					"date_asc" => "Datum aufsteigend", "date_desc" => "Datum absteigend");
			
			$movies = $api->getMovieOverview($sort, $filter, $genre);
			$collections = $api->getCollectionOverview($sort, $filter, $genres);
			$people = $api->getPeopleOverview($sort, $filter);
			$app->render("pageHeader.php", array("pageTitle" => $header." Index", "host" => $config["host"].$config["baseUrl"]));
			$app->render("headerBar.php", array("header" => $header, "target" => $config["baseUrl"], "scrape" => true));
			$app->render("movieOverview.php", array("movies" => $movies, "collections" => $collections, "people" => $people,
					"filter" => $filter, "sort" => $orgSort, "genre" => $genre, "sortOptions" => $sortOptions));
			$app->render("pageFooter.php", array("host" => $config["host"].$config["baseUrl"]));
		});

$app->get('/movies/:id',
		function($id) use ($app, $config, $api){
			$movie = $api->getMovie($id);
			$output = (isset($_GET["output"])) ? $_GET["output"] : "html";
			if ($output === "html"){
				$movie["moviePics"] = $config["moviePics"];
				$app->render("movieDetails.php", $movie);
			}
			if ($output === "edit"){
				$id = $movie["id"];
				unset($movie["id"]);
				$app->render("movieDetailsDialog.php", array("data" => $movie, "id" => $id, "filename" => $_GET["filename"]));
			}
		});

$app->get('/movies/lookup/:id',
		function($id) use($app, $config, $api){
			$movie = $api->lookupMovie($id, $_GET["filename"]);
			if ($movie !== null){
				unset($movie["id"]);
				$app->render("movieDetailsDialog.php", array("data" => $movie, "id" => $id));
			}
			else{
				echo "No Match";
			}
		});

$app->get('/collections/:id',
		function($id) use ($app, $config, $api){
			$col = $api->getCollection($id);
			$app->render("movieCollectionDetails.php", $col);
		});

$app->group('/newmovies', function() use ($app, $config, $api) {

	$app->get('/', 
		function() use ($app, $config, $api){
			$sort = (isset($_GET["sort"])) ? $_GET["sort"] : "name_asc";
			$filter = (isset($_GET["filter"])) ? $_GET["filter"] : "";
			$genres = (isset($_GET["genres"])) ? $_GET["genres"] : "";
			$offset = (isset($_GET["offset"])) ? $_GET["offset"] : "0";
			$offset = intval($offset, 10);
			$display = (isset($_GET["display"])) ? $_GET["display"] : "all";
			$cnt = 6;
			$movies = $api->getMovies($sort, $cnt, $offset, $filter, $genres);
			
			if ($offset > 0){
				$offsetPrev = $offset - $cnt;
				if ($offsetPrev < 0){
					$offsetPrev = 0;
				}
				$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres,
						"offset" => $offsetPrev, "cnt" => $cnt);
				$previous = "?".http_build_query($tmp);
				
			}
			else{
				$previous = "javascript: void(0);";
			}
			
			if ($offset + 2*$cnt <= $movies["cnt"]){
				$offsetNext = $offset + $cnt;
				$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres,
						"offset" => $offsetNext, "cnt" => $cnt);
				$next = "?".http_build_query($tmp);
			}
			else{
				if ($movies["cnt"] - $cnt > $offset){
					$offsetNext = $movies["cnt"] - $cnt;
					$tmp = array("sort" => $sort, "filter" => $filter, "genres" => $genres,
							"offset" => $offsetNext, "cnt" => $cnt);
					$next = "?".http_build_query($tmp);
				}
				else{
					$next = "javascript: void(0);";
				}
			}
					
			
			
			$header = "Filme";
			if ($display === "all"){
				$app->render("pageHeader.php", array("pageTitle" => $header." Index", "host" => $config["host"].$config["baseUrl"]));
				$app->render("headerBar.php", array("header" => $header, "target" => $config["baseUrl"], 
						"searchButtons" => true, "sort" => $sort, "filter" => $filter, "genres" => $genres));
				$view = $app->view();
				$view->setTemplatesDirectory("templates/");
				$view->clear();
				$view->set("movies", $movies["list"]);
				$view->set("moviePics", $config["moviePics"]);
				$view->set("previous", $previous);
				$view->set("next", $next);
				$movieOverview = $view->fetch("newMovieOverview.php");
				$app->render("newMovieWrapper.php", array("movieOverview" => $movieOverview, 
						"sort" => $sort, "filter" => $filter, "genres" => $genres, "offset" => $offset, "cnt" => $cnt));
				$app->render("pageFooter.php", array("host" => $config["host"].$config["baseUrl"]));
			}
			if ($display === "overview"){
				$app->render("newMovieOverview.php", array("movies" => $movies["list"], "moviePics" => $config["moviePics"], 
						"previous" => $previous, "next" => $next));
			}
		});
	
	$app->get('/search/',
			function() use ($app, $config){
				$app->render("newMovieSearch.php");
			});
	
	$app->get('/:id',
			function($id) use ($app, $config, $api){
				$movie = $api->getMovie($id);
				$output = (isset($_GET["output"])) ? $_GET["output"] : "html";
				if ($output === "html"){
					$movie["moviePics"] = $config["moviePics"];
					$movie["file"] = $config["aliasMovies"].$movie["filename"];
					$app->render("newMovieDetails.php", $movie);
				}
// 				if ($output === "edit"){
// 					$id = $movie["id"];
// 					unset($movie["id"]);
// 					$app->render("movieDetailsDialog.php", array("data" => $movie, "id" => $id, "filename" => $_GET["filename"]));
// 				}
			});
	
});

$app->run();