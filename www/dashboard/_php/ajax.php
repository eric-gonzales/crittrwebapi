<?php
$mode = htmlspecialchars($_GET['mode']);
switch($mode){
	case 'all':
		require_once(dirname(__FILE__).'/livefeed.php');
		$p = new LiveFeed(false);
		$p->echoMain();
		break;
	case 'users':
		require_once(dirname(__FILE__).'/userprofile.php');
		$p = new UserProfile(false);
		$p->echoMain();
		break;
}
