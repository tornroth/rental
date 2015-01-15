<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Connect to the Movie and Blog class with connections to the database
$movies = new CMovies($herbert['db']);
$news = new CBlog($herbert['db']);

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Hitta dina favoritfilmer";

$herbert['main'] = <<<EOD
<div id='content'>
{$movies->GetLatestMoviesToFirstpage()}
{$movies->GetCategriesToFirstpage()}
{$movies->GetSelectedMoviesToFirstpage()}
</div>
<div id='sidebar'>
{$news->GetNewsToFirstpage()}
<div style='float:left;width:235px;'><h2>Tävling</h2>
<div style='float:left;background-color:#ccc;'>
<h4 style='margin:12px;' ><a href='pig.php' style='display:block;color:#333;text-decoration:none;' >Vinn en hyrfilm!</a></h4>
<p style='margin:0 12px;' >Nu kan du utmana oss i det klassiska spelet "Kasta gris". Men det är inte bara en lek, du kan vinna...<br />EN HYRFILM!!</p>
<p style='margin:4px 12px 12px;text-align:right;' ><a href='pig.php' style='font-size:small;font-style:italic;text-decoration:none;'>Ta mig dig &raquo;</a></p>
</div>
</div>
</div>
<div style='clear:both;'></div>
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
