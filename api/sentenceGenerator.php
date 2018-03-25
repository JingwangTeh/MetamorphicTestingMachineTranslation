<?php

class SentenceGenerator
{
	private $filename_c = './resource/sourcetext/text0000c.txt'; // original sentence without annotation tags
	private $filename_s = './resource/sourcetext/text0000s.txt'; // simplified sentences
	private $files = [	['./resource/sourcetext/text2012.txt', './resource/sourcetext/text0000c.txt', './resource/sourcetext/text0000s.txt'],
						['./resource/sourcetext/text2013.txt', './resource/sourcetext/text0000c.txt', './resource/sourcetext/text0000s.txt'],
						['./resource/sourcetext/text2014.txt', './resource/sourcetext/text0000c.txt', './resource/sourcetext/text0000s.txt'] ];
						
	function generate_from_api () {
		// Generate
		/* Expected Response :
			{ "type": ..., "value": ... }
		*/
		$response = file_get_contents("http://api.icndb.com/jokes/random?escape=javascript");
		
		// Extract translated text from JSON into associative array
		$obj =json_decode($response,true);
		if($obj != null)
		{
			if($obj['type'] != "success") {
				return "Error occured";
			} else {
				
				return $obj['value']['joke'];
				
			}
		} else { return "Error : UNKNOWN"; }
	}
	
	function generate_from_file () {
		// open file
		$myfile = fopen($this->filename_s, 'r+') or die('unable to open file!');
		$line = fgets($myfile);
		fclose($myfile);
	
		// get random line
		$contents = file($this->filename_s, FILE_IGNORE_NEW_LINES);
		return $contents[rand(0, count($contents) - 1)];
		
		// delete first line
//		$contents = file($this->filename_s, FILE_IGNORE_NEW_LINES);
//		$first_line = array_shift($contents);
//		file_put_contents($this->filename_s, implode("\r\n", $contents));

		return $line;
	}
	
	function format_text_in_file () {
		for ($i = 0; $i < count($this->files); $i++) {
			$myfile = fopen($this->files[$i][0], 'r') or die('unable to open file!');
			$mynewfile_c = fopen($this->files[$i][1], 'a'); // simplified text
			$mynewfile_s = fopen($this->files[$i][2], 'a'); // no tags

			while(!feof($myfile)) {
				// get line
				$line = fgets($myfile);
				
				// filter text (remove tags)
				$line_c = strip_tags($line);
				// other formatting
				$line_c = html_entity_decode($line_c, ENT_QUOTES | ENT_HTML5);
				$line_c = preg_replace('!\s+!', ' ', $line_c);
				//$line_c = preg_replace('/\s([^a-zA-Z0-9])/', '$1', $line_c);
				$line_c = trim($line_c);
				$line_c .= PHP_EOL;

				// filter text (simplify)
				$line_s = preg_replace('#<gb>.*?<ge>#', '', $line);
				// other formatting
				$line_s = html_entity_decode($line_s, ENT_QUOTES | ENT_HTML5);
				$line_s = preg_replace('!\s+!', ' ', $line_s);
				//$line_s = preg_replace('/\s([^a-zA-Z0-9])/', '$1', $line_s);
				$line_s = trim($line_s);
				$line_s .= PHP_EOL ;

				// write to text
				fwrite($mynewfile_c, $line_c);
				fwrite($mynewfile_s, $line_s);
			}

			fclose($myfile);
			fclose($mynewfile_c);
			fclose($mynewfile_s);
		}
	}
}

function generate_sentence () {
	$sentence_generator = new SentenceGenerator();
//	return $sentence_generator->generate_from_api();
	return $sentence_generator->generate_from_file();
}

?>
