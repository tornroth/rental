<?php
/**
 * Blogcontent wrapper, provides a content API for the framework.
 *
 */
class CPost extends CContent
{

    /**
     * Members
     */
    private $blogSlug = null;


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
     * Get the content of the post.
     *
     * @param object $content to link to.
     * @return string with url to display content.
     */
    public function GetPost($slug = null) {
        $posts = parent::GetContent('post', $slug);
        foreach ($posts as $p) {
            // Sanitizing
            $p->title = htmlentities($p->title, null, 'UTF-8');
            $p->data = $this->filter->doFilter(htmlentities($p->data, null, 'UTF-8'), $p->filter);
        }
        return $posts;
    }

}