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
	
	public function process()
	{
		//Get the device if present
		$deviceVendorID = $this->post->deviceID;
		$this->db->from('CRDevice');
		$this->db->where('device_vendor_id',$deviceVendorID);
		$row = $this->db->get()->row();

		//Update the fields
		$this->db->set('appID', $this->post->appID);
		$this->db->set('appName', $this->post->appName);
		$this->db->set('appVersion', $this->post->appVersion);
		$this->db->set('modified', 'NOW()', FALSE);
		if(!$row)
		{
			//Insert
			$this->db->set('device_vendor_id', $deviceVendorID);
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->insert('CRDevice');
		}
		else
		{
			//Update
			$this->db->where('id', $row->id);
			$this->db->update('CRDevice');
		}
		
		//our result payload
		$this->setResult(array(
			'url_newaccount' => $this->config->item('base_url').'user/signup',
			'url_facebook' => $this->config->item('base_url').'user/facebook',
			'url_login' => $this->config->item('base_url').'user/login',
			'url_resetpassword' => $this->config->item('base_url').'user/reset',
			'url_updatepushtoken' => $this->config->item('base_url').'device/update',
			'url_resetbadgecount' => $this->config->item('base_url').'device/resetbadgecount'
		));
	}
}
