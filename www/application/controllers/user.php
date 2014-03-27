<?php
/**
 * User Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class User extends CI_Controller{
	function index(){}
	
	//Create new Account
	function signup(){
		$this->load->model('user_model');
		$this->user_model->signup();
		
		$data['status'] = $this->user_model->getStatus();
		$data['message'] = $this->user_model->getMessage();
		$data['result'] = $this->user_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
	//Login or Create New Account via Facebook
	function facebook(){}
	
	//Login
	function login(){}
	
	//Reset Lost Password
	function reset(){}
	
	//Update User Profile Photo
	function photo(){}
	
	//Add Friend
	function addfriend(){}
	
	//Remove Friend
	function removefriend(){}
	
}
