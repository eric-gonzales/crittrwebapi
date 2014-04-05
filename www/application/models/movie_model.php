<?php
/**
 * Movie Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Movie_model extends CR_Model {
	private $id;
	private $rotten_tomatoes_id;
	private $itunes_id;
	private $imdb_id;
	private $tmdb_id;
	private $tms_root_id;
	private $tms_movie_id;
	private $hashtag;
	private $title;
	private $box_office_release_date;
	private $dvd_release_date;
	private $tmdb_poster_path;
	private $critter_rating;
	private $priority;
	private $rt_details;
	private $imdb_details;
	private $tmdb_details;
	private $itunes_details;
	private $tms_details;
	private $tmdb_trailer_details;
	private $tms_trailer_details;
	private $tms_trailer_image_details;
	
	//Construct from RT ID
	public function __construct($rotten_tomatoes_id){
		parent::__construct();
		$inDB = false;
		
		//Try to get data from DB first
		$this->setRottenTomatoesID($rotten_tomatoes_id);
		$chk_stmt = $this->db->get_where('CRMovie',array('rotten_tomatoes_id' => $this->getRottenTomatoesID()), 1);
		if($chk_stmt->num_rows() > 0){
			$inDB = true; //this record is in the database, so we will update instead of insert
			$movie_info = $chk_stmt->row();
			$this->setID($movie_info->id);
		}
		
		//RT data
		$this->fetchRottenTomatoesData();
		if($inDB){
			if($movie_info->title != ''){
				$this->setTitle($movie_info->title);
				$this->setHashtag($this->makeHashtag($movie_info->title));
			}
			if($movie_info->box_office_release_date != ''){
				$this->setBoxOfficeReleaseDate($movie_info->box_office_release_date);
			}
			if($movie_info->dvd_release_date != ''){
				$this->setDVDReleaseDate($movie_info->dvd_release_date);
			}
			if($movie_info->imdb_id != ''){
				$this->setIMDBID($movie_info->imdb_id);
			}
		}
		
		//IMDB Data
		$this->fetchIMDBData();
		
		//TMDB data
		$this->fetchTMDBData();
		if($inDB){
			if($movie_info->tmdb_id != ''){
				$this->setTMDBID($movie_info->tmdb_id);
			}
			if($movie_info->tmdb_poster_path != ''){
				$this->setTMDBPosterPath($movie_info->tmdb_poster_path);
			}
		}
		
		//iTunes Data
		$this->fetchiTunesData();
		if($inDB){
			if($movie_info->itunes_id != ''){
				$this->setiTunesID($movie_info->itunes_id);
			}
		}
		
		//TMS Data 
		$this->fetchTMSData();
		if($inDB){
			if($movie_info->tms_movie_id != ''){
				$this->setTMSMovieID($movie_info->tms_movie_id);
			}
			if($movie_info->tms_root_id != ''){
				$this->setTMSRootID($movie_info->tms_root_id);
			}
			
		}
		
		//Critter Rating
		$this->fetchCritterRating();
		
		//Database Operations
		if($inDB){
			$this->db->where('id', $this->getID());
			$this->db->set('rotten_tomatoes_id', $this->getRottenTomatoesID());
			$this->db->set('itunes_id', $this->getiTunesID());
			$this->db->set('imdb_id', $this->getIMDBID());
			$this->db->set('tmdb_id', $this->getTMDBID());
			$this->db->set('tms_root_id', $this->getTMSRootID());
			$this->db->set('tms_movie_id', $this->getTMSMovieID());
			$this->db->set('hashtag', $this->getHashtag());
			$this->db->set('title', $this->getTitle());
			$this->db->set('box_office_release_date', $this->getBoxOfficeReleaseDate());
			$this->db->set('dvd_release_date', $this->getDVDReleaseDate());
			$this->db->set('tmdb_poster_path', $this->getTMDBPosterPath());
			$this->db->update('CRMovie');
		}
		else{
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('modified', 'NOW()', FALSE);									
			$this->db->set('rotten_tomatoes_id', $this->getRottenTomatoesID());
			$this->db->set('itunes_id', $this->getiTunesID());
			$this->db->set('imdb_id', $this->getIMDBID());
			$this->db->set('tmdb_id', $this->getTMDBID());
			$this->db->set('tms_root_id', $this->getTMSRootID());
			$this->db->set('tms_movie_id', $this->getTMSMovieID());
			$this->db->set('hashtag', $this->getHashtag());
			$this->db->set('title', $this->getTitle());
			$this->db->set('box_office_release_date', $this->getBoxOfficeReleaseDate());
			$this->db->set('dvd_release_date', $this->getDVDReleaseDate());
			$this->db->set('tmdb_poster_path', $this->getTMDBPosterPath());
			$this->db->insert('CRMovie');
			$this->setID($this->db->insert_id());
		}
		
		//Setting Result
		$result = array(
			'id' => hashids_encrypt($this->getID()),
			'rotten_tomatoes_id' => $this->getRottenTomatoesID(),
			'itunes_id' => $this->getiTunesID(),
			'imdb_id' => $this->getIMDBID(),
			'tmdb_id' => $this->getTMDBID(),
			'tms_root_id' => $this->getTMSRootID(),
			'tms_movie_id' => $this->getTMSMovieID(),
			'hashtag' => $this->getHashtag(),
			'title' => $this->getTitle(),
			'box_office_release_date' => $this->getBoxOfficeReleaseDate(),
			'dvd_release_date' => $this->getDVDReleaseDate(),
			'tmdb_poster_path' => $this->getTMDBPosterPath(),
			'critter_rating' => $this->getCritterRating(),
			'rt_details' => $this->getRTDetails(),
			'imdb_details' => $this->getIMDBDetails(),
			'tmdb_details' => $this->getTMDBDetails(),
			'tmdb_trailer_details' => $this->getTMDBTrailerDetails(),
			'itunes_details' => $this->getiTunesDetails(),
			'tms_details' => $this->getTMSDetails(),
			'tms_trailer_image_details' => $this->getTMSTrailerImageDetails(),
			'tms_trailer_details' => $this->getTMSTrailerDetails()
		);
		
		$this->setResult($result);
	}

	public function fetchRottenTomatoesData(){
		$url = sprintf($this->config->item('rotten_tomatoes_movie_url'), $this->getRottenTomatoesID(), $this->config->item('rotten_tomatoes_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('rotten_tomatoes_cache_seconds'));
		$res = json_decode($info);
		if(isset($res->title)){
			$this->setTitle($res->title);
			$this->setHashtag($this->makeHashtag($res->title));
		}
		if(isset($res->release_dates->theater)){
			$this->setBoxOfficeReleaseDate($res->release_dates->theater);
		}
		if(isset($res->release_dates->dvd)){
			$this->setDVDReleaseDate($res->release_dates->dvd);
		}
		if(isset($res->alternate_ids->imdb)){
			$this->setIMDBID($res->alternate_ids->imdb);
		}
		$this->setRTDetails($res);
	}
	
	public function fetchIMDBData(){
		$url = sprintf($this->config->item('omdb_title_url'), urlencode($this->getTitle()));
		$info = $this->_getCachedData($url, $this->config->item('omdb_cache_seconds'));
		$res = json_decode($info);
		if(isset($res->imdbID)){
			$this->setIMDBID($res->imdbID);
		}
		$this->setIMDBDetails($res);
	}
	
	public function fetchTMDBData(){
		$res = array();
		//Have TMDB id?
		if($this->getTMDBID() != ''){
			$res = $this->fetchTMDBDataByTMDBID();
		}
		elseif($this->getIMDBID() != ''){ //Have IMDB id?
			$res = $this->fetchTMDBDataByIMDBID();
		}
		elseif(($this->getTitle() != '') && ($this->getBoxOfficeReleaseDate() != '')){ //Have title and year?
			$res = $this->fetchTMDBDataByTitleAndYear();
		}
		else{ //Fetch by title
			$res = $this->fetchTMDBDataByTitle();
		}
		$this->setTMDBDetails($res);
	}

	public function fetchTMDBDataByTMDBID(){
		$url = sprintf($this->config->item('tmdb_id_url'), $this->getTMDBID(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res)){
			if(!empty($res->poster_path)){
				$this->setTMDBPosterPath($res->poster_path);
			}
			$this->fetchTMDBTrailerDetails();
			return $res;
		}
		else{
			return $this->fetchTMDBDataByIMDBID();
		}
		
	}
	
	public function fetchTMDBDataByIMDBID(){
		$url = sprintf($this->config->item('tmdb_imdb_id_url'), $this->getIMDBID(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->movie_results[0])){
			if(!empty($res->movie_results[0]->id)){
				$this->setTMDBID($res->movie_results[0]->id);
			}
			if(!empty($res->movie_results[0]->poster_path)){
				$this->setTMDBPosterPath($res->movie_results[0]->poster_path);
			}
			$this->fetchTMSTrailerDetails();
			return $res->movie_results[0];
		}
		else{
			$this->fetchTMDBDataByTitleAndYear();
			return array();
		}
	}
	
	public function fetchTMDBDataByTitleAndYear(){
		$year = substr($this->getBoxOfficeReleaseDate(), 0, 4);
		$url = sprintf($this->config->item('tmdb_title_year_url'),$this->config->item('tmdb_api_key'), urlencode($this->getTitle()), $year);
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->results[0])){
			if(!empty($res->results[0]->imdb_id)){
				$this->setIMDBID($res->results[0]->imdb_id);
			}
			if(!empty($res->results[0]->id)){
				$this->setTMDBID($res->results[0]->id);
			}
			if(!empty($res->results[0]->poster_path)){
				$this->setTMDBPosterPath($res->results[0]->poster_path);
			}
			$this->fetchTMDBTrailerDetails();
			return $res->results[0];
		}
		else{
			return $this->fetchTMDBDataByTitle();
		}
	}
	
	public function fetchTMDBDataByTitle(){
		$url = sprintf($this->config->item('tmdb_title_url'), $this->getTitle(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->results[0])){
			if(!empty($res->results[0]->imdb_id)){
				$this->setIMDBID($res->results[0]->imdb_id);
			}
			if(!empty($res->results[0]->id)){
				$this->setTMDBID($res->results[0]->id);
			}
			if(!empty($res->results[0]->poster_path)){
				$this->setTMDBPosterPath($res->results[0]->poster_path);
			}
			$this->fetchTMDBTrailerDetails();
			return $res->results[0];
		}
		else{
			return array();
		}
	}
	
	public function fetchTMDBTrailerDetails(){
		$url = sprintf($this->config->item('tmdb_trailer_url'), $this->getTMDBID(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		$this->setTMDBTrailerDetails($res);
	}
	
	public function fetchTMSTrailerDetails(){
		if($this->getTMSRootID() != ''){
			$url = sprintf($this->config->item('tms_trailer_url'), $this->getTMSRootID(), $this->config->item('tms_api_key'));
			$info = $this->_getCachedData($url, $this->config->item('tms_cache_seconds'));
			$res = json_decode($info);
			$this->setTMSTrailerDetails($res);
			$this->fetchTMSTrailerImageDetails();
		}
	}
	
	public function fetchTMSTrailerImageDetails(){
		$url = sprintf($this->config->item('tms_trailer_image_url'), $this->getTMSRootID(), $this->config->item('tms_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tms_cache_seconds'));
		$res = json_decode($info);
		$this->setTMSTrailerImageDetails($res);
	}
	
	public function fetchiTunesData(){
		$finalRes = array();
		$url = sprintf($this->config->item('itunes_title_url'), urlencode($this->getTitle()));
		$info = $this->_getCachedData($url, $this->config->item('itunes_cache_seconds'), 'itunes');
		$res = json_decode($info);
		if(isset($res->results)){
			foreach($res->results as $itunes){
				$iTunesReleaseYear = substr($itunes->releaseDate, 0, 4);
				$releaseYear = substr($this->getBoxOfficeReleaseDate(), 0, 4);
				//Sometimes the year is +/- 1 year, so get that as a fallback
				if((($releaseYear-1) <= $iTunesReleaseYear) && ($iTunesReleaseYear <= ($releaseYear+1))){
					$this->setiTunesID($itunes->trackId);
					$finalRes = $itunes;
					break;
				}
			}
		}
		$this->setiTunesDetails($finalRes);
	}
	
	public function fetchTMSData(){
		$finalRes = array();
		$url = sprintf($this->config->item('tms_title_url'), urlencode($this->getTitle()), $this->config->item('tms_api_key'));
		$info = $this->_fetchFromURL($url);
		$res = json_decode($info);
		if(isset($res->hits)){
			$matched = false;
			$releaseYear = substr($this->getBoxOfficeReleaseDate(), 0, 4);
			$secondTMSMovieID = '';
			$secondTMSRootID = '';
			$secondRes = array();
			foreach($res->hits as $tms){
				if($tms->program->entityType == 'Movie'){
					//Pull out salient details
					if($tms->program->releaseYear == $releaseYear){
						$matched = true;
					}
					elseif(abs($tms->program->releaseYear - $releaseYear) <= 1){
						if(isset($tms->program->tmsId)){
							$secondTMSMovieID = $tms->program->tmsId;
						}
						if(isset($tms->program->rootId)){
							$secondTMSRootID = $tms->program->rootId;
						}
					}
					else{
						continue;
					}
					if($matched){
						if(isset($tms->program->tmsId)){
							$this->setTMSMovieID($tms->program->tmsId);
						}
						if(isset($tms->program->rootId)){
							$this->setTMSRootID($tms->program->rootId);
						}
						$finalRes = $tms->program;
						$this->fetchTMSTrailerDetails();
						break;
					}
				}
			}
			if(!$matched){
				if($secondTMSMovieID  != ''){
					$this->setTMSMovieID($secondTMSMovieID);
				}
				if($secondTMSRootID  != ''){
					$this->setTMSRootID($secondTMSRootID);
					$this->fetchTMSTrailerDetails();
					$finalRes = $secondRes;
				}
			}
		}
		$this->setTMSDetails($finalRes);
	}

/*	
 	User rating values:	    
    CRMovieActionNone, 0
    CRMovieActionRecommend, 1
    CRMovieActionDontRecommend, 2
    CRMovieActionWatchList, 3
    CRMovieActionScrapPile 4 */

	public function fetchCritterRating()
	{
		$cacheKey = 'critter_rating_'.$this->getID();
		$rating = $this->cache->memcached->get($cacheKey);
		if(!$rating)
		{		
			//Count likes
			$this->db->where('movie_id', $this->getID());
			$this->db->where('rating', 1);
			$this->db->from('CRRating');
			$likeCount = $this->db->count_all_results();
			
			//Count dislikes
			$this->db->where('movie_id', $this->getID());
			$this->db->where('rating', 2);
			$this->db->from('CRRating');			
			$dislikeCount = $this->db->count_all_results();
			
			//Calculate average
			if ($likeCount + $dislikeCount > 0)
			{
				$rating = (($likeCount / ($likeCount + $dislikeCount)) * 100);
			}
			else
			{
				$rating = 0;
			}
		
			//Update cache
			$this->cache->memcached->save($cacheKey, $rating, $this->config->item('critter_rating_cache_seconds'));
			
			//update db record
			$this->db->where('id', $this->getID());
			$this->db->set('critter_rating', $rating);
			$this->db->update('CRMovie');
		}

		$this->setCritterRating($rating);
	}
	
	public function makeHashtag($string){
		return '#'.preg_replace("/[^a-z0-9]+/", "",strtolower($string));
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function setID($id){
		$this->id = $id;
	}
	
	public function getRottenTomatoesID(){
		return $this->rotten_tomatoes_id;
	}
	
	public function setRottenTomatoesID($rt_id){
		$this->rotten_tomatoes_id = $rt_id;
	}
	
	public function getiTunesID(){
		return $this->itunes_id;
	}
	
	public function setiTunesID($iTunesID){
		$this->itunes_id = $iTunesID;
	}
	
	public function getIMDBID(){
		return $this->imdb_id;
	}
	
	public function setIMDBID($imdbID){
		$this->imdb_id = $imdbID;
	}
	
	public function getTMDBID(){
		return $this->tmdb_id;
	}
	
	public function setTMDBID($tmdbID){
		$this->tmdb_id = $tmdbID;
	}
	
	public function getTMSRootID(){
		return $this->tms_root_id;
	}
	
	public function setTMSRootID($tmsRootID){
		$this->tms_root_id = $tmsRootID;
	}
	
	public function getTMSMovieID(){
		return $this->tms_movie_id;
	}
	
	public function setTMSMovieID($tmsMovieID){
		$this->tms_movie_id = $tmsMovieID;
	}
	
	public function getHashtag(){
		return $this->hashtag;
	}
	
	public function setHashtag($hashtag){
		$this->hashtag = $hashtag;
	}
	
	public function getTitle(){
		return $this->title;
	}
	
	public function setTitle($title){
		$this->title = $title;
	}
	
	public function getBoxOfficeReleaseDate(){
		return $this->box_office_release_date;
	}
	
	public function setBoxOfficeReleaseDate($date){
		$this->box_office_release_date = $date;
	}
	
	public function getDVDReleaseDate(){
		return $this->dvd_release_date;
	}
	
	public function setDVDReleaseDate($date){
		$this->dvd_release_date = $date;
	}
	
	public function getTMDBPosterPath(){
		return $this->tmdb_poster_path;
	}
	
	public function setTMDBPosterPath($path){
		$this->tmdb_poster_path = $path;
	}
	
	public function getPriority(){
		return $this->priority;
	}
	
	public function setPriority($priority){
		$this->priority = $priority;
	}
	
	public function getRTDetails(){
		return $this->rt_details;
	}
	
	public function setRTDetails($details){
		$this->rt_details = $details;
	}
	
	public function getIMDBDetails(){
		return $this->imdb_details;
	}
	
	public function setIMDBDetails($details){
		$this->imdb_details = $details;
	}
	
	public function getTMDBDetails(){
		return $this->tmdb_details;
	}
	
	public function setTMDBDetails($details){
		$this->tmdb_details = $details;
	}
	
	public function getiTunesDetails(){
		return $this->itunes_details;
	}
	
	public function setiTunesDetails($details){
		$this->itunes_details = $details;
	}
	
	public function getTMSDetails(){
		return $this->tms_details;
	}
	
	public function setTMSDetails($details){
		$this->tms_details = $details;
	}
	
	public function getTMDBTrailerDetails(){
		return $this->tmdb_trailer_details;
	}
	
	public function setTMDBTrailerDetails($details){
		$this->tmdb_trailer_details = $details;
	}
	
	public function getTMSTrailerDetails(){
		return $this->tms_trailer_details;
	}
	
	public function setTMSTrailerDetails($details){
		$this->tms_trailer_details = $details;
	}
	
	public function getTMSTrailerImageDetails(){
		return $this->tms_trailer_image_details;
	}
	
	public function setTMSTrailerImageDetails($details){
		$this->tms_trailer_image_details = $details;
	}
	
	public function getCritterRating(){
		return $this->critter_rating;
	}
	
	public function setCritterRating($rating){
		$this->critter_rating = $rating;
	}
	
	public function _getCachedData($url, $expiration, $mode = ''){
		$result = '';
		if(!$this->cache->memcached->get(urlencode($url))){
			$result = $this->_fetchFromURL($url, $expiration, true, $mode);
		}
		else{
			$result = $this->_getCache(urlencode($url));
		}
		return $result;
	}

	public function _fetchFromURL($url, $expiration = '', $shouldBeCached = false, $mode = ''){
		switch($mode){
			case 'itunes':
				$retries = 10;
				$used = 0;
				while($retries > 0){
					$info = str_replace("\n", '', $this->curl->simple_get($url));
					$curlInfo = $this->curl->info;
					if($curlInfo['http_code'] == 403){
						// wait for .5 seconds * number of retries
						usleep(500000*$used); //Backs off longer each time
						$retries--;
						$used++;
					}
					else{
						if($shouldBeCached){
							$this->cache->memcached->save(urlencode($url), $info, $expiration);
						}
						break;
					}
				} 
				break;
			default:
				$retries = 10;
				while($retries > 0){
					$info = str_replace("\n", '', $this->curl->simple_get($url));
					$curlInfo = $this->curl->info;
					if($curlInfo['http_code'] < 400){
						if($shouldBeCached){
							$this->cache->memcached->save(urlencode($url), $info, $expiration);
						}
						break;
					}
					else{
						$retries--;
					}
				}
				break;
		}
		
		return $info;
	}
	
	public function _getCache($key){
		return $this->cache->memcached->get($key);
	}
}