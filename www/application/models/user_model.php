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
	
	/**
     * Generates the default result (login result)
     * @param $userID string the id of the user
     * @return void
     * @access public 
     */
	public function defaultResult($userID){
		//get the hashed user id
		$hashedUserID = hashids_encrypt($userID);
		
		//fetch photo url from database
		$imageURL = '';
		$this->db->select('photo_url');
		$query = $this->db->get_where('CRUser', array('id' => $userID), 1);
		if($query->num_rows > 0){
			$row = $query->row();
			if(!empty($row->photo_url)){
				$imageURL = $row->photo_url;
			}
		}
		
		//fetch user's friends that aren't ignoring them
		$friends = array(); //this will be a collection of friend ids
		$this->db->select('friend_id');
		$query = $this->db->get_where('CRFriends', array('user_id' => $userID, 'ignore' => 0));
		if($query->num_rows > 0){
			foreach($query->result() as $row){
				$friends[] = $row->friend_id;
			}
		}
		
		//fetch unread user notifications
		$notifications = array();
		$this->db->select('id');
		$this->db->order_by('created', 'desc'); //newest first
		$query = $this->db->get_where('CRNotification', array('to_user_id' => $userID, 'is_viewed' => 0));
		if($query->num_rows > 0){
			foreach($query->result() as $row){
				$notifications[] = $row->id;
			}
		}
		
		//set result
		$this->setResult(array(
			'image_url' => $imageURL,
			'friends' => $friends,
			'notifications' => $notifications,
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
	
	//Signup
	public function signup(){
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $_POST['email']), 1);
		
		if($chk_stmt->num_rows() == 0){
			//get hashed password
			
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('username', $_POST['username']);
			$this->db->set('password_hash', $_POST['password']);
			$this->db->set('email', $_POST['email']);
			$this->db->insert('CRUser');
			
			//grab the user id from the last insert
			$userID = $this->db->insert_id();
			
			//get headers
			$headers = getallheaders();
			//First, select id from CRDevice where device_vendor_id = critter-device
			$this->db->select('id');
			$query = $this->db->get_where('CRDevice', array('device_vendor_id' => $headers['critter-device']), 1);
			//if we have a match, lets insert a new record into the table
			if($query->num_rows > 0){
				$row = $query->row();
				$this->db->set('device_id', $row->id);
				$this->db->set('user_id', $userID);
				$this->db->insert('CRDeviceUser');
			}
			else{
				//return error code
				$this->setStatus(1);
				$this->setMessage('Error: device could not be found.');
			}
			
			//finally, generate the default result
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
			$cr_user = $chk_stmt->row();
			if($_POST['password'] == $cr_user->password_hash){
				$this->defaultResult($cr_user->id);
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
	
}