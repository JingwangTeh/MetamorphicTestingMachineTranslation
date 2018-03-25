<!DOCTYPE HTML>
<html>
<head>

<!-- Page CSS -->
	<link rel="stylesheet" type="text/css" href="css/content.css" />

<!-- TITLE -->
	<title>CSCI318 Group Project</title>
	
<!-- Internal JavaScript -->

	<script>
	</script>
	
	
</head>
<body id="home">

<!-- --------------------Page Wrapper-------------------- -->
<div id="page-wrapper">
	
	
	
	<section id="content">
		
		<div>
			Source Language : 
			<select id="sourceLang" required="required">
				<option value="en">English</option>
			</select>
			Target Language : 
			<select id="targetLang" required="required">
				<option value="sv" selected="selected">Swedish</option>
				<option value="ms">Malay</option>
				<option value="fr">French</option>
				<option value="zh">Chinese</option>
				<option value="ar">Arabic</option>
			</select>
			Number of Rows to Generate : 
			<input type="number" id="inputCount" min="1"/>
			<button onclick="getScore()">Generate</button><br/>
			Optional Sentence :
			<input type="text" id="userSentence" />
			<label for="googleSearchScore">Include Google Custom Search Score:</label>
			<input type="checkbox" name="googleSearchCheckbox" id="googleSearchCheckbox"/>
		</div>
		
		<hr/>
		
		<div>
			<table>
				<tr>
					<th>Total Count</th>
					<th>Average Bing Translate Score</th>
					<th>Average Google Translate Score</th>
					<th>Average Yandex Translate Score</th>
				</tr>
				<tr>
					<td id="total_num_of_score">0</td>
					<td id="bing_score_avg">0</td>
					<td id="google_score_avg">0</td>
					<td id="yandex_score_avg">0</td>
				</tr>
			</table>
		</div>
		
		<hr/>
		
		<div>
			<table id="outputTable">
				<thead><tr>
					<th>No.</th>
					<th>Language</th>
					<th>Source Text</th>
					<th>Bing Translate</th>
					<th>Bing Score</th>
					<th>Google Translate</th>
					<th>Google Score</th>
					<th>Yandex Translate</th>
					<th>Yandex Score</th>
				</tr></thead>
				<tbody>
				</tbody>
			</table>
		</div>
		
	</section>
	
	
	
</div>
<!-- --------------------End Page-------------------- -->



