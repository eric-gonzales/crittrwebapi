<?php
class Authenticator{
	/**
	 * Method used for authentication
	 */
	public function auth(){
		define('CRITTER_SECRET', 'p0^:22MJQ4OkR6235w2+');
		$headers = getallheaders();
		$critterDevice = $headers['critter-device'];
		$headerVerify = $headers['critter-verify'];
		$test = sha1($critterDevice.CRITTER_SECRET);
		
		//if the hash doesn't match up or the critter-device header is empty
		if($headerVerify != $test || empty($critterDevice)){
			header('HTTP/1.0 403 Forbidden');
			die();
		}
	}
}
