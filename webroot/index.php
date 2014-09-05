<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Hej världen";

$herbert['main'] = <<<EOD
<h1>Hej världen</h1>
<p>Detta är en exempelsida som visar hur Herbert ser ut och fungerar.</p>
EOD;


// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
