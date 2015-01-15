<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */
// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 


// Code for source.php
$source = new CSource(array('secure_dir'=>'..', 'base_dir'=>'..'));
$content = $source->View();


// Define what to include to make the plugin to work
$herbert['stylesheets'][] = 'css/source.css';


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Källkod";

$herbert['main'] = <<<EOD
<h1>Visa källkod</h1>
<p>Följande filer finns foldern nedan. Klicka på en fil för att visa källkoden.</p>
$content
EOD;


// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
