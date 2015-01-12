<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Connect to the Movieclass with connections to the database
$movies = new CMovies($herbert['db']);
$adminpanel = null;
$output = null;

// Show info for a movie
if (isset($_GET['show']) && is_numeric($_GET['show'])) {
    // Get movie info
    $info = $movies->GetMovie($_GET['show']);

    // Set title
    $pagetitle = $info->title;

    // Create movie info
    $content = <<<EOD
<div style='float:left;width:25%;'>
    <img src='img/img.php?src=movies/{$info->image}.jpg' />
</div>
<div style='float:left;width:75%;'>
    <p>{$info->plot}</p>
    <p style='font-size:small;'>({$info->year}) {$movies->GetGenresAsLinks($_GET['show'])} <a href='http://www.imdb.com/title/{$info->imdb}/'><img src='img/img.php?src=imdb.png&height=14'></a></p>
    <p>Pris: {$info->price} kr</p>
    <iframe width="560" height="315" src="//www.youtube.com/embed/{$info->youtube}" frameborder="0" allowfullscreen></iframe>
</div>
<div style='clear:both;'></div>
EOD;

    // Create adminpanel
    if (isset($_SESSION['auth']) && $_SESSION['auth']->IsAuth()) {
        $adminpanel = "<p>Admin: <a href='movies.php?edit={$_GET['show']}'>Redigera film</a> | <a href='movies.php?remove={$_GET['show']}'>Radera film</a></p>";
    }
}
// Edit a movie
elseif (isset($_GET['edit'])) {
    // Set title
    $pagetitle = "Edit";

    // Create content
    $content = <<<EOD
<form method=post>
  <fieldset>
  <legend>Uppdatera info om film</legend>
  <input type='hidden' name='id' value='{$id}'/>
  <output>{$output}</output>
  <p><label>Titel:<br/><input type='text' name='title' value='{$movie->title}'/></label></p>
  <p><label>År:<br/><input type='text' name='year' value='{$movie->year}'/></label></p>
  <p><label>Bild:<br/><input type='text' name='image' value='{$movie->image}'/></label></p>
  <p><input type='submit' name='save' value='Spara'/> <input type='reset' value='Återställ'/> <input type='submit' name='delete' value='Ta bort film'/></p>
  </fieldset>
</form>
EOD;
}
// Add a movie
elseif (isset($_GET['add'])) {
    // Set title
    $pagetitle = "Add";

    // Create content
    $content = "Visa en film";
}
// Remove a movie
elseif (isset($_GET['remove'])) {
    // Set title
    $pagetitle = "Remove";

    // Create content
    $content = "Visa en film";
}
// Show all movies (default)
else {
    // Set title
    $pagetitle = "Filmer";

    $content = $movies->GetSearchForm();
    // Create movietable
    $content .= $movies->GetTable();

    // Create adminpanel
    if (isset($_SESSION['auth']) && $_SESSION['auth']->IsAuth()) {
        // $adminpanel = "<p><a href='movies_add.php'>Lägg till ny film</a> | <a href='movies_reset.php'>Återställ databas</a></p>";
    }
}





// Do it and store it all in variables in the Herbert container.
$herbert['title'] = $pagetitle;

$herbert['main'] = <<<EOD
<h1>$pagetitle</h1>
$adminpanel
$output
$content
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
