<?php
require 'config.php';



function setApparentDirectory($currentDirectory) {
	$directory = $currentDirectory;

	$directory = preg_replace("/\/+/i", "/", $directory);
	$directory = preg_replace("/^\.(.*)/i", "$1", $directory);
	$directory = preg_replace("/^\/\.(.*)/i", "$1", $directory);
	$directory = preg_replace("/(.*)\/$/i", "$1", $directory);
	return $directory;
}


function checkGET(){
	global $currentDirectory, $file, $search;
	if (!empty($_GET['file'])) {
		$file = $_GET['file'];
	}
	if (!empty($_GET['directory'])) {
		$currentDirectory = "./" . $_GET['directory'];
	} else {
		$currentDirectory = "./";
	}

	if (!empty($_GET['search'])) {
		$search = $_GET['search'];
	}

	checkOptions();
}

function checkOptions(){
	global $currentDirectory, $file, $search;

	if (!empty(getopt('f:'))) { // php index.php -f=filename
		$opts = getopt('f:');
		$file = $opts['f'];
	}

	if (!empty(getopt('s:'))) { // php search.php -s=search
		$opts = getopt('s:');
		$search = $opts['s'];
	}

	if (!empty(getopt('d:'))) { // php index.php -d=currentDirectory
		$opts = getopt('d:');
		$currentDirectory = $opts['d'];
	}

}

function sanitizeURL($sanitize) {
	return urlencode($sanitize);
}

function printSearchHTML() {
	print '<form class="form-inline headerSearch" action="search.php" method="get">
		<button type="submit" class="btn btn-default">Go</button>
		<div class="form-group">
			<input type="search" class="form-control" name="search" placeholder="search" value="" onkeyup="showResult(this.value)" onsearch="showResult(this.value)" autocomplete="off" >
		</div>
		<div id="markdownRepoLiveSearch"></div>
	</form>
';
}


function printHeader($baseTitle, $extraTitle) { //also returns generated page title for use elsewhere
	$pageTitle = DATA_STORE_NAME;
	if ($baseTitle != "") {
		$pageTitle = $baseTitle;
	}
	if ($extraTitle != "./" && $extraTitle != "") {
		$pageTitle .= ": $extraTitle";
	}

	print "<html>\n<head>\n<title>$pageTitle</title>\n";
	print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" media="screen" type="text/css" href="' . HTML_CSS_URL . '">
		<script>
		function showResult(str) {
		  if (str.length==0) {
		    document.getElementById("markdownRepoLiveSearch").innerHTML="";
		    document.getElementById("markdownRepoLiveSearch").style.border="0px";
		    return;
		  }
		  if (window.XMLHttpRequest) {
		    // code for IE7+, Firefox, Chrome, Opera, Safari
		    xmlhttp=new XMLHttpRequest();
		  } else {  // code for IE6, IE5
		    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		  }
		  xmlhttp.onreadystatechange=function() {
		    if (this.readyState==4 && this.status==200) {
		      document.getElementById("markdownRepoLiveSearch").innerHTML=this.responseText;
		      document.getElementById("markdownRepoLiveSearch").style.border="1px solid #A5ACB2";
		    }
		  }
		  xmlhttp.open("GET","livesearch.php?search="+str,true);
		  xmlhttp.send();
		}
		</script>
	</head>
	<body>';

	return $pageTitle;
}

//timestamp

function getFileModDate($filename) {
	return "this document last modified: " . date ("F d Y H:i", filemtime($filename));
}

//filename header

function addFileName($md, $file) {
	$file = preg_replace("/(.*)\.md/i", "$1", $file);
	$newMD = "# $file\n\n$md";
	return $newMD;
}

?>
