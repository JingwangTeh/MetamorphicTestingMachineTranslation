<?php

require_once("./api/apiKeys.php");

class YandexTranslate
{
	private $_apiKey = YANDEX_APIKEY;
	
	function translate ($inputStr, $sourceLanguage, $targetLanguage) {
		
		// Set Parameters for translation
		$params = "key=" . $this->_apiKey;
		$params .= "&text=" . rawurlencode($inputStr);
		$params .= "&lang=" . $sourceLanguage . "-" . $targetLanguage;
		$translateUrl = "https://translate.yandex.net/api/v1.5/tr.json/translate?$params";
		
		// Translate
		// Expected Response
		// - {"code":200,"lang":"en-ru","text":["Привет Мир"]}
		$response = @file_get_contents($translateUrl);
		
		// Extract translated text from JSON into associative array
		$obj =json_decode($response,true);
		if($obj != null)
		{
			//if(isset($obj['error'])) {
			//	return "Error is : ".$obj['error']['message'];
			//} else {
				
				return $obj['text'][0];
				
			//}
		} else { 
			//return "Error : UNKNOWN";
		}
		
	}
}

function yandex_translator ($string, $sourceLanguage, $targetLanguage) {
	$yandex_translation = new YandexTranslate();
	return $yandex_translation->translate($string, $sourceLanguage, $targetLanguage);
}

?>
