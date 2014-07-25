<?php
	$config['shared_secret'] = 'ff8347fskjqd823e2dh2fds'; //This doesn't work, have to edit application/hooks/authenticator
	$config['server_secret'] = 'kHmN5wZkwCwxe2B59B9q3w8D';
	
	//Critter
	$config['critter_warmcache_hourly_max'] = 1000;				    //Max movies to cache per hour
	$config['critter_movie_cache_seconds'] = 60 * 60 * 24;			//Cache new/upcoming/current movies 24hrs
	$config['critter_old_movie_cache_seconds'] = 60 * 60 * 24 * 14; //Cache old movies two weeks
	$config['critter_rating_cache_seconds'] = 60 * 30; 			    //Will recalc every 30 mins
	
?>	