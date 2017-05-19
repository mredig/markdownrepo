<?php
require 'parsedown/Parsedown.php';
require 'globals.php';

chdir(MD_BASE_PATH);
global $currentDirectory, $file, $apparentDirectory;
checkGET();
chdir($currentDirectory);
$apparentDirectory = setApparentDirectory($currentDirectory); // current directory might have extra slashes and start with . - apparent directory is cleaned up and starts with /

$pageTitle = printHeader($file, $apparentDirectory);
printSearchHTML();

// generate page contents
$Parsedown = new Parsedown(); //create Parsdown object
if ($file != "") { // check if there is a file specified - if so, display contents. if not, show directory contents
	$fileHandle = fopen($file, "r") or die("Unable to open file: $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	// print_r($md);

	$md = processImageLinks($md, $apparentDirectory);
} else {
	$allFilesInCD = glob("*");
	$mdFilesInCD = glob("*.md");

	$folderSection = getFolderList($allFilesInCD, $apparentDirectory);
	$fileSection = getFileList($mdFilesInCD, $apparentDirectory);
	$md = "# " . DATA_STORE_NAME . "\n" . $folderSection . $fileSection;
}
$md = addBreadcrumbs($file, $md, $apparentDirectory);
$mdOutput = $Parsedown->text($md);
print "$mdOutput";


//functions go here
function processImageLinks($md, $apparentDirectory) {
	$newLine = preg_replace("/^(.*)\((.*\.(png|jpg))\)(.*)/im", "$1(/image.php?directory=$apparentDirectory&file=$2)$4", $md);
	return $newLine;
}

function addBreadcrumbs($filename, $md, $cd) {
	$directory = $cd;

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

	if ($filename != "") {
		$dateString = getFileModDate($filename);
		$dateString = "<p class='timestamp'>$dateString</p>\n";
	}

	$breadcrumbs = $breadcrumbs . "\n$md\n" . $dateString . $breadcrumbs; //surround the imported md document with the breadcrumbs

	return $breadcrumbs;
}



function getFileList($filesInCD, $currentDirectory) {
	$string = "\n### Files:\n";
	foreach($filesInCD as $thisFile) {
		$dirLink = sanitizeURL($currentDirectory);
		$fileLink = sanitizeURL($thisFile);

		$thisFilePretty = preg_replace("/(.*)\.md/i", "$1",$thisFile);

		$thisFileString = "* [$thisFilePretty](?file=$fileLink&directory=$dirLink)\n";
		$string = $string . $thisFileString;
	}
	return $string;
}

function getFolderList($allFilesInCD, $currentDirectory) {
	$string = "### Folders:\n";
	foreach($allFilesInCD as $thisFile) {
		if ($thisFile == "assets") {
			continue;
		}
		if (is_dir($thisFile)) {
			$dirLink = sanitizeURL("$currentDirectory/$thisFile");
			$string .= "#### [$thisFile](?directory=$dirLink)\n";
		}
	}
	return $string;
}


?>

</body>
</html>
