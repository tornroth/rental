<?php 
/**
 * This is a PHP script which call CImage to get an image.
 *
 */

// Array for configuration
$config = array(
    'imgDir'    => realpath(__DIR__) . DIRECTORY_SEPARATOR,
    'cacheDir'  => realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache') . DIRECTORY_SEPARATOR,
    'maxWidth'  => 2000,
    'maxHeight' => 2000
    );

// Include source from CImage.php.
require_once('../../src/CImage/CImage.php');

// Create a image object
$image = new CImage($config);

// Get the incoming arguments and set the values in the image object
$image->setSrc(        (isset($_GET['src'])         ? $_GET['src']     : null));
$image->setVerbose(    (isset($_GET['verbose'])     ? true             : null));
$image->setSaveAs(     (isset($_GET['save-as'])     ? $_GET['save-as'] : null));
$image->setQuality(    (isset($_GET['quality'])     ? $_GET['quality'] : 60));
$image->setIgnoreCache((isset($_GET['no-cache'])    ? true             : null));
$image->setNewWidth(   (isset($_GET['width'])       ? $_GET['width']   : null));
$image->setNewHeight(  (isset($_GET['height'])      ? $_GET['height']  : null));
$image->setCropToFit(  (isset($_GET['crop-to-fit']) ? true             : null));
$image->setSharpen(    (isset($_GET['sharpen'])     ? true             : null));

// Get the image
$image->getOutput();