<?php
class Authenticator{
	/**
	 * Method used for authentication
	 */
	public function auth(){
		$headers = getallheaders();
		if(empty($headers)){
			$this->fail();
		}
		
		$critterDevice = $headers['critter-device'];
		$headerVerify = $headers['critter-verify'];
		$test = sha1($critterDevice.$this->config->item('shared_secret'));
		
		//if the hash doesn't match up or the critter-device header is empty
		if($headerVerify != $test || empty($critterDevice)){
			$this->fail();
		}
	}
	
	/**
	 * Fail Method
	 */
	public function fail(){
		header('HTTP/1.0 403 Forbidden');
		die();
	}
}
