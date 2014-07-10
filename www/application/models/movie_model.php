<?php
/**
 * Movie Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 * TODO: remove legacy properties/functions
 */

class Movie_model extends CR_Model {
	private $id;
	private $available_services;
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
	private $netflix_link;
	private $amazon_details;
	private $youtube_trailer_id;
	private $on_att;
	private $on_charter;
	private $on_comcast;
	private $on_cox;
	private $on_directv;
	private $on_dish;
	private $on_twc;
	private $on_verizon;
	private $on_amazon_prime;
	private $on_cinemax;
	private $on_crackle;
	private $on_hbo;
	private $on_hitbliss;
	private $on_hulu;
	private $on_hulu_plus;
	private $on_max;
	private $on_mgo;
	private $on_netflix_ca;
	private $on_netflix_uk;
	private $on_netflix_us;
	private $on_popcornflix;
	private $on_redbox;
	private $on_showtime;
	private $on_snagflilms;
	private $on_spiritclips;
	private $on_targettickets;
	private $on_vudu;
	private $on_youtube;
	
	//Construct from RT ID
	public function __construct($rotten_tomatoes_id)
	{
		parent::__construct();
		
		//Check the cache first
		$cacheKey = "CRMovie_" . $rotten_tomatoes_id;
		$result = $result = $this->cache->memcached->get($cacheKey);
		$this->setRottenTomatoesID($rotten_tomatoes_id);
		
		//Fetch critter rating
		$this->fetchCritterRating();
		
		if (!$result)
		{
			//Try to get data from DB 
			$inDB = FALSE;
			$movie_info = NULL;
			$chk_stmt = $this->db->get_where('CRMovie',array('rotten_tomatoes_id' => $this->getRottenTomatoesID()), 1);
			if($chk_stmt->num_rows() > 0)
			{
				$inDB = TRUE; //this record is in the database, so we will update instead of insert
				$movie_info = $chk_stmt->row();
				$this->setID($movie_info->id);
			}
		
			//RT data
			if($inDB)
			{
				if($movie_info->title != '')
				{
					$this->setTitle($movie_info->title);
					$this->setHashtag($this->makeHashtag($movie_info->title));
				}
				if($movie_info->box_office_release_date != '')
				{
					$this->setBoxOfficeReleaseDate($movie_info->box_office_release_date);
				}
				if($movie_info->dvd_release_date != '')
				{
					$this->setDVDReleaseDate($movie_info->dvd_release_date);
				}
				if($movie_info->imdb_id != '')
				{
					$this->setIMDBID($movie_info->imdb_id);
				}
			}
			$this->fetchRottenTomatoesData();
		
			//IMDB Data
			$this->fetchIMDBData();
		
			//TMDB data
			if($inDB)
			{
				if($movie_info->tmdb_id != '')
				{
					$this->setTMDBID($movie_info->tmdb_id);
				}
				if($movie_info->tmdb_poster_path != '')
				{
					$this->setTMDBPosterPath($movie_info->tmdb_poster_path);
				}
			}
			$this->fetchTMDBData();
		
			//iTunes Data
			if($inDB)
			{
				if($movie_info->itunes_id != '')
				{
					$this->setiTunesID($movie_info->itunes_id);
				}
			}
			$this->fetchiTunesData();
		
			//TMS Data 
			if($inDB)
			{
				if($movie_info->tms_movie_id != '')
				{
					$this->setTMSMovieID($movie_info->tms_movie_id);
				}
				if($movie_info->tms_root_id != '')
				{
					$this->setTMSRootID($movie_info->tms_root_id);
				}
			}
			else
			{
				$this->fetchTMSData();
			}
			
			//Fetch Trailer Details
			//Check to see if this movie is in the database and has a Youtube Trailer ID. If so, we will set this as the Youtube Trailer ID.
			if($inDB && !empty($movie_info->youtube_trailer_id)){
				$this->setYouTubeTrailerID($movie_info->youtube_trailer_id);
			}
			else{
				$this->fetchTrailerDetails();
			}
			
			//Netflix Link
			$this->fetchNetflixOnlineVideo();
			
			//Amazon Results 
			$this->fetchAmazonOnlineVideo();
			
			//Video On Demand
			if($inDB){
				$this->fetchAvailableServices();
				
				//**begin deprecated setters**//
				$this->setOnAtt($movie_info->on_att);
				$this->setOnCharter($movie_info->on_charter);
				$this->setOnComcast($movie_info->on_comcast);
				$this->setOnCox($movie_info->on_cox);
				$this->setOnDirectv($movie_info->on_directv);
				$this->setOnDish($movie_info->on_dish);
				$this->setOnTwc($movie_info->on_twc);
				$this->setOnVerizon($movie_info->on_verizon);
				$this->setOnAmazonPrime($movie_info->on_amazon_prime);
				$this->setOnCinemax($movie_info->on_cinemax);
				$this->setOnCrackle($movie_info->on_crackle);
				$this->setOnHBO($movie_info->on_hbo);
				$this->setOnHitbliss($movie_info->on_hitbliss);
				$this->setOnHulu($movie_info->on_hulu);
				$this->setOnHuluPlus($movie_info->on_hulu_plus);
				$this->setOnMax($movie_info->on_max);
				$this->setOnMgo($movie_info->on_mgo);
				$this->setOnNetflixCA($movie_info->on_netflix_ca);
				$this->setOnNetflixUK($movie_info->on_netflix_uk);
				$this->setOnNetflixUS($movie_info->on_netflix_us);
				$this->setOnPopcornflix($movie_info->on_popcornflix);
				$this->setOnRedbox($movie_info->on_redbox);
				$this->setOnShowtime($movie_info->on_showtime);
				$this->setOnSnagfilms($movie_info->on_snagfilms);
				$this->setOnSpiritclips($movie_info->on_spiritclips);
				$this->setOnTargettickets($movie_info->on_targettickets);
				$this->setOnVudu($movie_info->on_vudu);
				$this->setOnYoutube($movie_info->on_youtube);
				//**end deprecated setters**//
			}

			
			//Database Operations
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
			$this->db->set('youtube_trailer_id', $this->getYouTubeTrailerID());
			$this->db->set('modified', 'NOW()', FALSE);		
			if ($inDB)
			{
				//Update
				$this->db->where('id', $this->getID());
				$this->db->update('CRMovie');
			}
			else
			{
				//Insert
				$this->db->set('created', 'NOW()', FALSE);
				$this->db->insert('CRMovie');
				$this->setID($this->db->insert_id());
				
				//Add genres
				$this->load->model('genre_model');				
				$this->genre_model->addGenresToMovie($this->getID(), $this->getRTDetails()->genres);
			}
			
			//Setting Result
			$result = array
			(
				'id' => hashids_encrypt($this->getID()),
				'available_services' => $this->getAvailableServices(),
				'critter_rating' => $this->getCritterRating(),
				'hashtag' => $this->getHashtag(),
				'imdb_id' => $this->getIMDBID(),
				'imdb_rating' => $this->getIMDBDetails()->imdbRating, 
				'itunes_id' => $this->getiTunesID(),
				'rotten_tomatoes_id' => $this->getRottenTomatoesID(),
				'rotten_tomatoes_critics_score' => $this->getRTDetails()->ratings->critics_score,
				'mpaa_rating' => $this->getRTDetails()->mpaa_rating,
				//**begin legacy data**//
				'on_amazon_prime' => $this->getOnAmazonPrime(),
				'on_att' => $this->getOnAtt(),
				'on_charter' => $this->getOnCharter(),
				'on_cinemax' => $this->getOnCinemax(),
				'on_comcast' => $this->getOnComcast(),
				'on_cox' => $this->getOnCox(),
				'on_crackle' => $this->getOnCrackle(),
				'on_directv' => $this->getOnDirectv(),
				'on_dish' => $this->getOnDish(),
				'on_hbo' => $this->getOnHBO(),
				'on_hulu' => $this->getOnHulu(),
				'on_hulu_plus' => $this->getOnHuluPlus(),
				'on_max' => $this->getOnMax(),
				'on_mgo' => $this->getOnMgo(),
				'on_netflix_ca' => $this->getOnNetflixCA(),
				'on_netflix_uk' => $this->getOnNetflixUK(),
				'on_netflix_us' => $this->getOnNetflixUS(),
				'on_popcornflix' => $this->getOnPopcornflix(),
				'on_redbox' => $this->getOnRedbox(),
				'on_showtime' => $this->getOnShowtime(),
				'on_snagfilms' => $this->getOnSnagfilms(),
				'on_spiritclips' => $this->getOnSpiritclips(),
				'on_targettickets' => $this->getOnTargettickets(),
				'on_hitbliss' => $this->getOnHitbliss(),
				'on_twc' => $this->getOnTwc(),
				'on_verizon' => $this->getOnVerizon(),
				'on_vudu' => $this->getOnVudu(),
				'on_youtube' => $this->getOnYoutube(),
				//**end legacy data*//
				'original_image_url' => $this->getRTDetails()->posters->original,
				'poster_path' => $this->getTMDBPosterPath(),
				'release_date_dvd' => $this->getDVDReleaseDate(),
				'release_date_theater' => $this->getBoxOfficeReleaseDate(),
				'runtime' => $this->getRTDetails()->runtime,
				'synopsis' => $this->getRTDetails()->synopsis,				
				'title' => $this->getTitle(),				
				'tmdb_id' => $this->getTMDBID(),
				'tms_root_id' => $this->getTMSRootID(),
				'tms_movie_id' => $this->getTMSMovieID(),
				'url_string_amazon' => $this->getAmazonDetails()["DetailPageURL"], //legacy
				'url_string_imdb' => "http://www.imdb.com/title/" . $this->getIMDBID(),
				'url_string_netflix' => $this->getNetflixLink(), //legacy
				'url_string_rottentomatoes' => $this->getRTDetails()->links->alternate,
				'year' => $this->getRTDetails()->year,
				'youTubeTrailerID' => $this->getYouTubeTrailerID(),
				'cast' => $this->getRTDetails()->abridged_cast,
				'directors' => $this->getRTDetails()->abridged_directors,				
				'genres' => $this->getRTDetails()->genres
			);			
			
			//Save to cache (only caching as long as we cache the rating)
			$this->cache->memcached->save($cacheKey, $result, $this->config->item('critter_movie_cache_seconds'));
		}
		else
		{
			//(Potentially) update the calculated critter rating - they refresh every 15 mins where the movie itself refreshes every 24 hrs.
			$result['critter_rating'] = $this->getCritterRating();
		}
		
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
	
	public function fetchIMDBData()
	{
		$imdbID = $this->getIMDBID();
		$url = sprintf($this->config->item('omdb_title_url'), urlencode($this->getTitle()));
		if ($imdbID != NULL)
		{
			if (strpos($imdbID, "tt") !== 0) $imdbID = "tt" . $imdbID;
			$url = sprintf($this->config->item('omdb_imdb_id_url'), $imdbID);	
		}
		
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
			return $res->results[0];
		}
		else{
			return array();
		}
	}
	
