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
	private $priority;
	private $rt_details;
	private $imdb_details;
	private $tmdb_details;
	private $itunes_details;
	private $tms_details;
	
	//Construct from RT ID
	public function __construct($rotten_tomatoes_id){
		parent::__construct();
		$inDB = false;
		
		//Try to get data from DB first
		$this->setRottenTomatoesID($rotten_tomatoes_id);
		$chk_stmt = $this->db->get_where('CRMovie',array('rotten_tomatoes_id' => $rotten_tomatoes_id), 1);
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
			'dvd_release_date' => $this->getDVDReleaseDate(),
			'tmdb_poster_path' => $this->getTMDBPosterPath(),
			'rt_details' => $this->getRTDetails(),
			'imdb_details' => $this->getIMDBDetails(),
			'tmdb_details' => $this->getTMDBDetails(),
			'itunes_details' => $this->getiTunesDetails(),
			'tms_details' => $this->getTMSDetails()
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
		$this->setRTDetails($info);
	}
	
	public function fetchIMDBData(){
		$url = sprintf($this->config->item('omdb_title_url'), urlencode($this->getTitle()));
		$info = $this->_getCachedData($url, $this->config->item('omdb_cache_seconds'));
		$res = json_decode($info);
		if(isset($res->imdbID)){
			$this->setIMDBID($res->imdbID);
		}
		$this->setIMDBDetails($info);
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
		}
		else{
			$this->fetchTMDBDataByIMDBID();
		}
		return $info;
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
		}
		else{
			$this->fetchTMDBDataByTitleAndYear();
		}
		return $info;
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
		}
		else{
			$this->fetchTMDBDataByTitle();
		}
		return $info;
	}
	
	public function fetchTMDBDataByTitle(){
		$url = sprintf($this->config->item('tmdb_title_url'), $this->getTitle(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->results[0]->imdb_id)){
			$this->setIMDBID($res->results[0]->imdb_id);
		}
		if(!empty($res->results[0]->id)){
			$this->setTMDBID($res->results[0]->id);
		}
		if(!empty($res->results[0]->poster_path)){
			$this->setTMDBPosterPath($res->results[0]->poster_path);
		}
		return $info;
	}
	
	public function fetchiTunesData(){
		$finalRes = array();
		$url = sprintf($this->config->item('itunes_title_url'), urlencode($this->getTitle()));
		$info = $this->_fetchFromURL($url);
		$res = json_decode($info);
		if(isset($res->results)){
			foreach($res->results as $itunes){
				$iTunesReleaseYear = substr($itunes->releaseDate, 0, 4);
				$releaseYear = substr($this->getBoxOfficeReleaseDate(), 0, 4);
				if((($releaseYear-1) <= $iTunesReleaseYear) && ($iTunesReleaseYear <= ($releaseYear+1))){
					$this->setiTunesID($itunes->trackId);
					$finalRes = $itunes;
					break;
				}
			}
		}
		$this->setiTunesDetails($info);
	}
	
	public function fetchTMSData(){
		$finalRes = array();
		$url = sprintf($this->config->item('tms_title_url'), urlencode($this->getTitle()), $this->config->item('tms_api_key'));
		$info = $this->_fetchFromURL($url);
		$res = json_decode($info);
		if(isset($res->hits)){
			foreach($res->hits as $tms){
				if(isset($tms->program->tmsId)){
					$this->setTMSMovieID($tms->program->tmsId);
				}
				if(isset($tms->program->rootId)){
					$this->setTMSRootID($tms->program->rootId);
				}
				if(isset($tms->program->rootId) || isset($tms->program->tmsId)){
					$finalRes = $tms->program;
					break;
				}
			}
		}
		$this->setTMSDetails($info);
	}
	
	public function makeHashtag($string){
		return '#'.preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '',str_replace(' ', '', strtolower($string)));
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
	
	public function _getCachedData($url, $expiration){
		$result = '';
		if(!$this->cache->memcached->get(urlencode($url))){
			$result = $this->_fetchFromURL($url, $expiration, true);
		}
		else{
			$result = $this->_getCache(urlencode($url));
		}
		return $result;
	}

	public function _fetchFromURL($url, $expiration = '', $shouldBeCached = false){
		$info = str_replace("\n", '', $this->curl->simple_get($url));
		if($shouldBeCached){
			$this->cache->memcached->save(urlencode($url), $info, $expiration);
		}
		return $info;
	}
	
	public function _getCache($key){
		return $this->cache->memcached->get($key);
	}
}