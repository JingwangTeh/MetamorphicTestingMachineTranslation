<?php

require("./api/sentenceGenerator.php");

require("./api/bing.php");
require("./api/google.php");
require("./api/yandex.php");

require("./api/search.php");
include("./phpCommons/functions.php");

class score {
	public $sourceText;
	public $bing = [
		"", // translatedString #1
		"", // translatedString #2
		-1,	// score #1
		-1,	// score #2
		-1,	// composite (RTT) score with similar_text
		-1, // composite (RTT) score with meteor score
		-1	// composite (RTT) score with google search score
	];
	public $google = [
		"", // translatedString #1
		"", // translatedString #2
		-1,	// score #1
		-1,	// score #2
		-1,	// composite (RTT) score with similar_text
		-1, // composite (RTT) score with meteor score
		-1	// composite (RTT) score with google search score
	];
	public $yandex = [
		"", // translatedString #1
		"", // translatedString #2
		-1,	// score #1
		-1,	// score #2
		-1,	// composite (RTT) score with similar_text
		-1, // composite (RTT) score with meteor score
		-1	// composite (RTT) score with google search score
	];
}
$scores = new score;

$sourceLang = (inputNotEmpty($_POST['sourceLang']))? sanitize_input($_POST['sourceLang']) : null;
$targetLang = (inputNotEmpty($_POST['targetLang']))? sanitize_input($_POST['targetLang']) : null;
$userSentence = (inputNotEmpty($_POST['userSentence']))? sanitize_input($_POST['userSentence']) : null;
$googleSearchCheckbox = (inputNotEmpty($_POST['googleSearchCheck']))? sanitize_input($_POST['googleSearchCheck']) : null;

