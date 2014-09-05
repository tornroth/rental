<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */
// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "404";
$herbert['main'] = "Hey, what are you doing? It's not here... (404)";

// Send the 404 header 
header("HTTP/1.0 404 Not Found");


// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
