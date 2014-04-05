<?php
class Amazon{
	public function get_results($region, $amazon_access_key, $amazon_secret_access_key, $params){
		//Load up the default params
		$method = "GET";
		$domain = "webservices.amazon." . $region;
		$uri = "/onca/xml";
		$version = "2011-08-01";
		
		//Create the parameters
		$params["Service"] = "AWSECommerceService";
		$params["AWSAccessKeyId"] = $amazon_access_key;
		$params["Version"] = $version;
		$params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
		$params["AssociateTag"] = "critter0b-20";
		
		//Sort the parms by byte value
		ksort($params);
		
		//Push params into a new canonical string array
		foreach($params as $key => $value){
			$key = str_replace("%7E", "~", rawurlencode($key));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$canonical_array[] = $key . "=" . $value;
		}
		
		//Implode the array and add the &amp; sign.
		$canonical_uri = implode("&", $canonical_array);
		
		//Create the String to sign
		$string_to_sign = $method . "\n" . $domain . "\n" . $uri . "\n" . $canonical_uri;
		
		//Create signature with HMAC SHA256 base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $amazon_secret_access_key, true));
		
		//Escape the signature
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		
		//Add canonical uri to the unsigned url
		$unsigned_url = "http://" . $domain . $uri . "?" . $canonical_uri . "&Signature=" . $signature;
		
		$response = simplexml_load_file($unsigned_url);
		return $response;
	}
}