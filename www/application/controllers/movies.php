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
			$r = array();
			//get RT details using RT ID
			$r['rotten_tomatoes_id'] = $movie->id;
			$rt_url = sprintf($this->config->item('rotten_tomatoes_movie_url'), $r['rotten_tomatoes_id'], $this->config->item('rotten_tomatoes_api_key'));
			$rt_info = $this->_getCachedData($rt_url, $this->config->item('rotten_tomatoes_cache_seconds'));
			$rt_res = json_decode($rt_info);
			$r['title'] = $rt_res->title;
			$r['hashtag'] = '#'.str_replace(' ', '', strtolower($rt_res->title));
			if(isset($rt_res->release_dates->theater)){
				$r['box_office_release_date'] = $rt_res->release_dates->theater;
			}
			else{
				$r['box_office_release_date'] = '';
			}
			if(isset($rt_res->release_dates->dvd)){
				$r['dvd_release_date'] = $rt_res->release_dates->dvd;
			}
			else{
				$r['dvd_release_date'] = '';
			}
			if(isset($rt_res->alternate_ids->imdb)){
				$r['imdb_id'] = $rt_res->alternate_ids->imdb;
			}
			else{
				//Get IMDB details:
				$omdb_url = sprintf($this->config->item('omdb_title_url'), urlencode($r['title']));
				$omdb_info = $this->_getCachedData($omdb_url, $this->config->item('omdb_cache_seconds'));
				$omdb_res = json_decode($omdb_info);
				if(isset($omdb_res->imdbID)){
					$r['imdb_id'] = $omdb_res->imdbID;
				}
				else{
					$r['imdb_id'] = '';
				}
			}
			//Fetch TMS details
			if($r['imdb_id'] != ''){
				//search by IMDB ID
				$tms_url = sprintf($this->config->item('tmdb_imdb_id_url'), 'tt'.$r['imdb_id'], $this->config->item('tmdb_api_key'));
			}
			elseif($r['title'] != '' && $r['box_office_release_date'] != ''){
				//search by title and year
				$year = substr($r['box_office_release_date'], 0, 4);
				$tms_url = sprintf($this->config->item('tmdb_title_year_url'), $r['title'], $year,  $this->config->item('tmdb_api_key'));
			}
			else{
				//search by title
				$tms_url = sprintf($this->config->item('tmdb_title_url'), $r['title'], $this->config->item('tmdb_api_key'));;
			}
			$tmdb_info = $this->_getCachedData($tms_url, $this->config->item('tmdb_cache_seconds'));
			$tmdb_res = json_decode($tmdb_info);
			if(isset($tmdb_res->movie_results)){
				$tmdb = $tmdb_res->movie_results;
				if(isset($tmdb['id'])){
					$r['tmdb_id'] = $tmdb['id'];
				}
				if(isset($tmdb['poster_path'])){
					$r['tmdb_poster_path'] = $tmdb['poster_path'];
				}
			}
			
			$itunes_url = sprintf($this->config->item('itunes_title_url'), $r['title']);
			$itunes_info = $this->_fetchFromURL($itunes_url);
			$itunes_res = json_decode($itunes_info);
			$r['itunes_id'] = '';
			if(isset($itunes_res->results)){
				foreach($itunes_res->results as $itunes){
					$releaseYear = substr($itunes->releaseDate, 0, 4);
					if($releaseYear == substr($r['box_office_release_date'], 0, 4)){
						$r['itunes_id'] = $itunes->trackId;
					}
				}
			}
			
			
			
			
			if(!empty($r)){
				array_push($results, $r);
			}
		}
		
		$this->movies_model->setResult($results);
		$this->_response();
	}

	public function _getCachedData($url, $expiration){
		$result = '';
		
		if(!$this->cache->memcached->get($url)){
			$result = $this->_fetchFromURL($url, $expiration, true);
		}
		else{
			$result = $this->_getCache($url, $expiration);
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