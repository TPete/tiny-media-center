<?php
namespace API;

class Util{
	
	public static function glob_recursive($pattern, $flags = 0){
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir){
			$files = array_merge($files, Util::glob_recursive($dir.'/'.basename($pattern), $flags));
		}
		return $files;
	}
	
	public static function readJSONFile($file){
		$fileData = file_get_contents($file);
		if(!mb_check_encoding($fileData, 'UTF-8')){
			$fileData = utf8_encode($fileData);
		}
		$res = json_decode($fileData, true);
		
		return $res;
	}
	
	public static function writeJSONFile($file, $data){
		$json = json_encode($data);
		$res = file_put_contents($file, $json);
	
		return ($res !== false);
	}
	
	public static function sortFiles($data, $sort, $order){
		if ($sort === "name"){
			sort($data, SORT_STRING);
			if ($order === "desc"){
				$data = array_reverse($data);
			}
		}
		if ($sort === "date"){
			if ($order === "asc"){
				usort($data, function($a, $b) {
					return filemtime($a) > filemtime($b);
				});
			}
			else{
				usort($data, function($a, $b) {
					return filemtime($a) < filemtime($b);
				});
			}
		}
	
		return $data;
	}
	
	public static function prettyPrint($json) {
		$result = '';
		$level = 0;
		$prev_char = '';
		$in_quotes = false;
		$ends_line_level = NULL;
		$json_length = strlen($json);
	
		for($i = 0; $i < $json_length; $i ++) {
			$char = $json [$i];
			$new_line_level = NULL;
			$post = "";
			if ($ends_line_level !== NULL) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ($char === '"' && $prev_char != '\\') {
				$in_quotes = ! $in_quotes;
			}
			else{
				if (! $in_quotes) {
					switch ($char) {
						case '}' :
						case ']' :
							$level --;
							$ends_line_level = NULL;
							$new_line_level = $level;
							break;
	
						case '{' :
						case '[' :
							$level ++;
						case ',' :
							$ends_line_level = $level;
							break;
	
						case ':' :
							$post = " ";
							break;
	
						case " " :
						case "\t" :
						case "\n" :
						case "\r" :
							$char = "";
							$ends_line_level = $new_line_level;
							$new_line_level = NULL;
							break;
					}
				}
			}
			if ($new_line_level !== NULL) {
				$result .= "\r\n" . str_repeat ( "\t", $new_line_level );
			}
			$result .= $char . $post;
			$prev_char = $char;
		}
	
		return $result;
	}
	
	public static function filterData($data, $filter){
		$filterArray = explode(" ", $filter);
		$res = array();
	
		for($i = 0; $i < count($data); $i++){
			$row = $data[$i];
	
			if (strlen($filter) > 0){
				$comp = array();
				for($j = 0; $j < count($filterArray); $j++){
					$comp[$j] = false;
					if (stripos($data[$i], $filterArray[$j]) !== false){
						$comp[$j] = true;
					}
				}
				$comp = array_reduce($comp, function($a, $b){return $a && $b;}, true);
				if ($comp){
					$res[] = $row;
				}
			}
			else{
				$res[] = $row;
			}
		}
	
		return $res;
	}
	
	public static function resizeImage($source_image_path, $thumbnail_image_path, $width, $height) {
		if (file_exists($thumbnail_image_path)){
			return true;
		}
		list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);
		$source_gd_image = false;
		switch ($source_image_type) {
			case IMAGETYPE_GIF :
				$source_gd_image = imagecreatefromgif($source_image_path);
				break;
			case IMAGETYPE_JPEG :
				$source_gd_image = imagecreatefromjpeg($source_image_path);
				break;
			case IMAGETYPE_PNG :
				$source_gd_image = imagecreatefrompng($source_image_path);
				break;
		}
		if ($source_gd_image === false) {
			return false;
		}
		$source_aspect_ratio = $source_image_width / $source_image_height;
		$thumbnail_aspect_ratio = $width / $height;
		if ($source_image_width <= $width && $source_image_height <= $height) {
			$thumbnail_image_width = $source_image_width;
			$thumbnail_image_height = $source_image_height;
		}
		elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
			$thumbnail_image_width = ( int ) ($height * $source_aspect_ratio);
			$thumbnail_image_height = $height;
		}
		else {
			$thumbnail_image_width = $width;
			$thumbnail_image_height = ( int ) ($width / $source_aspect_ratio);
		}
		$thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
		imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);
		imagejpeg($thumbnail_gd_image, $thumbnail_image_path, 90);
		imagedestroy($source_gd_image);
		imagedestroy($thumbnail_gd_image);
		return true;
	}

	public static function getFolders($path){
		$elements = scandir($path);
		$folders = array();
		foreach($elements as $ele){
			if (!in_array($ele, array(".", "..", "\$RECYCLE.BIN", "System Volume Information"))){
				if (is_dir($path.$ele)){
					$folders[] = $ele;
				}
			}
		}
		
		return $folders;
	}
	
}