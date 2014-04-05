<?php

$critter_secret = 'xf3PncZ5pUY5qMrftPCMVYTF';
$attempt = htmlspecialchars($_GET['key']);

if($attempt == $critter_secret){
	require('amazon.php');
	$movieTitle = $_GET['title'];
	$movieYear = $_GET['year'];
	
	$amazon_access_key = "AKIAJQDQVFIMNW44HQ4Q";
	$amazon_secret_access_key = "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";
	
	$querystr = $movieTitle.' '.$movieYear;
	
	$query = array(
		"Operation" => "ItemSearch",
		"Keywords" => $querystr,
		"SearchIndex" => "All",
		"ResponseGroup" => "ItemAttributes"
	);
	$amazon = new Amazon();
	$results = $amazon->get_results("com", $amazon_access_key, $amazon_secret_access_key, $query);
	$videoResult = array();
	foreach($results as $array){
		foreach($array as $item){
			if(!empty($item->ItemAttributes)){
				if(($item->ItemAttributes->Binding == 'Amazon Instant Video' || $item->ItemAttributes->ProductTypeName == 'DOWNLOADABLE_MOVIE') && $item->ItemAttributes->ProductGroup == 'Movie'){
					$actorResult = array();
					foreach ( (array) $item->ItemAttributes->Actor as $index => $node ){
						$actorResult[$index] = $node;
					}
					foreach ( (array) $item->ItemAttributes->Director as $index => $node ){
						$directorResult[$index] = $node;
					}
					$videoResult = array(
						'ASIN' => (string)$item->ASIN,
						'Actor' => $actorResult,
						'AudienceRating' => (string)$item->ItemAttributes->AudienceRating,
						'DetailPageURL' => (string)$item->DetailPageURL,
						'Director' => $directorResult,
						'Genre' => (string)$item->ItemAttributes->Genre,
						'ReleaseDate' => (string)$item->ItemAttributes->ReleaseDate,
						'Studio' => (string)$item->ItemAttributes->Studio,
						'Title' => (string)$item->ItemAttributes->Title
					);
					break;
				}
			}
		}
	}
	header('Content-Type: text/javascript;charset=ISO-8859-1');
	echo stripslashes(urldecode(json_encode($videoResult)));
}
else{
	die();
}


