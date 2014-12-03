<?php

namespace API;

class ScrapeException extends \Exception{
		
	public function __construct($message){
		parent::__construct($message);
		$this->message = $message;
	}
	
}