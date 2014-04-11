<?php
class Push extends CI_Controller 
{
    function __construct() 
    {
        parent::__construct();
		$this->load->model('push_model');
    }
    
	public function feedback()
	{
		$this->push_model->feedback();
	}
	
	public function send()
	{
		$this->push_model->send();	
	}
	
	public function test($token, $message)
	{
		$this->push_model->test($token, $message);
	}
}
?>