<!-- Internal JavaScript -->
	<script>
		var total_num_of_score = document.getElementById("total_num_of_score");
		var bing_score_avg = document.getElementById("bing_score_avg");
		var google_score_avg = document.getElementById("google_score_avg");
		var yandex_score_avg = document.getElementById("yandex_score_avg");
		
		var total_score = {
			'bing'		: 0,
			'google'	: 0,
			'yandex'	: 0
		};
		
		var number_of_score = 0;
		
		
		
		var sourceLang = document.getElementById("sourceLang");
		var targetLang = document.getElementById("targetLang");
		var inputCount = document.getElementById("inputCount");
		var userSentence = document.getElementById("userSentence");
		var googleSearchCheckbox = document.getElementById("googleSearchCheckbox");
		var outputTable = document.getElementById("outputTable");
		
		
		
		function updateAvgScore(bing_score, google_score, yandex_score) {
			number_of_score++;
			
			total_score['bing']		+= bing_score;
			total_score['google']	+= google_score;
			total_score['yandex']	+= yandex_score;
			
			total_num_of_score.innerHTML = number_of_score;
			bing_score_avg.innerHTML = total_score['bing'] / number_of_score;
			google_score_avg.innerHTML = total_score['google'] / number_of_score;
			yandex_score_avg.innerHTML = total_score['yandex'] / number_of_score;
		}
		
		
		
		function getScore() {
			var sourceLangVal = sourceLang.value;
			var targetLangVal = targetLang.value;
			var count = 0;
			if (inputCount.value) { count = inputCount.value; }
			var userSentenceVal = userSentence.value.trim();
			var googleSearchCheckboxVal = googleSearchCheckbox.checked;
			
			var j = 0; // j solely used for console.log counting
			for (var i = 0; i < count; i++) {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						console.log(this.responseText);
						if (this.responseText) {
							var outputJSON = JSON.parse(this.responseText);
							//console.log(outputJSON);
						
							if (outputTable.getElementsByTagName("tbody")[0]){
								var output_table_contentWrapper = outputTable.getElementsByTagName("tbody")[0];
								var output_table_totalRows = outputTable.rows.length;
								
								// insert new row
								var newTableRow = output_table_contentWrapper.insertRow(-1);
								
								// insert new column
								// column #1 - No.
								var cell_0 = newTableRow.insertCell(0);
								cell_0.appendChild(document.createTextNode(output_table_totalRows));
								// column #2 - Language
								var cell_1 = newTableRow.insertCell(1);
								cell_1.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal));
								// column #3 - Source Text
								var cell_2 = newTableRow.insertCell(2);
								cell_2.appendChild(document.createTextNode(outputJSON['sourceText']));
								
								// column #3 - Translated Text #1
								var cell_3 = newTableRow.insertCell(3);
								cell_3.appendChild(document.createTextNode(outputJSON['bing'][0]));
								cell_3.appendChild(document.createElement("hr"));
								cell_3.appendChild(document.createTextNode(outputJSON['bing'][1]));
								
								// column #4 - Translated Text Score #1
								var cell_4 = newTableRow.insertCell(4);
								cell_4.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (similar_text): ' + outputJSON['bing'][4]));
								cell_4.appendChild(document.createElement("hr"));
								cell_4.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (METEOR): ' + outputJSON['bing'][5]));
								cell_4.appendChild(document.createElement("hr"));
								cell_4.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (GoogleSearch): ' + outputJSON['bing'][6]));
								
								// column #5 - Translated Text #2
								var cell_5 = newTableRow.insertCell(5);
								cell_5.appendChild(document.createTextNode(outputJSON['google'][0]));
								cell_5.appendChild(document.createElement("hr"));
								cell_5.appendChild(document.createTextNode(outputJSON['google'][1]));
								
								// column #6 - Translated Text Score #2
								var cell_6 = newTableRow.insertCell(6);
								cell_6.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (similar_text): ' + outputJSON['google'][4]));
								cell_6.appendChild(document.createElement("hr"));
								cell_6.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (METEOR): ' + outputJSON['google'][5]));
								cell_6.appendChild(document.createElement("hr"));
								cell_6.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (GoogleSearch): ' + outputJSON['google'][6]));
								
								// column #7 - Translated Text #3
								var cell_7 = newTableRow.insertCell(7);
								cell_7.appendChild(document.createTextNode(outputJSON['yandex'][0]));
								cell_7.appendChild(document.createElement("hr"));
								cell_7.appendChild(document.createTextNode(outputJSON['yandex'][1]));
								
								// column #8 - Translated Text Score #3
								var cell_8 = newTableRow.insertCell(8);
								cell_8.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (similar_text): ' + outputJSON['yandex'][4]));
								cell_8.appendChild(document.createElement("hr"));
								cell_8.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (METEOR): ' + outputJSON['yandex'][5]));
								cell_8.appendChild(document.createElement("hr"));
								cell_8.appendChild(document.createTextNode(sourceLangVal+'-'+targetLangVal+'-'+sourceLangVal+' (GoogleSearch): ' + outputJSON['yandex'][6]));
								
								updateAvgScore(outputJSON['bing'][4], outputJSON['google'][4], outputJSON['yandex'][4]);
							
								//...
								//console.log('Generated...'+j++);
							}
						} else {
							console.log("Invalid Input");
						}
					}
				};
				
				
				
				// url to respond to AJAX call for progressive facet search
				var url = "translate.php";
			
				// open POST request
				xmlhttp.open("POST", url, true);
				// set header for POST request
				xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			
				// convert array to JSON string
				//var jsonString = JSON.stringify(test);
				var var_send = "";//"chart_type="+encodeURIComponent(jsonString);
				var_send = "sourceLang="+sourceLangVal;
				var_send += "&targetLang="+targetLangVal;
				var_send += "&userSentence="+userSentenceVal;
				var_send += "&googleSearchCheck="+googleSearchCheckboxVal;
				
				// send AJAX POST request
				xmlhttp.send(var_send);
				
				
				//...
				console.log('Generating...'+i);
			}
		}
	</script>
	
</body>
</html>