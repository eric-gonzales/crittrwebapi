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
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	public function process(){
		//first we check if the device exists.
		$chk_stmt = $this->db->get_where('CRDevice',array('device_vendor_id' => $this->post->deviceID), 1);
		
		//if the device does not exist in the DB, we will add an entry
		if($chk_stmt->num_rows() == 0){
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('appID', $this->post->appID);
			$this->db->set('appName', $this->post->appName);
			$this->db->set('appVersion',$this->post->appVersion);
			$this->db->set('device_vendor_id', $this->post->deviceID);
			$this->db->insert('CRDevice');
		}
		
		//our result payload
		$this->setResult(array(
			'url_newaccount' => $this->config->item('base_url').'user/signup',
			'url_facebook' => $this->config->item('base_url').'user/facebook',
			'url_login' => $this->config->item('base_url').'user/login',
			'url_resetpassword' => $this->config->item('base_url').'user/reset'
		));
	}
}
