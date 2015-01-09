<?php
/**
 * A CImage class to view images in different ways.
 *
 */
class CImage
{

    // Static variables
    private static $imgDir;
    private static $cacheDir;
    private static $maxWidth;
    private static $maxHeight;

    // Incoming variables
    private $src;
    private $verbose;
    private $saveAs;
    private $quality;
    private $ignoreCache;
    private $newWidth;
    private $newHeight;
    private $cropToFit;
    private $sharpen;

    // Variable in class
    private $verboseOutput;



    /**
     * Validate and set static values
     *
     * @param array $init Configuration values.
     */
    public function __construct($init)
    {
        // Validate arguments
        is_dir($init['imgDir'])
            or $this->errorMessage('The image dir is not a valid directory.');
        is_writable($init['cacheDir'])
            or $this->errorMessage('The cache dir is not a writable directory.');
        // Set static values
        self::$imgDir    = $init['imgDir'];
        self::$cacheDir  = $init['cacheDir'];
        self::$maxWidth  = $init['maxWidth'];
        self::$maxHeight = $init['maxHeight'];
    }

    /**
     * Validate and set src value.
     *
     * @param string $value Value to set.
     */
    public function setSrc($value)
    {
        // Validate incoming argument
        isset($value)
            or $this->errorMessage('Must set src-attribute.');
        preg_match('#^[a-z0-9A-Z-_\.\/]+$#', $value)
            or $this->errorMessage('Filename contains invalid characters.');
        $this->src = $value;
    }

    /**
     * Set verbose value.
     *
     * @param string $value Value to set.
     */
    public function setVerbose($value)
    {
        $this->verbose = $value;
        if ($this->verbose) {
            $this->verboseInit();
        }
    }

    /**
     * Validate and set save as value.
     *
     * @param string $value Value to set.
     */
    public function setSaveAs($value)
    {
        // Validate incoming argument
        is_null($value)
            or in_array($value, array('jpg', 'jpeg', 'png'))
            or $this->errorMessage('Not a valid extension to save image as');
        $this->saveAs = $value;
    }

    /**
     * Validate and set quality value.
     *
     * @param string $value Value to set.
     */
    public function setQuality($value)
    {
        // Validate incoming argument
        is_null($value)
            or (is_numeric($value) and $value > 0 and $value <= 100)
            or $this->errorMessage('Quality out of range');
        $this->quality = $value;
    }

    /**
     * Set ignore cache value.
     *
     * @param string $value Value to set.
     */
    public function setIgnoreCache($value)
    {
        $this->ignoreCache = $value;
    }

    /**
     * Validate and set width value.
     *
     * @param string $value Value to set.
     */
    public function setNewWidth($value)
    {
        // Validate incoming argument
        is_null($value)
            or (is_numeric($value) and $value > 0 and $value <= self::$maxWidth)
            or $this->errorMessage('Width out of range');
        $this->newWidth = $value;
    }

    /**
     * Validate and set height value.
     *
     * @param string $value Value to set.
     */
    public function setNewHeight($value)
    {
        // Validate incoming argument
        is_null($value)
            or (is_numeric($value) and $value > 0 and $value <= self::$maxHeight)
            or $this->errorMessage('Height out of range');
        $this->newHeight = $value;
    }

    /**
     * Validate and set crop to fit value.
     *
     * @param string $value Value to set.
     */
    public function setCropToFit($value)
    {
        // Validate incoming argument
        is_null($value)
            or ($value and $this->newWidth and $this->newHeight)
            or $this->errorMessage('Crop to fit needs both width and height to work');
        $this->cropToFit = $value;
    }

    /**
     * Set sharpen value.
     *
     * @param string $value Value to set.
     */
    public function setSharpen($value)
    {
        $this->sharpen = $value;
    }


