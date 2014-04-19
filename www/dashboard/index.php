<?php
require_once(dirname(__FILE__).'/_php/page.php');
if(array_key_exists('mode', $_GET)){
	$mode = htmlspecialchars($_GET['mode']);
}
else{
	$mode = '';
}
$p = new Page($mode);
