<?php
require_once(dirname(__FILE__).'/report.php');

class Ratings_Home extends Report{
	function __construct(){
		parent::__construct();
		$this->type = '';
		$this->user = '';
		$this->movie = '';
		$this->lowerLimit = 0;
		$this->res_array = array();
		$this->cr_db = '';
		
		try{
			$this->cr_db = new PDO('mysql:host=mydb.convjtlfhxmu.us-west-2.rds.amazonaws.com;dbname=mydb', 'critteruser', 'Tbr6m52l56}n~bb');
    		$this->cr_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(Exception $e){
			echo '<pre>';
			print_r($e);
		}
		
		$this->mapPostGet();
		
		$this->genData();	
		
		$this->echoHTML();	
	}
	
	public function mapPostGet(){
		if(array_key_exists('t', $_GET)){
			$this->type = htmlspecialchars($_GET['t']);
		}
	}
	
	public function genData(){
	}
	
	public function echoJavascript(){
	}
		  
	public function echoMain(){
		$word = '';
		switch($this->type){
			case 1:
				$word = 'Likes';
				break;
			case 2:
				$word = 'Dislikes';
				break;
			case 3:
				$word = 'Watchlists';
				break;
			case 4:
				$word = 'Rejects';
				break;
		}
		echo '<h1>'.$word.'</h1>';
		echo '<ul>';
		echo '<li><a href="http://api.crittermovies.com/dashboard/index.php?mode=ratings_line&t='.$this->type.'">Over Time (Line Chart)</a></li>';
		echo '<li><a href="http://api.crittermovies.com/dashboard/index.php?mode=ratings&t='.$this->type.'">All Movies (Pie Chart)</a></li>';
		echo '</ul>';
	}
}
