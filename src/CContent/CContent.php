<?php
/**
 * Content wrapper, provides a content API for the framework.
 *
 */
class CContent extends CDatabase
{

    /**
     * Members
     */
    public $filter;
    private $content;
    //private $pageUrl = null;
    //private $blogSlug = null;


    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options containing details for connecting to the database.
     *
     */
    public function __construct($options) {
        $this->filter = new CTextFilter();
        parent::__construct($options);
    }


    /**
     * Get the content of the page.
     *
     * @param object $content to link to.
     * @return string with url to display content.
     */
    public function GetContent($type, $param) {
        $sql = "SELECT * FROM Content WHERE type = ?";
        if ($type == 'page') {
            $sql .= " AND url = ?";
        } elseif ($type == 'post' && !is_null($param)) {
            $sql .= " AND slug = ?";
        }
        $sql .= " AND published <= NOW()";
        $sql .= " AND deleted IS NULL;";
        $params = (is_null($param)) ? array($type) : array($type, $param);
        return $this->ExecuteSelectQueryAndFetchAll($sql, $params);
    }


    public function GetAllContent()
    {
        $sql = "SELECT *, (published <= NOW()) AS available
            FROM Content;";
        return $this->ExecuteSelectQueryAndFetchAll($sql);
    }


    /**
     * Get the content of the page.
     *
     * @param object $content to link to.
     * @return string with url to display content.
     */
    public function GetContentByID($id) {
        $sql = "SELECT * FROM Content WHERE id = ?";
        return (array) $this->ExecuteSelectQueryAndFetchAll($sql, array($id))[0];
    }


    public function AddContent()
    {
        // Get variables
        //$slug      = isset($_POST['slug']) ? $_POST['slug']  : null;
        $url       = isset($_POST['url']) ? strip_tags($_POST['url']) : null;
        $url       = empty($url) ? null : $url;
        $type      = isset($_POST['type']) ? strip_tags($_POST['type']) : array();
        $title     = isset($_POST['title']) ? $_POST['title'] : null;
        $slug      = $this->slugify($title);
        $data      = isset($_POST['data']) ? $_POST['data'] : array();
        $filter    = isset($_POST['filter']) ? $_POST['filter'] : array();
        $published = isset($_POST['published']) ? strip_tags($_POST['published']) : '';
        $published = ($this->validateDate($published)) ? $published : date('Y-m-d H:i:s');

        $sql = 'INSERT INTO Content (slug, url, type, title, data, filter, published, created) VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW());';
        $params = array($slug, $url, $type, $title, $data, $filter, $published);
        return $this->ExecuteQuery($sql, $params);
    }


    public function EditContent($id)
    {
        // Get variables
        $slug      = isset($_POST['slug']) ? $_POST['slug']  : null;
        $url       = isset($_POST['url']) ? strip_tags($_POST['url']) : null;
        $url       = empty($url) ? null : $url;
        $type      = isset($_POST['type']) ? strip_tags($_POST['type']) : array();
        $title     = isset($_POST['title']) ? $_POST['title'] : null;
        $data      = isset($_POST['data']) ? $_POST['data'] : array();
        $filter    = isset($_POST['filter']) ? $_POST['filter'] : array();
        $published = isset($_POST['published']) ? strip_tags($_POST['published']) : array();
        $published = ($this->validateDate($published)) ? $published : date('Y-m-d H:i:s');

        $sql = 'UPDATE Content SET
                slug      = ?,
                url       = ?,
                type      = ?,
                title     = ?,
                data      = ?,
                filter    = ?,
                published = ?,
                updated   = NOW()
            WHERE id      = ?;';
        $params = array($slug, $url, $type, $title, $data, $filter, $published, $id);
        return $this->ExecuteQuery($sql, $params);
    }


    public function DeleteContent($id)
    {
        $deleted = isset($_POST['delete']) ? date('Y-m-d H:i:s') : null;
        $sql = 'UPDATE Content SET deleted = ?
            WHERE id = ?;';
        $params = array($deleted, $id);
        return $this->ExecuteQuery($sql, $params);
    }


