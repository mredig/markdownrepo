<?php

//// copy this file to 'config.php' to customize. This one is used for defaults and new features.


//absolute path to directory housing your md files (md should be lowercase in file names)
define('MD_BASE_PATH', '.');

//URL path to where the css file are located for the md repo (optional)
define('HTML_CSS_URL', '/css/modern.css');

//database title
define('DATA_STORE_NAME', 'MD Documentation');

//1 for showing timestamp at bottom of document, 0 for not
define('SHOW_TIMESTAMP', 1);

//1 for showing filename as the header of the document, 0 for not
define('SHOW_FILENAME', 1);

//permalinks - will require write access to the given directory (generates a unique hash for each file so files can be linked even after getting moved around and saves to the file)
define('ENABLE_PERMALINKS', 1);

//popuplinks
define('ENABLE_POPUPLINKS', 1);


// ini_set('display_errors', 1); // leave this off when in production - useful for debugging, but will show errors from loading config
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




?>
