<?php

// this file could use a bit of cleanup yet to be more maintainable


//// main functions
function searchMain($search, $includeContext) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $search, $includeContext); //returns md string
	if (!$includeContext) { //easy fix
		natcasesort($resultArray);
	}
	$mdResults = compileResultsToString($resultArray);
	return $mdResults;
}

function searchPermaLinks($hash) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$permaResult = searchFilesForPermaLink($allMDFiles, $hash); //returns with directory and filename
	$fileUrl = getFileURL($permaResult['directory'], $permaResult['filename']);
	$metatag = formatPermalinkMetaTag($fileUrl);
	$permaArray = array();
	$permaArray['url'] = $fileUrl;
	$permaArray['metatag'] = $metatag;
	$permaArray['filename'] = $permaResult['filename'];
	return $permaArray;
}


//// iterate through all files and look for strings

function searchFilesForString($allMDFiles, $string, $includeContext) { //returns different things based on context (permalink vs not)
	$resultArray = array();
	foreach ($allMDFiles as $thisFile) {
		$thisResultArray = doesFileContainString($thisFile, $string); //iterate through all files and search for $string
		if (count($thisResultArray) > 0) { //doesFileContainString returns an array, so a result is positive if it has any members on the array
			$fileDirArray = extractDirectoryAndFile($thisFile);

			$thisMDResult = markResultDown($fileDirArray['directory'], $fileDirArray['filename']); //convert the file to a markdown link
			$resultArray[] = $thisMDResult;
			foreach ($thisResultArray as $thisResult) { //add context if there is any
				if ($thisResult == 1 || $includeContext != 1) { //check to see if it is just dummy result or if context is even turned on
					continue;
				}
				$resultContext = "\t* `$thisResult`\n";
				$resultArray[] = $resultContext;
			}

		}
	}
	return $resultArray;
}

function searchFilesForPermaLink($allMDFiles, $hash) {
	$resultArray = array();
	foreach ($allMDFiles as $thisFile) {
		$thisResultArray = doesFileContainHash($thisFile, $hash); //iterate through all files and search for $string
		if (count($thisResultArray) > 0) { //doesFileContainString returns an array, so a result is positive if it has any members on the array
			$permaInfoArray = extractDirectoryAndFile($thisFile);
			break;
		}
	}
	return $permaInfoArray;

}


//// called on by file iterators to examine individual file contents

function doesFileContainString($file, $string) { //returns an array of search results
	$fileHandle = fopen($file, "r") or die("Unable to open file (string search): $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$thisResultArray = array(); //create return object


	$md = stripPermalink($md);
	$md = preg_replace("/[\*|\`|\[|\]|\#]/im", "", $md); //remove special characters before search
	$noBasePath = removeBaseFromPath($file);

	$fnMatch = preg_match("/$string/im", $noBasePath, $output_array);
	$match = preg_match("/.*$string.*/im", $md, $regexArray); // check if $file contains $string
	$match = $match | $fnMatch; // Consider it matched whether it was in filename or file contents



	if ($match) {
		$thisResultArray[] = 1; //so the return value has at least 1 result (if only the filename matches, there won't be any context result)
		foreach ($regexArray as $foundResult) {
			$thisResultArray[] = "..." . $foundResult . "..."; // wrap context in elipses
		}
	}



	return $thisResultArray;
}

function doesFileContainHash($file, $inHash) { //returns an array containing filename and directory of file containing given hash
	$fileHandle = fopen($file, "r") or die("Unable to open file (hash search): $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$permaInfo = array(); //create return object


	$fileHash = checkPermalinkExists($md);
	if (preg_match("/^$inHash$/i", $fileHash)) {
		$permaInfo[] = $fileHash;
	}
	return $permaInfo;
}


//// support functions

function getAllMDFiles() { // returns an array of all MD files
	global $allFolders;
	$allMDFiles = array();
	foreach ($allFolders as $thisFolder) {
		$allMDFiles = array_merge($allMDFiles, getFileListIn($thisFolder));
	}
	return $allMDFiles;
}


function getFileListIn($pPath) { //returns an array of files in a directory
	$tPath = confirmTrailingSlash($pPath);
	$allMDFilesInCD = glob("$tPath*.md");
	return $allMDFilesInCD;
}

function getFolderListIn($aFolder) { //adds folders to global array of folders
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

function extractDirectoryAndFile($thisResult) { //returns associated array with a filename and directory
	$thisResult = removeBaseFromPath($thisResult);
	preg_match("/^(.*\/)([^\/]*.md)$/", $thisResult, $output_array); //extract filename and directory
	$filename = $output_array[2];
	$directory = $output_array[1];

	$rArray = array();
	$rArray['directory'] = $directory;
	$rArray['filename'] = $filename;

	return $rArray;
}

//// string formatting

function formatPermalinkMetaTag($url) {
	$metatag = "<meta http-equiv='refresh' content=\"0;URL='$url'\" />\n";
	return $metatag;
}

function compileResultsToString($resultArray) {
	global $search;
	$mdResults = "";
	foreach ($resultArray as $thisResult) {
		$mdResults .= $thisResult;
	}
	if ($mdResults == "") {
		$mdResults = "No results found for '$search'.\n";
	}
	return $mdResults;
}

function markResultDown($directory, $filename) {
	$url = getFileURL($directory, $filename);
	$thisMDResult = "* [$filename]($url)\n";

	return $thisMDResult;
}

function getFileURL($directory, $filename) {
	$dirLink = sanitizeURL($directory);
	$fileLink = sanitizeURL($filename);
	$url = "/?directory=$dirLink&file=$fileLink";
	return $url;
}

function removeBaseFromPath($path) {
	$basePath = MD_BASE_PATH; //convert to variable as we need to do some light editing to it
	$basePath = preg_replace("/\//", "\\/", $basePath); //escape any forward slashes
	$noBasePath = preg_replace("/$basePath/", "", $path); // remove base path from link
	return $noBasePath;
}

function confirmTrailingSlash ($pPath) {

	$tPath = $pPath;
	if (!preg_match("/\/$/", $pPath, $outarray)) {
		$tPath .= "/";
	}

	return $tPath;
}


?>
