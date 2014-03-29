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
		//configure URL
		$url = sprintf($this->config->item('rotten_tomatoes_search_url'), $this->config->item('rotten_tomatoes_api_key'), $searchTerm, $limit, $page);
		//get movie results from this URL
		$result = $this->_movieResults($url, $this->config->item('rotten_tomatoes_cache_seconds'));
		//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
		$this->movies_model->setResult($result);
		
		$this->_response();
	}

	public function _movieResults($url, $expiration){
		//array of results
		$results = array();
		
		//check to see if this is already in the cache
		if(!$this->cache->memcached->get($url)){
			//get search results
			$movie_info = $this->_fetchFromURL($url);
			$response = json_decode($movie_info);
			$movies = $response->movies;
			foreach($movies as $movie){
				$result = array();
				//get RT details using RT ID
				$movieModel = new Movie_model($movie->id);
				$result = $movieModel->getResult();
				if(!empty($r)){
					array_push($results, $result);
				}
			}
			$this->cache->memcached->save($url, $results, $expiration);
		}
		else{
			$results = $this->_getCache($url);
		}
		//finally return the results
		return $results;
	}

	public function _getCachedData($url, $expiration){
		$result = '';
		
		if(!$this->cache->memcached->get($url)){
			$result = $this->_fetchFromURL($url, $expiration, true);
		}
		else{
			$result = $this->_getCache($url);
		}
		
		return $result;
	}

	public function _fetchFromURL($url, $expiration = '', $shouldBeCached = false){
		$info = str_replace("\n", '', $this->curl->simple_get($url));
		if($shouldBeCached){
			$this->cache->memcached->save($url, $info, $expiration);
		}
		return $info;
	}
	
	public function _getCache($key){
		return $this->cache->memcached->get($key);
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