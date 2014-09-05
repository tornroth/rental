<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */
// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 


// Define what to include to make the plugin to work
$herbert['stylesheets'][]        = 'css/slideshow.css';
$herbert['javascript_include'][] = 'js/slideshow.js';


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Slideshow för att testa JavaScript i Herbert";

$herbert['main'] = <<<EOD
<div id="slideshow" class='slideshow' data-host="" data-path="img/me/" data-images='["me-1.jpg", "me-2.jpg", "me-4.jpg", "me-6.jpg"]'>
<img src='img/me/me-6.jpg' width='950px' height='180px' alt='Me'/>
</div>

<h1>En slideshow med JavaScript</h1>
<p>Detta är en exempelsida som visar hur Herbert fungerar tillsammans med JavaScript.</p>
EOD;


// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
