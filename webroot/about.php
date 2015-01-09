<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Om oss";

$herbert['main'] = <<<EOD
<h1>Om Rental Movies</h1>
<p>Vi finns för att... Ja, för att du finns! Vår historia är kort, men ändå längre än <a href="http://www.imdb.com/title/tt0052618/">Ben-Hur</a> (3:32).</p>
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
