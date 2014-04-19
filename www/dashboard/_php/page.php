<?php

class Page{
	function __construct($mode){
		switch($mode){
			case 'ratings_home':
				require_once(dirname(__FILE__).'/ratings_home.php');
					$p = new Ratings_Home();
				break;
			case 'ratings':
					require_once(dirname(__FILE__).'/ratings.php');
					$p = new Ratings();
				break;
			case 'ratings_line':
				require_once(dirname(__FILE__).'/ratings_line.php');
					$p = new Ratings_Line();
				break;
			case 'user_profile':
					require_once(dirname(__FILE__).'/userprofile.php');
					$p = new UserProfile();
				break;
			default:
					require_once(dirname(__FILE__).'/livefeed.php');
					$p = new LiveFeed();
				break;
		}
	}
}
