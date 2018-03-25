<?php

/*
 * Function : sanitize_input
 * Usage    : clear whitespaces, slashes, and any special characters
 * Input	: string to be sanitized
 * Return   : sanitized value
 */
function sanitize_input($user_input)
{
	$user_input = trim($user_input);
	$user_input = htmlspecialchars($user_input);
	$user_input = stripslashes($user_input);
	
	return $user_input;
}

/*
 * Function : inputNotEmpty
 * Usage    : check if input is empty 
 *			  (to handle input with value of 0, as 0 is considered as empty)
 * Input	: input string to check
 * Return   : boolean true/false, true if not empty, false if empty and not 0
 */
function inputNotEmpty($input)
{
	return ($input === "0" || $input);
}



function sentence_similarity_percent($text_src, $text_mt) {
	if ($text_mt == '') return -1;
	
	similar_text($text_src, $text_mt, $similarity_percent);
	return $similarity_percent/100;
}



function meteor_scoring($sourceText, $translatedText) {
	try {
		if ($translatedText == '') return -1;
		
		// remove symbols so there are words only
		$sourceText_cleaned = preg_replace("/[^A-Za-z0-9 ]/", '', $sourceText);
		$sourceText_cleaned = strtolower($sourceText_cleaned);
		$translatedText_cleaned = preg_replace("/[^A-Za-z0-9 ]/", '', $translatedText);
		$translatedText_cleaned = strtolower($translatedText_cleaned);
		
		// split into array of words
		$sourceSubstringArray = explode(" ", $sourceText_cleaned);
		$translatedSubstringArray = explode(" ", $translatedText_cleaned);
		
		// prepare values for precision and recall
		$intersectedStringArray = array_intersect($translatedSubstringArray, $sourceSubstringArray);
		$totalIntersectedString = count($intersectedStringArray);
		$totalUnigramInTranslation = count($translatedSubstringArray);
		$totalUnigramInSource = count($sourceSubstringArray);

		// find precision and recall
		$precision = $totalIntersectedString / $totalUnigramInTranslation;
		if ($precision > 1) $precision = 1;
		$recall = $totalIntersectedString / $totalUnigramInSource;
		if ($recall > 1) $recall = 1;
		
		// find fmean
		if ($precision == 0 && $recall == 0) {
			$fmean = 0;
		} else {
			$fmean = (10 * $precision * $recall) / (9 * $precision + $recall);
		}
		
		// find chunks
		$chunks = 0;
		$alignment = [];

		// find alignment first
		$translatedSubstringArray_tmp = $translatedSubstringArray;
		$sourceSubstringArray_tmp = $sourceSubstringArray;
		foreach ($sourceSubstringArray as $sourceWord) {
			$index = array_search($sourceWord, $translatedSubstringArray_tmp);
			$alignment[] = $index; // empty if not found
			
			$translatedSubstringArray_tmp[$index] = '';
		}
		
		// then find number of chunks
		for($i = 0; $i < count($alignment); $i++) {
			
			// last index already, so add 1 to chunk to finish off
			if (($i + 1) >= count($alignment)) {
				$chunks++;
			} else {
				$cur = $alignment[$i];
				$next = $alignment[$i+1];
				
				// both cur and next are empty, do nothing (consider consecutive non-matches as 1 chunk)
				if (((string) ($cur) === '') && ((string) ($next) === '')) {
					// do nothing
				}
				// cur is empty but next is not, FINISH the consecutive non-matches as 1 chunk
				else if (((string) ($cur) === '') && ((string) ($next) !== '') ) {
					// do nothing
				}
				// cur is not empty, BUT next is empty = not aligned
				else if (!isset($next) || (string) $next === '') {
					$chunks++;
				}
				// cur is not empty, BUT next is not i+1 = not aligned (not adjacently matched to each other)
				else if (($cur + 1) != $next) {
					$chunks++;
				}
				// cur and next have matches (not empty) AND are adjacently matched to each other
				else if (($cur + 1) == $next) {
					// do nothing
				}
				// same as above condition (as above condition already covers all scenarios)
				else {
					// do nothing
				}
			}
			// next word is aligned, dont add to chunks (do nothing)
		}
		
		// find matches
		$matches = $totalIntersectedString;
		if ($chunks > $matches) $chunks = $matches;
		
		// find fragmentation
		if ($matches == 0) {
			$fragmentation = 1;
		} else {
			$fragmentation = $chunks / $matches;
		}
		
		// find penalty
		$penalty = 0.5 * (pow($fragmentation, 3));
		
		// find score
		$score = $fmean * (1 - $penalty);
		if ($score > 1) $score = 1;
		else if ($score < 0) $score = 0;
		
		return $score;
	} catch (Exception $error) {
		// echo 'Error : ' . $error->getMessage() . '\n';
		return -1;
	}
}



function saveOutputToCSV($fileName, $header, $content) {
	try {
		/*
		 * Write header to file if does not exist yet
		 */
		if (!file_exists($fileName)) {
			$handle = fopen($fileName, "a");
			
			// writes file header for correct encoding
			fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
			foreach ($header as $fields) {
				fputcsv($handle, $fields);
			}	
			
			fclose($handle);
		}
		
		/*
		 * Write content to file
		 */
		$handle = fopen($fileName, "a");
		// writes file header for correct encoding
		fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
		foreach ($content as $fields) {
			fputcsv($handle, $fields);
		}
		
		fclose($handle);
	} catch (Exception $err) { echo "file error : ".$err; }
}
?>
