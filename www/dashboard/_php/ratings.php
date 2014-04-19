<?php
require_once(dirname(__FILE__).'/report.php');

class Ratings extends Report{
	function __construct(){
		parent::__construct();
		$this->type = '';
		$this->user = '';
		$this->lowerLimit = 0;
		$this->res_array = array();
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
		
		$this->echoHTML();	
	}
	
	public function mapPostGet(){
		if(array_key_exists('t', $_GET)){
			$this->type = htmlspecialchars($_GET['t']);
		}
		if(array_key_exists('ll', $_POST)){
			$this->lowerLimit = htmlspecialchars($_POST['ll']);
		}
		if(array_key_exists('user', $_POST)){
			$this->user = htmlspecialchars($_POST['user']);
		}
	}
	
	public function genData(){
		if($this->user != ''){
			$stmt = $this->cr_db->prepare('SELECT * FROM CRAnalytics WHERE event = ? AND event_type = ? AND subject_id = ?');
			$stmt->execute(array("rates", $this->type, $this->user));
		}
		else{
			$stmt = $this->cr_db->prepare('SELECT * FROM CRAnalytics WHERE event = ? AND event_type = ?');
			$stmt->execute(array("rates", $this->type));
		}
			
			
			$movie_array = array();
			while($r = $stmt->fetch(PDO::FETCH_OBJ)){
				if(!in_array($r->object_id, $movie_array)){
					$movie_array[] = $r->object_id;
				}
				$res = array(
					'id' => $r->id,
					'subject_id' => $r->subject_id,
					'event_id' => $r->event_id,
					'time' => $r->created
				);
				$this->res_array[$r->object_id][] = $res;
			}
	}
	
	public function echoJavascript(){
		echo '<script type="text/javascript">
	      google.load("visualization", "1", {packages:["corechart"]});
	      google.setOnLoadCallback(drawChart);
	      function drawChart() {
	        var data = google.visualization.arrayToDataTable([
	          [\'Movie\', \'Users\'],';
	          $i = 0;
	          foreach($this->res_array as $movie => $res){
	          		if(count($this->res_array[$movie]) > $this->lowerLimit){
	          			echo '[';
						$mov_stmt = $this->cr_db->prepare('SELECT * FROM CRMovie WHERE id = ? LIMIT 1');
						$mov_stmt->execute(array($movie));
						$mov = $mov_stmt->fetch(PDO::FETCH_OBJ);
						echo '\''.addslashes($mov->title).'\',';
						echo ''.count($this->res_array[$movie]).'';
						echo ']';
						$i++;
						if($i != count($this->res_array)){
							echo ',';
						}
	          		}
				}
				echo ']);
	
	        var options = {
	          title: \'Movies\',
	          backgroundColor: { fill:\'transparent\' },
	          is3D: true
	        };
	
	        var chart = new google.visualization.PieChart(document.getElementById(\'piechart\'));
	        chart.draw(data, options);
	      }
	   </script>';
	}
		  
	public function echoMain(){
		$word = '';
		switch($this->type){
			case 1:
				$word = 'Like';
				break;
			case 2:
				$word = 'Dislike';
				break;
			case 3:
				$word = 'Watchlist';
				break;
			case 4:
				$word = 'Reject';
				break;
		}
		try {
			if($this->type != ''){
			echo '<h2>Movies that Users '.$word.'</h2>';
			echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF']).'?mode=ratings&t='.$this->type.'" method="post">';
			echo '<p>Show movies that have more than <input type="text" value="'.$this->lowerLimit.'" name="ll" style="width:20px;"> ratings.</p>';
			echo 'Show User: ';
			echo '<select name="user">';
			
			$users_stmt = $this->cr_db->prepare('SELECT * FROM CRUser');
			$users_stmt->execute(array());
			echo '<option value="">All Users</option>';
			while($user = $users_stmt->fetch(PDO::FETCH_OBJ)){
				echo '<option value="'.$user->id.'">'.$user->name.'</option>';
			}
			echo '</select>';
			echo '<input type="submit" value="Update">';
			echo '</form>';
			echo ' <div id="piechart" style="width: 900px; height: 500px;"></div>';
			echo '<table class="summary">';
			echo '<tr><th colspan="2">Summary</th></tr>';
			echo '<tr><th>Movie</th><th>Number of Users</th></tr>';
			foreach($this->res_array as $movie => $res){
				echo '<tr>';
				$mov_stmt = $this->cr_db->prepare('SELECT * FROM CRMovie WHERE id = ? LIMIT 1');
				$mov_stmt->execute(array($movie));
				$mov = $mov_stmt->fetch(PDO::FETCH_OBJ);
				echo '<td>'.$mov->title.'</td>';
				echo '<td>'.count($this->res_array[$movie]).'</td>';
				echo '</tr>';
			}
			echo '</table>';
			}
		else{
			echo '<h1>Welcome</h1>';
			echo '<p>Please use the navigation on the left for reports.</p>';
		}
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		}
	}
}
