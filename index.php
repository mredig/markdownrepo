<?php
require 'parsedown/Parsedown.php';
require 'globals.php';

// error_reporting(E_ALL); //enable these two lines for debugging info printed to browser
// ini_set('display_errors', 1);

chdir(MD_BASE_PATH);
global $currentDirectory, $file, $apparentDirectory;
checkGET();
chdir($currentDirectory);
$apparentDirectory = setApparentDirectory($currentDirectory); // current directory might have extra slashes and start with . - apparent directory is cleaned up and starts with /

$pageTitle = printHeader($file, $apparentDirectory);
printSearchHTML();

// generate page contents
$Parsedown = new Parsedown(); //create Parsdown object
if (!empty($file)) { // check if there is a file specified - if so, display contents. if not, show directory contents
	$fileHandle = fopen($file, "r") or die("Unable to open file: $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	// print_r($md);

	if (ENABLE_PERMALINKS) {
		$permalink = checkForPermaLink($md, $file);
	}

	if (SHOW_FILENAME) {
		$md = addFileName($md, $file);
	}
	$md = processImageLinks($md, $apparentDirectory);
} else {
	$allFilesInCD = glob("*");
	$mdFilesInCD = glob("*.md");

	$folderSection = getFolderList($allFilesInCD, $apparentDirectory);
	$fileSection = getFileList($mdFilesInCD, $apparentDirectory);
	$md = "# " . DATA_STORE_NAME . "\n" . $folderSection . $fileSection;
}
$md = addWrappers($file, $md, $apparentDirectory, $permalink);
$mdOutput = $Parsedown->text($md);
print "$mdOutput";


//functions go here
function processImageLinks($md, $apparentDirectory) {
	$urlApparentDirectory = sanitizeURL($apparentDirectory);
	$newLine = preg_replace("/^(.*)\((.*\.(png|jpg|gif))\)(.*)/im", "$1(/image.php?directory=$urlApparentDirectory&file=$2)$4", $md);
	return $newLine;
}

function checkForPermaLink($md, $filename) {
	//permalink
	if (ENABLE_PERMALINKS && !empty($filename)) {
		$hash = checkPermalinkExists($md);
		if ($hash == "0") {
			$genhash = generatePermalinkHash($md);
			$hashComment = generatePermalinkComment($genhash);
			$md = $hashComment . $md;
			saveFileInCD($filename, $md);
			$hash = $genhash;
		}

		// if ($hash != "0") {
		// 	$md = stripPermalink($md);
		// 	saveFileInCD($filename, $md);
		// }
		$permalink = generatePermlink($hash);
	}
	return $permalink;
}

function addWrappers($filename, $md, $cd, $permalink) {

	//breadcrumbs
	$directory = $cd;
	$breadcrumbs = ""; //var declaration

	$split = explode("/", $directory); // create array called "split" from the directory string, separated by the "/"
	$c = count($split);
	for ($i=0; $i < $c; $i++) { //remove leading '.'
		if ($split[$i] == ".") {
			$split[$i] = "";
		}
	}

	for ($i=0; $i < $c; $i++) {
		$pathString = "";
		for ($h=0; $h <= $i; $h ++) { //generate link url based on depth of current directory
			if (preg_match("/\/$/", $pathString, $output_array)) { // if $pathString ends in "/", don't add one
				$pathString .= $split[$h];
			} else {
				$pathString .= "/" . $split[$h];
			}
		}
		$linkString = $split[$i];
		if ($linkString == "" && $i == 0) {
			$linkString = "Home";
		}
		$pathString = sanitizeURL($pathString);
		$breadcrumbs .= "[$linkString](?directory=$pathString) / ";
	}

	//file mod date
	if ($filename != "" && SHOW_TIMESTAMP) {
		$dateString = getFileModDate($filename);
		$dateString = "<p class='mdrTimestamp'>$dateString</p>\n";
	}



	$breadcrumbs = $breadcrumbs . "\n$md\n***\n" . $dateString . $permalink . $breadcrumbs; //surround the imported md document with the breadcrumbs

	return $breadcrumbs;
}



function getFileList($filesInCD, $currentDirectory) {

	foreach($filesInCD as $thisFile) {
		$dirLink = sanitizeURL($currentDirectory);
		$fileLink = sanitizeURL($thisFile);

		$thisFilePretty = preg_replace("/(.*)\.md/i", "$1",$thisFile);

		$thisFileString = "* [$thisFilePretty](?file=$fileLink&directory=$dirLink)\n";
		$string = $string . $thisFileString;
	}

	if (!empty($string)) {
		$string = "\n### Files:\n$string";
	}

	return $string;
}

function getFolderList($allFilesInCD, $currentDirectory) {

	foreach($allFilesInCD as $thisFile) {
		if ($thisFile == "assets") {
			continue;
		}
		if (is_dir($thisFile)) {
			$dirLink = sanitizeURL("$currentDirectory/$thisFile");
			$string .= "#### [$thisFile](?directory=$dirLink)\n";
		}
	}

	if (!empty($string)) {
		$string = "### Folders:\n$string";
	}

	return $string;
}


?>

</body>
</html>
