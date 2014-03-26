<?php
abstract class CR_Model extends CI_Model{
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		$this->status = 0;
		$this->message = '';
		$this->result = array();
	}
	
	abstract public function process(){}
	
	public function setResult($result){
		$this->result = $result;
	}
	
	public function setMessage($message){
		$this->message = $message;
	}
	
	public function setStatus($status){
		$this->status = $status;
	}
	
	public function getResult(){
		return $this->result;
	}
	
	public function getMessage(){
		return $this->message;
	}
	
	public function getStatus(){
		return $this->status;
	}
}
