<?php

if(!defined('BASEPATH')) exit('No direct script access allowed');

$apnsPath = $_SERVER['DOCUMENT_ROOT'].'/application/third_party/ApnsPHP/';
if (php_sapi_name() == 'cli')
{
	$apnsPath = "application/third_party/ApnsPHP/";
}
require_once $apnsPath.'Log/Interface.php';

class Notification_model extends CR_Model implements ApnsPHP_Log_Interface
{

    protected $apnsDir = '';

    // -----------------------------------------------

    /**
     * Setup some basic stuffs
     * @param void
     * @return void
     * @access public 
     */
    public function __construct() {

        parent::__construct();

        /* get all the APNS files */
        $this->apnsDir = $_SERVER['DOCUMENT_ROOT'].'/application/third_party/ApnsPHP/';
        if ($this->input->is_cli_request()) $this->apnsDir = 'application/third_party/ApnsPHP/';    
        $this->_apns_req();

        return;

    } /* /__construct() */

    // -----------------------------------------------

    /**
     * Will send the actual iOS notification to the user
     * @param $token string iOS device token
     * @param $msg string 
     * @param $attrs array Key/value pairs to be sent as meta with APN
     * @return void
     * @access public
     */
    public function send_ios($token=null, $msg=null, $badge=0, $sound = FALSE, $attrs=array()) {

        if(!$token || !$msg) return;

        // Instantiate a new ApnsPHP_Push object
        $pushEnvironment = ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;
        $pushCert = $this->apnsDir. "SSL/" . $this->config->item("push_production_cert");
        
        //Sandbox mode?
        if ($this->config->item("push_sandbox"))
        {
	        $pushEnvironment = ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;
	        $pushCert =  $this->apnsDir. "SSL/" . $this->config->item("push_sandbox_cert");
        }
        $push = new ApnsPHP_Push($pushEnvironment, $pushCert);

        // Suppress logging when not run from command line (prevents log spew in our returned JSON)
		$push->setLogger($this);

        // Set the Root Certificate Autority to verify the Apple remote peer
        $push->setRootCertificationAuthority($this->apnsDir.'SSL/entrust_root_certification_authority.pem');

        // Connect to the Apple Push Notification Service
        $push->connect();

        // Instantiate a new Message with a single recipient
        $message = new ApnsPHP_Message($token);

        // Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
        // over a ApnsPHP_Message object retrieved with the getErrors() message.
        $message->setCustomIdentifier("PHP-Push");

        // Set badge icon to "3"
		$message->setBadge($badge);

        // Set a simple welcome text
        $message->setText($msg);

        // Play the default sound
        if ($sound)
        {
	        $message->setSound();
        }

        // Set custom properties
        if( is_array($attrs) && count($attrs) )
        {
            foreach( $attrs as $attr_key => $attr_val )
            {
                $message->setCustomProperty($attr_key, $attr_val);
            }
        }
        
        // Set the expiry value - in seconds
        $message->setExpiry(120);

        // Add the message to the message queue
        $push->add($message);

        // Send all messages in the message queue
        $push->send();

        // Disconnect from the Apple Push Notification Service
        $push->disconnect();

        // Examine the error message container
        // $aErrorQueue = $push->getErrors();
        // if (!empty($aErrorQueue)) {
        //  var_dump($aErrorQueue);
        // }

        return TRUE;

    } /* /send_ios() */
    
	public function feedback()
	{
        // Instantiate a new ApnsPHP_Push object
        $pushEnvironment = ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;
        $pushCert =  $this->apnsDir."SSL/" . $this->config->item("push_production_cert");
        if ($this->config->item("push_sandbox"))
        {
	        $pushEnvironment = ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;
	        $pushCert =  $this->apnsDir."SSL/" . $this->config->item("push_sandbox_cert");
        }
        $pushFeedback = new ApnsPHP_Feedback($pushEnvironment, $pushCert);
		$pushFeedback->connect();
		$aDeviceTokens = $pushFeedback->receive();
		$pushFeedback->disconnect();		
		return $aDeviceTokens;
	}    
	
	public function log($sMessage)
	{
		if ($this->input->is_cli_request())
		{
			printf("%s ApnsPHP[%d]: %s\n",
				date('r'), getmypid(), trim($sMessage)
			);
		}
		else
		{
			error_log("ApnsPHP: $sMessage");
		}
	}

    // -----------------------------------------------

    private function _apns_req() {

        require_once $this->apnsDir.'Abstract.php';
        require_once $this->apnsDir.'Exception.php';
        require_once $this->apnsDir.'Feedback.php';
        require_once $this->apnsDir.'Message.php';
        require_once $this->apnsDir.'Log/Interface.php';
        require_once $this->apnsDir.'Log/Embedded.php';
        require_once $this->apnsDir.'Message/Custom.php';
        require_once $this->apnsDir.'Message/Exception.php';
        require_once $this->apnsDir.'Push.php';
        require_once $this->apnsDir.'Push/Exception.php';
        require_once $this->apnsDir.'Push/Server.php';
        require_once $this->apnsDir.'Push/Server/Exception.php';

        return;

    } /* /_apns_req() */

} /* /Notification_model{} */

/* End of file notification_model.php */
/* Location: ./application/models/notification_model.php */