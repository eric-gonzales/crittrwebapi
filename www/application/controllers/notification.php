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