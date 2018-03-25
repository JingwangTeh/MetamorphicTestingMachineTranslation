<?php

require_once("./api/apiKeys.php");

class GoogleSearch
{
	private $_apiKey = GOOGLE_APIKEY;
	private $_cxKey = GOOGLE_CXKEY;
	
	function search ($inputStr, $start = 1) {
		
		// Set Parameters for translation
		$params = "key=" . $this->_apiKey;
		$params .= "&cx=" . $this->_cxKey;
		$params .= "&q=" . rawurlencode($inputStr);
		$params .= "&start=" . $start;
		$params .= "&alt=json";
		//$params .= "&fields=queries(request(totalResults))"
		$searchUrl = "https://www.googleapis.com/customsearch/v1?$params";
		// example : https://www.googleapis.com/customsearch/v1?key=AIzaSyA9CwlgXrbQm8LrNg4IDkgLu16ZdqujPvU&cx=015759813487222597246:ewdojwzgznu&alt=json&start=1&q=test
		
		// Search
		/* Expected Response :
			// (See Below)
		*/
		try {
			$response = @file_get_contents($searchUrl);
			json_decode($response, true);
			if (json_last_error() == JSON_ERROR_NONE) {

				// Extract translated text from JSON into associative array
				return json_decode($response,true);
				
			} else {
				//return "Error : UNKNOWN"; 
			}
		} catch (Exception $err) {
			//return "Error : UNKNOWN"; 
		}
	
	}
}

function google_search ($inputStr, $start = 1) {
	$google_search = new GoogleSearch();
	// returns associative_array of result from json_decode
	// returns nothing if error
	return $google_search->search($inputStr, $start);
}

function getGoogleSearchLinks ($inputStr) {
	$totalSearchResultsRequired = 50;
	$numOfQueriesRequired = floor($totalSearchResultsRequired / 10); // Max of 10 results returned per query (cannot go more than 10 per query)
	
	$arrayOfLinks = [];
	$totalResults = -1;
	for ($i = 0; $i < $numOfQueriesRequired; $i++) {
		// get 10 google results
		$start = 1 + $i * 10;
		$searchResults = google_search($inputStr, $start);

		if (isset($searchResults) && (isset($searchResults['error']))) {
			return [[], -1];
		} else if (isset($searchResults) && ($searchResults['searchInformation']['totalResults'] == 0)) {
			return [[], 0];
		} else if (isset($searchResults) && ($searchResults['searchInformation']['totalResults'] > 0)) {
			if ($totalResults == -1) {
				$totalResults = $searchResults['searchInformation']['totalResults'];
			} /*else if ($totalResults != $searchResults['searchInformation']['totalResults']) {
				$totalResults = -2;
			}*/
			
			// add all 10 result links into array
			foreach ($searchResults['items'] as $item) {
				$arrayOfLinks[] = $item['link'];
			}
			//print_r($search_result_sourceText['queries']);
		} else { break; }
	}
	
	return [$arrayOfLinks, $totalResults];
}

function google_search_similarity_score ($sourceText, $translatedTexts) {
	
	$links = [
		'source' => '',			// 'source' is an array of links from $sourceText
		'translated' => []		// 'translated' is an array of arrays, where array inside contains array of links for each $translatedText
								// (e.g. array of 3 arrays if using bing, google, and yandex, with each of the 3 arrays contains an array of links for the $translatedText)
	];
	// return results
	$sourceTotalResults = '';						// should be same as $numOfSourceLinks
	$numOfSourceLinks = '';							// should be same as $sourceTotalResults
	$arrayOfTranslatedTotalResults = [];			// translatedTexts equivalent of $sourceTotalResults
	$arrayOfTotalTranslatedIntersectedLinks = []; 	// total for translatedTexts result links intersect with source links
	$arrayOfScores = [];							// score for above intersection
	
	
	// ensure $translatedText is an array
	if (!is_array($translatedTexts)) {
		return false;
	}
	
	// Get Google Search Result Links for $sourceText and each of the translated text in $translatedTexts array
	// , and also the total results
	$sourceGoogleLinks = getGoogleSearchLinks($sourceText);
	$links['source'] = $sourceGoogleLinks[0];
	$sourceTotalResults = $sourceGoogleLinks[1];
	
	foreach ($translatedTexts as $translatedText) {
		$translatedGoogleLinks = getGoogleSearchLinks($translatedText);
		$links['translated'][] = $translatedGoogleLinks[0];
		$arrayOfTranslatedTotalResults[] = $translatedGoogleLinks[1];
	}
	$arraySizeTranslated = count($links['translated']); // get array size for validation and intersection
	
	
	// ensure $links['score'] is an array, and that there is an equal number of arrays in $links['translated'] compared to $translatedTexts array
	if ( !is_array($links['source']) || (count($translatedTexts) != $arraySizeTranslated) ) {
		return false;
	}
	
	
	// get score of intersection between translatedLinks and sourceLinks
	$numOfSourceLinks = count($links['source']);
	
	for ($i = 0; $i < $arraySizeTranslated; $i++) {
		$arrayOfIntersectedLinks = array_intersect($links['translated'][$i], $links['source']);
		$numOfIntersectedLinks = count($arrayOfIntersectedLinks);
		
		$arrayOfTotalTranslatedIntersectedLinks[] = $numOfIntersectedLinks;
		if ($numOfSourceLinks != 0) {
			$arrayOfScores[] = $numOfIntersectedLinks / $numOfSourceLinks;
		} else {
			$arrayOfScores[] = 0;
		}
	}
	
	// return result
	return [
		'sourceTotal' => $sourceTotalResults,
		'sourceLinksTotal' => $numOfSourceLinks,
		'translatedTotal_array' => $arrayOfTranslatedTotalResults,
		'translatedTotalIntersect_array' => $arrayOfTotalTranslatedIntersectedLinks,
		'scores_array' => $arrayOfScores
	];
}