    public function RestoreContent($id)
    {
        $sql = 'UPDATE Content SET deleted = NULL
            WHERE id = ?;';
        $params = array($id);
        return $this->ExecuteQuery($sql, $params);
    }


    /**
     * Create a link to the content, based on its type.
     *
     * @param object $content to link to.
     * @return string with url to display content.
     */
    public function getUrlToContent($content) {
      switch($content->type) {
        case 'page': return "content_page.php?url={$content->url}"; break;
        case 'post': return "content_post.php?slug={$content->slug}"; break;
        default: return null; break;
      }
    }


    public function GetTitle()
    {
        // Sanitize content before using it.
        $title = htmlentities($content->title, null, 'UTF-8');

        return null;
    }


    public function GetBody()
    {
        // Sanitize content before using it.
        $data = CTextFilter::doFilter(htmlentities($content->data, null, 'UTF-8'), $content->filter);

        return null;
    }


    /**
     * Create a slug of a string, to be used as url.
     *
     * @param string $str the string to format as slug.
     * @returns str the formatted slug. 
     */
    private function slugify($str) {
        $str = mb_strtolower(trim($str));
        $str = str_replace(array('å','ä','ö'), array('a','a','o'), $str);
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = trim(preg_replace('/-+/', '-', $str), '-');
    return $str;
    }


    private function validateDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $d && $d->format('Y-m-d H:i:s') == $date;
    }


    public function CreateContentTable($demo = false)
    {
        $sql = "DROP TABLE IF EXISTS Content;
            CREATE TABLE Content
            (
                id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
                slug CHAR(80) UNIQUE,
                url CHAR(80) UNIQUE,
                type CHAR(80),
                title VARCHAR(80),
                data TEXT,
                filter CHAR(80),
                published DATETIME,
                created DATETIME,
                updated DATETIME,
                deleted DATETIME
            ) ENGINE INNODB CHARACTER SET utf8;
        ";
        if ($demo) {
            $sql .= "INSERT INTO Content (slug, url, type, title, data, filter, published, created) VALUES
                ('hem', 'hem', 'page', 'Hem', 'Detta är min hemsida. Den är skriven i [url=http://en.wikipedia.org/wiki/BBCode]bbcode[/url] vilket innebär att man kan formattera texten till [b]bold[/b] och [i]kursiv stil[/i] samt hantera länkar.\n\nDessutom finns ett filter \'nl2br\' som lägger in <br>-element istället för \\\\n, det är smidigt, man kan skriva texten precis som man tänker sig att den skall visas, med radbrytningar.', 'bbcode,nl2br', NOW(), NOW()),
                ('om', 'om', 'page', 'Om', 'Detta är en sida om mig och min webbplats. Den är skriven i [Markdown](http://en.wikipedia.org/wiki/Markdown). Markdown innebär att du får bra kontroll över innehållet i din sida, du kan formattera och sätta rubriker, men du behöver inte bry dig om HTML.\n\nRubrik nivå 2\n-------------\n\nDu skriver enkla styrtecken för att formattera texten som **fetstil** och *kursiv*. Det finns ett speciellt sätt att länka, skapa tabeller och så vidare.\n\n###Rubrik nivå 3\n\nNär man skriver i markdown så blir det läsbart även som textfil och det är lite av tanken med markdown.', 'markdown', NOW(), NOW()),
                ('blogpost-1', NULL, 'post', 'Välkommen till min blogg!', 'Detta är en bloggpost.\n\nNär det finns länkar till andra webbplatser så kommer de länkarna att bli klickbara.\n\nhttp://dbwebb.se är ett exempel på en länk som blir klickbar.', 'link,nl2br', NOW(), NOW()),
                ('blogpost-2', NULL, 'post', 'Nu har sommaren kommit', 'Detta är en bloggpost som berättar att sommaren har kommit, ett budskap som kräver en bloggpost.', 'nl2br', NOW(), NOW()),
                ('blogpost-3', NULL, 'post', 'Nu har hösten kommit', 'Detta är en bloggpost som berättar att sommaren har kommit, ett budskap som kräver en bloggpost.', 'nl2br', NOW(), NOW());
            ";
        }
        return $this->ExecuteQuery($sql);
    }

}
