<?php
class Import extends CI_Controller 
{
    function __construct() 
    {
        parent::__construct();
    }
    
	public function movies()
	{
		$inputFile = "/var/www/html/www/application/third_party/Movie.json";
		$handle = fopen($inputFile, "r");
		$movies = json_decode(fread($handle, filesize($inputFile)));
		fclose($handle);
		
		foreach($movies->results as $movie)
		{
			$this->db->set("hashtag", $movie->hashtag);
			$this->db->set("itunes_id", $movie->itunes_id);
			$this->db->set("imdb_id", $movie->imdb_id);			
			$this->db->set("title", $movie->title);		
			$this->db->set("tmdb_id", $movie->tmdb_id);						
			$this->db->set("rotten_tomatoes_id", $movie->rotten_tomatoes_id);
			$this->db->set("tmdb_poster_path", $movie->poster_path);		
			$this->db->set("priority", $movie->priority);
			$this->db->set("created", $movie->created);
			$this->db->set("modified", $movie->created);													
			$this->db->insert('CRMovie');
		}
	}
	
}
?>