/* Expected Response from GoogleSearch API:
{
  "kind": "customsearch#search",
  "url": {
    "type": "application/json",
    "template": "https://www.googleapis.com/customsearch/v1?q={searchTerms}&num={count?}&start={startIndex?}&safe={safe?}&cx={cx?}&sort={sort?}&filter={filter?}&gl={gl?}&cr={cr?}&googlehost={googleHost?}&c2coff={disableCnTwTranslation?}&hq={hq?}&hl={hl?}&siteSearch={siteSearch?}&siteSearchFilter={siteSearchFilter?}&exactTerms={exactTerms?}&excludeTerms={excludeTerms?}&linkSite={linkSite?}&orTerms={orTerms?}&relatedSite={relatedSite?}&dateRestrict={dateRestrict?}&lowRange={lowRange?}&highRange={highRange?}&searchType={searchType}&fileType={fileType?}&rights={rights?}&imgSize={imgSize?}&imgType={imgType?}&imgColorType={imgColorType?}&imgDominantColor={imgDominantColor?}&alt=json"
  },
  "queries": {
    (key): [
      {
        "title": string,
        "totalResults": long,
        "searchTerms": string,
        "count": integer,
        "startIndex": integer,
        "startPage": integer,
        "language": string,
        "inputEncoding": string,
        "outputEncoding": string,
        "safe": string,
        "cx": string,
        "sort": string,
        "filter": string,
        "gl": string,
        "cr": string,
        "googleHost": string,
        "disableCnTwTranslation": string,
        "hq": string,
        "hl": string,
        "siteSearch": string,
        "siteSearchFilter": string,
        "exactTerms": string,
        "excludeTerms": string,
        "linkSite": string,
        "orTerms": string,
        "relatedSite": string,
        "dateRestrict": string,
        "lowRange": string,
        "highRange": string,
        "fileType": string,
        "rights": string,
        "searchType": string,
        "imgSize": string,
        "imgType": string,
        "imgColorType": string,
        "imgDominantColor": string
      }
    ]
  },
  "promotions": [
    {
      "title": string,
      "htmlTitle": string,
      "link": string,
      "displayLink": string,
      "bodyLines": [
        {
          "title": string,
          "htmlTitle": string,
          "url": string,
          "link": string
        }
      ],
      "image": {
        "source": string,
        "width": integer,
        "height": integer
      }
    }
  ],
  "context": {
    "title": string,
    "facets": [
      [
        {
          "label": string,
          "anchor": string,
          "label_with_op": string
        }
      ]
    ]
  },
  "searchInformation": {
    "searchTime": double,
    "formattedSearchTime": string,
    "totalResults": long,
    "formattedTotalResults": string
  },
  "spelling": {
    "correctedQuery": string,
    "htmlCorrectedQuery": string
  },
  "items": [
    {
      "kind": "customsearch#result",
      "title": string,
      "htmlTitle": string,
      "link": string,
      "displayLink": string,
      "snippet": string,
      "htmlSnippet": string,
      "cacheId": string,
      "mime": string,
      "fileFormat": string,
      "formattedUrl": string,
      "htmlFormattedUrl": string,
      "pagemap": {
        (key): [
          {
            (key): (value)
          }
        ]
      },
      "labels": [
        {
          "name": string,
          "displayName": string,
          "label_with_op": string
        }
      ],
      "image": {
        "contextLink": string,
        "height": integer,
        "width": integer,
        "byteSize": integer,
        "thumbnailLink": string,
        "thumbnailHeight": integer,
        "thumbnailWidth": integer
      }
    }
  ]
}*/
?>


