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
		 /* get all the Mixpanel files */
        $this->mixpanel_dir = $_SERVER['DOCUMENT_ROOT'].'/application/third_party/mixpanel-php/lib/';
		$this->_mixpanel_req();
		$this->mp = Mixpanel::getInstance($this->config->item('mixpanel_project_token'));
	}
	
	private function _mixpanel_req(){
		require_once $this->mixpanel_dir.'Mixpanel.php';
	}
	
	//Create new Account
	function signup()
	{
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->post->email), 1);
		if($chk_stmt->num_rows() == 0)
		{
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('modified', 'NOW()', FALSE);			
			$this->db->set('name', $this->post->name);
			$this->db->set('password_hash', $this->phpass->hash($this->post->password));
			$this->db->set('email', $this->post->email);
			$this->db->insert('CRUser');
			$this->user_model->setID($this->db->insert_id());
			
			//Associate device
			$this->associateDeviceWithUser();
			
			//Track the user sign up
			$this->mp->track("User Signed Up", array("label" => "sign-up"));

			//finally, generate the default result
			$this->user_model->defaultResult();
		}
		else{
			$this->_generateError('Email is already in use',$this->config->item('error_already_in_use'));
		}
		$this->_response();
	}
	
	function associateDeviceWithUser()
	{
		//Find the device, bail on fail
		$deviceVendorID = $this->input->get_request_header('critter-device', TRUE);
		$this->db->from('CRDevice');
		$this->db->where('device_vendor_id',$deviceVendorID);
		$row = $this->db->get()->row();
		if (!$row)
		{
			return;
		}		

		//Delete existing linkages for this device (so we only push to the most recent user on a single device)
		$this->db->where('device_id', $row->id);
		$this->db->delete('CRDeviceUser');
		
		//Insert a new linkage
		$this->db->set('device_id', $row->id);
		$this->db->set('user_id', $this->user_model->getID());
		$this->db->insert('CRDeviceUser');
	}
	
	//Login or Create New Account via Facebook
	function facebook()
	{
		//get facebook token
		$facebook_token = $this->post->facebook_token;
		if($facebook_token != '')
		{
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
			if($fb_id)
			{
		      	//We have a Facebook ID, so probably a logged in user.
		      	//If not, we'll get an exception, which we handle below.
	     		try 
	     		{
	     			//Query Facebook API for /me object
	        		$user_profile = $facebook->api('/me','GET');
	        		$facebook_name = $user_profile['name'];	        		
	        		$facebook_username = $user_profile['username'];
	        		$facebook_email = $user_profile['email'];
	        		$facebook_photo_url = "http://graph.facebook.com/$facebook_username/picture?type=large";

					//check if user is signed up
					$this->db->select('id');
					$this->db->where('facebook_id', $fb_id);
					$query = $this->db->get('CRUser');
					$user = $query->row();

					//Update DB fields - we will either be doing a create, or an update
					$this->db->set('name', $facebook_name);						
					$this->db->set('email', $facebook_email);
					$this->db->set('facebook_username', $facebook_username);
					$this->db->set('photo_url', $facebook_photo_url);
					$this->db->set('modified', 'NOW()', FALSE);
					
					//If user exists, update it 
					if($user)
					{
						//Update user account details (FB may have changed)
						$this->db->where('facebook_id', $fb_id);
						$this->db->update('CRUser');
					
						//fetch user details
						$this->user_model->setID($user->id);
						$this->user_model->fetchNotifications();
						$this->user_model->fetchFriends();
						$this->user_model->defaultResult();
					}
					else
					{
						//create new user
						$this->db->set('created', 'NOW()', FALSE);
						$this->db->set('facebook_id', $fb_id);
							
						//Set a default junk password so the account isn't blank - users can reset
						$this->db->set('password_hash', sha1($fb_id . "junk")); //Just to put something in so we don't have an empty login for this account - user can run pw reset if they need to log in later without FB
						
						$this->db->insert('CRUser');
						$this->user_model->setID($this->db->insert_id());
						$this->user_model->defaultResult();
					}
					
					//Associate device
					$this->associateDeviceWithUser();
					
	      		} 
	      		catch(FacebookApiException $e) 
	      		{
	      			$this->_generateError($e->getMessage(), $this->config->item('error_entity_not_found'));
	      		}   
		    } 
			else
			{
				$this->_generateError('Facebook ID Could Not Be Found', $this->config->item('error_entity_not_found'));
			}
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Login
	function login()
	{		
		$this->db->select('id, password_hash');
		//look for matching email in CRUser table
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->post->email), 1);
		if($chk_stmt->num_rows() == 0)
		{
			$this->_generateError('User Could Not Be Found', $this->config->item('error_entity_not_found'));
		}
		else
		{
			//fetch the row
			$cr_user = $chk_stmt->row();
			//check if credentials match
			if($this->phpass->check($this->post->password, $cr_user->password_hash))
			{
				//set the proper result for the user
				$this->user_model->setID($cr_user->id);
				$this->user_model->fetchNotifications();
				$this->user_model->fetchFriends();
				$this->user_model->defaultResult();
				
				//Associate device
				$this->associateDeviceWithUser();
			}
			else
			{
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
	function addfriend($hashedUserID)
	{
		//check if friend or user id is empty
		if ($this->post->friendID != '' && $hashedUserID != '')
		{
			//decrypt userID and friendID
			$user_id = hashids_decrypt($hashedUserID);
			$friend_id = hashids_decrypt($this->post->friendID);
			$this->db->from('CRUser');
			$this->db->where('id', $user_id);
			$user = $this->db->get()->row();
			
			//check if user->friend friendship exiss
			$this->db->from('CRFriends');
			$this->db->where('user_id', $user_id);
			$this->db->where('friend_id', $friend_id);
			$chk_stmt = $this->db->get();
			if ($chk_stmt->num_rows() == 0)
			{
				//Check for friend->user friendship (or ignore)
				$this->db->from('CRFriends');
				$this->db->where('user_id', $friend_id);
				$this->db->where('friend_id', $user_id);				
				$friends_stmt = $this->db->get();
				$existingFriendship = $friends_stmt->row();
				if (!$existingFriendship || $existingFriendship->ignore==0)
				{
					//Establish the friendship
					$this->db->set('user_id', $user_id);
					$this->db->set('friend_id', $friend_id);
					$this->db->set('created', 'NOW()', FALSE);
					$this->db->set('modified', 'NOW()', FALSE);						
					$this->db->insert('CRFriends');
					$this->user_model->setID($user_id);
					$this->user_model->fetchName();
					
					if (!$existingFriendship)
					{
						//Send them a notification
						$message = $user->name . ' wants to be your Critter friend!';
						$this->db->set('from_user_id', $user_id);
						$this->db->set('to_user_id', $friend_id);
						$this->db->set('notification_type', 'friendrequest');
						$this->db->set('message', $message);
						$this->db->set('created', 'NOW()', FALSE);
						$this->db->set('modified', 'NOW()', FALSE);
						$this->db->insert('CRNotification');
						$notification_id = $this->db->insert_id();

						//set a push notification to each device linked to the friend
						$this->db->select('CRDevice.*');						
						$this->db->from('CRDevice');
						$this->db->join('CRDeviceUser', 'CRDevice.id = CRDeviceUser.user_id');
						$this->db->where('CRDeviceUser.user_id', $friend_id);
						$query = $this->db->get();
						foreach ($query->result() as $device)
						{
							//increment badge value
							$badge = $device->badge_count + 1;
							$this->db->where('id', $device->id);
							$this->db->set('badge_count', $badge);
							$this->db->update('CRDevice');
							
							//now create push notification
							$this->db->set('device_id', $device->id);
							$this->db->set('message', $message);
							$this->db->set('notification_id', $notification_id);							
							$this->db->set('badge', $badge);
							$this->db->set('created', 'NOW()', FALSE);
							$this->db->set('modified', 'NOW()', FALSE);														
							$this->db->insert('CRPushNotification');
						}
					}
				}
				else
				{
					$this->_generateError('Friendship Denied By Recipient', $this->config->item('error_entity_not_found'));
				}
			}
			else
			{
				$this->_generateError('Friendship Already Exists', $this->config->item('error_entity_not_found'));
			}
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Remove Friend
	function removefriend($hashedUserID)
	{
		//decrypt userID and friendID
		$user_id = hashids_decrypt($hashedUserID);
		$friend_id = hashids_decrypt($this->post->friendID);
		
		if ($user_id && $friend_id)
		{
			//Remove friendship if it exists
			$this->db->where('user_id', $user_id);
			$this->db->where('friend_id', $friend_id);
			$this->db->delete('CRFriends');
		}
		else
		{
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