if ($sourceLang != '' && $targetLang != '') {
	// random sentence
	if ($userSentence == '') $scores->sourceText = generate_sentence();
	else $scores->sourceText = $userSentence;
	
	// translate
	$scores->bing[0] = bing_translator($scores->sourceText, $sourceLang, $targetLang);
	$scores->bing[1] = bing_translator($scores->bing[0], $targetLang, $sourceLang);
	$scores->google[0] = google_translator($scores->sourceText, $sourceLang, $targetLang);
	$scores->google[1] = google_translator($scores->google[0], $targetLang, $sourceLang);
	$scores->yandex[0] = yandex_translator($scores->sourceText, $sourceLang, $targetLang);
	$scores->yandex[1] = yandex_translator($scores->yandex[0], $targetLang, $sourceLang);

	// compare similarity
	if ($scores->bing[1] != '' && $scores->google[1] != '' && $scores->yandex[1] != '') {
		//$scores->bing[2] = 0;
		//$scores->bing[3] = 0;
		$scores->bing[4] = sentence_similarity_percent($scores->sourceText, $scores->bing[1]);
		$scores->bing[5] = meteor_scoring($scores->sourceText, $scores->bing[1]);

		//$scores->google[2] = 0;
		//$scores->google[3] = 0;
		$scores->google[4] = sentence_similarity_percent($scores->sourceText, $scores->google[1]);
		$scores->google[5] = meteor_scoring($scores->sourceText, $scores->google[1]);
	
		//$scores->yandex[2] = 0;
		//$scores->yandex[3] = 0;
		$scores->yandex[4] = sentence_similarity_percent($scores->sourceText, $scores->yandex[1]);
		$scores->yandex[5] = meteor_scoring($scores->sourceText, $scores->yandex[1]);
	
		// google search comparison
		$canOutput = false;
		if ($googleSearchCheckbox != "true") {
			// set filename, header, and list
			$fileName = "translation-".$sourceLang."-".$targetLang.".csv";
			$header = array(
				array(
					"Language"
				,	"Source Text"
				,	"Bing Translate #1"
				,	"Bing Translate #2"
				,	"Bing Score (RTT with similar_text)"
				,	"Bing Score (RTT with meteor)"
				,	"Google Translate #1"
				,	"Google Translate #2"
				,	"Google Score (RTT with similar_text)"
				,	"Google Score (RTT with meteor)"
				,	"Yandex Translate #1"
				,	"Yandex Translate #2"
				,	"Yandex Score (RTT with similar_text)"
				,	"Yandex Score (RTT with meteor)"
				)
			);
			$list = array (
				array(
					$sourceLang.'-'.$targetLang
				,	$scores->sourceText
				,	$scores->bing[0]
				,	$scores->bing[1]
				,	$scores->bing[4]
				,	$scores->bing[5]
				,	$scores->google[0]
				,	$scores->google[1]
				,	$scores->google[4]
				,	$scores->google[5]
				,	$scores->yandex[0]
				,	$scores->yandex[1]
				,	$scores->yandex[4]
				,	$scores->yandex[5]
				)
			);
			
			$canOutput = true;
		} else if ($googleSearchCheckbox == "true") {
			$googleSearchComparisonResult = google_search_similarity_score($scores->sourceText, [$scores->bing[1], $scores->google[1], $scores->yandex[1]]);

			if (is_array($googleSearchComparisonResult) && 
				(count($googleSearchComparisonResult['scores_array']) == 3) &&
				(count($googleSearchComparisonResult['translatedTotal_array']) == 3) &&
				(count($googleSearchComparisonResult['translatedTotalIntersect_array']) == 3)
			) {
				$scores->bing[6] = $googleSearchComparisonResult['scores_array'][0];
				$scores->google[6] = $googleSearchComparisonResult['scores_array'][1];
				$scores->yandex[6] = $googleSearchComparisonResult['scores_array'][2];

				$sourceTotal = ''; // actual total result returned from google custom search
				$sourceLinksTotal = ''; // max of multiples of 10 due to limited queries
				if (isset($googleSearchComparisonResult['sourceTotal'])) { $sourceTotal = $googleSearchComparisonResult['sourceTotal']; }
				if (isset($googleSearchComparisonResult['sourceLinksTotal'])) { $sourceLinksTotal = $googleSearchComparisonResult['sourceLinksTotal']; }
		
				// set filename, header, and list
				$fileName = "translation-".$sourceLang."-".$targetLang."--withGoogleSearch.csv";
				$header = array(
					array(
						"Language"
					,	"Source Text"
				,	"Source Text Total Google Results"
				,	"Source Text Total Google Results (limited by total queries sent)"
					,	"Bing Translate #1"
					,	"Bing Translate #2"
					,	"Bing Score (RTT with similar_text)"
					,	"Bing Score (RTT with meteor)"
				,	"Bing Score (RTT with GoogleSearch)"
				,	"Bing Total Google Results"
				,	"Bing Total Intersected Links"
					,	"Google Translate #1"
					,	"Google Translate #2"
					,	"Google Score (RTT with similar_text)"
					,	"Google Score (RTT with meteor)"
				,	"Google Score (RTT with GoogleSearch)"
				,	"Google Total Google Results"
				,	"Google Total Intersected Links"
					,	"Yandex Translate #1"
					,	"Yandex Translate #2"
					,	"Yandex Score (RTT with similar_text)"
					,	"Yandex Score (RTT with meteor)"
				,	"Yandex Score (RTT with GoogleSearch)"
				,	"Yandex Total Google Results"
				,	"Yandex Total Intersected Links"
					)
				);
				$list = array (
					array(
						$sourceLang.'-'.$targetLang
					,	$scores->sourceText
				,	$sourceTotal
				,	$sourceLinksTotal
					,	$scores->bing[0]
					,	$scores->bing[1]
					,	$scores->bing[4]
					,	$scores->bing[5]
				,	$scores->bing[6]
				,	$googleSearchComparisonResult['translatedTotal_array'][0]
				,	$googleSearchComparisonResult['translatedTotalIntersect_array'][0]
					,	$scores->google[0]
					,	$scores->google[1]
					,	$scores->google[4]
					,	$scores->google[5]
				,	$scores->google[6]
				,	$googleSearchComparisonResult['translatedTotal_array'][0] // [1]
				,	$googleSearchComparisonResult['translatedTotalIntersect_array'][0] // [1]
					,	$scores->yandex[0]
					,	$scores->yandex[1]
					,	$scores->yandex[4]
					,	$scores->yandex[5]
				,	$scores->yandex[6]
				,	$googleSearchComparisonResult['translatedTotal_array'][1] // [2]
				,	$googleSearchComparisonResult['translatedTotalIntersect_array'][1] // [2]
					)
				);
				
				$canOutput = true;
			}
		}
		
		try {
			if ($canOutput) {
				// append to file
				saveOutputToCSV($fileName, $header, $list);
						
				// send scores after saving output into csv file
				echo json_encode($scores);			
			} else { echo "File Output prevented"; }
		} catch (Exception $err) { echo "File Output Error"; }
	} else { echo "Translation Empty"; }
} else { echo "Invalid Input"; }

?>