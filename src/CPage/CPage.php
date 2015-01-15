<?php
/**
 * Pagecontent wrapper, provides a content API for the framework.
 *
 */
class CPage extends CContent
{

    /**
     * Members
     */
    private $defaultUrl = "hem";
    

    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options containing details for connecting to the database.
     *
     */
    public function __construct($options) {
        parent::__construct($options);
    }


    /**
     * Get the content of the page.
     *
     * @param object $content to link to.
     * @return string with url to display content.
     */
    public function GetPage($url = null) {
        $url = (is_null($url)) ? $this->defaultUrl : $url;
        $res = parent::GetContent('page', $url);
        isset($res[0]) or die('No page to view.');
        $content = (array) $res[0];

        // Sanitizing
        $content['title'] = htmlentities($content['title'], null, 'UTF-8');
        $content['data'] = $this->filter->doFilter(htmlentities($content['data'], null, 'UTF-8'), $content['filter']);

        return $content;
    }
}