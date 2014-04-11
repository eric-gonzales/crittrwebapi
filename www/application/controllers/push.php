<?php
class Push extends CI_Controller 
{
    function __construct() 
    {
        parent::__construct();
		$this->load->model('notification_model');
    }
    
	public function feedback()
	{
		foreach($this->notification_model->feedback() as $feedbackToken)
		{
			$token = $feedback["deviceToken"];
			$this->db->where("push_token", $token);
			$this->db->set("push_token", NULL);
			$this->db->update("CRDevice");
		}
	}
	
	public function send()
	{
		$this->db->select('CRPushNotification.*, CRDevice.push_token');
		$this->db->from('CRPushNotification');
		$this->db->join('CRDevice', 'CRDevice.id = CRPushNotification.device_id');
		$this->db->where('sent', FALSE);
		$this->db->where('CRDevice.push_token is not null');
		$query = $this->db->get();
		
		foreach($query->result() as $row)
		{
			$this->notification_model->send_ios($row->push_token, $row->message, intval($row->badge));
			$this->db->where('id', $row->id);
			$this->db->set('sent', TRUE);
			$this->db->update('CRPushNotification');
		}
	}
	
	public function test($token, $message)
	{
		$this->notification_model->send_ios($token, $message);
	}
}
?>