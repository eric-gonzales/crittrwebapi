<?php
/**
 * Movie Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class Movie_model extends CI_Model {
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
	
	public function __construct(){
		parent::__construct();
	}
	
	//Construct from RT ID
	public function __construct($rotten_tomatoes_id){
		$chk_stmt = $this->db->get_where('CRMovie',array('rotten_tomatoes_id' => $rotten_tomatoes_id), 1);
		if($chk_stmt->num_rows() > 0){
			$movie_info = $chk_stmt->row();
			$this->setID($movie_info->id);
		}
		else{
			
		}
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
}