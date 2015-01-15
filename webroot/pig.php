<?php 
/**
 * This is a Herbert pagecontroller.
 *
 */
// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php'); 

// Check for new game
// RM: Line below. ORG: $newGame = isset($_GET['new']) ? $_GET['new'] : (isset($_POST['new']) ? $_POST['new'] : null);
$newGame = ( !isset($_SESSION['pigGame']) || isset($_GET['new']) || isset($_POST['new'])) ? 'computer' : null;

// Start a new game. Well, just unset current session
if (isset($newGame)) {
    unset($_SESSION['pigGame']);
    header("Refresh: 0; pig.php");
}

// Get session if exist, or create new game
$pig = (isset($_SESSION['pigGame'])) ? $_SESSION['pigGame'] : new CPigGame($newGame);


// Action if it's the computers turn
if ($pig->IsComputersTurn()) {
    $pig->ComputersMove();
}

// Action on roll
elseif (isset($_POST['roll'])) {
    $pig->RollDice($_POST['player']);
}

// Action on stop
elseif (isset($_POST['stop'])) {
    $pig->StopRound($_POST['player']);
}

// Set page refresh if it's the computers turn
if ($pig->IsComputersTurn()) {
    header("Refresh: 1; pig.php");
}

// Render game plan to the right
$pig0 = $pig->GetGamePlan(0);

// Render game plan to the left, if it's a challange
$pig1 = ($pig->IsChallange()) ? $pig->GetGamePlan(1) : null;

// Set true if debugging
$debug = (false) ? myDump($pig) : "";

// Save session
$_SESSION['pigGame'] = $pig;

// Define what to include to make the plugin to work
$herbert['stylesheets'][] = 'css/pig.css';

// Do it and store it all in variables in the Herbert container.
$herbert['title'] = "Kasta gris";

$herbert['main'] = <<<EOD
<h1>Kasta gris</h1>
<p>Spelet är enkelt. Kasta tärningen så många gånger du vill. När du stannar rundan får du spara dina poäng. Men får du en etta, så förlorar du poängen för rundan och turen går vidare. Först till 100 vinner!</p>
<p>{$pig->GetInfo()}</p>
<div id="pigcontent"> 
{$pig0}
{$pig1}
<div class="clear"></div>
</div>
{$debug}
EOD;

// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
