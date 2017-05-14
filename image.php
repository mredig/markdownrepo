<?php
require 'globals.php';
chdir(MD_BASE_PATH);

global $currentDirectory, $file;

checkGET();
chdir($currentDirectory);


if (file_exists($file)) {
	header('Content-Type: image/jpg');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	exit;
}


?>
