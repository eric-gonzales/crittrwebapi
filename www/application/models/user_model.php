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
	
	public function defaultResult($userID){
		//get the hashed user id
		$hashedUserID = hashids_encrypt($userID);
		
		$this->setResult(array(
			'image_url' => '',
			'friends' => '',
			'notifications' => '',
			'url_profilephoto' => $this->config->item('base_url').'user/photo/'.$hashedUserID,
			'url_addfriend' => $this->config->item('base_url').'user/addfriend/'.$hashedUserID,
			'url_removefriend' => $this->config->item('base_url').'user/removefriend/'.$hashedUserID,
			'url_sendnotification' => $this->config->item('base_url').'notification/send',
			'url_clearnotification' => $this->config->item('base_url').'notification/viewed',
			'url_unreadnotification' => $this->config->item('base_url').'notification/unread/'.$hashedUserID,
			'url_updatepushtoken' => $this->config->item('base_url').'device/update',
			'url_moviespriority' => $this->config->item('base_url').'movies/priority/'.$hashedUserID,
			'url_moviesunrated' => $this->config->item('base_url').'movies/unreated/'.$hashedUserID,
			'url_moviesboxoffice' => $this->config->item('base_url').'movies/boxoffice/'.$hashedUserID.'/Limit/CountryCode',
			'url_moviesopening' => $this->config->item('base_url').'movies/opening/'.$hashedUserID.'/Limit/CountryCode',
			'url_moviesupcoming' => $this->config->item('base_url').'movies/upcoming/'.$hashedUserID.'/Limit/Page/CountryCode',
			'url_moviesnewreleasedvd' => $this->config->item('base_url').'movies/newreleasedvds/'.$hashedUserID.'/Limit/Page/CountryCode',
			'url_moviescurrentdvd' => $this->config->item('base_url').'movies/currentdvds/'.$hashedUserID.'/Limit/Page/CountryCode',
			'url_moviesupcomingdvd' => $this->config->item('base_url').'movies/upcomingdvds/'.$hashedUserID.'/Limit/Page/CountryCode',
			'url_moviessearch' => $this->config->item('base_url').'movies/search/'.$hashedUserID.'/searchTerm/Limit/Page',
			'url_ratingupdate' => $this->config->item('base_url').'ratings/update/'.$hashedUserID,
			'url_ratingusermovie' => $this->config->item('base_url').'ratings',
			'url_ratingallmovie' => $this->config->item('base_url').'ratings/'.$hashedUserID.'/hashedMovieID',
			'url_ratingalluser' => $this->config->item('base_url').'ratings/user',
		));
	}
	
	public function signup(){
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $_POST['email']), 1);
		
		if($chk_stmt->num_rows() == 0){
			//get hashed password
			$hashedPassword = $this->hash($_POST['password']);
			
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('username', $_POST['username']);
			$this->db->set('password_hash', $hashedPassword);
			$this->db->set('email', $_POST['email']);
			$this->db->insert('CRUser');
			
			$userID = $this->db->insert_id();
			$this->defaultResult($userID);
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
	public function login(){
		//look for matching email in CRUser table
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $_POST['email']), 1);
		
		if($chk_stmt->num_rows() == 0){
			//return error code
			$this->setStatus(1);
			$this->setMessage('Error: email does not exist.');
		}
		else{
			$cr_user = $chk_stmt->result();
			echo '<br>';
			$post_pass = $_POST['password'];
			echo $post_pass;
			$hashedPassword = sha1($post_pass.base64_encode($this->config->item('server_secret')));
			echo '<br>';
			echo $hashedPassword;
			echo '<br>';
			echo $cr_user[0]->password_hash;
			echo '<hr>';
			if($hashedPassword == $cr_user[0]->password_hash){
				//$this->defaultResult($userID);
			}
			else{
				//return error code
				$this->setStatus(1);
				$this->setMessage('Error: invalid credentials');
			}
		}
	}
	
	//Reset Lost Password
	public function reset(){}
	
	//Update User Profile Photo
	public function photo(){}
	
	//Add Friend
	public function addfriend(){}
	
	//Remove Friend
	public function removefriend(){}
	
	//Hash
	private function pass_hash($string){
	}
}