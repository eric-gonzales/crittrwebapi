<?php
class AmazonVideo{
	function __construct($access_key, $secret_access_key){
		require_once(dirname(__FILE__).'/amazon.php');
		$this->access_key = $access_key;
		$this->secret_access_key = $secret_access_key;
	}
	function search($movie_title, $movie_year){
		$querystr = $movieTitle.' '.$movieYear;
		$query = array(
			"Operation" => "ItemSearch",
			"Keywords" => $querystr,
			"SearchIndex" => "All",
			"ResponseGroup" => "ItemAttributes"
		);
		$amazon = new Amazon();
		$results = $amazon->get_results("com", $this->access_key, $this->secret_access_key, $query);
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
		return stripslashes(urldecode(json_encode($videoResult)));
	}
}



