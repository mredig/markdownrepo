<?php
if (file_exists("config.php")) {
	require 'config.php';
}
require 'config-dist.php';



function setApparentDirectory($currentDirectory) {
	$directory = $currentDirectory;

	$directory = preg_replace("/\/+/i", "/", $directory);
	$directory = preg_replace("/^\.(.*)/i", "$1", $directory);
	$directory = preg_replace("/^\/\.(.*)/i", "$1", $directory);
	$directory = preg_replace("/(.*)\/$/i", "$1", $directory);
	return $directory;
}


function checkGET(){
	global $currentDirectory, $file, $search, $perma;
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

	if (!empty($_GET['perma'])) {
		$perma = $_GET['perma'];
	}

	checkOptions();
}

function checkOptions(){
	global $currentDirectory, $file, $search, $perma;

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

	if (!empty(getopt('p:'))) { // php index.php -p=permalinkhash
		$opts = getopt('p:');
		$perma = $opts['p'];
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


function printHeader($baseTitle, $extraTitle, $extraHeaderTags = "") { //also returns generated page title for use elsewhere
	$pageTitle = DATA_STORE_NAME;
	if ($baseTitle != "") {
		$pageTitle = $baseTitle;
	}
	if ($extraTitle != "./" && $extraTitle != "") {
		$pageTitle .= ": $extraTitle";
	}

	print "<html>\n<head>\n<title>$pageTitle</title>\n";
	print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		' . $extraHeaderTags . '
		<link rel="stylesheet" media="screen" type="text/css" href="' . HTML_CSS_URL . '?v=1">
		<link rel="stylesheet" media="print" type="text/css" href="' . HTML_CSS_URL . '?v=1">
		<script>
            var lastRequest = 0;
            var lastAttempt = 0;

            function showResult(str, updateAttempt = 1) {
                var date = new Date();
                var currentTime = date.getTime();
                var timeElapsed = currentTime - lastRequest;

                var minimumPassingTime = 300;

                if (str.length==0) {
                    document.getElementById("markdownRepoLiveSearch").innerHTML="";
                    document.getElementById("markdownRepoLiveSearch").style.border="0px";
                    return;
                }
                if (timeElapsed < minimumPassingTime) {
                    if (updateAttempt == 1) {
                        lastAttempt = currentTime;
                    }
                    var thisAttempt = lastAttempt;
                    var timeout = minimumPassingTime + 5;
                    setTimeout(delayedSubmission, timeout, thisAttempt, str);
                    return;
                }
                lastAttempt = currentTime;
                lastRequest = currentTime;
            //    alert(lastRequest);

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

            function delayedSubmission(timeSubmitted, submission) {
            //    console.log("time submitted: " + timeSubmitted + " lastAttempt: " + lastAttempt + " submission:" + submission + "\n");
                if (timeSubmitted == lastAttempt) {
            //        console.log("still the same!\n");
                    showResult(submission, false);
                }
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


// permalink stuff

function checkPermalinkExists($md) {
	preg_match("/\A<!-- permalink: ([0-9a-f]+) DO NOT DELETE OR EDIT THIS LINE -->/", $md, $output_array);
	if (count($output_array) > 0) {
		return $output_array[1];
	} else {
		return 0;
	}
}

function stripPermalink($md) {
	$noPerma = preg_replace("/\A<!-- permalink: ([0-9a-f]+) DO NOT DELETE OR EDIT THIS LINE -->\s/m", "", $md);
	return $noPerma;
}

function generatePermalinkHash($md) {
	$rando = rand();
	$saltedMD = $md . $rando; // just to be sure it's a unique hash
	$hash = hash("md5", $saltedMD);
	return $hash;
}

function generatePermalinkComment($hash) {
	$commentString = "<!-- permalink: $hash DO NOT DELETE OR EDIT THIS LINE -->\n";
	return $commentString;
}

function generatePermlink($hash) {
	$hashLink = "<p class='mdrPermalink'><a href='/permalink.php?perma=$hash'>permalink</a></p>\n";
	// $hashLink = "<p class='mdrPermalink'>[permalink](/permalink.php?perma=$hash)</p>\n";
	return $hashLink;
}

function saveFileInCD($file, $md, $withOutMod = 1) {
	// try to make mod without affecting time mod date

	//this code is commented so i dont accidentally use it without reviewing it

	if ($withOutMod) {
		$prevTimestamp = filemtime($file);
	}
	// print "UNIX Timestamp: $prevTimestamp\n";

	$fileHandle = fopen($file, "w") or die("Unable to open file: $file!");
	$md = fwrite($fileHandle,$md);
	fclose($fileHandle);

	if ($withOutMod) {
		touch($file, $prevTimestamp); //set back to original mod time
	}
}

?>
