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
<h1>Välkommen</h1>

<div>{$movies->GetMoviesToFirstpage()}</div>

<p>Visa de tre senaste blogginläggen.</p>
<p>Visa en översikt av de kategorier som finns för filmerna.</p>
<p>Visa bilder på mest populära film och senast hyrda film (okey att hårdkoda).</p>
<p>Lägg till övrig information efter eget tycke för att göra en presentabel första sida.</p>
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
