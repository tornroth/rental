<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */
// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Om Herbert";

$herbert['main'] = <<<EOD
<h1>Om Herbert</h1>
<p>I kursen <a href="http://dbwebb.se/oophp">Databaser och objektorienterad programmering i PHP</a> vid <a href="http://bth.se">Blekinge Tekniska Högskola</a> var en av deluppgifterna att skapa en webbmall. Ur detta skapades Herbert.</p>
<h3>Varför Herbert?</h3>
<p>Namnet kommer delvis från ingenstans. Och delvis från ett taskigt namnminne. Men någonstans i bakhuvudet ser jag en äldre man som tidigare var min kollega på en skola där han arbetade som speciallärare. Han hade en förmåga att hålla ihop olika brokiga grupper och ge var och en möjlighet att visa sina styrkor. Eleverna i dessa grupper kunde därför tillsammans utvecklas och visa på stor kompetens. Tyvärr har jag glömt hans namn, men det är inte långt från Herbert.</p>
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
