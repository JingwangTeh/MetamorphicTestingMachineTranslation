<?php

require_once("./api/apiKeys.php");

class CurlWrapper
{
	function curlRequest ($url, $header = array(), $postData = '') {
		
		// initialize curl request
		$ch = curl_init();
		
		// set curl url
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// set curl header
		if(!empty($header)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		
		// set post data
		if(!empty($postData)){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
		}
		
		// set other curl options
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		// execute and close curl request
		$curlResponse = curl_exec($ch);
		curl_close($ch);
		
		// return response from curl post request
		return $curlResponse;
		
	}
}

class BingGetKeyAndTranslate extends CurlWrapper
{
	private $_clientID = BING_CLIENTID;
	private $_clientSecret = BING_CLIENTSECRET;

	private $_grantType = "client_credentials";
	private $_scopeUrl = "http://api.microsofttranslator.com";
	private $_authUrl = "https://api.cognitive.microsoft.com/sts/v1.0/issueToken";
	
	// Get Subscription Key (Expires every 10 minutes)
	private function _getSubscriptionKey () {
		try{
			
			// set header and post data
			$header = array('Ocp-Apim-Subscription-Key: '.$this->_clientSecret);
			$postData = array(
				'grant_type' => $this->_grantType,
				'scope' => $this->_scopeUrl,
				'client_id' => $this->_clientID,
				'client_secret' => $this->_clientSecret
			);
			
			// send curl post request
			$response = $this->curlRequest($this->_authUrl, $header, $postData);
			if (!empty($response)) return $response; // return generated subscription key
			
		} catch (Exception $e) {
			//echo "Exception-" . $e->getMessage();
		}
	}

	function translate ($inputStr, $sourceLanguage, $targetLanguage) {
		
		// Set Parameters for translation
		$params = "text=" . rawurlencode($inputStr);
		$params .= "&from=" . $sourceLanguage;
		$params .= "&to=" . $targetLanguage;
		$translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";

		// Place subscription key in header
		try {
			$accessToken = $this->_getSubscriptionKey();
			if (strpos($accessToken, 'error')) { return 'error:'.$accessToken; }
		} catch (Exception $e) { return 'error'; }
		
		$authHeader = "Authorization: Bearer " . $accessToken;
		$header = array($authHeader, "Content-Type: text/xml");
		
		// Translate
		// (Expected Response: <string xmlns="http://schemas.microsoft.com/2003/10/Serialization/">Salut tout le monde</string>)
		$curlResponse = $this->curlRequest($translateUrl, $header);

		// Extract translated text from XML
		$xmlObj = simplexml_load_string($curlResponse);

		$translatedStr = '';
		foreach((array)$xmlObj[0] as $val){
			if (!is_object($val)) {
				$translatedStr = $val;
			}
			else {
				//$translatedStr = $val->h1;
			}
		}
		
		// Return translated text
		return $translatedStr;
		
	}
}

function bing_translator($string, $sourceLanguage, $targetLanguage) {
	$bing_translation = new BingGetKeyAndTranslate();
	return $bing_translation->translate($string, $sourceLanguage, $targetLanguage);
}

?>
