<?php
require_once(dirname(__FILE__).'/report.php');

class LiveFeed extends Report{
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
	}
	
	public function genData(){
	}
	
	public function echoJavascript(){
		echo '<script>  
		$(function() {
			update();
			$("#userfeed").scroll(function() {
			   window.hasScrolled = 1;
			 });
		}); 
		function update() {
			if(window.hasScrolled != 1){
				  $.ajax({
				    type: \'GET\',
				    url: \'_php/ajax.php?mode=all\',
				    timeout: 2000,
				    success: function(data) {
				      $("#dynamic_content").html(data);
				      $("#notice_div").html(\'\'); 
				      window.setTimeout(update, 1000);
					  $("#userfeed").scroll(function() {
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
		echo '<h2>Live Feed</h2>';
	    echo '<table id="userfeed"> '; 
		$analytic_stmt = $this->cr_db->prepare('SELECT * FROM CRAnalytics ORDER BY created DESC');
		$analytic_stmt->execute(array());
		while($e = $analytic_stmt->fetch(PDO::FETCH_OBJ)){
			echo '<tr><td style="display: table-cell; vertical-align: middle;">';
			$subject = '';
			$action = '';
			$object = '';
			$user = '';
			if($e->subject_id != ''){
				$user_stmt = $this->cr_db->prepare('SELECT * FROM CRUser WHERE id = ? LIMIT 1');
				$user_stmt->execute(array($e->subject_id));
				$user = $user_stmt->fetch(PDO::FETCH_OBJ);
				$subject = $user->name;
			}
			switch($e->event){
				case 'rates':
					switch($e->event_type){
						case 1:
							$action = 'liked';
							break;
						case 2:
							$action = 'did not like';
							break;
						case 3:
							$action = 'watchlisted';
							break;
						case 4:
							$action = 'rejected';
							break;
						default:
							$action = 'rated';
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
			echo '<span style="margin-right:20px;font-size:10px;color:grey">'.$e->created.'</span>';
			echo '<a href="?mode=user_profile&user='.$e->subject_id.'">'.$subject.'</a> '.$action.' <a href="">'.$object.'</a>';
			echo '</td></tr>';
		}
		echo '</table>';
	}
		
}

