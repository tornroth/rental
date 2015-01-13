<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Connect to the Movieclass with connections to the database
$movies = new CMovies($herbert['db']);

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = $movies->GetPageTitle();

$herbert['main'] = <<<EOD
<h1>{$movies->GetPageTitle()}</h1>
{$movies->GetPageContent()}
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
