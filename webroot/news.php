<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Connect to the blog class with connections to the database
$news = new CBlog($herbert['db']);

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = $news->GetPageTitle();

$herbert['main'] = <<<EOD
<h1>{$news->GetPageTitle()}</h1>
{$news->GetPageContent()}
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
