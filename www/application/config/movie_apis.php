<?php
	//Rotten Tomatoes API Key
	$config['rotten_tomatoes_api_key'] = "yytapgjcae7pu4j42dwmmmg5";
	$config['rotten_tomatoes_cache_seconds'] = 60 * 60 * 24;
	$config['rotten_tomatoes_search_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=%1$s&q=%2$s&page_limit=%3$d&page=%4$s';
	$config['rotten_tomatoes_movie_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/movies/%1$s.json?apikey=%2$s';
	$config['rotten_tomatoes_box_office_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/movies/box_office.json?apikey=%1$s&limit=%2$s&country=%3$s';
	$config['rotten_tomatoes_opening_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/movies/opening.json?apikey=%1$s&limit=%2$s&country=%3$s';
	$config['rotten_tomatoes_upcoming_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/movies/upcoming.json?apikey=%1$s&page_limit=%2$s&page=%3$s&country=%4$s';
	$config['rotten_tomatoes_new_dvds_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/dvds/new_releases.json?apikey=%1$s&page_limit=%2$s&page=%3$s&country=%4$s';
	$config['rotten_tomatoes_current_dvds_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/dvds/current_releases.json?apikey=%1$s&page_limit=%2$s&page=%3$s&country=%4$s';
	$config['rotten_tomatoes_upcoming_dvds_url'] = 'http://api.rottentomatoes.com/api/public/v1.0/lists/dvds/upcoming.json?apikey=%1$s&page_limit=%2$s&page=%3$s&country=%4$s';
	
	//The Movie DB API
	$config['tmdb_api_key'] = "1e0e9eb6323a33a3b940f6720a2915f1";
	$config['tmdb_cache_seconds'] = 60 * 60 * 24;	
	$config['tmdb_id_url'] = 'http://api.themoviedb.org/3/movie/%1$s?api_key=%2$s';
	$config['tmdb_imdb_id_url'] = 'http://api.themoviedb.org/3/find/%1$s?api_key=%2$s&external_source=imdb_id';
	$config['tmdb_title_url'] = 'http://api.themoviedb.org/3/search/movie?api_key=%1$s&query=%2$s';
	$config['tmdb_title_year_url'] = 'http://api.themoviedb.org/3/search/movie?api_key=%1$s&query=%2$s&year=%3$s';
	$config['tmdb_trailer_url'] = 'http://api.themoviedb.org/3/movie/%1$s/trailers?api_key=%2$s';

	//TMS API KEY
	$config['tms_api_key'] = "9a782k4scvuzsgye7xg9mfsh";	
	$config['tms_cache_seconds'] = 60 * 60 * 24;
	$config['tms_title_url'] = 'http://data.tmsapi.com/v1/programs/search?q=%1$s&queryFields=title&entityType=movie&titleLang=en&descriptionLang=en&limit=1&api_key=%2$s';
	$config['tms_trailer_url'] = 'http://data.tmsapi.com/v1/screenplayTrailers?rootids=%1$s&bitrateids=449,472,461,457,460,471,455,452&trailersonly=1&languageid=en&player_url=0&best_eclip=1&api_key=%2$s';
	$config['tms_trailer_image_url'] = 'http://data.tmsapi.com/v1/screenplayTrailers?rootids=%1$s&bitrateids=382&trailersonly=1&languageid=en&player_url=0&best_eclip=1&api_key=%2$s';
	
	//OMDB
	$config['omdb_imdb_id_url'] = 'http://www.omdbapi.com/?i=%1$s';
	$config['omdb_title_url'] = 'http://www.omdbapi.com/?t=%1$s';
	$config['omdb_cache_seconds'] = 60 * 60 * 24;
	
	//iTunes
	$config['itunes_title_url'] = 'https://itunes.apple.com/search?country=us&entity=movie&attribute=movieTerm&term=%1$s';
	$config['itunes_cache_seconds'] = 60 * 60 * 24;
	
	//Netflix
	$config['netflix_base_url'] = 'http://www.netflix.com/WiPlayer?movieid=';
	
	//Critter
	$config['critter_rating_cache_seconds'] = 60 * 60 * 24;
?>