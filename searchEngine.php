<?php

// this file could use a bit of cleanup yet to be more maintainable


function searchMain($search) {
	global $allFolders;
	$allFolders = array(MD_BASE_PATH);
	getFolderListIn(MD_BASE_PATH);
	$allMDFiles = getAllMDFiles();
	$resultArray = searchFilesForString($allMDFiles, $search);
	// print_r($resultArray);
	$mdResults = markResultsDown($resultArray);
	// print "$mdResults\n";
	return $mdResults;
}

function markResultsDown($resultArray) {
	$mdResults = "";
	foreach ($resultArray as $thisResult) {
		$basePath = MD_BASE_PATH;
		$basePath = preg_replace("/\//", "\\/", $basePath); //escape any forward slashes
		$thisResult = preg_replace("/$basePath/", "", $thisResult);
		preg_match("/^(.*\/)([^\/]*.md)$/", $thisResult, $output_array);
		$fileName = $output_array[2];
		$directory = $output_array[1];
		$dirLink = sanitizeURL($directory);
		$fileLink = sanitizeURL($fileName);
		$url = "/?directory=$dirLink&file=$fileLink";
		$thisMDResult = "* [$fileName]($url)\n";
		$mdResults .= $thisMDResult;
	}
	return $mdResults;
}

function doesFileContainString($file, $string) {
	$fileHandle = fopen($file, "r") or die("Unable to open file: $file!");
	$md = fread($fileHandle,filesize($file));
	fclose($fileHandle);

	$md = preg_replace("/[\*|\`|\[|\]|\#]/im", "", $md); //remove special characters before search

	$match = preg_match("/$string/im", $md, $output_array); // check if $file contains $string
	$fnMatch = preg_match("/$string/im", $file, $output_array); //TODO: change this to remove the MD_BASE_PATH - checks if filename contains $string

	$match = $match | $fnMatch;
	// print "$match\n";

	return $match;
}


function searchFilesForString($allMDFiles, $string) {
	$resultArray = array();
	foreach ($allMDFiles as $thisFile) {
		if (doesFileContainString($thisFile, $string)) {
			$resultArray[] = $thisFile;
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
	// print "Directory $tPath\n";
	// print_r($allMDFilesInCD);
	return $allMDFilesInCD;
}

function getFolderListIn($aFolder) {
	global $allFolders;
	// print "searching $aFolder\n";
	if ($aFolder == "") {
		$theFolder = MD_BASE_PATH;
	} else {
		$theFolder = $aFolder;
	}

	// if (!preg_match("/\/$/", $theFolder, $outarray)) {
	// 	// print "matched $theFolder\n";
	// 	$theFolder .= "/";
	// 	// print "fixed $theFolder\n";
	//
	// }

	$theFolder = confirmTrailingSlash($theFolder);

	// print "will search $theFolder\n";

	$allFilesInCD = glob("$theFolder*"); ///// go through each folder, get a list of all folders, etc - once we ahve an array of all absolute paths for folders we cycle through each one to glob md files and then search each md file
	foreach($allFilesInCD as $thisFile) {
		if (is_dir($thisFile)) {
			$newFolder = "$thisFile";
			// print "found: $newFolder\n";
			$allFolders[] = $newFolder;
			// print_r($allFolders);
			getFolderListIn($newFolder);
		}
	}

}

function confirmTrailingSlash ($pPath) {

	$tPath = $pPath;
	if (!preg_match("/\/$/", $pPath, $outarray)) {
		// print "matched $theFolder\n";
		$tPath .= "/";
		// print "fixed $theFolder\n";
	}

	return $tPath;
}

?>
