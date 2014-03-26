<?php
class Authenticator{
	public function auth(){
		$auth_flag = 0;
		$headers = getallheaders();
		echo '<pre>';
		print_r($headers);
	}
}
