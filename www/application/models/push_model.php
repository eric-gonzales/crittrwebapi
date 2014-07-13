<?php
class Push_model extends CR_Model 
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
	
	public function queuePushForUser($user_id, $message, $notification_id, $incrementBadge = TRUE)
	{
		//set a push notification to each device linked to the user
		$this->db->select('CRDevice.*');						
		$this->db->from('CRDevice');
		$this->db->join('CRDeviceUser', 'CRDevice.id = CRDeviceUser.device_id');
		$this->db->where('CRDeviceUser.user_id', $user_id);
		$this->db->where('CRDevice.push_token IS NOT NULL');						
		$query = $this->db->get();
		foreach ($query->result() as $device)
		{
			//increment badge value
			$badge = $device->badge_count;
			if ($incrementBadge)
			{
				$badge = $device->badge_count + 1;
				$this->db->where('id', $device->id);
				$this->db->set('badge_count', $badge);
				$this->db->update('CRDevice');
			}
			
			//now create push notification
			$this->db->set('device_id', $device->id);
			$this->db->set('message', $message);
			$this->db->set('notification_id', $notification_id);							
			$this->db->set('badge', $badge);
			$this->db->set('created', 'NOW()', FALSE);
			$this->db->set('modified', 'NOW()', FALSE);														
			$this->db->insert('CRPushNotification');
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