<?php
/**
 * User Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class User_model extends CR_Model {
	private $id;
	private $username;
	private $email;
	private $facebook_id;
	private $full_name;
	private $photo_url;
	private $notifications;
	private $friends;
	private $devices;
	
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		parent::__construct();
		
		//Initalize empty properties
		$this->setPhotoURL('');
		$this->setFriends(array());
		$this->setNotifications(array());
	}
	
	/**
     * Generates the default result (login result)
     * @param $userID string the id of the user
     * @return void
     * @access public 
     */
	public function defaultResult(){
		//get the hashed user id
		$hashedUserID = hashids_encrypt($this->getID());
		
		//set result
		$this->setResult(array(
			'image_url' => $this->getPhotoURL(),
			'friends' => $this->getFriends(),
			'notifications' => $this->getNotifications(),
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
	
	/*
	 * Fetch username
	 */
	public function fetchUsername(){
		$this->db->select('username');
		$query = $this->db->get_where('CRUser', array('id' => $this->getID()), 1);
		$row = $query->row();
		$this->setUsername($row->username);
	}
	
	/*
	 * Fetch Devices
	 */
	public function fetchDevices(){
		$devices = array();
		$query = $this->db->get_where('CRDeviceUser', array('user_id' => $this->getID()));
		if($query->num_rows > 0){
			foreach($query->result() as $device){
				$devices[] = $device->device_id;
			}
		}
		$this->setDevices($devices);
	}
	
	/*
	 * Fetch the photo_url
	 */
	public function fetchPhotoURL(){
		$this->db->select('photo_url');
		$query = $this->db->get_where('CRUser', array('id' => $this->getID()), 1);
		if($query->num_rows > 0){
			$row = $query->row();
			if(!empty($row->photo_url)){
				$this->setPhotoURL($row->photo_url);
			}
		}
	}
	
	/*
	 * Fetch unread notifications 
	 */
	public function fetchNotifications(){
		$notifications = array();
		$this->db->select('id');
		$this->db->order_by('created', 'desc'); //newest first
		$query = $this->db->get_where('CRNotification', array('to_user_id' => $this->getID(), 'is_viewed' => 0));
		if($query->num_rows > 0){
			foreach($query->result() as $row){
				$notifications[] = $row->id;
			}
		}
		$this->setNotifications($notifications);
	}
	
	/*
	 * Fetch friends that aren't ignoring me
	 */
	public function fetchFriends(){
		$friends = array();
		$this->db->select('friend_id');
		$query = $this->db->get_where('CRFriends', array('user_id' => $this->getID(), 'ignore' => 0));
		if($query->num_rows > 0){
			foreach($query->result() as $row){
				$friends[] = $row->friend_id;
			}
		}
		$this->setFriends($friends);
	}
	
	/*
	 * Email token
	 */
	public function newEmailToken(){
		$this->load->helper('string');
		$this->db->set('created', 'NOW()', FALSE);
		$this->db->set('token', random_string('unique'));
		$this->db->set('user_id', $this->getID());
		$this->db->insert('CREmailToken');
	}

	public function setID($id){
		$this->id = $id;
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function setUsername($username){
		$this->username = $username;
	}
	
	public function getUsername(){
		return $this->username;
	}
	
	public function setEmail($email){
		$this->email = $email;
	}
	
	public function getEmail(){
		return $this->email;
	}
	
	public function setFacebookID($facebook_id){
		$this->facebook_id = $facebook_id;
	}
	
	public function getFacebookID(){
		return $this->facebook_id;
	}
	
	public function setFullName($full_name){
		$this->full_name = $full_name;
	}
	
	public function getFullName(){
		return $this->full_name;
	}
	
	public function setPhotoURL($photo_url){
		$this->photo_url = $photo_url;
	}
	
	public function getPhotoURL(){
		return $this->photo_url;
	}
	
	public function setNotifications($notifications){
		$this->notifications = $notifications;
	}
	
	public function getNotifications(){
		return $this->notifications;
	}
	
	public function setFriends($friends){
		$this->friends = $friends;
	}
	
	public function getFriends(){
		return $this->friends;
	}
	
	public function setDevices($devices){
		$this->devices = $devices;
	}
	
	public function getDevices(){
		return $this->devices;
	}
}