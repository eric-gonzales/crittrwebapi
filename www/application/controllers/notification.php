<?php
/**
 * Notification Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Notification extends CI_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('notification_model');
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	//Send Notification
	public function send(){
		$from_user_id = $this->post->fromUserID;
		$to_user_id = $this->post->toUserID;
		$notification_type = $this->post->notificationType;
		$rating_id = $this->post->ratingID;
		//check required post fields
		if($from_user_id != '' && $to_user_id != '' && $notification_type != '' && $rating_id != ''){
			//decrypt information
			$from_user_id = hashids_decrypt($from_user_id);
			$to_user_id = hashids_decrypt($to_user_id);
			$rating_id = hashids_decrypt($rating_id);
			//check if from user exists
			$chk_stmt = $this->db->get_where('CRUser',array('id' => $from_user_id), 1);
			if($chk_stmt->num_rows() > 0){
				//check if to user exists
				$to_chk_stmt = $this->db->get_where('CRUser',array('id' => $to_user_id), 1);
				if($to_chk_stmt->num_rows() > 0){
					//check if rating exists
					$rating_chk_stmt = $this->db->get_where('CRRating',array('int' => $rating_id), 1);
					if($rating_chk_stmt->num_rows() > 0){
						//add entry to notification table
						$this->db->set('created', 'NOW()', FALSE);
						$this->db->set('modified', 'NOW()', FALSE);						
						$this->db->set('from_user_id', $from_user_id);
						$this->db->set('to_user_id', $to_user_id);
						$this->db->set('rating_id', $rating_id);
						$this->db->set('notification_type', $this->post->notificationType);
						$this->db->set('message', $this->post->message);
						$this->db->insert('CRNotification');
						//now to get all of the user's devices and send push notifications to each
						$this->load->model('user_model');
						$this->user_model->setID($from_user_id);
						$this->user_model->fetchDevices();
						foreach($this->user_model->getDevices() as $device_id){
							//how many push notifications does this device currently have?
							$this->db->select('badge_count');
							$badge_stmt = $this->db->get_where('CRDevice',array('id' => $device_id), 1);
							$r = $badge_stmt->row();
							//increment badge value
							$badge = $r->badge_count + 1;
							$this->db->where('id', $device_id);
							$this->db->set('badge_count', $badge);
							$this->db->update('CRDevice');
							//now create push notification
							$this->db->set('created', 'NOW()', FALSE);
							$this->db->set('modified', 'NOW()', FALSE);							
							$this->db->set('device_id', $device_id);
							$this->db->set('notification_id', $notification_id);
							$this->db->set('message', $this->post->message);
							$this->db->set('badge', $badge);
							$this->db->insert('CRPushNotification');
						}
					}
					else{
						$this->_generateError('Rating Not Found',$this->config->item('error_entity_not_found'));	
					}
				}
				else{
					$this->_generateError('To User Not Found',$this->config->item('error_entity_not_found'));
				}
			}
			else{
				$this->_generateError('From User Not Found',$this->config->item('error_entity_not_found'));
			}
		}
		else{
			$this->_generateError('Required Fields Missing', $this->config->item('error_required_fields'));
		}
		$this->_response();
	}
	
	//Mark Notification Viewed
	public function viewed($notificationID){
		//decrypt hashed notification ID
		$notification_id = hashids_decrypt($notificationID);
		//locate notification record for this ID
		$chk_stmt = $this->db->get_where('CRNotification', array('id' => $notification_id), 1);
		if($chk_stmt->num_rows() > 0){
			//set notification viewed
			$this->db->where('id', $notification_id);
			$this->db->set('is_viewed', 1);
			$this->db->update('CRNotification');
		}
		else{
			$this->_generateError('Notification Not Found',$this->config->item('error_entity_not_found'));
		}
		$this->_response();
	}
	
	//Unread Notifications for user
	public function unread($hashedUserID){
		$notifications = array();
		//decrypt hashed user id
		$user_id = hashids_decrypt($hashedUserID);
		//locate unread notifications
		$this->load->model('user_model');
		$this->user_model->setID($user_id);
		$this->user_model->fetchNotifications();
		//set the result to be an array of unread CRNotifications
		$this->notification_model->setResult($this->user_model->getNotifications());
		$this->_response();
	}

	//Generate Error
	public function _generateError($message, $status = 1){
		$this->notification_model->setStatus($status);
		$this->notification_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->notification_model->getStatus();
		$data['message'] = $this->notification_model->getMessage();
		$data['result'] = $this->notification_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
}