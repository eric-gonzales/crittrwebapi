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
	}
	
	//Send Notification
	public function send(){
		//check required post fields
		if(!empty($this->input->post('fromUserID')) && !empty($this->input->post('toUserID')) && !empty($this->input->post('notificationType')) && !empty($this->input->post('ratingID'))){
			//decrypt information
			$from_user_id = hashids_decrypt($this->input->post('fromUserID'));
			$to_user_id = hashids_decrypt($this->input->post('toUserID'));
			$rating_id = hashids_decrypt($this->input->post('ratingID'));
				
			//check if from user exists
			$chk_stmt = $this->db->get_where('CRUser',array('id' => $from_user_id), 1);
			if($chk_stmt->num_rows() > 0){
				//check if to user exists
				$to_chk_stmt = $this->db->get_where('CRUser',array('id' => $to_user_id), 1);
				if($to_chk_stmt->num_rows() > 0){
					//check if rating exists
					$rating_chk_stmt = $this->db->get_where('CRRating',array('id' => $rating_id), 1);
					if($rating_chk_stmt->num_rows() > 0){
						//add entry to notification table
						$this->db->set('created', 'NOW()', FALSE);
						$this->db->set('from_user_id', $from_user_id);
						$this->db->set('to_user_id', $to_user_id);
						$this->db->set('rating_id', $rating_id);
						$this->db->set('notification_type', $this->input->post('notificationType'));
						$this->db->set('message', $this->input->post('message'));
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
							$this->db->set('device_id', $device_id);
							$this->db->set('notification_id', $notification_id);
							$this->db->set('message', $this->input->post('message'));
							$this->db->set('badge', $badge);
							$this->db->insert('CRPushNotification');
						}
					}
					else{
						$this->_generateError('rating not exist.');	
					}
				}
				else{
					$this->_generateError('to user does not exist.');
				}
			}
			else{
				$this->_generateError('from user does not exist.');
			}
		}
		else{
			$this->_generateError('required field(s) missing');
		}
		$this->_response();
	}
	
	//Mark Notification Viewed
	public function viewed($notificationID){}
	
	//Unread Notifications for user
	public function unread($hashedUserID){}

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