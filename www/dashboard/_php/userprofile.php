<?php
require_once(dirname(__FILE__).'/report.php');

class UserProfile extends Report{
	function __construct($autoEcho = true){
		parent::__construct($autoEcho);
		$this->user = '';
		$this->cr_db = '';
		
		try{
			$this->cr_db = new PDO('mysql:host=mydb.convjtlfhxmu.us-west-2.rds.amazonaws.com;dbname=mydb', 'critteruser', 'Tbr6m52l56}n~bb');
    		$this->cr_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(Exception $e){
			echo '<pre>';
			print_r($e);
		}
		
		$this->mapPostGet();
		
		$this->genData();	
		if($autoEcho){
			$this->echoHTML();
		}
	}
	
	public function mapPostGet(){
		if(array_key_exists('user', $_GET)){
			$this->user = htmlspecialchars($_GET['user']);
		}
	}
	
	public function genData(){
	}
	
	public function echoJavascript(){
				echo '<script>  
				$(function() { 
				       update();
					   $("#userlist").scroll(function() {
					   	window.hasScrolled = 1;
					   });
				}); 
				function update() {
					if(window.hasScrolled != 1){
						  $.ajax({
						    type: \'GET\',';
							if($this->user == ''){
								echo 'url: \'_php/ajax.php?mode=users\',';
							}
							else{
								echo 'url: \'_php/ajax.php?mode=users&user='.$this->user.'\',';
							}
							echo 'timeout: 2000,
						    success: function(data) {
						      $("#dynamic_content").html(data);
						      $("#notice_div").html(\'\'); 
						      window.setTimeout(update, 1000);
							  $("#userlist").scroll(function() {
								   window.hasScrolled = 1;
							  });
						    },
						    error: function (XMLHttpRequest, textStatus, errorThrown) {
						      $("#notice_div").html(\'Timeout contacting server..\');
						      window.setTimeout(update, 10000);
						    }
						});
					}
				}
				</script>';
	}
		  
	public function echoMain(){
		if($this->user == ''){
			echo '<h2>Users</h2>';
			echo '<table id="userlist">';
			echo '<tr><th></th><th>User</th><th>Email</th><th>Sign-up Date</th><th>Facebook</th></tr>';
			try{
				$user_stmt = $this->cr_db->prepare('SELECT * FROM CRUser');
				$user_stmt->execute(array());
				while($user = $user_stmt->fetch(PDO::FETCH_OBJ)){
					echo '<tr><td><a href="?mode=user_profile&user='.$user->id.'"><img src="'.$user->photo_url.'" style="width:50px;height:50px;"></a></td><td><a href="?mode=user_profile&user='.$user->id.'">'.$user->name.'</a></td><td>'.$user->email.'</td><td>'.$user->created.'</td><td>';
					if($user->facebook_id != ''){
						echo '<a href="http://facebook.com/'.$user->facebook_id.'">Facebook</a>';
					}
					echo '</td></tr>';
				}
			}
			catch(Exception $e){
				echo '<pre>';
				print_r($e);
			}
			echo '</table>';
		}
		else{
			$user_stmt = $this->cr_db->prepare('SELECT * FROM CRUser WHERE id = ?');
			$user_stmt->execute(array($this->user));
			$user = $user_stmt->fetch(PDO::FETCH_OBJ);
			echo '<table id="usertable">';
			echo '<tr><th rowspan="4"><img src="'.$user->photo_url.'" style="max-width:125px;max-height:125px;float:left;"></th><th colspan="2">'.$user->name.'</th></tr>';
			echo '<tr><td>Email: </td><td>'.$user->email.'</td></tr>';
			echo '<tr><td>Member since: </td><td>'.$user->created.'</td></tr>';
			echo '<tr><td>Facebook:</td><td><a href="http://facebook.com/'.$user->facebook_id.'">Facebook</a></td></tr>';
			echo '</table>';
			echo '<table id="userfeed">';
			$user_stmt = $this->cr_db->prepare('SELECT * FROM CRAnalytics WHERE subject = "user" AND subject_id = ? ORDER BY created DESC');
			$user_stmt->execute(array($this->user));
			while($e = $user_stmt->fetch(PDO::FETCH_OBJ)){
				echo '<tr><td>';
				$action = '';
				$object = '';
				switch($e->event){
					case 'rates':
						switch($e->event_type){
							case 1:
								$action = 'Liked';
								break;
							case 2:
								$action = 'Did not like';
								break;
							case 3:
								$action = 'Watchlisted';
								break;
							case 4:
								$action = 'Rejected';
								break;
							default:
								$action = 'Rated';
								break;
						}
						break;
					default:
						$action = '';
						break;
								
				}
				switch($e->object){
					case 'movie':
						$movie_stmt = $this->cr_db->prepare('SELECT * FROM CRMovie WHERE id = ? LIMIT 1');
						$movie_stmt->execute(array($e->object_id));
						$movie = $movie_stmt->fetch(PDO::FETCH_OBJ);
						$object = $movie->title;
						break;
					default:
						$object = '';
						break;
				}
				echo $action.' <b>'.$object.'</b> on '.$e->created;
				echo '</td></tr>';
			}
			echo '</table>';
		}
		
	}
}

