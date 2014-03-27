<?php
/**
 * User Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class User extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('user_model');
	}
	
	function index(){}
	
	//Create new Account
	function signup(){
		//first we check if the email is already in use
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->input->post('email')), 1);
		
		if($chk_stmt->num_rows() == 0){
			//create new entry in CRUser table
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('username', $this->input->post('username'));
			$this->db->set('password_hash', $this->phpass->hash($this->input->post('password')));
			$this->db->set('email', $this->input->post('email'));
			$this->db->insert('CRUser');
			
			//grab the user id from the last insert
			$this->user_model->setID($this->db->insert_id());
			
			//get headers
			$headers = getallheaders();
			//First, select id from CRDevice where device_vendor_id = critter-device
			$this->db->select('id');
			$query = $this->db->get_where('CRDevice', array('device_vendor_id' => $headers['critter-device']), 1);
			//if we have a match, lets insert a new record into the table
			if($query->num_rows > 0){
				$row = $query->row();
				$this->db->set('device_id', $row->id);
				$this->db->set('user_id', $this->user_model->getID());
				$this->db->insert('CRDeviceUser');
			}
			else{
				//return error code
				$this->user_model->setStatus(1);
				$this->user_model->setMessage('Error: device could not be found.');
			}
			
			//finally, generate the default result
			$this->user_model->defaultResult();
		}
		else{
			//return error code
			$this->user_model->setStatus(1);
			$this->user_model->setMessage('Error: email is already in use.');
		}
		
		$data['status'] = $this->user_model->getStatus();
		$data['message'] = $this->user_model->getMessage();
		$data['result'] = $this->user_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
	//Login or Create New Account via Facebook
	function facebook(){}
	
	//Login
	function login(){		
		$this->db->select('id, password_hash');
		//look for matching email in CRUser table
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->input->post('email')), 1);
		
		if($chk_stmt->num_rows() == 0){
			//return error code
			$this->user_model->setStatus(1);
			$this->user_model->setMessage('Error: email does not exist.');
		}
		else{
			//fetch the row
			$cr_user = $chk_stmt->row();
			
			//check if credentials match
			if($this->phpass->check($this->input->post('password'), $cr_user->password_hash)){
				$this->user_model->setID($cr_user->id);
				$this->user_model->defaultResult();
			}
			else{
				//return error code
				$this->user_model->setStatus(1);
				$this->user_model->setMessage('Error: invalid credentials');
			}
		}
		
		$data['status'] = $this->user_model->getStatus();
		$data['message'] = $this->user_model->getMessage();
		$data['result'] = $this->user_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
	//Reset Lost Password
	function reset($hashedUserID = '', $token = ''){
		$this->db->select('id');
		//look for matching email in CRUser table
		$chk_stmt = $this->db->get_where('CRUser',array('email' => $this->input->post('email')), 1);
		
		if($chk_stmt->num_rows() == 0){
			//return error code
			$this->user_model->setStatus(1);
			$this->user_model->setMessage('Error: email does not exist.');
		}
		else{
			$cr_user = $chk_stmt->row();
			$this->db->order_by('created', 'desc');
			//check if token is already generated for this user_id. 
			$chk_tkn_stmt = $this->db->get_where('CREmailToken',array('user_id' => $cr_user->id), 1);
			if($chk_tkn_stmt->num_rows() > 0){
				$tkn = $chk_tkn_stmt->row();
				
				//check if token is expired
				if(strtotime($tkn->created) < (time()-60*60*24*7)){
					//if token expired, generate new email token
					$this->user_model->setID($cr_user->id);
					$this->user_model->newEmailToken();
				}
				else{
					//return error code
					$this->user_model->setStatus(1);
					$this->user_model->setMessage('Error: token has already been generated');
				}	
			}
			else{
				//if no token exists, generate email token
				$this->user_model->setID($cr_user->id);
				$this->user_model->newEmailToken();
			}
		}
		
		$data['status'] = $this->user_model->getStatus();
		$data['message'] = $this->user_model->getMessage();
		$data['result'] = $this->user_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
	
	//Update User Profile Photo
	function photo($hashedUserID){}
	
	//Add Friend
	function addfriend($hashedUserID){}
	
	//Remove Friend
	function removefriend($hashedUserID){}
}
