<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Nyheter";

$herbert['main'] = <<<EOD
<h1>Nyheter</h1>
<p>Detta är en exempelsida som visar hur Herbert fungerar tillsammans med återanvändbara moduler.</p>
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
