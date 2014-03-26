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
	}
	
	public function process(){
		//our result payload
		$this->setResult(array(
			'url_newaccount' => $this->config->item('base_url').'user/signup',
			'url_facebook' => $this->config->item('base_url').'user/facebook',
			'url_login' => $this->config->item('base_url').'user/login',
			'url_resetpassword' => $this->config->item('base_url').'user/reset'
		));
		
		//first we check if the device exists.
		$chk_stmt = $this->db->get_where('CRDevice',array('device_vendor_id' => $_POST['deviceID']), 1);
		
		//if the device does not exist in the DB, we will add an entry with the data above
		if($chk_stmt->num_rows() == 0){
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('appID', $_POST['appID']);
			$this->db->set('appName', $_POST['appName']);
			$this->db->set('appVersion', $_POST['appVersion']);
			$this->db->set('device_vendor_id', $_POST['deviceID']);
			$this->db->insert('CRDevice');
		}
	}
}
