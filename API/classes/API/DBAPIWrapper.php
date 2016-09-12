<?php
namespace TinyMediaCenter\API;

abstract class DBAPIWrapper{
	
	protected $baseUrl;
	protected $defaultArgs;
	
	public function __construct($baseUrl, $defaultArgs = array()){
		$this->baseUrl = $baseUrl;
		$this->defaultArgs = $defaultArgs;
	}
	
	protected function curlDownload($url, $args = array()){
		$url = $this->baseUrl.$url;
		$args = array_merge($this->defaultArgs, $args);
		$argStr = http_build_query($args);
		$url .= "?".$argStr;
	
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
	
		return $output;
	}
	
	protected function downloadImage($url, $file){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$raw = curl_exec($ch);
		curl_close ($ch);
		if(file_exists($file)){
			unlink($file);
		}
		$fp = fopen($file,'x');
		fwrite($fp, $raw);
		fclose($fp);
	}
	
}