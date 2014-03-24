<?php

require_once('aws/aws-autoloader.php');
use Aws\S3\S3Client;
        
class Awslib 
{
	protected $_CI;

	public function __construct($config = array())
	{
		$this->_CI =& get_instance();
	}
	
    function S3()
    {
		return S3Client::factory(array(
		'key'    => $this->_CI->config->item('aws_key'),
		'secret' => $this->_CI->config->item('aws_secret')
		));
    }
}
?>