<?php
/**
 * User Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class User_model extends CR_Model {
	private $id;
	private $name;
	private $email;
	private $facebook_id;
	private $facebook_username;
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
			'user' => $this->fetchUser($this->getID()),
			'friends' => $this->getFriends(),
			'notifications' => $this->getNotifications(),
			'url_profilephoto' => $this->config->item('base_url').'user/photo/'.$hashedUserID,
			'url_updateuserprofile' => $this->config->item('base_url').'user/update/'.$hashedUserID,
			'url_addfriend' => $this->config->item('base_url').'user/addfriend/'.$hashedUserID,
			'url_removefriend' => $this->config->item('base_url').'user/removefriend/'.$hashedUserID,			
			'url_linkfacebook' => $this->config->item('base_url').'user/linkfacebook/'.$hashedUserID,
			'url_friendsforuser' => $this->config->item('base_url').'user/friendsforuser',			
			'url_addfriend' => $this->config->item('base_url').'user/addfriend/'.$hashedUserID,	
			'url_usersearch' => $this->config->item('base_url').'user/search/'.$hashedUserID,						
			'url_addcontactfriends' => $this->config->item('base_url').'user/addcontactfriends/'.$hashedUserID,			
			'url_sendnotification' => $this->config->item('base_url').'notification/send',
			'url_clearnotification' => $this->config->item('base_url').'notification/viewed',
			'url_notifications' => $this->config->item('base_url').'user/notifications/'.$hashedUserID,
			'url_moviespriority' => $this->config->item('base_url').'movies/priority/'.$hashedUserID,
			'url_moviescurated' => $this->config->item('base_url').'movies/curated/'.$hashedUserID,			
			'url_moviesunrated' => $this->config->item('base_url').'movies/unrated/'.$hashedUserID,
			'url_moviesopening' => $this->config->item('base_url').'movies/opening/'.$hashedUserID,
			'url_moviesupcoming' => $this->config->item('base_url').'movies/upcoming/'.$hashedUserID,
			'url_moviesnewreleasedvd' => $this->config->item('base_url').'movies/newreleasedvds/'.$hashedUserID,
			'url_moviescurrentdvd' => $this->config->item('base_url').'movies/currentdvds/'.$hashedUserID,
			'url_moviesupcomingdvd' => $this->config->item('base_url').'movies/upcomingdvds/'.$hashedUserID,
			'url_moviessearch' => $this->config->item('base_url').'movies/search',
			'url_moviebycritterid' => $this->config->item('base_url').'movies/get',
			'url_moviebyrottentomatoesid' => $this->config->item('base_url').'movies/rottentomatoes',			
			'url_ratingupdate' => $this->config->item('base_url').'ratings/update/'.$hashedUserID,
			'url_ratingdelete' => $this->config->item('base_url').'ratings/delete/'.$hashedUserID,			
			'url_ratingusermovie' => $this->config->item('base_url').'ratings',
			'url_ratingallmovie' => $this->config->item('base_url').'ratings/all',
			'url_ratingalluser' => $this->config->item('base_url').'ratings/user',
			'url_ratingforRTID' => $this->config->item('base_url').'ratings/critterRatingForMovie',
		));
	}
	
	public function fetchUser($userID)
	{
		$query = $this->db->get_where('CRUser', array('id' => $this->getID()), 1);
		$row = $query->row();
		unset($row->password_hash);
		$row->id = hashids_encrypt($row->id);
		return $row;
	}
	
	/*
	 * Fetch name
	 */
	public function fetchName(){
		$this->db->select('name');
		$query = $this->db->get_where('CRUser', array('id' => $this->getID()), 1);
		$row = $query->row();
		$this->setName($row->name);
	}
	
	/*
	 * 
	 */
	public function fetchEmail(){
		$this->db->select('email');
		$query = $this->db->get_where('CRUser', array('id' => $this->getID()), 1);
		$row = $query->row();
		$this->setEmail($row->email);
	}
	
	/*
	 * Fetch Devices
	 */
	public function fetchDevices()
	{
		$this->db->select('CRDevice.*');	
		$this->db->from('CRDevice');
		$this->db->join('CRDeviceUser', 'CRDevice.id = CRDeviceUser.device_id');
		$this->db->where('CRDeviceUser.user_id', $this->getID());
		$query = $this->db->get();
		$this->setDevices($query->result());
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
	 * Fetch friends that aren't ignoring me
	 */
	public function fetchFriends(){
		$friends = array();
		$this->db->select('friend_id');
		$query = $this->db->get_where('CRFriends', array('user_id' => $this->getID(), 'ignore' => 0));
		if($query->num_rows > 0){
			foreach($query->result() as $row){
				$result = $this->db->get_where('CRUser', array('id' => $row->friend_id), 1);
				$friend = $result->row();
				$friends[] = array(
					'id' => hashids_encrypt($friend->id),
					'name' => $friend->name,
					'email' => $friend->email,
					'facebook_id' => $friend->facebook_id,
					'facebook_username' => $friend->facebook_username,
 				    'photo_url' => $friend->photo_url
				);
			}
		}
		$this->setFriends($friends);
	}
	
	/*
	 * Email token
	 */
	public function newEmailToken(){
		$this->fetchEmail();
		$this->load->helper('string');
		$tok = random_string('unique');
		$this->db->set('created', 'NOW()', FALSE);
		$this->db->set('modified', 'NOW()', FALSE);								
		$this->db->set('token', $tok);
		$this->db->set('user_id', $this->getID());
		$this->db->insert('CREmailToken');
		$hashedUserID = hashids_encrypt($this->getID());
		$reset_url = 'http://request.crittermovies.com/?a='.sha1('resetmypassword').'&t='.$tok.'&u='.$hashedUserID;
		$address = $this->getEmail();
		$to  = $address;
		$subject = 'Password Reset';
		$message = '
		<html>
		<head>
		  <title>Password Reset</title>
		</head>
		<body>
		  <p>Hello,</p>
		  <p>Here is your password reset link: <a href="'.$reset_url.'">'.$reset_url.'</a>. Please use within 24 hours or it will expire!</p>
		  <p>--Critter</p>
		</body>
		</html>
		';
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: Critter <donotreply@crittermovies.com>' . "\r\n";
		mail($to, $subject, $message, $headers);
	}

	public function setID($id){
		$this->id = $id;
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function setName($name){
		$this->name = $name;
	}
	
	public function getName(){
		return $this->$name;
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
	
	public function setFacebookUsername($username){
		$this->facebook_username = $username;
	}
	
	public function getFacebookUsername(){
		return $this->facebook_username;
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