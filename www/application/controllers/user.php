<?php
/**
 * User Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class User extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('user_model');
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	//Create new Account
	function signup(){
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->post->email), 1);
		if($chk_stmt->num_rows() == 0){
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('modified', 'NOW()', FALSE);			
			$this->db->set('name', $this->post->name);
			$this->db->set('password_hash', $this->phpass->hash($this->post->password));
			$this->db->set('email', $this->post->email);
			$this->db->insert('CRUser');
			//grab the user id from the last insert
			$this->user_model->setID($this->db->insert_id());
			//First, select id from CRDevice where device_vendor_id = critter-device
			$this->db->select('id');
			$query = $this->db->get_where('CRDevice', array('device_vendor_id' => $this->input->get_request_header('critter-device', TRUE)), 1);
			//if we have a match, lets insert a new record into the table
			if($query->num_rows > 0){
				$row = $query->row();
				$this->db->set('device_id', $row->id);
				$this->db->set('user_id', $this->user_model->getID());
				$this->db->insert('CRDeviceUser');
			}
			else{
				$this->_generateError('Device Not Found',$this->config->item('error_entity_not_found'));
			}
			//finally, generate the default result
			$this->user_model->defaultResult();
		}
		else{
			$this->_generateError('Email is already in use',$this->config->item('error_already_in_use'));
		}
		$this->_response();
	}
	
	//Login or Create New Account via Facebook
	function facebook(){
		//get facebook token
		$facebook_token = $this->post->facebook_token;
		if($facebook_token != ''){
			//load facebook library
			$this->load->library('facebook');
			$facebook = new Facebook(array(
				'appId' => $this->config->item('facebook_app_id'),
				'secret' => $this->config->item('facebook_secret'),
				'cookie' => false
			));
			//set access token
			$facebook->setAccessToken($facebook_token);
			$fb_id = $facebook->getUser();
			//check if valid facebook id
			if($fb_id){
		      	//We have a Facebook ID, so probably a logged in user.
		      	//If not, we'll get an exception, which we handle below.
	     		try {
	     			//Query Facebook API for /me object
	        		$user_profile = $facebook->api('/me','GET');
	        		$facebook_username = $user_profile['name'];
					//check if user is signed up
					$this->db->select('id');
					$chk_stmt = $this->db->get_where('CRUser',array('facebook_id' => $fb_id), 1);
					if($chk_stmt->num_rows() > 0){
						//fetch user details
						$cr_user = $chk_stmt->row();
						$this->user_model->setID($cr_user->id);
						$this->user_model->fetchNotifications();
						$this->user_model->fetchFriends();
						$this->user_model->defaultResult();
					}
					else{
						//create new user
						$this->db->set('created', 'NOW()', FALSE)->set('facebook_id', $fb_id)->set('modified', 'NOW()', FALSE)->set('facebook_username', $facebook_username);					
						$this->db->insert('CRUser');
						$this->user_model->setID($this->db->insert_id());
						$this->user_model->defaultResult();
					}
	      		} catch(FacebookApiException $e) {
	      			$this->_generateError('Facebook Username Could Not Be Found', $this->config->item('error_entity_not_found'));
	      		}   
		    } 
			else{
				$this->_generateError('Facebook ID Could Not Be Found', $this->config->item('error_entity_not_found'));
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Login
	function login(){		
		$this->db->select('id, password_hash');
		//look for matching email in CRUser table
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->post->email), 1);
		if($chk_stmt->num_rows() == 0){
			$this->_generateError('User Could Not Be Found', $this->config->item('error_entity_not_found'));
		}
		else{
			//fetch the row
			$cr_user = $chk_stmt->row();
			//check if credentials match
			if($this->phpass->check($this->post->password, $cr_user->password_hash)){
				//set the proper result for the user
				$this->user_model->setID($cr_user->id);
				$this->user_model->fetchNotifications();
				$this->user_model->fetchFriends();
				$this->user_model->defaultResult();
			}
			else{
				$this->_generateError('Invalid Credentials', $this->config->item('error_not_authorized'));
			}
		}
		$this->_response();
	}
	
	//Reset Lost Password
	function reset(){
		if($this->post->email != ''){
			$this->db->select('id');
			//look for matching email in CRUser table
			$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->post->email), 1);
			if($chk_stmt->num_rows() == 0){
				$this->_generateError('Email Could Not Be Found', $this->config->item('error_entity_not_found'));
			}
			else{
				$cr_user = $chk_stmt->row();
				$this->db->order_by('created', 'desc');
				//check if token is already generated for this user_id. 
				$chk_tkn_stmt = $this->db->get_where('CREmailToken',array('user_id' => $cr_user->id), 1);
				if($chk_tkn_stmt->num_rows() > 0){
					$tkn = $chk_tkn_stmt->row();
					//check if token is expired
					if((strtotime($tkn->created) < (time()-60*60*24)) || $tkn->used == 1){
						//if token expired, generate new email token
						$this->user_model->setID($cr_user->id);
						$this->user_model->newEmailToken();
					}
					else{
						$this->_generateError('Token is Already Generated', $this->config->item('error_token_generated'));
					}	
				}
				else{
					//if no token exists, generate email token
					$this->user_model->setID($cr_user->id);
					$this->user_model->newEmailToken();
				}
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Update User Profile Photo
	function photo($hashedUserID){
		if($hashedUserID != ''){
			$user_id = hashids_decrypt($hashedUserID);
			$chk_stmt = $this->db->get_where('CRUser',array('id' => $user_id), 1);
			if($chk_stmt->num_rows() > 0){
				//load aws library
				$this->load->library('awslib');
				//initiate Amazon class
				$awslib = new Awslib();
				$client = $awslib->S3();
				//convert Base64 encoded photo to jpg
				$base64 = urldecode($this->post('photo'));
				$data = str_replace(' ', '+', $base64);
				$photo_data = base64_decode($data);
				$photo = imagecreatefromstring($photo_data);
				//create a JPG
				ob_start();
				imagejpeg($photo);
				$image = ob_get_clean();
				//put object into S3 bucket
				$result = $client->putObject(array(
				    'Bucket' => 'critterphotos',
				    'Key' => $hashedUserID.'/photo.jpg',
				    'Body' => $image,
				    'ACL' => 'public-read'
				));
				//update database record
				$this->db->where('id', $user_id)->set('photo_url', $result['ObjectURL']);
				$this->db->update('CRUser');
			}
			else{
				$this->_generateError('User Could Not Be Found', $this->config->item('error_entity_not_found'));
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Add Friend
	function addfriend($hashedUserID){
		//check if friend or user id is empty
		if($this->post->friendID != '' && $user_id != ''){
			//decrypt userID and friendID
			$user_id = hashids_decrypt($hashedUserID);
			$friend_id = hashids_decrypt($this->post->friendID);
			//check if user id exists
			$chk_stmt = $this->db->get_where('CRUser',array('id' => $user_id), 1);
			if($chk_stmt->num_rows() > 0){
				//check if friend id exists
				$friend_chk_stmt = $this->db->get_where('CRUser',array('id' => $friend_id), 1);
				if($friend_chk_stmt->num_rows() > 0){
					$friends_chk_stmt = $this->db->get_where('CRFriends',array('user_id' => $user_id, 'friend_id' => $friend_id), 1);
					if($friends_chk_stmt->num_rows() == 0){
						//if both user and friend exists, let's make them friends 
						$this->db->set('created', 'NOW()', FALSE)->set('user_id', $user_id)->set('friend_id', $friend_id);
						$this->db->set('modified', 'NOW()', FALSE);						
						$this->db->insert('CRFriends');
						$this->user_model->setID($user_id);
						$this->user_model->fetchName();
						//check if friend is ignoring user and is not our friend already
						$this->db->select('ignore');
						$friends_stmt = $this->db->get_where('CRFriends',array('user_id' => $friend_id, 'friend_id' => $user_id), 1);
						if($friends_stmt->num_rows() == 0){		
							//and let's send them a notification so they know
							$this->db->set('created', 'NOW()', FALSE)->set('from_user_id', $user_id)->set('to_user_id', $friend_id)->set('message', $this->user_model->getName().' wants to be your Critter friend!');
							$this->db->set('modified', 'NOW()', FALSE);													
							$this->db->insert('CRNotification');
							$notification_id = $this->db->insert_id();
							//set a push notification to each device linked to the friend
							$this->load->model('user_model', 'friend');
							$this->friend->setID($friend_id);
							$this->friend->fetchDevices();
							foreach($this->friend->getDevices() as $device_id){
								//how many push notifications does this device currently have?
								$this->db->select('badge_count');
								$badge_stmt = $this->db->get_where('CRDevice',array('id' => $device_id), 1);
								$r = $badge_stmt->row();
								//increment badge value
								$badge = $r->badge_count + 1;
								$this->db->where('id', $device_id)->set('badge_count', $badge);
								$this->db->update('CRDevice');
								//now create push notification
								$this->db->set('created', 'NOW()', FALSE)->set('device_id', $device_id)->set('notification_id', $notification_id)->set('badge', $badge);
								$this->db->set('modified', 'NOW()', FALSE);														
								$this->db->insert('CRPushNotification');
							}
						}
					}
					else{
						$this->_generateError('Users Are Already Friends', $this->config->item('error_invalid_request'));
					}
				}
				else{
					$this->_generateError('Friend Not Found', $this->config->item('error_entity_not_found'));
				}
			}
			else{
				$this->_generateError('User Not Found', $this->config->item('error_entity_not_found'));
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Remove Friend
	function removefriend($hashedUserID){
		//decrypt userID and friendID
		$user_id = hashids_decrypt($hashedUserID);
		$friend_id = hashids_decrypt($this->post->friendID);
		//check if friend id is empty
		if(!empty($friend_id) && !empty($user_id)){
			//check if user id exists
			$chk_stmt = $this->db->get_where('CRUser',array('id' => $user_id), 1);
			if($chk_stmt->num_rows() > 0){
				//check if friend id exists
				$friend_chk_stmt = $this->db->get_where('CRUser',array('id' => $friend_id), 1);
				if($friend_chk_stmt->num_rows() > 0){
					//get most recent record
					$this->db->order_by('created', 'desc');
					$this->db->select('id');
					$friends_stmt = $this->db->get_where('CRFriends',array('user_id' => $user_id, 'friend_id' => $friend_id), 1);
					if($friends_stmt->num_rows() == 0){
						//add matching entry in CRFriend table
						$this->db->set('created', 'NOW()', FALSE)->set('user_id', $user_id)->set('friend_id', $friend_id)->set('ignore', 1);
						$this->db->set('modified', 'NOW()', FALSE);						
						$this->db->insert('CRFriends');
					}
					else{
						//update current entry in CRFriend table
						$friends = $friends_stmt->row();
						$this->db->where('id', $friends->id)->set('ignore', 1);
						$this->db->update('CRFriends');
					}
				}
				else{
					$this->_generateError('Friend Not Found', $this->config->item('error_entity_not_found'));
				}
			}
			else{
				$this->_generateError('User Not Found', $this->config->item('error_entity_not_found'));
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Password Update
	function updatepass(){
		if(!empty($this->post->user) && !empty($this->post->pass)){
			$this->db->where('id', $this->post->user)->set('password_hash', $this->phpass->hash($this->post->pass));
			$this->db->update('CRUser');
		}
		else{
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
		$this->user_model->setStatus($status);
		$this->user_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->user_model->getStatus();
		$data['message'] = $this->user_model->getMessage();
		$data['result'] = $this->user_model->getResult();
		$this->load->view('standard_response', $data);
	}
}