<?php
/**
 * Init Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Init extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('init_model');
		$this->post = json_decode(file_get_contents('php://input'));
	}
	
	//Initalization
	function index(){
		if($this->post->appID != '' && $this->post->appName != '' && $this->post->appVersion != '' && $this->post->deviceID != ''){
			$this->init_model->process();	
		}
		else{
			$this->_generateError('required fields missing');
		}
		
		$this->_response();
	}
	
	//Generate Error
	public function _generateError($message, $status = 1){
		$this->init_model->setStatus($status);
		$this->init_model->setMessage('Error: '.$message);
	}
	
	//Produce Response
	public function _response(){
		$data['status'] = $this->init_model->getStatus();
		$data['message'] = $this->init_model->getMessage();
		$data['result'] = $this->init_model->getResult();
		$this->load->view('standard_response', $data);
	}
}
