<?php

// this file could use a bit of cleanup yet to be more maintainable


// function searchMain($search, $includeContext) {
function searchMain($search) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $search);
	$mdResults = compileResultsToString($resultArray);
	return $mdResults;
}


function compileResultsToString($resultArray) {
	$mdResults = "";
	foreach ($resultArray as $thisResult) {
		$mdResults .= $thisResult;
	}
	return $mdResults;
}

function markResultDown($thisResult) {
	$basePath = MD_BASE_PATH; //convert to variable as we need to do some light editing to it
	$basePath = preg_replace("/\//", "\\/", $basePath); //escape any forward slashes
	$thisResult = preg_replace("/$basePath/", "", $thisResult); // remove base path from link
	preg_match("/^(.*\/)([^\/]*.md)$/", $thisResult, $output_array); //extract filename and directory
	$fileName = $output_array[2];
	$directory = $output_array[1];
	$dirLink = sanitizeURL($directory);
	$fileLink = sanitizeURL($fileName);
	$url = "/?directory=$dirLink&file=$fileLink";
	$thisMDResult = "* [$fileName]($url)\n";

	return $thisMDResult;
}

function doesFileContainString($file, $string) {
	$fileHandle = fopen($file, "r") or die("Unable to open file: $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$md = preg_replace("/[\*|\`|\[|\]|\#]/im", "", $md); //remove special characters before search


	$fnMatch = preg_match("/$string/im", $file, $output_array); //TODO: change this to remove the MD_BASE_PATH - checks if filename contains $string
	$match = preg_match("/.*$string.*/im", $md, $regexArray); // check if $file contains $string
	$match = $match | $fnMatch; // Consider it matched whether it was in filename or file contents


	$thisResultArray = array();
	if ($match) {
		$thisResultArray[] = 1; //so the return value has at least 1 result (if only the filename matches, there won't be any context result)
		foreach ($regexArray as $foundResult) {
			$thisResultArray[] = "..." . $foundResult . "..."; // wrap context in elipses
		}
	}

	return $thisResultArray;
}


function searchFilesForString($allMDFiles, $string) {
	$resultArray = array();
	foreach ($allMDFiles as $thisFile) {
		$thisResultArray = doesFileContainString($thisFile, $string); //iterate through all files and search for $string
		if (count($thisResultArray) > 0) { //doesFileContainString returns an array, so a result is positive if it has any members on the array
			$thisMDResult = markResultDown($thisFile); //convert the file to a markdown link
			$resultArray[] = $thisMDResult;
			foreach ($thisResultArray as $thisResult) { //add context if there is any
				if ($thisResult == 1) { //check to see if it is just dummy result TODO: or if context is even turned on
					continue;
				}
				$resultContext = "\t* `$thisResult`\n";
				$resultArray[] = $resultContext;
			}
		}
	}
	return $resultArray;
}

function getAllMDFiles() {
	global $allFolders;
	$allMDFiles = array();
	foreach ($allFolders as $thisFolder) {
		$allMDFiles = array_merge($allMDFiles, getFileListIn($thisFolder));
	}
	return $allMDFiles;
}


function getFileListIn($pPath) {
	$tPath = confirmTrailingSlash($pPath);
	$allMDFilesInCD = glob("$tPath*.md");
	return $allMDFilesInCD;
}

function getFolderListIn($aFolder) {
	global $allFolders;
	if ($aFolder == "") {
		$theFolder = MD_BASE_PATH;
	} else {
		$theFolder = $aFolder;
	}

	$theFolder = confirmTrailingSlash($theFolder);


	$allFilesInCD = glob("$theFolder*"); ///// go through each folder, get a list of all folders, etc - once we ahve an array of all absolute paths for folders we cycle through each one to glob md files and then search each md file
	foreach($allFilesInCD as $thisFile) {
		if (is_dir($thisFile)) {
			$newFolder = "$thisFile";
			$allFolders[] = $newFolder;
			getFolderListIn($newFolder);
		}
	}

}

function confirmTrailingSlash ($pPath) {

	$tPath = $pPath;
	if (!preg_match("/\/$/", $pPath, $outarray)) {
		$tPath .= "/";
	}

	return $tPath;
}

?>
