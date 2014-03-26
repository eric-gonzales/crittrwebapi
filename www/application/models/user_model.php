<?php

/**
 * Init Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class User_model extends CR_Model {
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		parent::__construct();
	}
	
	public function signup(){
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $_POST['email']), 1);
		
		if($chk_stmt->num_rows() == 0){
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('username', $_POST['username']);
			$this->db->set('password_hash', $_POST['password']);
			$this->db->set('email', $_POST['email']);
			$this->db->insert('CRUser');
		}
		else{
			//return error code
			$this->setStatus(1);
			$this->setMessage('Error: email is already in use.');
		}
	}
	
	//Login or Create New Account via Facebook
	public function facebook(){}
	
	//Login
	public function login(){}
	
	//Reset Lost Password
	public function reset(){}
	
	//Update User Profile Photo
	public function photo(){}
	
	//Add Friend
	public function addfriend(){}
	
	//Remove Friend
	public function removefriend(){}
}