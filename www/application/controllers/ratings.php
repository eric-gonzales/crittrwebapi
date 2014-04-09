<?php
/**
 * Ratings Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Ratings extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('ratings_model');
		$this->load->driver('cache');
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	//Update Movie Rating for User
	function update($hashedUserID)
	{
		if($hashedUserID!= NULL && $this->post->movie_id != '' && $this->post->rating != '')
		{
			//Find existing rating for user and movie
			$rating_id = NULL;
			$user_id = hashids_decrypt($hashedUserID);
			$movie_id = hashids_decrypt($this->post->movie_id);			
			$this->db->from('CRRating');
			$this->db->where('user_id', $user_id);
			$this->db->where('movie_id', $movie_id);
			$query = $this->db->get();
			if ($query->num_rows() == 0)
			{
				//Insert a new rating
				$this->db->set('user_id', $user_id);
				$this->db->set('movie_id', $movie_id);
				$this->db->set('rating', $this->post->rating);
				if (array_key_exists("comment", $this->post)) $this->db->set('comments', $this->post->comment);
				if (array_key_exists("notified_box_office", $this->post)) $this->db->set('notified_box_office', $this->post->notified_box_office);
				if (array_key_exists("notified_dvd", $this->post)) $this->db->set('notified_dvd', $this->post->notified_dvd);					$this->db->set('created', 'NOW()', FALSE);
				$this->db->set('modified', 'NOW()', FALSE);				
				$this->db->insert('CRRating');
				$rating_id = $this->db->insert_id();
			}
			else
			{
				//Update existing rating
				$rating_id = $query->row()->id;
				$this->db->where('id', $rating_id);
				$this->db->set('rating', $this->post->rating);
				if (array_key_exists("comment", $this->post)) $this->db->set('comments', $this->post->comment);
				if (array_key_exists("notified_box_office", $this->post)) $this->db->set('notified_box_office', $this->post->notified_box_office);
				if (array_key_exists("notified_dvd", $this->post)) $this->db->set('notified_dvd', $this->post->notified_dvd);	
				$this->db->set('modified', 'NOW()', FALSE);				
				$this->db->update('CRRating');				
			}
			
			//NOTE: Intentionally not updating critter ratings on every insert; they are re-calculated as they expire from cache.
			
			$this->ratings_model->setResult(hashids_encrypt($rating_id));
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch Movie Rating for Specific User and Movie
	function movie($hashedUserID, $hashedMovieID)
	{
		if($hashedUserID != '' || $hashedMovieID != '')
		{
			//Do the query
			$user_id = hashids_decrypt($hashedUserID);
			$movie_id = hashids_decrypt($hashedMovieID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->where('user_id', $user_id);
			$this->db->where('movie_id', $movie_id);	
			$chk_stmt = $this->db->get();					
			$rating = $chk_stmt->row();
			
			//Clean up the result
			$rating->id = hashids_encrypt($rating->id);
			$rating->user_id = hashids_encrypt($rating->user_id);
			$rating->movie_id = hashids_encrypt($rating->movie_id);
			$this->ratings_model->setResult($rating);
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch Movie Ratings for User
	function user($hashedUserID, $modifiedSinceDateTime = NULL)
	{
		$results = array();
		if ($hashedUserID != '')
		{
			//Set up the query
			$user_id = hashids_decrypt($hashedUserID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->where('user_id', $user_id);
			$this->db->order_by('CRRating.created', 'asc'); //creation order
			
			//Add filter on modified field if present
			if ($modifiedSinceDateTime)
			{
				$this->db->where('CRRating.modified >=', $modifiedSinceDateTime);
			}

			//Query and clean up the results
			$chk_stmt = $this->db->get();
			$results = $chk_stmt->result();	
			foreach($results as $rating)
			{
				$rating->id = hashids_encrypt($rating->id);
				$rating->user_id = hashids_encrypt($rating->user_id);
				$rating->movie_id = hashids_encrypt($rating->movie_id);
			}
			$this->ratings_model->setResult($results);
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch All Ratings for Movie
	function all($hashedMovieID, $limit = 100, $offset = 0)
	{
		if($hashedMovieID != '')
		{
			//Set up the query
			$movie_id = hashids_decrypt($hashedMovieID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->where('movie_id', $movie_id);
			$this->db->order_by('CRRating.created', 'desc'); //Newest first
			$this->db->limit($limit);
			$this->db->offset($offset);
			
			//Query and clean up the results
			$chk_stmt = $this->db->get();
			$results = $chk_stmt->result();	
			foreach($results as $rating)
			{
				$rating->id = hashids_encrypt($rating->id);
				$rating->user_id = hashids_encrypt($rating->user_id);
				$rating->movie_id = hashids_encrypt($rating->movie_id);
			}
			$this->ratings_model->setResult($results);
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	/*
	 * Generate Error
	 * Status Codes:
	 * 1 -- General Error
	 * 100 -- Required Post Fields Missing
	 * 200 -- Entity(s) Not Found
	 */
	public function _generateError($message, $status = 1){
		$this->ratings_model->setStatus($status);
		$this->ratings_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->ratings_model->getStatus();
		$data['message'] = $this->ratings_model->getMessage();
		$data['result'] = $this->ratings_model->getResult();
		$this->load->view('standard_response', $data);
	}
}