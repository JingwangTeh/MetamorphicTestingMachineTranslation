<?php

require_once("./api/apiKeys.php");

class GoogleTranslate
{
	private $_apiKey = GOOGLE_APIKEY;
	
	function translate ($inputStr, $sourceLanguage, $targetLanguage) {
		
		// Set Parameters for translation
		$params = "key=" . $this->_apiKey;
		$params .= "&q=" . rawurlencode($inputStr);
		$params .= "&source=" . $sourceLanguage;
		$params .= "&target=" . $targetLanguage;
		$params .= "&format=text";
		$translateUrl = "https://translation.googleapis.com/language/translate/v2?$params";
		
		// Translate
		/* Expected Response :
			{
			  "data": {
				"translations": [
				  {
					"translatedText": "Bonjour le monde",
					"detectedSourceLanguage": "en"
				  }
				]
			  }
			}
		*/
		$response = @file_get_contents($translateUrl);
		
		// Extract translated text from JSON into associative array
		$obj =json_decode($response,true);
		if($obj != null)
		{
			if(isset($obj['error'])) {
				//return "Error is : ".$obj['error']['message'];
			} else {
				
				return $obj['data']['translations'][0]['translatedText'];
				
			}
		} else {
			//return "Error : UNKNOWN"; 
		}
		
	}
}

function google_translator ($inputStr, $sourceLanguage, $targetLanguage) {
	$google_translation = new GoogleTranslate();
	return $google_translation->translate($inputStr, $sourceLanguage, $targetLanguage);
}

?>
