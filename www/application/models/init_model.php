<?php
/**
 * Init Model
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */
 
class Init_model extends CI_Model {
	
	/**
     * Default constructor
     * @param void
     * @return void
     * @access public 
     */
	public function __construct(){
		parent::__construct();
		
		error_log('called');
	}
	
	public function getData(){
		$query = $this->db->get('CRUser');
		if($query->num_rows() > 0){
			return $query->result();
		}
		else{}
	}
	
	
}
