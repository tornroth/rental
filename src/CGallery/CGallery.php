<?php
/**
 * A CGallery class to view images in a gallery.
 *
 */
class CGallery
{

    // Static variables
    private static $galleryPath;
    private static $galleryBaseurl;

    /**
     * Validate and set static values
     *
     * @param array $init Configuration values.
     */
    public function __construct($init)
    {
        // Validate argument
        is_dir($init['galleryPath'])
            or $this->errorMessage('The gallery dir is not a valid directory.');
        // Set static values
        self::$galleryPath    = $init['galleryPath'];
        self::$galleryBaseurl = $init['galleryBaseurl'];
    }


    /**
     * Create and get a breadcrumb of the gallery query path.
     *
     * @param string $path to the current gallery directory.
     * @return string html with ul/li to display the thumbnail.
     */
    public function getBreadcrumb($path) {
        // Prepare incoming argument and validate
        $path = realpath(self::$galleryPath . DIRECTORY_SEPARATOR . $path);
        substr_compare(self::$galleryPath, $path, 0, strlen(self::$galleryPath)) == 0
            or $this->errorMessage('Security constraint: Source gallery is not directly below the directory '.self::$galleryPath.'.');
        $parts = explode('/', trim(substr($path, strlen(self::$galleryPath) + 1), '/'));
        $breadcrumb = "<ul class='breadcrumb'>\n<li><a href='?'>Hem</a> »</li>\n";

        if(!empty($parts[0])) {
            $combine = null;
            foreach($parts as $part) {
                $combine .= ($combine ? '/' : null) . $part;
                $breadcrumb .= "<li><a href='?path={$combine}'>$part</a> » </li>\n";
            }
        }

        $breadcrumb .= "</ul>\n";
        return $breadcrumb;
    }


    /**
     * Finds out if this will return a image or a gallery with help of other methods.
     *
     * @param string $path to the current source.
     * @return string html with the content.
     */
    public function getGallery($path)
    {
        // Prepare incoming argument and validate
        $path = realpath(self::$galleryPath . DIRECTORY_SEPARATOR . $path);
        substr_compare(self::$galleryPath, $path, 0, strlen(self::$galleryPath)) == 0
            or $this->errorMessage('Security constraint: Source gallery is not directly below the directory '.self::$galleryPath.'.');
        if(is_dir($path)) {
            return $this->readAllItemsInDir($path);
        }
        else if(is_file($path)) {
            return $this->readItem($path);
        }
    }


    /**
     * Read directory and return all items in a ul/li list.
     *
     * @param string $path to the current gallery directory.
     * @param array $validImages to define extensions on what are considered to be valid images.
     * @return string html with ul/li to display the gallery.
     */
    public function readAllItemsInDir($path, $validImages = array('png', 'jpg', 'jpeg')) {
        $files = glob($path . '/*'); 
        $gallery = "<ul class='gallery'>\n";
        $len = strlen(self::$galleryPath);
        $liImg = "";
        $liDir = "";

        foreach($files as $file) {
            $parts = pathinfo($file);
            $href  = str_replace('\\', '/', substr($file, $len + 1));

            // Is this an image or a directory
            if(is_file($file) && in_array($parts['extension'], $validImages)) {
                $item    = "<img src='image/" . self::$galleryBaseurl . $href . "&amp;width=128&amp;height=128&amp;crop-to-fit' alt=''/>";
                $caption = basename($file); 
            }
            elseif(is_dir($file)) {
                $item    = "<img src='image/folder.png' alt=''/>";
                $caption = basename($file) . '/';
            }
            else {
                continue;
            }

            // Avoid to long captions breaking layout
            $fullCaption = $caption;
            if(strlen($caption) > 18) {
                $caption = substr($caption, 0, 10) . '…' . substr($caption, -5);
            }

            // $gallery .= "<li><a href='?path={$href}' title='{$fullCaption}'><figure class='figure overview'>{$item}<figcaption>{$caption}</figcaption></figure></a></li>\n";
            if(is_file($file) && in_array($parts['extension'], $validImages)) {
                $liImg .= "<li><a href='?path={$href}' title='{$fullCaption}'><figure class='figure overview'>{$item}<figcaption>{$caption}</figcaption></figure></a></li>\n";
            }
            elseif(is_dir($file)) {
                $liDir .= "<li><a href='?path={$href}' title='{$fullCaption}'><figure class='figure overview'>{$item}<figcaption>{$caption}</figcaption></figure></a></li>\n";
            }

        }
        $gallery .= $liDir . $liImg . "</ul>\n";

        return $gallery;
    }



    /**
     * Read and return info on choosen item.
     *
     * @param string $path to the current gallery item.
     * @param array $validImages to define extensions on what are considered to be valid images.
     * @return string html to display the gallery item.
     */
    public function readItem($path, $validImages = array('png', 'jpg', 'jpeg')) {
        $parts = pathinfo($path);
        if(!(is_file($path) && in_array($parts['extension'], $validImages))) {
            return "<p>This is not a valid image for this gallery.";
        }

        // Get info on image
        $imgInfo = list($width, $height, $type, $attr) = getimagesize($path);
        $mime = $imgInfo['mime'];
        $gmdate = gmdate("D, d M Y H:i:s", filemtime($path));
        $filesize = round(filesize($path) / 1024); 

        // Get constraints to display original image
        $displayWidth  = $width > 800 ? "&amp;width=800" : null;
        $displayHeight = $height > 600 ? "&amp;height=600" : null;

        // Display details on image
        $len = strlen(self::$galleryPath);
        $href = self::$galleryBaseurl . str_replace('\\', '/', substr($path, $len + 1));
        $item = <<<EOD
<p><img src='image/{$href}{$displayWidth}{$displayHeight}' alt=''/></p>
<p>Original image dimensions are {$width}x{$height} pixels. <a href='image/{$href}'>View original image</a>.</p>
<p>File size is {$filesize}KBytes.</p>
<p>Image has mimetype: {$mime}.</p>
<p>Image was last modified: {$gmdate} GMT.</p>
EOD;

        return $item;
    }




    /**
     * Display error message.
     *
     * @param string $message the error message to display.
     */
    private function errorMessage($message) {
        header("Status: 404 Not Found");
        die('CGallery 404: ' . htmlentities($message));
    }

}