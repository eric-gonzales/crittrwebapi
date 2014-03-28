<?php
/**
 * Notification Controller
 * @author Eric Gonzales <eric@crittermovies.com>
 * @copyright 2014 Critter
 */

class Notification extends CI_Controller{
	public function __construct(){
		parent::__construct();
	}
	
	//Send Notification
	public function send(){}
	
	//Mark Notification Viewed
	public function viewed($notificationID){}
	
	//Unread Notifications for user
	public function unread($hashedUserID){}
}