    /**
     * Call to get the image. If needed, prepare and save cahce.
     *
     */
    public function getOutput()
    {
        // Set path to the image
        $imgPath = realpath(self::$imgDir . $this->src);
        substr_compare(self::$imgDir, $imgPath, 0, strlen(self::$imgDir)) == 0
            or $this->errorMessage('Security constraint: Source image is not directly below the directory '.self::$imgDir.'.');

        // Get information on the image
        $imgInfo = list($width, $height, $type, $attr) = getimagesize($imgPath);
        !empty($imgInfo)
            or $this->errorMessage("The file doesn't seem to be an image.");
        $mime = $imgInfo['mime'];

        // If verbose mode, send some image info
        if($this->verbose) {
            $this->verbose("Image file: {$imgPath}");
            $this->verbose("Image information: ". print_r($imgInfo, true));
            $filesize = filesize($imgPath);
            $this->verbose("Image file size: {$filesize} bytes.");
            $this->verbose("Image width x height (type): {$width} x {$height} ({$type}).");
            $this->verbose("Image mime type: {$mime}.");
        }

        // Calculate new width and height for the image
        $aspectRatio = $width / $height;
        if($this->cropToFit && $this->newWidth && $this->newHeight) {
            $targetRatio = $this->newWidth / $this->newHeight;
            $cropWidth   = $targetRatio > $aspectRatio ? $width : round($height * $targetRatio);
            $cropHeight  = $targetRatio > $aspectRatio ? round($width  / $targetRatio) : $height;
            if($this->verbose) {
                $this->verbose("Crop to fit into box of {$this->newWidth}x{$this->newHeight}. Cropping dimensions: {$cropWidth}x{$cropHeight}.");
            }
        }
        else if($this->newWidth && !$this->newHeight) {
            $this->newHeight = round($this->newWidth / $aspectRatio);
            if($this->verbose) {
                $this->verbose("New width is known {$this->newWidth}, height is calculated to {$this->newHeight}.");
            }
        }
        else if(!$this->newWidth && $this->newHeight) {
            $this->newWidth = round($this->newHeight * $aspectRatio);
            if($this->verbose) {
                $this->verbose("New height is known {$this->newHeight}, width is calculated to {$this->newWidth}.");
            }
        }
        else if($this->newWidth && $this->newHeight) {
            $ratioWidth  = $width  / $this->newWidth;
            $ratioHeight = $height / $this->newHeight;
            $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
            $this->newWidth  = round($width  / $ratio);
            $this->newHeight = round($height / $ratio);
            if($this->verbose) {
                $this->verbose("New width & height is requested, keeping aspect ratio results in {$this->newWidth}x{$this->newHeight}.");
            }
        }
        else {
            $this->newWidth = $width;
            $this->newHeight = $height;
            if($this->verbose) {
                $this->verbose("Keeping original width & heigth.");
            }
        }

        // Creating a filename for the cache
        $parts         = pathinfo($imgPath);
        $fileExtension = $parts['extension'];
        $this->saveAs  = is_null($this->saveAs)    ? $fileExtension : $this->saveAs;
        $quality_      = is_null($this->quality)   ? null : "_q{$this->quality}";
        $cropToFit_    = is_null($this->cropToFit) ? null : "_cf";
        $sharpen_      = is_null($this->sharpen)   ? null : "_s";
        $dirName       = preg_replace('/\//', '-', dirname($this->src));
        $cacheFileName = self::$cacheDir . "-{$dirName}-{$parts['filename']}_{$this->newWidth}_{$this->newHeight}{$quality_}{$cropToFit_}{$sharpen_}.{$this->saveAs}";
        $cacheFileName = preg_replace('/^a-zA-Z0-9\.-_/', '', $cacheFileName);
        if($this->verbose) {
            $this->verbose("Cache file is: {$cacheFileName}");
        }

        // Is there already a valid image in the cache directory, then use it and exit
        $imageModifiedTime = filemtime($imgPath);
        $cacheModifiedTime = is_file($cacheFileName) ? filemtime($cacheFileName) : null;
        if(!$this->ignoreCache && is_file($cacheFileName) && $imageModifiedTime < $cacheModifiedTime) {
            if($this->verbose) {
                $this->verbose("Cache file is valid, output it.");
            }
            // Cached image is valid, go and get it!
            $this->outputImage($cacheFileName);
        }

        // Ok, no valid cache. Prepare to maka a cache image from the original file
        if($this->verbose) {
            $this->verbose("Cache is not valid, process image and create a cached version of it.");
            $this->verbose("File extension is: {$fileExtension}");
        }

        // Open up the original image from file
        switch($fileExtension) {  
            case 'jpg':
            case 'jpeg': 
                $image = imagecreatefromjpeg($imgPath);
                if($this->verbose) {
                    $this->verbose("Opened the image as a JPEG image.");
                }
                break;  
            case 'png':  
                $image = imagecreatefrompng($imgPath);
                if($this->verbose) {
                    $this->verbose("Opened the image as a PNG image.");
                }
                break;  
            default:
                errorPage('No support for this file extension.');
        }

        // Resize the image if needed
        if($this->cropToFit) {
            if($this->verbose) {
                $this->verbose("Resizing, crop to fit.");
            }
            $cropX = round(($width - $cropWidth) / 2);  
            $cropY = round(($height - $cropHeight) / 2);    
            $imageResized = $this->createImageKeepTransparency($this->newWidth, $this->newHeight);
            imagecopyresampled($imageResized, $image, 0, 0, $cropX, $cropY, $this->newWidth, $this->newHeight, $cropWidth, $cropHeight);
            $image = $imageResized;
            $width = $this->newWidth;
            $height = $this->newHeight;
        }
        else if(!($this->newWidth == $width && $this->newHeight == $height)) {
            if($this->verbose) {
                $this->verbose("Resizing, new height and/or width.");
            }
            $imageResized = $this->createImageKeepTransparency($this->newWidth, $this->newHeight);
            imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $this->newWidth, $this->newHeight, $width, $height);
            $image = $imageResized;
            $width = $this->newWidth;
            $height = $this->newHeight;
        }

