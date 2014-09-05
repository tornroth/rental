<?php
/**
 * Config-file for Herbert. Change settings here to affect installation.
 *
 */

/**
 * Set the error reporting.
 *
 */
error_reporting(-1);              // Report all type of errors
ini_set('display_errors', 1);     // Display all errors 
ini_set('output_buffering', 0);   // Do not buffer outputs, write directly


/**
 * Define Herbert paths.
 *
 */
define('HERBERT_INSTALL_PATH', __DIR__ . '/..');
define('HERBERT_THEME_PATH', HERBERT_INSTALL_PATH . '/theme/render.php');


/**
 * Include bootstrapping functions.
 *
 */
include(HERBERT_INSTALL_PATH . '/src/bootstrap.php');


/**
 * Start the session.
 *
 */
session_name(preg_replace('/[^a-z\d]/i', '', __DIR__));
session_start();


/**
 * Create the Herbert variable.
 *
 */
$herbert = array();


/**
 * Site wide settings.
 *
 */
$herbert['lang'] = 'sv';
$herbert['title_append'] = ' | Herbert';

$herbert['header'] = <<<EOD
<a href='./' class='sitelogo'><img src='img/herbert.png' alt='Herbert Logo'/></a>
<span class='sitetitle'>Webbtemplate</span>
<span class='siteslogan'>Återanvändbara moduler för webbutveckling med PHP</span>
EOD;

$herbert['menu'] = array(
  'callback' => 'modifyNavbar',
  'items' => array(
    'home' => array('text'=>'HEM', 'url'=>'./', 'class'=>null),
    'about' => array('text'=>'OM', 'url'=>'about.php', 'class'=>null),
    'slideshow' => array('text'=>'SLIDESHOW', 'url'=>'slideshow.php', 'class'=>null),
    'source' => array('text'=>'KÄLLKOD', 'url'=>'source.php', 'class'=>null)
  )
);

$herbert['footer'] = <<<EOD
<footer>
	<p>© 2014 Marcus Törnroth | <a href='https://github.com/rcus/herbert'>GitHub</a> | <a href='http://validator.w3.org/unicorn/check?ucn_uri=referer&amp;ucn_task=conformance'>Unicorn</a></p>
</footer>
EOD;


/**
 * Theme related settings.
 *
 */
$herbert['stylesheets'] = array('css/style.css');
$herbert['favicon']    = 'favicon.ico';


/**
 * Settings for JavaScript.
 *
 */
$herbert['modernizr'] = 'js/modernizr.js';
$herbert['jquery'] = '//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js'; // Set to null to disable jQuery 
$herbert['javascript_include'] = array();
//$herbert['javascript_include'] = array('js/main.js'); // To add extra javascript files


/**
 * Google analytics.
 *
 */
$herbert['google_analytics'] = null; // Enter your Google Analytics ID 'UA-XXXXXXXX-X'
