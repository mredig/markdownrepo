<?php
require 'parsedown/Parsedown.php';
require 'globals.php';
require 'searchEngine.php';

chdir(MD_BASE_PATH);
global $currentDirectory, $file, $apparentDirectory, $search, $allFolders;
$allFolders = array(MD_BASE_PATH);
checkGET();


$pageTitle = printHeader( DATA_STORE_NAME . " Search", $search);
printSearchHTML();

// generate page contents
$Parsedown = new Parsedown();
$md = getSearchResults($search);
$mdOutput = $Parsedown->text($md);
print "$mdOutput";




//functions go here

function getSearchResults($search) {

	$breadcrumbs = "\n[Home](/?directory=/)\n";

	$mdResults = "# Search results for: $search\n";
	$mdResults .= searchMain($search);

	$mdResults = $breadcrumbs . $mdResults . $breadcrumbs;

	return $mdResults;

}







?>

</body>

</html>