        // Apply filters and postprocessing of image
        if($this->sharpen) {
            if($this->verbose) {
                $this->verbose("Apply filter: Sharpen.");
            }
            $image = $this->sharpenImage($image);
        }

        // Save the image
        switch($this->saveAs) {
            case 'jpeg':
            case 'jpg':
                if($this->verbose) {
                    $this->verbose("Saving image as JPEG to cache using quality = {$this->quality}.");
                }
                imagejpeg($image, $cacheFileName, $this->quality);
            break;  
        case 'png':  
            if($this->verbose) {
                $this->verbose("Saving image as PNG to cache.");
            }
            // Turn off alpha blending and set alpha flag
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $cacheFileName);  
            break;  
        default:
            $this->errorMessage('No support to save as this file extension.');
            break;
        }
        if($this->verbose) { 
            clearstatcache();
            $cacheFilesize = filesize($cacheFileName);
            $this->verbose("File size of cached file: {$cacheFilesize} bytes."); 
            $this->verbose("Cache file has a file size of " . round($cacheFilesize/$filesize*100) . "% of the original size.");
        }

        // Now is the time to get the new image
        $this->outputImage($cacheFileName);
    }


    /**
     * Display error message.
     *
     * @param string $message the error message to display.
     */
    private function errorMessage($message) {
        header("Status: 404 Not Found");
        die('CImage 404: ' . htmlentities($message));
    }



     /**
     * Init verbose
     *
     */
    private function verboseInit() {
        $query = array();
        parse_str($_SERVER['QUERY_STRING'], $query);
        $url = $query['src'];
        unset($query['src']);
        unset($query['verbose']);
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $imgUrl = end(explode('/', $url));
        $this->verboseOutput = "<html lang='en'>".PHP_EOL.
            "<meta charset='UTF-8'/>".PHP_EOL.
            "<title>CImage verbose mode</title>".PHP_EOL.
            "<h1>Verbose mode</h1>".PHP_EOL.
            "<p><a href=$imgUrl><code>$url</code></a><br>".PHP_EOL.
            "<img src='{$imgUrl}' /></p>".PHP_EOL;
    }



    /**
     * Store log message.
     *
     * @param string $message the log message to display.
     */
    private function verbose($message) {
        $this->verboseOutput .= "<p>" . htmlentities($message) . "</p>" . PHP_EOL;
    }


    /**
     * Display log message and exit.
     *
     */
    private function verboseOutput() {
        echo $this->verboseOutput;
        exit;
    }


    /**
     * Create new image and keep transparency
     *
     * @param resource $image the image to apply this filter on.
     * @return resource $image as the processed image.
     */
    function createImageKeepTransparency($width, $height) {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);  
        return $img;
    }

    /**
     * Output an image together with last modified header, then exit.
     *
     * @param string $file as path to the image.
     */
    private function outputImage($file) {
        $info = getimagesize($file);
        !empty($info)
            or $this->errorMessage("The file doesn't seem to be an image.");
        $mime = $info['mime'];

        $lastModified = filemtime($file);  
        $gmdate = gmdate("D, d M Y H:i:s", $lastModified);

        if($this->verbose) {
            $this->verbose("Memory peak: " . round(memory_get_peak_usage() /1024/1024) . "M");
            $this->verbose("Memory limit: " . ini_get('memory_limit'));
            $this->verbose("Time is {$gmdate} GMT.");
        }

        if(!$this->verbose) {
            header('Last-Modified: ' . $gmdate . ' GMT');
        }
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified){
            if($this->verbose) {
                $this->verbose("Would send header 304 Not Modified, but its verbose mode.");
                $this->verboseOutput();
            }
            header('HTTP/1.0 304 Not Modified');
        } else {  
            if($this->verbose) {
                $this->verbose("Would send header to deliver image with modified time: {$gmdate} GMT, but its verbose mode.");
                $this->verboseOutput();
            }
            header('Content-type: ' . $mime);  
            readfile($file);
        }
        exit;
    }


    /**
     * Sharpen image as http://php.net/manual/en/ref.image.php#56144
     * http://loriweb.pair.com/8udf-sharpen.html
     *
     * @param resource $image the image to apply this filter on.
     * @return resource $image as the processed image.
     */
    private function sharpenImage($image) {
        $matrix = array(
            array(-1,-1,-1,),
            array(-1,16,-1,),
            array(-1,-1,-1,)
        );
        $divisor = 8;
        $offset = 0;
        imageconvolution($image, $matrix, $divisor, $offset);
        return $image;
    }

}