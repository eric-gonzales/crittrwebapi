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
	public function send(){}
	
	//Mark Notification Viewed
	public function viewed($notificationID){}
	
	//Unread Notifications for user
	public function unread($hashedUserID){}
	
	//Generate Error
	public function _generateError($message, $status = 1){
		$data['status'] = $this->notification_model->getStatus();
		$data['message'] = $this->notification_model->getMessage();
		$data['result'] = $this->notification_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
}