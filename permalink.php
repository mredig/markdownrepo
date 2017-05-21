<?php
require 'parsedown/Parsedown.php';
require 'globals.php';
require 'searchEngine.php';

chdir(MD_BASE_PATH);
global $currentDirectory, $file, $apparentDirectory, $search, $allFolders, $perma;
$allFolders = array(MD_BASE_PATH);
checkGET();

$permaArray = searchPermaLinks($perma);

$pageTitle = printHeader( DATA_STORE_NAME . " Search", $search, $permaArray['metatag']);
printSearchHTML();

// generate page contents
$Parsedown = new Parsedown();
$md = pageMD($permaArray);
$mdOutput = $Parsedown->text($md);
print "$mdOutput";




//functions go here

function pageMD($permaArray) {
	$breadcrumbs = "\n[Home](/?directory=/) /\n";

	$url = $permaArray['url'];
	$filename = $permaArray['filename'];

	$mdResults = "### Redirecting...\n\nUse this link if [$filename]($url) doesn't redirect automatically.";
	$mdResults = $breadcrumbs . $mdResults . $breadcrumbs;

	return $mdResults;
}

?>

</body>

</html>
