<?php
/**
 * Ratings Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Ratings extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('ratings_model');
		$this->load->driver('cache');
		$this->post = json_decode(file_get_contents('php://input'));
		$this->load->model('push_model');		
	}
	
	private function messageForRating($userName, $rating, $originalRating = NULL)
	{
		$message = NULL;
		
		//Basic rating 
		switch($rating)
		{
			case 1: $message = $userName . " recommends:"; break;
			case 2: $message = $userName . " does not recommend:"; break;
			case 3: $message = $userName . " sent you an invite to:"; break;						
			case 4: $message = $userName . " scraplisted:"; break;						
		}
		
		//If not a reply, bail
		if ($originalRating == NULL)
		{
			return $message;
		}
		
		switch($originalRating)
		{
             case 1: //CRMovieActionRecommend:
             {
                 switch ($rating)
                 {
					 case 1: $message = $userName . " agreed and liked:"; break;
					 case 2: $message = $userName . " disagreed and disliked:"; break;
					 case 3: $message = $userName . " took note and watchlisted:"; break;
					 case 4: $message = $userName . " ignored your recommendation and rejected:"; break;							 							 							 
                 }
                 break;
             }
             case 2: //CRMovieActionDontRecommend:
             {
                 switch ($rating)
                 {
					 case 1: $message = $userName . " disagreed and liked:"; break;
					 case 2: $message = $userName . " agreed and disliked:"; break;
					 case 3: $message = $userName . " ignored your warning and watchlisted:"; break;
					 case 4: $message = $userName . " took note and rejected:"; break;							 							 							 
                 }
                 break;
             }
             case 3: //CRMovieActionWatchList:
             {
                 switch ($rating)
                 {
					 case 1: $message = $userName . " watched and recommended:"; break;
					 case 2: $message = $userName . " watched and did not recommend:"; break;
					 case 3: $message = $userName . " accepted your invitation to watch:"; break;
					 case 4: $message = $userName . " was not interested in watching:"; break;							 							 							 
                 }
                 break;
             }					
		}
		return $message;
	}
	
	function notifyFriendsForRating($friends, $rating_id)
	{
		error_log("Notifying friends for rating $rating_id:.".json_encode($friends));
	
		//Look up rating
		$this->db->from('CRRating');
		$this->db->where('id', $rating_id);
		$rating = $this->db->get()->row();
		
		//Look up movie
		$this->db->from('CRMovie');
		$this->db->where('id', $rating->movie_id);
		$movie = $this->db->get()->row();
				
		//Look up user
		$this->db->from('CRUser');
		$this->db->where('id', $rating->user_id);
		$user = $this->db->get()->row();

		//Set up the notification tyoe and message
		$notification_type = "invite";
		$message = $this->messageForRating($user->name, $rating->rating);
		
		//Loop and notify friends
		foreach($friends as $friendHashedID)
		{
			//Get the friend id
			$friend_id = hashids_decrypt($friendHashedID);
			
			//If this friend already sent _us_ a notification on this movie, we need to frame this as a reply
			$this->db->select('CRRating.*');
			$this->db->from('CRNotification');
			$this->db->join('CRRating', 'CRNotification.rating_id=CRRating.id');
			$this->db->join('CRMovie', 'CRRating.movie_id=CRMovie.id');
			$this->db->where('CRNotification.from_user_id', $friend_id);
			$this->db->where('CRNotification.to_user_id', $user->id);
			$this->db->where('CRNotification.notification_type','invite');
			$this->db->where('CRRating.movie_id',$movie->id);
			$originalRating = $this->db->get()->row();
			if($originalRating)
			{
				//User did send us a rating first - frame the reply
				$notification_type = "reply";
				$message = $this->messageForRating($user->name, $rating->rating, $originalRating->rating);
			}
			
			//Add a CRNotification
			$this->db->set("notification_type", $notification_type);
			$this->db->set("from_user_id", $user->id);
			$this->db->set("to_user_id", $friend_id);					
			$this->db->set("rating_id", $rating_id);	
			$this->db->set("message", $message);
			$this->db->set("created", "NOW()", FALSE);
			$this->db->set("modified", "NOW()", FALSE);
			$this->db->insert("CRNotification");		
			$notification_id = $this->db->insert_id();

			//set a push notification to each device linked to the friend
			$this->db->select('CRDevice.*');						
			$this->db->from('CRDevice');
			$this->db->join('CRDeviceUser', 'CRDevice.id = CRDeviceUser.device_id');
			$this->db->where('CRDeviceUser.user_id', $friend_id);
			$this->db->where('CRDevice.push_token IS NOT NULL');			
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
				$this->db->set('message', $message . "\n" . $movie->title);
				$this->db->set('notification_id', $notification_id);							
				$this->db->set('badge', $badge);
				$this->db->set('created', 'NOW()', FALSE);
				$this->db->set('modified', 'NOW()', FALSE);														
				$this->db->insert('CRPushNotification');
			}
			
			//Send pending pushes now - we may change this to a cron and remove this call later, but for now send ASAP.
			$this->push_model->send();
		}
	}
	
	//Update Movie Rating for User
	function update($hashedUserID)
	{
		if($hashedUserID!= NULL && $this->post->movie_id != '' && $this->post->rating != '')
		{
			//Get user's device
			$this->db->where('device_vendor_id', $this->input->get_request_header('Critter-device', TRUE));
			$device = $this->db->get('CRDevice')->row();
			
			//Find existing rating for user and movie
			$rating_id = NULL;
			$user_id = hashids_decrypt($hashedUserID);
			$movie_id = hashids_decrypt($this->post->movie_id);			
			$this->db->from('CRRating');
			$this->db->where('user_id', $user_id);
			$this->db->where('movie_id', $movie_id);
			$query = $this->db->get();
			if ($query->num_rows() == 0)
			{
				//Insert a new rating
				$this->db->set('user_id', $user_id);
				$this->db->set('movie_id', $movie_id);
				$this->db->set('rating', $this->post->rating);
				if (array_key_exists("comment", $this->post)) $this->db->set('comments', $this->post->comment);
				if (array_key_exists("notified_box_office", $this->post)) $this->db->set('notified_box_office', $this->post->notified_box_office);
				if (array_key_exists("notified_dvd", $this->post)) $this->db->set('notified_dvd', $this->post->notified_dvd);					$this->db->set('created', 'NOW()', FALSE);
				$this->db->set('modified', 'NOW()', FALSE);				
				$this->db->insert('CRRating');
				$rating_id = $this->db->insert_id();
			} 
			else
			{
				//Update existing rating
				$rating = $query->row();
				$rating_id = $query->row()->id;
				$this->db->where('id', $rating_id);
				$this->db->set('rating', $this->post->rating);
				if (array_key_exists("comment", $this->post)) $this->db->set('comments', $this->post->comment);
				if (array_key_exists("notified_box_office", $this->post)) $this->db->set('notified_box_office', $this->post->notified_box_office);
				if (array_key_exists("notified_dvd", $this->post)) $this->db->set('notified_dvd', $this->post->notified_dvd);	
				$this->db->set('modified', 'NOW()', FALSE);				
				$this->db->update('CRRating');
				
				//Look up movie
				$this->db->from('CRMovie');
				$this->db->where('id', $rating->movie_id);
				$movie = $this->db->get()->row();
						
				//Look up user
				$this->db->from('CRUser');
				$this->db->where('id', $rating->user_id);
				$user = $this->db->get()->row();				
				
				//Loop and update any existing notifications that are tied to this rating, to handle changes
				$this->db->from('CRNotification');
				$this->db->where('rating_id', $rating_id);
				foreach($this->db->get()->result() as $notification)
				{
					
					//Set new message
					$message = $this->messageForRating($user->name, $this->post->rating);
					
					//If this is a scraplist rating, we want to hide the notification
					if ($this->post->rating == 4)
					{
						$this->db->set('is_viewed', TRUE);		
					}
						
					//If this friend already sent _us_ a notification on this movie, we need to frame this as a reply
					$this->db->select('CRRating.*');
					$this->db->from('CRNotification');
					$this->db->join('CRRating', 'CRNotification.rating_id=CRRating.id');
					$this->db->join('CRMovie', 'CRRating.movie_id=CRMovie.id');
					$this->db->where('CRNotification.from_user_id', $notification->to_user_id);
					$this->db->where('CRNotification.to_user_id', $user->id);
					$this->db->where('CRNotification.notification_type','invite');
					$this->db->where('CRRating.movie_id',$movie->id);
					$originalRating = $this->db->get()->row();
					if($originalRating)
					{
						$message = $this->messageForRating($user->name, $this->post->rating, $originalRating->rating);
					}				
					
					//Update the notification
					$this->db->where('id', $notification->id);
					$this->db->set('message', $message);
					$this->db->set("modified", "NOW()", FALSE);			
					$this->db->update('CRNotification');
				}
			}
			
			//Add action to analytics
			$this->db->set('subject', 'user');
			$this->db->set('subject_id', $user_id);
			$this->db->set('subject_type', 'user');
			$this->db->set('event', 'rates');
			$this->db->set('event_id', $rating_id);
			$this->db->set('event_type', $this->post->rating);
			$this->db->set('object', 'movie');
			$this->db->set('object_id', $movie_id);
			$this->db->set('device', 'iPad');
			$this->db->set('device_id', $device->id);
			$this->db->set('created', 'NOW()', FALSE);	
			$this->db->set('modified', 'NOW()', FALSE);					
			$this->db->insert('CRAnalytics');
			
			//NOTE: Intentionally not updating critter ratings on every insert; they are re-calculated as they expire from cache.
			$this->ratings_model->setResult(hashids_encrypt($rating_id));
			
			//Loop and notify friends?
			if ($this->post->notify_friends)
			{
				$this->notifyFriendsForRating($this->post->notify_friends, $rating_id);
			}
			
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch Movie Rating for Specific User and Movie
	function movie($hashedUserID, $hashedMovieID)
	{
		if($hashedUserID != '' || $hashedMovieID != '')
		{
			//Do the query
			$user_id = hashids_decrypt($hashedUserID);
			$movie_id = hashids_decrypt($hashedMovieID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->where('user_id', $user_id);
			$this->db->where('movie_id', $movie_id);	
			$chk_stmt = $this->db->get();					
			$rating = $chk_stmt->row();
			
			//Clean up the result
			$rating->id = hashids_encrypt($rating->id);
			$rating->user_id = hashids_encrypt($rating->user_id);
			$rating->movie_id = hashids_encrypt($rating->movie_id);
			$this->ratings_model->setResult($rating);
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch Movie Ratings for User
	function user($hashedUserID, $modifiedSinceDateTime = NULL)
	{
		$results = array();
		if ($hashedUserID != '')
		{
			//Set up the query
			$user_id = hashids_decrypt($hashedUserID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path');			
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->where('user_id', $user_id);
			$this->db->order_by('CRRating.created', 'asc'); //creation order
			
			//Add filter on modified field if present
			if ($modifiedSinceDateTime)
			{
				$this->db->where('CRRating.modified >', urldecode($modifiedSinceDateTime));
			}

			//Query and clean up the results
			$chk_stmt = $this->db->get();
			$results = $chk_stmt->result();	
			foreach($results as $rating)
			{
				$rating->id = hashids_encrypt($rating->id);
				$rating->user_id = hashids_encrypt($rating->user_id);
				$rating->movie_id = hashids_encrypt($rating->movie_id);
			}
			$this->ratings_model->setResult($results);
		}
		else
		{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Fetch All Ratings for Movie
	function all($hashedMovieID, $limit = 100, $offset = 0)
	{
		if($hashedMovieID != '')
		{
			//Set up the query
			$movie_id = hashids_decrypt($hashedMovieID);
			$this->db->select('CRRating.*, CRMovie.title, CRMovie.hashtag, CRMovie.rotten_tomatoes_id, CRMovie.tmdb_poster_path, CRUser.name as user_name, CRUser.photo_url as user_photo_url');
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');
			$this->db->join('CRUser', 'CRUser.id = CRRating.user_id');			
			$this->db->where('movie_id', $movie_id);
			$this->db->where_in('rating', array(1,2)); //Only likes/dislikes
			$this->db->order_by('CRRating.created', 'desc'); //Newest first
			$this->db->limit($limit);
			$this->db->offset($offset);
			
			//Query and clean up the results
			$chk_stmt = $this->db->get();
			$results = $chk_stmt->result();	
			foreach($results as $rating)
			{
				$rating->id = hashids_encrypt($rating->id);
				$rating->user_id = hashids_encrypt($rating->user_id);
				$rating->movie_id = hashids_encrypt($rating->movie_id);
			}
			$this->ratings_model->setResult($results);
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
		$this->ratings_model->setStatus($status);
		$this->ratings_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->ratings_model->getStatus();
		$data['message'] = $this->ratings_model->getMessage();
		$data['result'] = $this->ratings_model->getResult();
		$this->load->view('standard_response', $data);
	}
}