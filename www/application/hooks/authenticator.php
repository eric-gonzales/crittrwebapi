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
		
		define('CRITTER_SECRET', 'p0^:22MJQ4OkR6235w2+');
		$critterDevice = $headers['critter-device'];
		$headerVerify = $headers['critter-verify'];
		$test = sha1($critterDevice.CRITTER_SECRET);
		
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
