<?php
/**
 * Movies Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Movies extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('movies_model');
		
		//load cache driver
		$this->load->driver('cache');
		
		//load curl 
		$this->load->spark('curl/1.3.0');
	}
	
	//Fetch Priority Movies
	public function priority($hashedUserID){}
	
	//Fetch Unrated Movies for User
	public function unrated($hashedUserID){}
	
	//Fetch Box Office Movies for User
	public function boxoffice($hashedUserID, $limit, $countryCode){}
	
	//Fetch Opening Movies for User
	public function opening($hashedUserID, $limit, $countryCode){}
	
	//Fetch Upcoming Movies for User
	public function upcoming($hashedUserID, $limit, $page, $countryCode){}
	
	//Fetch New Release DVDs for User
	public function newreleasedvds($hashedUserID, $limit, $page, $countryCode){}
	
	//Fetch Current Release DVDs for User
	public function currentdvds($hashedUserID, $limit, $page, $countryCode){}
	
	//Fetch Upcoming DVDs for User
	public function upcomingdvds($hashedUserID, $limit, $page, $countryCode){}
	
	//Movie Search
	public function search($searchTerm, $limit, $page){
		//array for results
		$results = array();
		 
		//configure URL
		$url = sprintf($this->config->item('rotten_tomatoes_search_url'), $this->config->item('rotten_tomatoes_api_key'), $searchTerm, $limit, $page);
		//get search results
		$movie_info = $this->_getCachedData($url, $this->config->item('rotten_tomatoes_cache_seconds'));
		$response = json_decode($movie_info);
		$movies = $response->movies;
		foreach($movies as $movie){
			//get RT details using RT ID:
			$results['rotten_tomatoes_id'] = $movie->id;
			$rt_url = sprintf($this->config->item('rotten_tomatoes_movie_url'), $results['rotten_tomatoes_id'], $this->config->item('rotten_tomatoes_api_key'));
			//$rt_info = $this->_getCachedData($rt_url, $this->config->item('rotten_tomatoes_cache_seconds'));
			//$rt_res = json_decode($rt_info);
			$results['title'] = $rt_res['title'];
			$results['hashtag'] = '#'.str_replace(' ', '', strtolower($rt_res['title']));
			$results['box_office_release_date'] = $rt_res['release_dates']['theater'];
			$results['dvd_release_date'] = $rt_res['release_dates']['dvd'];
			$results['imdb_id'] = $rt_res['alternate_ids']['imdb'];
			
			
		}
		
		$this->movies_model->setResult($results);
		$this->_response();
	}

	public function _getCachedData($url, $expiration){
		$result = '';
		
		if(!$this->cache->memcached->get($url)){
			$result = $this->_storeCache($url, $expiration);
		}
		else{
			$result = $this->_getCache($url, $expiration);
		}
		
		return $result;
	}

	public function _storeCache($url, $expiration){
		$info = str_replace("\n", '', $this->curl->simple_get($url));
		$this->cache->memcached->save($url, $info, $expiration);
		return $info;
	}
	
	public function _getCache($url, $expiration){
		return $this->cache->memcached->get($url);
	}
	
	//Generate Error
	public function _generateError($message, $status = 1){
		$this->movies_model->setStatus($status);
		$this->movies_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->movies_model->getStatus();
		$data['message'] = $this->movies_model->getMessage();
		$data['result'] = $this->movies_model->getResult();
		$this->load->view('standard_response', $data);
	}
}