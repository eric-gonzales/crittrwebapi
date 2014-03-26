<?php
/**
 * Init Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class Init_model extends CR_Model {
	
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		parent::__construct();
		$this->status = 0;
		$this->message = '';
		$this->result = array();
	}
	
	public function process(){
		//our result payload
		$this->setResult(array(
			'url_newaccount' => $this->config->item('base_url').'user/signup',
			'url_facebook' => $this->config->item('base_url').'user/facebook',
			'url_login' => $this->config->item('base_url').'user/login',
			'url_resetpassword' => $this->config->item('base_url').'user/reset'
		));
		
		//array: (key) database column => (value) post var
		$data = array(
			'appID' => $_POST['appID'],
			'appName' => $_POST['appName'],
			'appVersion' => $_POST['appVersion'],
			'device_vendor_id' => $_POST['deviceID']
		);
		
		//first we check if the device exists.
		$chk_stmt = $this->db->get_where('CRDevice',array('device_vendor_id' => $data['device_vendor_id']), 1);
		
		//if the device does not exist in the DB, we will add an entry with the data above
		if($chk_stmt->num_rows() == 0){
			//TODO: configure s.t. when data is inserted, the "created" column automatically updates with the timestamp.
			$this->db->insert('CRDevice', $data);
		}
	}

	public function setResult($result){
		$this->result = $result;
	}
	
	public function setMessage($message){
		$this->message = $message;
	}
	
	public function setStatus($status){
		$this->status = $status;
	}
	
	public function getResult(){
		return $this->result;
	}
	
	public function getMessage(){
		return $this->message;
	}
	
	public function getStatus(){
		return $this->status;
	}
	
	
}
