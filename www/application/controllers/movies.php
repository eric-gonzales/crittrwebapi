<?php
/**
 * Movies Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Movies extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('movies_model');
		$this->load->model('genre_model');		
		
		//load cache driver
		$this->load->driver('cache');
		
		//load curl 
		$this->load->spark('curl/1.3.0');
		
		//add Movie Model
		require_once(dirname(__FILE__).'/../models/movie_model.php');
	}
	
	//Generate Error
	public function _generateError($message, $status = 1)
	{
		$this->movies_model->setStatus($status);
		$this->movies_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response()
	{
		$data['status'] = $this->movies_model->getStatus();
		$data['message'] = $this->movies_model->getMessage();
		$data['result'] = $this->movies_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
	function pruneResults($userID, $results)
	{
		$newResults = array();
		foreach($results as $movie)
		{
			$movie_id = hashids_decrypt($movie['id']);
			$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id, 'user_id' => $userID), 1);
			if($chk_stmt->num_rows() == 0)
			{
				array_push($newResults, $movie);
			}
		}
		return $newResults;		
	}
	
	function fetchFromURL($hashedUserID, $url, $pruneAlreadyRated = TRUE)
	{
		//Check memcache first
		$results = $this->cache->memcached->get($url);
		
		//Not in memcache?  Pull it by hand
		if(!$results)
		{
			error_log("Cache miss: $url");
		
			//Do RT hit and clean up result
			$movie_info = $this->curl->simple_get($url);
			$movie_info = str_replace("\n", "", $movie_info);
			$response = json_decode($movie_info);
			
			//Loop and build the list of movies
			$results = array();
			foreach($response->movies as $movie)
			{
				$movieModel = new Movie_model($movie->id);
				$result = $movieModel->getResult();
				array_push($results, $result);
			}
			
			//Cache it
			$this->cache->memcached->save($url, $results, $this->config->item('rotten_tomatoes_cache_seconds'));
		}
		else
		{
			error_log("Cache hit: $url");		
		}
		
		//Prune results for this user?
		if ($pruneAlreadyRated)
		{
			$user_id = hashids_decrypt($hashedUserID);	
			$results = $this->pruneResults($user_id, $results);
		}
		
		//Set the results and exit
		$this->movies_model->setResult($results);
		$this->_response();
	}	
	
	//Fetch Box Office Movies
	public function boxoffice($limit, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		//Set up URL
		$url = sprintf($this->config->item('rotten_tomatoes_box_office_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $countryCode);
		error_log("Hitting $url");
		
		//Do it
		$this->fetchFromURL(NULL, $url, FALSE);
	}
	
	//Fetch Opening Movies for User
	public function opening($hashedUserID, $limit, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		//configure URL
		$url = sprintf($this->config->item('rotten_tomatoes_opening_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $countryCode);
		
		//Do it
		$this->fetchFromURL($hashedUserID, $url);
	}
	
	//Fetch Upcoming Movies for User
	public function upcoming($hashedUserID, $limit, $page, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		$url = sprintf($this->config->item('rotten_tomatoes_upcoming_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
		
		//Do it
		$this->fetchFromURL($hashedUserID, $url);
	}		
	
	//Fetch New Release DVDs for User
	public function newreleasedvds($hashedUserID, $limit, $page, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		$url = sprintf($this->config->item('rotten_tomatoes_new_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
		
		//Do it
		$this->fetchFromURL($hashedUserID, $url);
	}		
	
	//Fetch Current Release DVDs for User
	public function currentdvds($hashedUserID, $limit, $page, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		$url = sprintf($this->config->item('rotten_tomatoes_current_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
		
		//Do it
		$this->fetchFromURL($hashedUserID, $url);
	}
	
	//Fetch Upcoming DVDs for User
	public function upcomingdvds($hashedUserID, $limit, $page, $countryCode)
	{
		//Substitutions
		if (strtoupper($countryCode) == "GB")
		{
			$countryCode = @"UK";
		}
	
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $countryCode === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		$url = sprintf($this->config->item('rotten_tomatoes_upcoming_dvds_url'), $this->config->item('rotten_tomatoes_api_key'), $limit, $page, $countryCode);
		
		//Do it
		$this->fetchFromURL($hashedUserID, $url);
	}		
	
	//Movie Search
	public function search($searchTerm, $limit = 20, $page = 1)
	{
		//Sanity check
		if ($searchTerm === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		$url = sprintf($this->config->item('rotten_tomatoes_search_url'), $this->config->item('rotten_tomatoes_api_key'), $searchTerm, $limit, $page);
		
		//Do it
		$this->fetchFromURL(NULL, $url, FALSE);
	}		
	
	//Fetch Priority Movies
	public function priority($hashedUserID)
	{
		//Sanity check
		if ($hashedUserID === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
	
		//decrypt user id
		$user_id = hashids_decrypt($hashedUserID);
		
		//Check memcache first
		$results = $this->cache->memcached->get('priority_movies');
		if(!$results)
		{
			//Fetch from DB, removing already-rated movies
			$results = array();
			$this->db->from('CRMovie');
			$this->db->where('priority IS NOT NULL');
			$this->db->order_by('priority', 'ASC');
			$movie_stmt = $this->db->get();
			foreach($movie_stmt->result() as $movie)
			{
				$result = array();
				$movieModel = new Movie_model($movie->rotten_tomatoes_id);
				$result = $movieModel->getResult();
				array_push($results, $result);
			}
			//TODO: Fix cache config item
			$this->cache->memcached->save('priority_movies', $results, $this->config->item('critter_priority_cache_seconds'));
		}
		
		//Prune already rated
		$results = $this->pruneResults($user_id, $results);

		//Set result and bail		
		$this->movies_model->setResult($results);
		$this->_response();
	}

	//Fetch Unrated Movies for User
	public function unrated($hashedUserID, $limit, $offset)
	{
		//Sanity check
		if ($hashedUserID === NULL || $limit === NULL || $offset === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
		
		//Get the POST
		$posted = json_decode(file_get_contents('php://input'));
		$genres = $posted->genres;
	
		//Set up the query to get all unrated movies (paged, because this is a big one that can return a lot)
		$user_id = intval(hashids_decrypt($hashedUserID));
		$results = array();
		$this->db->from('CRMovie');
		$this->db->where("`id` NOT IN (select movie_id from CRRating where user_id=$user_id)", NULL, FALSE);
		
		//Add genre filters?
		if ($genres)
		{
			$this->db->where("`id` IN (select movie_id from CRGenreMovie b join CRGenre c on b.genre_id=c.id where c.name in ('" . implode("','", $genres) . "'))", NULL, FALSE);
		}
		
		$this->db->order_by('box_office_release_date', 'DESC');
		$this->db->limit($limit);
		$this->db->offset($offset);
		$movie_stmt = $this->db->get();
			
		//Build results
		foreach($movie_stmt->result() as $movie)
		{
			$result = array();
			$movieModel = new Movie_model($movie->rotten_tomatoes_id);
			$result = $movieModel->getResult();
			array_push($results, $result);
		}		

		$this->movies_model->setResult($results);
		$this->_response();
	}

	//Return a single result based on the rotten tomatoes ID
	public function rottentomatoes($rottenTomatoesID){
		//Sanity check
		if($rottenTomatoesID === NULL){
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
		//create a movie model based on the rotten tomatoes ID and generate response
		$movieModel = new Movie_model($rottenTomatoesID);
		$result = $movieModel->getResult();
		$this->movies_model->setResult($result);
		$this->_response();
	}
	
	//Return a single result based on the hashed movie ID
	public function get($hashedMovieID)
	{
		//Sanity check
		$movie_id = hashids_decrypt($hashedMovieID);
		if($movie_id === NULL)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();
			return;
		}
		
		//Look up the movie from the DB
		$this->db->from('CRMovie');
		$this->db->where('id', $movie_id);
		$row = $this->db->get()->row();
		if (!$row)
		{
			$this->_generateError('Movie not found', $this->config->item('error_entity_not_found'));		
			$this->_response();
			return;
		}
		
		//create a movie model based on the rotten tomatoes ID and generate response
		$movieModel = new Movie_model($row->rotten_tomatoes_id);
		$result = $movieModel->getResult();
		$this->movies_model->setResult($result);
		$this->_response();
	}
	
	public function warmcache()
	{
		$this->db->from('CRMovie');
		$this->db->order_by('box_office_release_date', 'DESC');
		$query = $this->db->get();
		foreach($query->result() as $movie)
		{
			echo "Warming cache: " . $movie->title . " \n";
			$movieModel = new Movie_model($movie->rotten_tomatoes_id);
		}
	}
	
	public function import($filename)
	{
		$this->load->helper('file');
		$string = read_file($filename);
		$file = json_decode($string);
		foreach($file->results as $result)
		{
			//Get the RT ID
			$rtID = $result->rotten_tomatoes_id;
			
			//Find the movie
			$this->db->from('CRMovie');
			$this->db->where('rotten_tomatoes_id', $rtID);
			$row = $this->db->get()->row();
			
			if ($row)
			{
				error_log("Skipping movie " . $result->title);
			}
			else
			{
				$this->db->set('box_office_release_date', $result->boxOfficeReleaseDate->iso);
				$this->db->set('dvd_release_date', $result->dvdReleaseDate->iso);				
				$this->db->set('hashtag', $result->hashtag ? $result->hashtag : "#" . str_replace(" ", "", $result->title));
				$this->db->set('tmdb_poster_path', $result->poster_path);
				$this->db->set('imdb_id', $result->imdb_id);				
				$this->db->set('tmdb_id', $result->tmdb_id);
				$this->db->set('itunes_id', $result->itunes_id);				
				$this->db->set('title', $result->title);
				$this->db->set('rotten_tomatoes_id', $rtID);				
				$this->db->insert('CRMovie');
				error_log("Imported " . $result->title);
			}
		}
	}
	
	public function fixgenres()
	{
		$this->db->from('CRMovie');
		$this->db->order_by('rotten_tomatoes_id');
		$query = $this->db->get()->result();
		foreach($query as $movie)
		{
			error_log("MOVIE: " . $movie->rotten_tomatoes_id);
			$cacheKey = "CRMovie_" . $movie->rotten_tomatoes_id;
			$result = $result = $this->cache->memcached->get($cacheKey);	
			$movie_id = hashids_decrypt($result["id"]);
			
			//See if we have genres
			$this->db->from('CRGenreMovie');
			$this->db->where('movie_id', $movie_id);
			$genres = $this->db->get();
			if ($genres->num_rows() == 0)
			{
				error_log("Adding genres to $movie_id: " . json_encode($result["genres"]));
			
				//Add them
				$this->genre_model->addGenresToMovie($movie_id, $result["genres"]);				
			}
			
		}
	}	
}