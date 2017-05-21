<?php

// this file could use a bit of cleanup yet to be more maintainable


function searchMain($search, $includeContext) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $search, $includeContext); //returns md string
	$mdResults = compileResultsToString($resultArray);
	return $mdResults;
}

function searchPermaLinks($perma) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $perma, 0, 1); //returns array of arrays
	$firstResultArray = $resultArray[0]; //only care about first result (shouldn't result in more than one anyway)
	$fileUrl = getFileURL($firstResultArray['directory'], $firstResultArray['filename']);
	$metatag = formatPermalinkMetaTag($fileUrl);
	$permaArray = array();
	$permaArray['url'] = $fileUrl;
	$permaArray['metatag'] = $metatag;
	$permaArray['filename'] = $firstResultArray['filename'];
	return $permaArray;
}

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

function extractDirectoryAndFile($thisResult) {
	$thisResult = removeBaseFromPath($thisResult);
	preg_match("/^(.*\/)([^\/]*.md)$/", $thisResult, $output_array); //extract filename and directory
	$filename = $output_array[2];
	$directory = $output_array[1];

	$rArray = array();
	$rArray['directory'] = $directory;
	$rArray['filename'] = $filename;

	return $rArray;
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

function doesFileContainString($file, $string, $permalink) {
	$fileHandle = fopen($file, "r") or die("Unable to open file: $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$thisResultArray = array(); //create return object


	if ($permalink) {
		$hash = checkPermalinkExists($md);
		if (preg_match("/^$string$/i", $hash)) {
			$thisResultArray[] = $hash;
		}
		return $thisResultArray;
	} else {
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

}


function searchFilesForString($allMDFiles, $string, $includeContext, $permalink = 0) { //returns different things based on context (permalink vs not)
	$resultArray = array();
	foreach ($allMDFiles as $thisFile) {
		$thisResultArray = doesFileContainString($thisFile, $string, $permalink); //iterate through all files and search for $string
		if (count($thisResultArray) > 0) { //doesFileContainString returns an array, so a result is positive if it has any members on the array
			$fileDirArray = extractDirectoryAndFile($thisFile);
			if ($permalink) { //if searching for permalink don't do other stuff
				$resultArray[] = $fileDirArray;
			} else {
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
