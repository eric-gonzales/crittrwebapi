<?php
class Genre_model extends CR_Model 
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function addGenresToMovie($movieID, $genres)
	{
		error_log("Adding genres to movie $movieID: " . json_encode($genres));
		foreach($genres as $genreName)
		{
			//Find the genre ID, create if needed
			$genreID = NULL;
			$query = $this->db->get_where('CRGenre',array('name' => $genreName), 1);
			if ($query->num_rows() > 0)
			{
				$genre = $query->row();
				$genreID = $genre->id;
			}
			else
			{
				$this->db->set('name', $genreName);
				$this->db->insert('CRGenre');
				$genreID = $this->db->insert_id();
			}
			
			//Add it to the CRGenreMovie table
			$this->db->set('movie_id', $movieID);
			$this->db->set('genre_id', $genreID);
			$this->db->insert('CRGenreMovie');
		}
	}

}