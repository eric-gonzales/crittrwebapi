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
	
	public function signup(){}
	
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