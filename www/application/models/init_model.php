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
		if($chk_stmt->num_rows() == 0){
			//if the device does not exist in the DB, we will add an entry
			$this->db->set('created', 'NOW()', FALSE)->set('appID', $this->post->appID)->set('appName', $this->post->appName)->set('appVersion',$this->post->appVersion)->set('device_vendor_id', $this->post->deviceID);
			$this->db->set('modified', 'NOW()', FALSE);									
			$this->db->insert('CRDevice');
		}
		else{
			//if it does exist, we will update the modified field
			$this->db->where('device_vendor_id', $this->post->deviceID)->set('modified', 'NOW()', FALSE);
			$this->db->update('CRDevice');
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
