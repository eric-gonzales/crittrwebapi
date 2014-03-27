<?php
/**
 * Init Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Init extends CI_Controller{
	function index(){
		$this->load->model('init_model');
		
		$this->init_model->process();
		
		$data['status'] = $this->init_model->getStatus();
		$data['message'] = $this->init_model->getMessage();
		$data['result'] = $this->init_model->getResult();
		
		$this->load->view('standard_response', $data);
	}
}
