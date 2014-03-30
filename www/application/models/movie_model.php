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
			$this->setTitle($movie_info->title);
			$this->setHashtag($this->makeHashtag($movie_info->title));
			$this->setBoxOfficeReleaseDate($movie_info->box_office_release_date);
			$this->setDVDReleaseDate($movie_info->dvd_release_date);
			$this->setIMDBID($movie_info->imdb_id);
		}
		
		//MDB Data
		$this->fetchIMDBData();
		
		//TMDB data
		$this->fetchTMDBData();
		
		if($inDB){
			$this->setTMDBID($movie_info->tmdb_id);
			$this->setTMDBPosterPath($movie_info->tmdb_poster_path);
		}
		
		//iTunes ID if it is missing
		if(empty($movie_info->itunes_id)){
			$this->fetchiTunesData();
		}
		else{
			$this->setiTunesID($movie_info->itunes_id);
		}
		
		//TMS Data 
		$this->fetchTMSData();
		
		if($inDB){
			$this->setTMSMovieID($movie_info->tms_movie_id);
			$this->setTMSRootID($movie_info->tms_root_id);
		}
		
		if($inDB){
			//update the CRMovie record
		}
		else{
			//create the CRMovie record
		}
		
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
			'tmdb_poster_path' => $this->getTMDBPosterPath()
		);
		
		//print_r($result);
		
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
	}
	
	public function fetchIMDBData(){
		$url = sprintf($this->config->item('omdb_title_url'), urlencode($this->getTitle()));
		$info = $this->_getCachedData($url, $this->config->item('omdb_cache_seconds'));
		$res = json_decode($info);
		if(isset($res->imdbID)){
			$this->setIMDBID($res->imdbID);
		}
	}
	
	public function fetchTMDBData(){
		//Have TMDB id?
		if($this->getTMDBID() != ''){
			$this->fetchTMDBDataByTMDBID();
		}
		elseif($this->getIMDBID() != ''){ //Have IMDB id?
			$this->fetchTMDBDataByIMDBID();
		}
		elseif(($this->getTitle() != '') && ($this->getBoxOfficeReleaseDate() != '')){ //Have title and year?
			$this->fetchTMDBDataByTitleAndYear();
		}
		else{ //Fetch by title
			$this->fetchTMDBDataByTitle();
		}
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
	}
	
	public function fetchTMDBDataByIMDBID(){
		$url = sprintf($this->config->item('tmdb_imdb_id_url'), $this->getIMDBID(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->movie_results)){
			if(!empty($res->movie_results->id)){
				$this->setTMDBID($res->movie_results->id);
			}
			if(!empty($res->movie_results->poster_path)){
				$this->setTMDBPosterPath($res->movie_results->poster_path);
			}
		}
		else{
			$this->fetchTMDBDataByTitleAndYear();
		}
	}
	
	public function fetchTMDBDataByTitleAndYear(){
		$year = substr($this->getBoxOfficeReleaseDate(), 0, 4);
		$url = sprintf($this->config->item('tmdb_title_year_url'), $this->getTitle(), $year, $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->results)){
			if(!empty($res->results->imdb_id)){
				$this->setIMDBID($res->imdb_id);
			}
			if(!empty($res->results->id)){
				$this->setTMDBID($res->id);
			}
			if(!empty($res->results->poster_path)){
				$this->setTMDBPosterPath($res->poster_path);
			}
		}
		else{
			$this->fetchTMDBDataByTitle();
		}
	}
	
	public function fetchTMDBDataByTitle(){
		$url = sprintf($this->config->item('tmdb_title_url'), $this->getTitle(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		if(!empty($res->results->imdb_id)){
			$this->setIMDBID($res->results->imdb_id);
		}
		if(!empty($res->results->id)){
			$this->setTMDBID($res->results->id);
		}
		if(!empty($res->results->poster_path)){
			$this->setTMDBPosterPath($res->results->poster_path);
		}
	}
	
	public function fetchiTunesData(){
		$url = sprintf($this->config->item('itunes_title_url'), $this->getTitle());
		$info = $this->_fetchFromURL($url);
		$res = json_decode($info);
		if(isset($res->results)){
			foreach($res->results as $itunes){
				$releaseYear = substr($itunes->releaseDate, 0, 4);
				if($releaseYear == substr($this->getBoxOfficeReleaseDate(), 0, 4)){
					$this->setiTunesID($itunes->trackId);
					break;
				}
			}
		}
	}
	
	public function fetchTMSData(){
		$url = sprintf($this->config->item('tms_title_url'), $this->getTitle(), $this->config->item('tms_api_key'));
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
			}
		}
	}
	
	public function makeHashtag($string){
		return '#'.str_replace(' ', '', strtolower($string));
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
	
	public function setIMDBID(){
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
	
	public function _getCachedData($url, $expiration){
		$result = '';
		
		if(!$this->cache->memcached->get($url)){
			$result = $this->_fetchFromURL(urlencode($url), $expiration, true);
			echo 'test1';
		}
		else{
			$result = $this->_getCache(urlencode($url));
			echo 'test2';
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