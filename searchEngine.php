<?php

// this file could use a bit of cleanup yet to be more maintainable


//// main functions
function searchMain($search, $includeContext) {
	global $allFolders;
	$mdDir = getcwd();
	$allFolders = array($mdDir);
	getFolderListIn($mdDir);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $search); //returns md string
	$mdResults = compileSearchResultsToString($resultArray, $includeContext);
	return $mdResults;
}

function searchPermaLinks($hash) {
	global $allFolders;
	$mdDir = getcwd();
	$allFolders = array($mdDir);
	getFolderListIn($mdDir);
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

function searchFilesForString($allMDFiles, $string) { //returns different things based on context (permalink vs not)
	$objectArray = array();
	foreach ($allMDFiles as $thisFile) {
		$thisResultArray = doesFileContainString($thisFile, $string); //iterate through all files and search for $string
		if (count($thisResultArray) > 0) { //doesFileContainString returns an array, so a result is positive if it has any members on the array
			$fileDirArray = extractDirectoryAndFile($thisFile);

			$contextArray = array();
			foreach ($thisResultArray as $thisResult) { //add context if there is any
				if ($thisResult == 1) { //check to see if it is just dummy result or if context is even turned on
					continue;
				}

				$contextArray[] = $thisResult;
			}

			$thisSearchResult = new SearchResult($fileDirArray['filename'], $fileDirArray['directory'], $contextArray);
			$objectArray[] = $thisSearchResult;
		}
	}

	usort($objectArray, "sortSearchResultObjectArray"); //sort alphabetical results

	return $objectArray;
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

function doesFileContainString($file, $string) { //returns an array of results within a file or filename
	$fileHandle = fopen($file, "r") or die("Unable to open file (string search): $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$thisResultArray = array(); //create return object


	$md = stripPermalink($md);
	$md = preg_replace("/[\*|\`|\[|\]|\#]/im", "", $md); //remove special characters before search
	$noBasePath = removeBaseFromPath($file);
	$md = "$noBasePath\n\n$md";

	$searchTerms = preg_split("/ /", $string, NULL, PREG_SPLIT_NO_EMPTY);

	$termsMatched = 0;
	$tempResultArray = array();
	foreach ($searchTerms as $term) { // search string to find any matches
		$thisTermMatch = preg_match("/.*$term.*/im", $md, $regexArray); // check if $file contains $term
		if ($thisTermMatch) {
			$termsMatched ++;
			for ($i = 0; $i < count($regexArray); $i++) {
				$tempResultArray[$regexArray[$i]] = 1; //value doens't matter... basically just creating a set
			}
		}
	}

	if ($termsMatched == count($searchTerms)) {
		$contextArray = array();
		foreach ($tempResultArray as $line => $value) {
			$contextArray[] = $line;
		}

		foreach ($searchTerms as $term) { // bold the search terms in the results
			for ($i = 0; $i < count($contextArray); $i++) {
				$contextArray[$i] = preg_replace("/\**($term)\**/im", "**$1**", $contextArray[$i]);
			}
		}

		$thisResultArray[] = 1; //so the return value has at least 1 result (if only the filename matches, there won't be any context result)
		foreach ($contextArray as $foundResult) {
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

function compileSearchResultsToString($searchResultArray, $includeContext) { //operates on class objects
	global $search;
	$mdResults = "";
	foreach ($searchResultArray as $thisSearchResult) {
		$thisResultMD = createMarkdownFileLink($thisSearchResult->directory, $thisSearchResult->file);
		$mdResults .= "* $thisResultMD\n";
		if ($includeContext) {
			$mdResults .= "\t* location: **$thisSearchResult->directory**\n";
			foreach ($thisSearchResult->context as $thisContext) {
				$mdResults .= "\t* $thisContext\n";
			}
		}
	}
	if ($mdResults == "") {
		$mdResults = "No results found for '$search'.\n";
	}
	return $mdResults;
}


function createMarkdownFileLink($directory, $filename) { //returns a markdown link of a provided file
	$url = getFileURL($directory, $filename);
	$thisMDResult = "[$filename]($url)";

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
	$basePath2 = getMarkdownDirectory(); //if MD_BASE_PATH is a relative directory, we needed to previously convert it to an absolute path. This will result in a different string that still needs removal
	$basePath2 = preg_replace("/\//", "\\/", $basePath2); //escape any forward slashes
	$noBasePath = preg_replace("/$basePath/", "", $path); // remove base path from link
	$noBasePath = preg_replace("/$basePath2/", "", $path); // remove base path from link
	return $noBasePath;
}

function confirmTrailingSlash ($pPath) {

	$tPath = $pPath;
	if (!preg_match("/\/$/", $pPath, $outarray)) {
		$tPath .= "/";
	}

	return $tPath;
}

function sortSearchResultObjectArray($a, $b) {
	return strcmp($a->file, $b->file);
}


//// search results class

class SearchResult {
    public $file;
    public $directory;
	public $context;

	function __construct($file, $directory, $context = array()) {
		$this->file = $file;
		$this->directory = $directory;
		$this->context = $context;
	}

}


?>
