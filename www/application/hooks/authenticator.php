<?php
class Authenticator{
	public function auth(){
		foreach (getallheaders() as $name => $value) {
    		echo "$name: $value";
			echo '<br>';
		}	
	}
}
