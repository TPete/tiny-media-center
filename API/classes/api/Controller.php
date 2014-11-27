<?php
namespace API;

abstract class Controller{
	
	protected $path;
	protected $alias;
	protected $store;
	protected $scraper;
	
	public function __construct($path, $alias, $store, $scraper){
		$this->path = $path;
		$this->alias = $alias;
		$this->store = $store;
		$this->scraper = $scraper;
	}
	
	public abstract function getCategories();
	
	public abstract function updateData();
	
}