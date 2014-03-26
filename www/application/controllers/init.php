<?php

class Init extends CI_Controller{
	function index(){
		$this->load->model('init_model');
		
		$data['result'] = $this->init_model->getResult();
		
		$this->load->view('init_response', $data);
	}
}