	public function fetchTrailerDetails(){
		//if the movie has a TMDB ID, query TMDB for details
		if($this->getTMDBID() != ''){
			$this->fetchTMDBTrailerDetails();
		}
		//Now we will check if there is not a Youtube ID. (Database and TMDB has no results)
		if($this->getYouTubeTrailerID() == ''){
			if($this->getTMSRootID() != ''){
				//DJS - Commenting out TMS trailer fetch - unused
				//$this->fetchTMSTrailerDetails();
			}
			else{
				//write to the log that no trailer was found from DB, TMDB, or TMS
				log_message('error', '[no-trailer] No Trailer Found for: '.$this->getTitle(), false,  'no-trailer');	
			}
		}
	}
	
	public function fetchTMDBTrailerDetails(){
		$url = sprintf($this->config->item('tmdb_trailer_url'), $this->getTMDBID(), $this->config->item('tmdb_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tmdb_cache_seconds'));
		$res = json_decode($info);
		$this->setTMDBTrailerDetails($res);
	}
	
	public function fetchTMSTrailerDetails(){
		log_message('error', '[tms-trailer] Fetch '.$this->getTMSRootID(), false, 'tms-trailer');
		$url = sprintf($this->config->item('tms_trailer_url'), $this->getTMSRootID(), $this->config->item('tms_api_key'));
		$info = $this->_getCachedData($url, $this->config->item('tms_cache_seconds'));
		$res = json_decode($info);
		$this->setTMSTrailerDetails($res);
		$this->fetchTMSTrailerImageDetails();
	}
	
