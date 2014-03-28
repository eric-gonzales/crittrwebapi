<?php
/**
 * Device Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Device extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('device_model');
	}
	
	//Update Push Token
	function update($pushtoken){
		//First, select id from CRDevice where device_vendor_id = critter-device
		$this->db->select('id');
		$query = $this->db->get_where('CRDevice', array('device_vendor_id' => $this->input->get_request_header('critter-device', TRUE)), 1);
		//if we have a match, lets insert a new record into the table
		if($query->num_rows > 0){
			$device = $query->row();
			$this->db->where('id', $device->id);
			$this->db->set('push_token', $pushtoken);
			$this->db->update('CRDevice');
		}
		else{
			$this->_generateError('device not found');
		}
		$this->_response();
	}
	
	//Generate Error
	public function _generateError($message, $status = 1){
		$this->device_model->setStatus($status);
		$this->device_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->device_model->getStatus();
		$data['message'] = $this->device_model->getMessage();
		$data['result'] = $this->device_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
}