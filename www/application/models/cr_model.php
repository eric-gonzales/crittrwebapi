<?php class CR_Model extends CI_Model{
	
	private $status;
	private $message;
	private $result;
	
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		$this->setStatus(0);
		$this->setMessage('');
		$this->setResult(array());
	}
	
	public function process(){}
	
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
