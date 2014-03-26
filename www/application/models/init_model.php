<?php
/**
 * Init Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class Init_model extends CI_Model {
	
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		parent::__construct();
	}
	
	
	/**
	 * Returns result with a list of all the endpoints we will use when not logged in 
	 * @param void
	 * @return $result array Key/value pairs to be sent as response
	 */
	public function getResult(){
		//post data
		$data = array(
			'appID' => $_POST['appID'],
			'appName' => $_POST['appName'],
			'appVersion' => $_POST['appVersion'],
			'device_vendor_id' => $_POST['deviceID']
		);
		
		//our result payload
		$result = array(
			'url_newaccount' => $this->config->item('base_url').'user/signup',
			'url_facebook' => $this->config->item('base_url').'user/facebook',
			'url_login' => $this->config->item('base_url').'user/login',
			'url_resetpassword' => $this->config->item('base_url').'user/reset'
		);
		
		//first we check if the device exists.
		$chk_stmt = $this->db->get_where('CRDevice',array('device_vendor_id' => $data['device_vendor_id']), 1);
		
		//if the device does not exist in the DB, we will add an entry with the data above
		if($chk_stmt->num_rows() == 0){
			$this->db->insert('CRDevice', $data);
		}
		
		//finally return the result
		return $result;
		
	}
	
	
}
