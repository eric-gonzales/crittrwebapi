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
	}
	
	//Update Movie Rating for User
	function update($hashedUserID){
		
	}
	
	//Fetch Movie Rating for Specific User and Movie
	function movie($hashedUserID, $hashedMovieID){
		
	}
	
	//Fetch Movie Ratings for User
	function user($hashedUserID, $modifiedSinceDateTime){
		
	}
	
	//Fetch All Ratings for Movie
	function all($hashedUserID, $limit, $offset){
		
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