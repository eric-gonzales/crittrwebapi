<?php
/**
 * Movies Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
//TODO: investigate consolidating design pattern re: box office, opening, etc. 

class Movies extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('movies_model');
		
		//load cache driver
		$this->load->driver('cache');
		
		//load curl 
		$this->load->spark('curl/1.3.0');
		
		//add Movie Model
		require_once(dirname(__FILE__).'/../models/movie_model.php');
	}
	
	//Fetch Priority Movies
	public function priority($hashedUserID){
		//array of priority movie results
		$results = array(); 
		//decrypt user id
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			$results = array();
			if(!$this->cache->memcached->get('priority_movies')){
				$this->db->order_by('priority', 'ASC');
				$movie_stmt = $this->db->get_where('CRMovie','priority IS NOT NULL');
				foreach($movie_stmt->result() as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->rotten_tomatoes_id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save('priority_movies', $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache('priority_movies');
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user');
		}
		$this->_response();
	}
	
	//Fetch Unrated Movies for User
	public function unrated($hashedUserID){
		//array of priority movie results
		$results = array(); 
		//decrypt user id
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			$results = array();
			//TODO: I'm pretty sure we are going to have to set some sort of limit here eventually
			$this->db->order_by('box_office_release_date', 'ASC');
			$movie_stmt = $this->db->get_where('CRMovie','priority IS NULL');
			foreach($movie_stmt->result() as $movie){
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie->id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() == 0){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->rotten_tomatoes_id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
			}		
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch Box Office Movies for User
	public function boxoffice($hashedUserID, $limit, $countryCode){
		//array of box office results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_box_office_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch Opening Movies for User
	public function opening($hashedUserID, $limit, $countryCode){
		//array of results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_opening_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch Upcoming Movies for User
	public function upcoming($hashedUserID, $limit, $page, $countryCode){
		//array of results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_upcoming_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch New Release DVDs for User
	public function newreleasedvds($hashedUserID, $limit, $page, $countryCode){
		//array of results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_new_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch Current Release DVDs for User
	public function currentdvds($hashedUserID, $limit, $page, $countryCode){
		//array of results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_current_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Fetch Upcoming DVDs for User
	public function upcomingdvds($hashedUserID, $limit, $page, $countryCode){
		//array of results
		$results = array();
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			//configure URL
			$url = sprintf($this->config->item('rotten_tomatoes_upcoming_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
			//get movie results from this URL
			if(!$this->cache->memcached->get($url)){
				//get search results
				$movie_info = $this->_fetchFromURL($url);
				$response = json_decode($movie_info);
				foreach($response->movies as $movie){
					$result = array();
					//get RT details using RT ID
					$movieModel = new Movie_model($movie->id);
					$result = $movieModel->getResult();
					array_push($results, $result);
				}
				$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
			}
			else{
				$results = $this->_getCache($url);
			}
			//before returning the array, remove any movies the user has already rated
			foreach($results as $key => $movie){
				$movie_id = hashids_decrypt($movie['id']);
				$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					unset($results[$key]);
				}
			}
			//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
			$this->movies_model->setResult($results);
		}
		else{
			$this->_generateError('could not find user with the specified id');
		}
		$this->_response();
	}
	
	//Movie Search
	public function search($searchTerm, $limit, $page){
		//array of search results
		$results = array();
		//configure URL
		$url = sprintf($this->config->item('rotten_tomatoes_search_url'), $this->config->item('rotten_tomatoes_api_key'), $searchTerm, $limit, $page);
		//get movie results from this URL
		if(!$this->cache->memcached->get($url)){
			//get search results
			$movie_info = $this->_fetchFromURL($url);
			$response = json_decode($movie_info);
			foreach($response->movies as $movie){
				$result = array();
				//get RT details using RT ID
				$movieModel = new Movie_model($movie->id);
				$result = $movieModel->getResult();
				array_push($results, $result);
			}
			$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
		}
		else{
			$results = $this->_getCache($url);
		}
		//return an array of CRMovie records with associated details attached from RT, IMDB, TMDB, iTunes, and TMS
		$this->movies_model->setResult($results);
		
		$this->_response();
	}

	//Get cached data
	public function _getCachedData($url, $expiration){
		$result = '';
		if(!$this->cache->memcached->get(urlencode($url))){
			$result = $this->_fetchFromURL($url, $expiration, true);
		}
		else{
			$result = $this->_getCache(urlencode($url));
		}
		return $result;
	}
	
	//Fetch data from URL
	public function _fetchFromURL($url, $expiration = '', $shouldBeCached = false){
		$info = str_replace("\n", '', $this->curl->simple_get($url));
		if($shouldBeCached){
			$this->cache->memcached->save(urlencode($url), $info, $expiration);
		}
		return $info;
	}
	
	//Get from cache
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