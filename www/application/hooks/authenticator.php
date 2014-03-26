<?php
class Authenticator{
	public function auth(){
		define('CRITTER_SECRET', 'p0^:22MJQ4OkR6235w2+');
		$headers = getallheaders();
		$critterDevice = $headers['critter-device'];
		$headerVerify = $headers['critter-verify'];
		$test = sha1($critterDevice.CRITTER_SECRET);
		if($headerVerify != $test){
			die();
		}
	}
}
