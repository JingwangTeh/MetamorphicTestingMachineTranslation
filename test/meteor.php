<?php

//include_once("http://localhost:8081/EchoServer/java/Java.inc");

//java_autoload("meteor-1.5.jar");

//$echo = new java("EchoServer");
//echo $echo->echo(array("2", 3.14, java_context()->getHttpServletRequest()));
//echo $echo->echo(["2", 3.14, java_context()->getHttpServletRequest()]);

//$test = new java("testOut");
//echo $test->echo([]);

//$test = new java("testOut1");
//echo $test->echo();

//$System = java("java.lang.System");
//$test = new java("meteor-1.5.meteor-1.5");
//echo $System->getProperties();


// reference text
$sourceText00 = "the cat sat on the mat";
$sourceText10 = "warframe a";
$sourceText11 = "warframe";	// METEOR is not good for comparison text with only one word 
							// (because, in this implementation, unigram is one word and corpus is one sentence,
							// better to use a character unigram if comparing characters in a word only)
$sourceTextX = "Chuck Norris doesn't stub his toes. He accidentally destroys chairs, bedframes, and sidewalks.";
$sourceTextY1 = "it 's not just the mega pixels , though .";
$sourceTextY2 = "seat layout is exactly what drives the battle between the latest jets .";

// candidate/hypothesis text

$translatedText01 = "on the mat sat the cat";
$translatedText02 = "the cat sat on the mat";
$translatedText03 = "the cat was sat on the mat";

$translatedText1 = "Warframe a";
$translatedText2 = "warframe a";
$translatedText3 = "warframe";

$translatedTextX1 = "Chuck Norris does not boil his fingers. It accidentally destroys chairs, chassis and sidewalks.";
$translatedTextX2 = "Chuck Norris doesn't stub his toes. He accidentally destroys chairs, foundations, and sidewalks.";

$translatedTextY1 = "It's not just megapixels, though.";
$translatedTextY2 = "the seat layout is exactly what drives the battle between the latest jets .";

echo 'Source Text : ' . $sourceText00 . ' | Translated Text : ' . $translatedText01 . '<br/>';
meteor_scoring($sourceText00, $translatedText01);
echo '<hr/>';

echo 'Source Text : ' . $sourceText00 . ' | Translated Text : ' . $translatedText02 . '<br/>';
meteor_scoring($sourceText00, $translatedText02);
echo '<hr/>';

echo 'Source Text : ' . $sourceText00 . ' | Translated Text : ' . $translatedText03 . '<br/>';
meteor_scoring($sourceText00, $translatedText03);
echo '<hr/>';

echo 'Source Text : ' . $sourceText10 . ' | Translated Text : ' . $translatedText1 . '<br/>';
meteor_scoring($sourceText10, $translatedText1);
echo '<hr/>';

echo 'Source Text : ' . $sourceText10 . ' | Translated Text : ' . $translatedText2 . '<br/>';
meteor_scoring($sourceText10, $translatedText2);
echo '<hr/>';

echo 'Source Text : ' . $sourceText11 . ' | Translated Text : ' . $translatedText3 . '<br/>';
meteor_scoring($sourceText11, $translatedText3);
echo '<hr/>';

echo 'Source Text : ' . $sourceTextY1 . ' | Translated Text : ' . $translatedTextY1 . '<br/>';
meteor_scoring($sourceTextY1, $translatedTextY1);
echo '<hr/>';

echo 'Source Text : ' . $sourceTextY2 . ' | Translated Text : ' . $translatedTextY2 . '<br/>';
meteor_scoring($sourceTextY2, $translatedTextY2);
echo '<hr/>';



function meteor_scoring($sourceText, $translatedText) {
	try {
		// remove symbols so there are words only
		$sourceText_cleaned = preg_replace("/[^A-Za-z0-9 ]/", '', $sourceText);
		$sourceText_cleaned = strtolower($sourceText_cleaned);
		echo $sourceText_cleaned . '<br/>';

		$translatedText_cleaned = preg_replace("/[^A-Za-z0-9 ]/", '', $translatedText);
		$translatedText_cleaned = strtolower($translatedText_cleaned);
		echo $translatedText_cleaned . '<br/>';

		// split into array of words
		$sourceSubstringArray = explode(" ", $sourceText_cleaned);
		//print_r($sourceSubstringArray);
		//echo '<br/>';
		$translatedSubstringArray = explode(" ", $translatedText_cleaned);
		//print_r($translatedSubstringArray);
		//echo '<br/>';
		
		// prepare values for precision and recall
		$intersectedStringArray = array_intersect($translatedSubstringArray, $sourceSubstringArray);
		print_r($intersectedStringArray);
		$totalIntersectedString = count($intersectedStringArray);
		$totalUnigramInTranslation = count($translatedSubstringArray);
		$totalUnigramInSource = count($sourceSubstringArray);

		// find precision and recall
		$precision = $totalIntersectedString / $totalUnigramInTranslation;
		if ($precision > 1) $precision = 1;
		$recall = $totalIntersectedString / $totalUnigramInSource;
		if ($recall > 1) $recall = 1;
		echo 'Precision : '.$precision. ' ~~ ('.$totalIntersectedString.'/'.$totalUnigramInTranslation.')'.'<br/>';
		echo 'Recall : '.$recall. ' ~~ ('.$totalIntersectedString.'/'.$totalUnigramInSource.')'.'<br/>';

		// find fmean
		if ($precision == 0 && $recall == 0) {
			$fmean = 0;
		} else {
			$fmean = (10 * $precision * $recall) / (9 * $precision + $recall);
		}
		echo 'Fmean : '.$fmean. ' ~~ ( (10 * precision:'.$precision.' * recall:'.$recall.') / ( 9 * precision:'.$precision.' + recall:'.$recall.') )'.'<br/>';

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
		echo 'Alignment : ';
		print_r($alignment);
		echo '<br/>';
		
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
		echo 'Chunks is : ' . $chunks . '<br>';;

		// find matches
		$matches = $totalIntersectedString;
		echo 'Matches : ' .$matches.'<br/>';
		if ($chunks > $matches) $chunks = $matches;
			
		// find fragmentation
		if ($matches == 0) {
			$fragmentation = 1;
		} else {
			$fragmentation = $chunks / $matches;
		}
		echo 'Fragmentation : '.$fragmentation. ' ~~ ( chunks:'.$chunks.' / matches:'.$matches.' )'.'<br/>';

		// find penalty
		$penalty = 0.5 * (pow($fragmentation, 3));
		echo 'Penalty : '.$penalty. ' ~~ ( 0.5 * ( fragmentation:'.$fragmentation.' ^ 3 ) )'.'<br/>';

		// find score
		$score = $fmean * (1 - $penalty);
		echo 'Score : '.$score. ' ~~ ( fmean:'.$fmean.' * ( 1 - penalty:'.$penalty.' ) )'.'<br/>';
		if ($score > 1) $score = 1;
		else if ($score < 0) $score = 0;
	} catch (Exception $error) {
		// echo 'Error : ' . $error->getMessage() . '\n';
		return -1;
	}
}

?>
