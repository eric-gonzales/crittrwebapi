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
		$this->load->model('push_model');		
		$this->post = json_decode(file_get_contents('php://input'));
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
			$this->db->set('name', $this->post->username);
			$this->db->set('password_hash', $this->phpass->hash($this->post->password));
			$this->db->set('email', $this->post->email);
			$this->db->insert('CRUser');
			$this->user_model->setID($this->db->insert_id());
			
			//Associate device
			$this->associateDeviceWithUser();

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
		$deviceVendorID = $this->input->get_request_header('Critter-device', TRUE);
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
	
	function linkfacebook($hashedUserID)
	{
		//Check for required fields
		$facebook_token = $this->post->facebook_token;
		$user_id = hashids_decrypt($hashedUserID);
		if (!$user_id)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();				
			return;
		}
		
		//Load the user, bail on fail
		$this->db->from('CRUser');
		$this->db->where('id', $user_id);
		$user = $this->db->get()->row();
		if (!$user)
		{
			$this->_generateError('User not found', $this->config->item('error_entity_not_found'));		
			$this->_response();				
			return;
		}
		
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
		if($fb_id)
		{
			//See if another user account is already linked
			$this->db->from('CRUser');
			$this->db->where('facebook_id', $fb_id);
			$user = $this->db->get()->row();
			if ($user && $user->id != $user_id)
			{
				$this->_generateError('Facebook account already linked to another Critter user.', $this->config->item('error_already_in_use'));		
				$this->_response();				
				return;
			}
			
			//All is well, link the account
			$this->db->set('facebook_id', $fb_id);
			$this->db->where('id', $user_id);
			$this->db->update('CRUser');
			$this->_response();							
		}
		else
		{
			$this->_generateError('Facebook user not found', $this->config->item('error_entity_not_found'));		
			$this->_response();				
		}
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
					$facebook_gender = $user_profile['gender'];
					$facebook_birthday = $user_profile['birthday'];
					
	        		if ($facebook_email == "")
	        		{
		        		$facebook_email = "$facebook_username@facebook.com";
	        		}
	        		$facebook_photo_url = "http://graph.facebook.com/$fb_id/picture?type=large";

					error_log("Facebook login for $facebook_token : " . json_encode($user_profile));

					//check if user is signed up
					$this->db->select('id');
					$this->db->where('facebook_id', $fb_id);
					$this->db->or_where('email', $facebook_email);					
					$query = $this->db->get('CRUser');
					$user = $query->row();

					//Update DB fields - we will either be doing a create, or an update
					$this->db->set('name', $facebook_name);						
					$this->db->set('email', $facebook_email);
					$this->db->set('facebook_username', $facebook_username);
					$this->db->set('photo_url', $facebook_photo_url);
					$this->db->set('modified', 'NOW()', FALSE);
					$this->db->set('facebook_id', $fb_id);
					$this->db->set('gender', $facebook_gender);
					
					$facebook_education = '';
					if(array_key_exists('education', $user_profile)){
						foreach($user_profile['education'] as $school){
							$facebook_education = $school['type'];
						}
					}
					
					$facebook_location = '';
					if(array_key_exists('location', $user_profile)){
							$facebook_location = $user_profile['location']['id'];
					}
					
					$this->db->set('education', $facebook_education);
				//	$this->db->set('location', $facebook_location);
					$this->db->set('birthday', date('Y-m-d H:i:s', strtotime(stripslashes($facebook_birthday))));
					
					//If user exists, update it 
					if($user)
					{
						//Update user account details (FB may have changed)
						$this->db->where('id', $user->id);
						$this->db->update('CRUser');
					
						//fetch user details
						$this->user_model->setID($user->id);
						$this->user_model->fetchFriends();
						$this->user_model->fetchVODProviders();								
						$this->user_model->defaultResult();
					}
					else
					{
						//create new user
						$this->db->set('created', 'NOW()', FALSE);
							
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
				$this->user_model->fetchFriends();
				$this->user_model->fetchVODProviders();				
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
	function photo($hashedUserID)
	{
		//Sanity check
		$user_id = hashids_decrypt($hashedUserID);
		if (!$hashedUserID || !$user_id)
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
			$this->_response();
			return;
		}
	
		//Get the user, bail on fail
		$this->db->from('CRUser');
		$this->db->where('id', $user_id);
		$user = $this->db->get()->row();
		if (!$user)
		{
			$this->_generateError('User not found', $this->config->item('error_entity_not_found'));
			$this->_response();
			return;
		}
		
		//load aws library
		$this->load->library('awslib');
		$client = $this->awslib->S3();

		//Decode base64 photo data
		$photo_data = base64_decode($this->post->photo);
				
		//Save it to S3
		$fileKey = md5($hashedUserID) . ".jpg";
		$bucket = "critterphotos"; //TODO: Read this from a config
		$client->upload($bucket, $fileKey, $photo_data, 'private');
		
		//Generate the URL
		$expires = time() + (31536000 * 20); //URLs good for 20 years
		$command = $client->getCommand('GetObject', array('Bucket' => $bucket,'Key' => $fileKey));
		$photo_url = $command->createPresignedUrl($expires);		

		//Update the user account
		$this->db->set('photo_url', $photo_url);
		$this->db->set('modified', 'NOW()', FALSE);
		$this->db->where('id', $user_id);
		$this->db->update('CRUser');

		//Done!
		$this->_response();
	}
	
	//Add Friends from Facebook and Email Contacts
	function addContactFriends($hashedUserID)
	{
		//Sanity check - ensure require fields
		$user_id = hashids_decrypt($hashedUserID);
		if (!$user_id || (!$this->post->emails && !$this->post->facebook_ids))
		{
			echo("User $user_id emails: ". json_encode($this->post->emails) . " facebook_ids:" . json_encode($this->post->facebook_ids));
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));		
			$this->_response();				
			return;
		}
		
		//Look up the user
		$this->db->from('CRUser');
		$this->db->where('id', $user_id);
		$user = $this->db->get()->row();		
	
		//Build the list of matching people
		$this->db->select(array('id','name','facebook_id','facebook_username','photo_url'));
		$this->db->from('CRUser');
		if ($this->post->emails && $this->post->facebook_ids)
		{
			$this->db->where_in('email', $this->post->emails);
			$this->db->or_where_in('facebook_id', $this->post->facebook_ids);
		}
		else if ($this->post->facebook_ids)
		{
			$this->db->where_in('facebook_id', $this->post->facebook_ids);
		}
		else
		{
			$this->db->where_in('email', $this->post->emails);
		}
		$query = $this->db->get();
		
		//Loop and add matching friends
		$new_friends = array();
		foreach($query->result() as $friend)
		{
			$friend_id = $friend->id;
		
			//Look up the friendship and add if 
			$this->db->from('CRFriends');
			$this->db->where('user_id', $user_id);
			$this->db->where('friend_id', $friend->id);
			$chk_stmt = $this->db->get();
			if ($chk_stmt->num_rows() == 0)
			{
				//Establish the friendship
				$this->db->set('user_id', $user_id);
				$this->db->set('friend_id', $friend_id);
				$this->db->set('created', 'NOW()', FALSE);
				$this->db->set('modified', 'NOW()', FALSE);						
				$this->db->insert('CRFriends');
				$this->user_model->setID($user_id);
				$this->user_model->fetchName();
				
				//Add the friend to the results
				$friend->id = hashids_encrypt($friend->id);
				array_push($new_friends, $friend);

				//Check for friend->user friendship (or ignore)
				$this->db->from('CRFriends');
				$this->db->where('user_id', $friend_id);
				$this->db->where('friend_id', $user_id);
				$friends_stmt = $this->db->get();
				$existingFriendship = $friends_stmt->row();
				
				//Check for existing friend request
				$this->db->from('CRNotification');
				$this->db->where('from_user_id', $user_id);
				$this->db->where('to_user_id', $friend_id);
				$this->db->where('notification_type', 'friendrequest');
				$existingRequest = $this->db->get()->row();
				
				if (!$existingFriendship && !$existingRequest)
				{
					//No existing friendship - Send them a notification
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
					$this->push_model->queuePushForUser($friend_id, $message, $notification_id);
					
					//Send pending pushes now - we may change this to a cron and remove this call later, but for now send ASAP.
					$this->push_model->send();
				}
			}
		}
		
		$this->user_model->setResult($new_friends);
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
				//Establish the friendship
				$this->db->set('user_id', $user_id);
				$this->db->set('friend_id', $friend_id);
				$this->db->set('created', 'NOW()', FALSE);
				$this->db->set('modified', 'NOW()', FALSE);						
				$this->db->insert('CRFriends');
				$this->user_model->setID($user_id);
				$this->user_model->fetchName();

				//Check for friend->user friendship (or ignore)
				$this->db->from('CRFriends');
				$this->db->where('user_id', $friend_id);
				$this->db->where('friend_id', $user_id);
				$friends_stmt = $this->db->get();
				$existingFriendship = $friends_stmt->row();
				
				//Check for existing friend request
				$this->db->from('CRNotification');
				$this->db->where('from_user_id', $user_id);
				$this->db->where('to_user_id', $friend_id);
				$this->db->where('notification_type', 'friendrequest');
				$existingRequest = $this->db->get()->row();
				
				if (!$existingFriendship && !$existingRequest)
				{
					//No existing friendship - Send them a notification
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
					$this->push_model->queuePushForUser($friend_id, $message, $notification_id);
					
					//Send pending pushes now - we may change this to a cron and remove this call later, but for now send ASAP.
					$this->push_model->send();
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
	
	//Get friends for a given user
	function friendsForUser($hashedUserID)
	{
		//decrypt userID
		$user_id = hashids_decrypt($hashedUserID);
		
		//Look up their friends
		$this->user_model->setID($user_id);
		$this->user_model->fetchFriends();
		$this->user_model->setResult($this->user_model->getFriends());
		$this->_response();
	}
	
	//Password Update
	function update($hashedUserID)
	{
		$user_id = hashids_decrypt($hashedUserID);
		if ($user_id)
		{
			//If the user specified an email, ensure it's not used by any other accounts
			if ($this->post->email)
			{
				$this->db->where('email', $this->post->email);
				$this->db->where('id <>', $user_id);
				$this->db->from('CRUser');
				$user = $this->db->get()->row();
				if ($user)
				{
					$this->_generateError('Email address is already in use.', $this->config->item('error_already_in_use'));		
					$this->_response();							
					return;
				}
			}
		
			if ($this->post->email) $this->db->set('email', $this->post->email);
			if ($this->post->password) $this->db->set('password_hash', $this->phpass->hash($this->post->password));
			if ($this->post->push_enabled) $this->db->set('push_enabled', $this->post->push_enabled);
			if ($this->post->push_watchlist_enabled) $this->db->set('push_watchlist_enabled', $this->post->push_watchlist_enabled);			
			$this->db->where('id', $user_id);
			$this->db->update('CRUser');
			
			//Update user's VOD providers
			if ($this->post->vodproviders)
			{
				//Delete existing choices
				$this->db->where('user_id', $user_id);
				$this->db->delete('CRUserVOD');
				
				//Loop and insert new choices
				foreach ($this->post->vodproviders as $provider_identifier)
				{
					//Look it up
					$this->db->where('identifier', $provider_identifier);
					$this->db->from('CRVODProvider');
					$vod = $this->db->get()->row();
					if ($vod)
					{
						$this->db->set('user_id', $user_id);
						$this->db->set('vod_id', $vod->id);
						$this->db->insert('CRUserVOD');
					}
				}
			}
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	function notifications($hashedUserID, $modifiedSinceDateTime = NULL)
	{
		$user_id = hashids_decrypt($hashedUserID);
		if ($user_id)
		{
			//Base query
			$this->db->from('CRNotification');
			$this->db->where('to_user_id', $user_id);
			$this->db->order_by('modified', 'asc');
			
			//Add filter on modified field if present
			if ($modifiedSinceDateTime)
			{
				$this->db->where('CRNotification.modified >', urldecode($modifiedSinceDateTime));
			}
			
			//Loop and process
			$notifications = array();
			$query = $this->db->get();			
			foreach($query->result() as $notification)
			{
				//Look up the from user
				$this->db->select('id, name, email, photo_url, facebook_id, facebook_username');				
				$this->db->from('CRUser');
				$this->db->where('id', $notification->from_user_id);
				$fromUser = $this->db->get()->row();
				$fromUser->id = hashids_encrypt($fromUser->id);
				
				//Look up the rating, if we have one
				$rating = NULL;
				if ($notification->rating_id != NULL)
				{
					$ratingID = $notification->rating_id;
					$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');
					$this->db->from('CRRating');
					$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
					$this->db->where('CRRating.id', $ratingID);
					$rating = $this->db->get()->row();
					$rating->id = hashids_encrypt($rating->id);
					$rating->movie_id = hashids_encrypt($rating->movie_id);					
				}
				
				//Add the notification
				$notifications[] = array(
					'id' => hashids_encrypt($notification->id),
					'notification_type' => $notification->notification_type,
					'rating' => $rating,
					'from_user' => $fromUser,
					'to_user_id' => hashids_encrypt($notification->to_user_id),
					'is_viewed' => $notification->is_viewed,					
					'message' => $notification->message,
					'created' => $notification->created,
					'modified' => $notification->modified,
				);
			}
			
			$this->user_model->setResult($notifications);			
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
			
	}
	
	//User search
	function search($hashedUserID, $searchText)
	{
		$user_id = hashids_decrypt($hashedUserID);
		if ($user_id)
		{
			$this->db->from('CRUser');
			$this->db->select(array('id','name','facebook_id','photo_url'));
			$this->db->like('name', urldecode($searchText));
			$this->db->where('active', 1);
			$this->db->limit(100);
			$result = $this->db->get()->result();
			foreach($result as $user)
			{
				$user->id = hashids_encrypt($user->id);
			}

			$this->user_model->setID($user_id);
			$this->user_model->setResult($result);
		}		
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	function report($hashedUserID, $hashedUserIDToReport, $hashedRatingID=NULL)
	{
		$user_id = hashids_decrypt($hashedUserID);
		$report_user_id = hashids_decrypt($hashedUserIDToReport);		
		$rating_id = hashids_decrypt($hashedRatingID);		
		if ($user_id && $report_user_id)
		{
			//Insert the user report
			$this->db->set('reporting_user_id', $user_id);
			$this->db->set('reported_user_id', $report_user_id);
			if ($rating_id != NULL) $this->db->set('rating_id', $rating_id);
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('modified', 'NOW()', FALSE);						
			$this->db->insert('CRUserReport');
			
			//Flag this user inactive - they can still use the app but won't show up in user searches or reviews
			$this->db->set('active',0);
			$this->db->where('id', $report_user_id);
			$this->db->set('modified', 'NOW()', FALSE);						
			$this->db->update('CRUser');
		}		
		else
		{
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
