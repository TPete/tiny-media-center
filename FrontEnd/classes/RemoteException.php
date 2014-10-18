<?php

class RemoteException extends Exception{
	
	private $trace;
	
	public function __construct($message, $trace){
		parent::__construct($message);
		$this->message = $message;
		$this->trace = $trace;
	}
	
	public function getStackTrace(){
		return $this->trace;
	}
	
}