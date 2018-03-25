<?php

$inputStr = 'To be or not to be? That is the question. The answer? Chuck Norris.';
$searchUrl = 'https://www.googleapis.com/customsearch/v1?key=AIzaSyA9CwlgXrbQm8LrNg4IDkgLu16ZdqujPvU&cx=015759813487222597246:ewdojwzgznu&alt=json&start=1';
$searchUrl .= "&q=" . rawurlencode($inputStr);

$response = file_get_contents($searchUrl);
$obj = json_decode($response, true);

print_r($obj);
?>
