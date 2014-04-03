<?php
/*
| -------------------------------------------------------------------
| ERROR CONFIGURATION
| -------------------------------------------------------------------
| Codes:
| 1 -- (default) General Error
| 100 -- Required Fields Missing
| 200 -- Entity Not Found
| 250 -- Already In Use
| 300 -- Not Authorized
| 350 -- Token Already Generated
| 400 -- Invalid Request
 */
 
 
/**
 * Post Data Errors
 */
//Required Post Fields are Missing
$config['error_required_fields'] = 100;


/**
 * Database Lookup Errors
 */
//Database Lookup Failed
$config['error_entity_not_found'] = 200;

//Email already in use
$config['error_already_in_use'] = 250;


/**
 * Security Errors
 */
//Email or Password Incorrect
$config['error_not_authorized'] = 300;

//Password Token has previously been generated and is not expired
$config['error_token_generated'] = 350;

/**
 * Request Errors
 */
//Invalid Request
$config['error_invalid_request'] = 400;
