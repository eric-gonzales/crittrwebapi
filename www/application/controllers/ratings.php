<?php
/**
 * Ratings Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Ratings extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('ratings_model');
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	//Update Movie Rating for User
	function update($hashedUserID){
		if($this->post->movieID != '' && $this->post->rating != ''){
			$user_id = hashids_decrypt($hashedUserID);
			if(!empty($user_id)){
				$movie_id = hashids_decrypt($this->post->movieID);
				$rating_id = 0;
				$this->db->select('int');
				$chk_stmt = $this->db->get_where('CRRating',array('user_id' => $user_id, 'movie_id' => $movie_id), 1);
				if($chk_stmt->num_rows() > 0){
					//grab id
					$rating = $chk_stmt->row();
					$rating_id = $rating->int;
					//update record
					$this->db->where('int', $rating_id)->set('rating', $this->post->rating)->set('comment', $this->post->comment);
					$this->db->update('CRRating');
				}
				else{
					//create record
					$this->db->set('created', 'NOW()', FALSE)->set('user_id', $user_id)->set('movie_id', $movie_id)->set('rating', $this->post->rating)->set('comment', $this->post->comment);
					$this->db->insert('CRRating');
					//grab id
					$rating_id = $this->db->insert_id();
				}
				$this->ratings_model->setResult(array(hashids_encrypt($rating_id)));
			}
			else{
				$this->_generateError('user not found');
			}
		}
		else{
			$this->_generateError('required fields missing');
		}
		$this->_response();
	}
	
	//Fetch Movie Rating for Specific User and Movie
	function movie($hashedUserID, $hashedMovieID){
		$user_id = hashids_decrypt($hashedUserID);
		$movie_id = hashids_decrypt($hashedMovieID);
		if(!empty($user_id) || !empty($movie_id)){
			$chk_stmt = $this->db->get_where('CRRating',array('user_id' => $user_id, 'movie_id' => $movie_id), 1);
			if($chk_stmt->num_rows() > 0){
				$rating = $chk_stmt->row();
				$result = array(
					'int' => hashids_encrypt($rating->int),
					'user_id' => $rating->user_id,
					'movie_id' => hashids_encrypt($rating->movie_id),
					'notified_box_office' => $rating->notified_box_office,
					'notified_dvd' => $rating->notified_dvd,
					'rating' => $rating->rating,
					'comment' => $rating->comment
				);
				$this->ratings_model->setResponse($result);
			}
			else{
				$this->_generateError('record with specified user/movie combination not found');
			}
		}
		else{
			$this->_generateError('user and/or movie not found');
		}
		$this->_response();
	}
	
	//Fetch Movie Ratings for User
	function user($hashedUserID, $modifiedSinceDateTime = ''){
		$user_id = hashids_decrypt($hashedUserID);
		if(!empty($user_id)){
			$results = array();
			if($modifiedSinceDateTime == ''){
				$chk_stmt = $this->db->get_where('CRRating',array('user_id' => $user_id), 1);
				if($chk_stmt->num_rows() > 0){
					foreach($chk_stmt->result() as $rating){
						$result = array(
							'int' => hashids_encrypt($rating->int),
							'user_id' => $rating->user_id,
							'movie_id' => hashids_encrypt($rating->movie_id),
							'notified_box_office' => $rating->notified_box_office,
							'notified_dvd' => $rating->notified_dvd,
							'rating' => $rating->rating,
							'comment' => $rating->comment
						);
						$results[] = $result;
					}
					$this->ratings_model->setResponse($results);
				}
				else{
					$this->_generateError('ratings not found for the user specified');
				}
			}
		}
		else{
			$this->_generateError('user not found');
		}
		$this->_response();
	}
	
	//Fetch All Ratings for Movie
	function all($hashedMovieID, $limit, $offset){
		$movie_id = hashids_decrypt($hashedMovieID);
		if(!empty($movie_id)){
			$results = array();
			$this->db->order_by('created', 'desc'); //newest first
			$chk_stmt = $this->db->get_where('CRRating',array('movie_id' => $movie_id), $limit, $offset);
			foreach($chk_stmt->result() as $rating){
				$result = array(
					'int' => hashids_encrypt($rating->int),
					'user_id' => $rating->user_id,
					'movie_id' => hashids_encrypt($rating->movie_id),
					'notified_box_office' => $rating->notified_box_office,
					'notified_dvd' => $rating->notified_dvd,
					'rating' => $rating->rating,
					'comment' => $rating->comment
				);
				$results[] = $result;
			}
			$this->ratings_model->setResponse($results);
		}
		else{
			$this->_generateError('movie not found');
		}
		$this->_response();
	}
	
	//Generate Error
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