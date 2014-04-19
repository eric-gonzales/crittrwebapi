<?php

class Report{
	function __construct(){}
	
	public function echoHTML(){
		$this->echoHead();
		$this->echoBody();
		$this->echoFooter();
	}
	
	public function echoHead(){
		echo '<!DOCTYPE html>
		<html>
			<head>
				<title>Critter Dashboard</title>
				<script src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
				<script type="text/javascript" src="https://www.google.com/jsapi"></script>
				<link href="http://fonts.googleapis.com/css?family=Titillium+Web" rel="stylesheet" type="text/css">
				<link href="http://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet" type="text/css">
				<link rel="stylesheet" href="dashboard.css" type="text/css">';
				$this->echoJavascript();
				echo '</head><body>';
	}
	
	public function echoBody(){
		echo '<header>
			<h1><a href="index.php">Critter Dashboard</a></h1>
		</header>
		<nav>
			<h1>Feeds</h1>
			<ul>
				<li class="navheading">General</li>
				<ul>
					<li><a href="index.php">Overview</a></li>
				</ul>
				<li class="navheading">User Profiles</li>
				<ul>
					<li><a href="?mode=user_profile">Select User</a></li>
				</ul>
			</ul>
			<h1>Reports</h1>
			<ul>
				<li class="navheading">Ratings</li>
				<ul>
					<li><a href="?mode=ratings_home&t=1">Likes</a></li>
					<li><a href="?mode=ratings_home&t=2">Dislikes</a></li>
					<li><a href="?mode=ratings_home&t=4">Rejects</a></li>
					<li><a href="?mode=ratings_home&t=3">Watchlists</a></li>
				</ul>
				<li class="navheading">Sharing</li>
				<ul>
					<li><a href="">Recommend</a></li>
					<li><a href="">Not Recommend</a></li>
					<li><a href="">Invite to See</a></li>
				</ul>
				<li class="navheading">Users</li>
				<ul>
					<li><a href="">Add Friend</a></li>
					<li><a href="">Add Device</a></li>
				</ul>
			</ul>
		</nav>
			<div id="notice_div"></div>
		<main id="dynamic_content">';
		$this->echoMain();
	}

	public function echoFooter(){
		echo '</main>
	</body>
</html>';
	}
	
}
