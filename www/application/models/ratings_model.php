<?php
/**
 * Ratings Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class Ratings_model extends CR_Model 
{

	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct()
	{
		parent::__construct();
	}
	
	/*	
	 	User rating values:	    
	    CRMovieActionNone, 0
	    CRMovieActionRecommend, 1
	    CRMovieActionDontRecommend, 2
	    CRMovieActionWatchList, 3
	    CRMovieActionScrapPile 4 */
	
	public function critterRatingForMovie($rottenTomatoesID)
	{
		$cacheKey = 'critter_rating_'.$rottenTomatoesID;
		$rating = $this->cache->memcached->get($cacheKey);
		if(!$rating)
		{
			//Count superlikes
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');			
			$this->db->where('CRMovie.rotten_tomatoes_id', $rottenTomatoesID);
			$this->db->where('CRRating.rating', 1);
			$this->db->where('CRRating.super', 1);
			$superLikeCount = $this->db->count_all_results();			
				
			//Count likes
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');			
			$this->db->where('CRMovie.rotten_tomatoes_id', $rottenTomatoesID);
			$this->db->where('CRRating.rating', 1);
			$this->db->where('CRRating.super', 0);			
			$likeCount = $this->db->count_all_results();
			
			//Count dislikes
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');			
			$this->db->where('CRMovie.rotten_tomatoes_id', $rottenTomatoesID);
			$this->db->where('CRRating.rating', 2);
			$this->db->where('CRRating.super', 0);			
			$dislikeCount = $this->db->count_all_results();

			//Count superhates
			$this->db->from('CRRating');
			$this->db->join('CRMovie', 'CRMovie.id = CRRating.movie_id');			
			$this->db->where('CRMovie.rotten_tomatoes_id', $rottenTomatoesID);
			$this->db->where('CRRating.rating', 2);
			$this->db->where('CRRating.super', 1);			
			$superHateCount = $this->db->count_all_results();
			
			//Calculate average
			$ratingCount = $superLikeCount + $likeCount + $dislikeCount + $superHateCount;
			if ($ratingCount > 0)
			{
				$sum = ($superLikeCount * 3) + ($likeCount * 2) + ($dislikeCount * 1) + ($superHateCount * 0);
				$average = $sum / $ratingCount;
				$rating = intval((($average / 3) * 100));
			}
			else
			{
				$rating = -1;
			}
		
			//Update cache
			$this->cache->memcached->save($cacheKey, $rating, $this->config->item('critter_rating_cache_seconds'));
			
			//update db record
			if ($rating != -1)
			{
				$this->db->where('rotten_tomatoes_id', $rottenTomatoesID);
				$this->db->set('critter_rating', $rating);
				$this->db->update('CRMovie');
			}
		}
		return $rating;
	}
}