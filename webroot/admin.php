<?php
/**
 * This is a Herbert pagecontroller.
 *
 */

// Include the essential config-file which also creates the $herbert variable with its defaults.
include(__DIR__.'/config.php');

// Get current auth-session or create a new.
$auth = (isset($_SESSION['auth'])) ? $_SESSION['auth'] : new CUser($herbert['db']);
$output = null;

// Action on login
if(isset($_POST['login'])) {
    if (!$auth->Login($_POST['user'], $_POST['password'])) {
        $output = "Det blev något fel. Försök igen!";
    }
    // Save session
    $_SESSION['auth'] = $auth;

// Action on logout
} elseif (isset($_POST['logout']) || isset($_GET['logout'])) {
    if ($_SESSION['auth']->Logout()) {
        unset($_SESSION['auth']);
        $output = "Du är nu utloggad!";
    }
}

// View if user is authenticated.
if ($auth->IsAuth()) {
    $title = "Min sida";
    $body = <<<EOD
<p>Du är nu inloggad som {$auth->GetName()} ({$auth->GetAcronym()}).
<form method=post>
    <fieldset>
    <input type='submit' name='logout' value='Logga ut'/>
    <output><b>{$output}</b></output>
    </fieldset>
</form>
EOD;
}

// View if user is NOT authenticated.
else {
    $title = "Logga in";
    $body = <<<EOD
<p>Välkommen att logga in som <em>guest:guest</em>.
<form method=post>
    <fieldset>
    <p><label>Användare:<br/><input type='text' name='user' value=''/></label></p>
    <p><label>Lösenord:<br/><input type='password' name='password' value=''/></label></p>
    <p><input type='submit' name='login' value='Logga in'/></p>
    <output><b>{$output}</b></output>
    </fieldset>
</form>
EOD;
}


// Do it and store it all in variables in the Herbert container.
$herbert['title'] = $title;

$herbert['main'] = <<<EOD
<h1>{$title}</h1>
{$body}
EOD;


// Finally, leave it all to the rendering phase of Herbert.
include(HERBERT_THEME_PATH);