	public function fetchTMSTrailerImageDetails(){
		log_message('error', '[tms-trailer-image] Fetch '.$this->getTMSRootID(), false, 'tms-trailer-image');
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
				$releaseYear = $this->getRTDetails()->year;
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
			$releaseYear = $this->getRTDetails()->year;
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
					$finalRes = $secondRes;
				}
			}
		}
		$this->setTMSDetails($finalRes);
	}

	public function fetchNetflixOnlineVideo(){
		$releaseYear = $this->getRTDetails()->year;
		$this->db->select('netflix_id');
		$query = $this->db->get_where('CRNetflix', array('title' => $this->getTitle(), 'release_year' => $releaseYear, 'season' => 0, 'avail_us' => 1), 1);
		if($query->num_rows() > 0){
			$result = $query->row();
			if($result->netflix_id != ''){
				$this->setNetflixLink($this->config->item('netflix_base_url').$result->netflix_id);
			}
		}
	}
	
	public function fetchAmazonOnlineVideo(){
		$title = $this->getTitle();
		$releaseYear = $this->getRTDetails()->year;
		if($title != '' && $releaseYear != ''){
			require_once(dirname(__FILE__).'/../libraries/amazonvideo.php');
			$amazon_video = new AmazonVideo($this->config->item('aws_key'), $this->config->item('aws_secret'));
			$result = $amazon_video->search($title, $releaseYear);
			$this->setAmazonDetails($result);
		}
	}

	public function fetchCritterRating()
	{
		$this->load->model('ratings_model');						
		$rating = $this->ratings_model->critterRatingForMovie($this->getRottenTomatoesID());
		$this->setCritterRating($rating);
	}

	public function fetchAvailableServices(){
		$services = array();
		$query = $this->db->get_where('CRMovieVOD', array('movie_id' => $this->getID()));
		foreach($query->result() as $movie_vod){
			$vod_query = $this->db->get_where('CRVODProvider', array('id' => $movie_vod->vod_id), 1);
			$vod_provider = $vod_query->row();
			$vod_provider_name = str_replace(' ', '_', str_replace('-', '', strtolower($vod_provider->name)));
			//For Amazon Prime, we want to use the Amazon URL
			if($movie_vod->vod_id == 2){
				$arr = array( 'name' => 'amazon_prime', 'view_url' => $this->getAmazonDetails()["DetailPageURL"]);
				array_push($services, $arr);
			}
			else{
				$arr = array( 
					'name' => $vod_provider_name,
					'app_url' => str_replace('/', '\/', $movie_vod->app_url),
					'view_url' => str_replace('/', '\/', $movie_vod->app_url)
				);
				array_push($services, $arr);
			}
		}
		//Add Amazon URL
		if($this->getAmazonDetails()["DetailPageURL"] != ''){
			$arr = array( 'name' => 'amazon', 'view_url' => $this->getAmazonDetails()["DetailPageURL"]);
				array_push($services, $arr);
		}
		$this->setAvailableServices($services);
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
		foreach($details->youtube as $trailer)
		{
			$this->setYouTubeTrailerID($trailer->source);
			break;
		}
	}
	
	public function setYouTubeTrailerID($trailerID)
	{
		$this->youtube_trailer_id = $trailerID;
	}
	
	public function getYouTubeTrailerID()
	{
		return $this->youtube_trailer_id;
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
	
	public function getAmazonDetails(){
		return $this->amazon_details;
	}
	
	public function setAmazonDetails($details){
		$this->amazon_details = $details;
	}
	
	public function getNetflixLink(){
		return $this->netflix_link;
	}
	
	public function setNetflixLink($link){
		$this->netflix_link = $link;
	}
	
	public function getAvailableServices(){
		return $this->available_services;
	}
	
	public function setAvailableServices($services){
		$this->available_services = $services;
	}
	
	public function _getCachedData($url, $expiration, $mode = '')
	{
		$result = $this->cache->memcached->get($url);
		if(!$result)
		{
			$result = $this->_fetchFromURL($url, $expiration, true, $mode);
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
							$this->cache->memcached->save($url, $info, $expiration);
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
							$this->cache->memcached->save($url, $info, $expiration);
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
	
	
	//***begin legacy functions***//
	
	public function getOnAtt(){
		return $this->att;
	}
	
	public function setOnAtt($id){
		$this->on_att = $id;
	}
	public function getOnCharter(){
		return $this->on_charter;
	}
	
	public function setOnCharter($id){
		$this->on_charter = $id;
	}
	public function getOnComcast(){
		return $this->on_comcast;
	}
	
	public function setOnComcast($id){
		$this->on_comcast = $id;
	}
	
	public function getOnCox(){
		return $this->on_cox;
	}
	
	public function setOnCox($id){
		$this->on_cox = $id;
	}
	
	public function getOnDirectv(){
		return $this->on_directv;
	}
	
	public function setOnDirectv($id){
		$this->on_directv = $id;
	}
	
	public function getOnDish(){
		return $this->on_dish;
	}
	
	public function setOnDish($id){
		$this->on_dish = $id;
	}
	
	public function getOnTwc(){
		return $this->on_twc;
	}
	
	public function setOnTwc($id){
		$this->on_twc = $id;
	}
	
	public function getOnVerizon(){
		return $this->on_verizon;
	}
	
	public function setOnVerizon($id){
		$this->on_verizon = $id;
	}
	
	public function getOnAmazonPrime(){
		return $this->on_amazon_prime;
	}
	
	public function setOnAmazonPrime($id){
		$this->on_amazon_prime = $id;
	}
	
	public function getOnCinemax(){
		return $this->on_cinemax;
	}
	
	public function setOnCinemax($id){
		$this->on_cinemax = $id;
	}
	
	public function getOnCrackle(){
		return $this->on_crackle;
	}
	
	public function setOnCrackle($id){
		$this->on_crackle = $id;
	}
	
	public function getOnHBO(){
		return $this->on_hbo;
	}
	
	public function setOnHBO($id){
		$this->on_hbo = $id;
	}
	
	public function getOnHitbliss(){
		return $this->on_hitbliss;
	}
	
	public function setOnHitbliss($id){
		$this->on_hitbliss = $id;
	}
	
	public function getOnHulu(){
		return $this->on_hulu;
	}
	
	public function setOnHulu($id){
		$this->on_hulu = $id;
	}
	
	public function getOnHuluPlus(){
		return $this->on_hulu_plus;
	}
	
	public function setOnHuluPlus($id){
		$this->on_hulu_plus = $id;
	}
	
	public function getOnMax(){
		return $this->on_max;
	}
	
	public function setOnMax($id){
		$this->on_max = $id;
	}
	
	public function getOnMgo(){
		return $this->on_mgo;
	}
	
	public function setOnMgo($id){
		$this->on_mgo = $id;
	}
	
	public function getOnNetflixCA(){
		return $this->on_netflix_ca;
	}
	
	public function setOnNetflixCA($id){
		$this->on_netflix_ca = $id;
	}
	
	public function getOnNetflixUK(){
		return $this->on_netflix_uk;
	}
	
	public function setOnNetflixUK($id){
		$this->on_netflix_uk = $id;
	}
	
	public function getOnNetflixUS(){
		return $this->on_netflix_us;
	}
	
	public function setOnNetflixUS($id){
		$this->on_netflix_us = $id;
	}
	
	public function getOnPopcornflix(){
		return $this->on_popcornflix;
	}
	
	public function setOnPopcornflix($id){
		$this->on_popcornflix = $id;
	}
	
	public function getOnRedbox(){
		return $this->on_redbox;
	}
	
	public function setOnRedbox($id){
		$this->on_redbox = $id;
	}
	
	public function getOnShowtime(){
		return $this->on_showtime;
	}
	
	public function setOnShowtime($id){
		$this->on_showtime = $id;
	}
	
	public function getOnSnagfilms(){
		return $this->on_snagflilms;
	}
	
	public function setOnSnagfilms($id){
		$this->on_snagflilms = $id;
	}
	
	public function getOnSpiritclips(){
		return $this->on_spiritclips;
	}
	
	public function setOnSpiritclips($id){
		$this->on_spiritclips = $id;
	}
	
	public function getOnTargettickets(){
		return $this->on_targettickets;
	}
	
	public function setOnTargettickets($id){
		$this->on_targettickets = $id;
	}
	
	public function getOnVudu(){
		return $this->on_vudu;
	}
	
	public function setOnVudu($id){
		$this->on_vudu = $id;
	}
	
	public function getOnYoutube(){
		return $this->on_youtube;
	}
	
	public function setOnYoutube($id){
		$this->on_youtube = $id;
	}
}
