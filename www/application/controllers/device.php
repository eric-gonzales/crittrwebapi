<?php
/**
 * Device Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Device extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('device_model');
	}
	
	//Update Push Token
	function update($pushtoken)
	{
		//Clear the push token from any other devices that use it.
		//This is because repeated install/uninstalls could create new "devices", and we don't want to multispam the user when we send the push.
		$this->db->where('push_token', $pushtoken);
		$this->db->set('push_token', NULL);
		$this->db->update('CRDevice');
	
		//Update the device
		$this->db->where('device_vendor_id', $this->input->get_request_header('Critter-device', TRUE));
		$this->db->set('push_token', $pushtoken);
		$this->db->update('CRDevice');
		error_log(json_encode($this->db));
		$this->_response();
	}
	
	function resetBadgeCount()
	{
		$this->db->where('device_vendor_id', $this->input->get_request_header('Critter-device', TRUE));
		$this->db->set('badge_count', 0);
		$this->db->update('CRDevice');
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