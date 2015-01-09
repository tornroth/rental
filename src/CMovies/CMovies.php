<?php
/**
 * Movie wrapper, provides a Moivedatabase API for the framework but hides details of implementation.
 *
 */
class CMovies extends CDatabase
{

    /**
     * Members
     */
    private $orderby = 'id';
    private $order = 'ASC';
    private $hitsPerPage = 5;
    private $hitsPerPageAlt = array(5,10);
    private $hitsSum;
    private $page = 1;
    private $startPage = 1;


    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options containing details for connecting to the database.
     *
     */
    public function __construct($options) {
        parent::__construct($options);
        if ((isset($_GET['orderby'])) && (in_array($_GET['orderby'], array('id', 'title', 'year')))) {
            $this->orderby = strtolower($_GET['orderby']);
        }
        if ((isset($_GET['order'])) && (in_array($_GET['order'], array('asc', 'desc')))) {
            $this->order = strtoupper($_GET['order']);
        }
        $this->hitsPerPage = isset($_GET['hits']) ? $_GET['hits'] : $this->hitsPerPage;
        $this->page = isset($_GET['page']) ? $_GET['page'] : $this->page;
    }


    /**
     * Function to create movietable
     *
     * @param object $res result from the SQL-query
     * @return string list of movies in a HTML-table
     */
    public function GetTable($params=array()) {
        $paramsIn = self::ValidateParams($params);
        $paramsOut = array();

        // SELECT
        $sql = "SELECT * FROM rm_VMovie";

        // Search by title
        if (isset($paramsIn['title'])) {
            $sql .= " WHERE title LIKE :title";
            $paramsOut[':title'] = "%".$paramsIn['title']."%";
        }

        // Search by title
        if (isset($paramsIn['genre'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "genre LIKE :genre";
            $paramsOut[':genre'] = "%".$paramsIn['genre']."%";
        }

        // Search from year
        if (isset($paramsIn['fromYear'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "year >= :fromYear";
            $paramsOut[':fromYear'] = $paramsIn['fromYear'];
        }

        // Search to year
        if (isset($paramsIn['toYear'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "year <= :toYear";
            $paramsOut[':toYear'] = $paramsIn['toYear'];
        }

        // Set up for pagination
        $this->hitsSum = count(parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut));
        $startNo = ($this->page - 1) * $this->hitsPerPage;
        $sql .= " ORDER BY {$paramsIn['orderby']} {$paramsIn['order']}";
        $sql .= " LIMIT $this->hitsPerPage OFFSET $startNo";

        // Get query
        $res = parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut);
        if ($this->hitsSum < 1) {
            $html = "<p>Inga filmer att visa. :(</p>";
        }
        else {
            $html = "<p>Antal filmer: $this->hitsSum</p>" . PHP_EOL;
            $html .= $this->GetHitsPerPageLink() . PHP_EOL;
            $html .= "<table><tr><th>Bild</th><th>Titel " . self::OrderBy('title') . "</th><th></th><th>År " . self::OrderBy('year') . "</th><th>Genre</th></tr>" . PHP_EOL;
            foreach($res AS $key => $val) {
                $image = (isset($val->image)) ? $val->image.".jpg" : "default.jpg";
                $html .= "<tr><td><a href='movies.php?show={$val->id}'><img src='img/img.php?src=movies/{$image}&width=80' alt='{$val->title}' /></a></td><td><a href='movies.php?show={$val->id}'>{$val->title}</a></td><td><a href='http://www.imdb.com/title/{$val->imdb}/'><img src='img/imdb.png' height='18'></a> <a href='http://youtu.be/{$val->youtube}'><img src='img/youtube.png' height='18'></a></td><td>{$val->year}</td><td>{$val->genre}</td></tr>" . PHP_EOL;
            }
            $html .= "</table>" . PHP_EOL;
            $html .= $this->GetPageNavigation();
        }
        return $html;
    }


    /**
     * Function to get genres
     *
     * @return array $genres all genres
     */
    public function GetGenres() {
        $sql = "SELECT DISTINCT G.name FROM rm_Genre AS G
            INNER JOIN rm_Movie2Genre AS M2G ON G.id = M2G.idGenre";
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        foreach($res as $val) {
            $genres[] = $val->name;
        }
        return $genres;
    }


    /**
     * Function to create genres as links
     *
     * @return string $html genres as links
     */
    public function GetGenreLinks() {
        $html = null;
        $sql = "SELECT DISTINCT G.name FROM rm_Genre AS G
            INNER JOIN rm_Movie2Genre AS M2G ON G.id = M2G.idGenre";
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        foreach($res as $val) {
            $html .= "<a href=?genre={$val->name}>{$val->name}</a> ";
        }
        return $html;
    }


    /**
     * Function to get genres
     *
     * @param int $id id of movie
     * @return string title of movie
     */
    public function GetMovie($id) {
        $sql = "SELECT * FROM rm_VMovie WHERE id = ".$id;
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        return $res[0];
    }


    /**
     * Create links for hits per page.
     *
     * @param array $hits a list of hits-options to display.
     * @return string as a link to this page.
     */
    public function GetHitsPerPageLink() {
        $html = "Träffar per sida: ";
        foreach($this->hitsPerPageAlt AS $hitsVal) {
            $pageVal = ceil((($this->page-1)*$this->hitsPerPage+1)/$hitsVal);
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('hits' => $hitsVal, 'page' => $pageVal))) . "'>$hitsVal</a> ";
        }
        return $html;
    }


    /**
     * Create navigation among pages.
     *
     * @param integer $hits per page.
     * @param integer $page current page.
     * @param integer $max number of pages. 
     * @param integer $min is the first page number, usually 0 or 1. 
     * @return string as a link to this page.
     */
    public function GetPageNavigation() {
        $html = "";
        $stopPage = ceil($this->hitsSum/$this->hitsPerPage);
        if ($this->startPage <> $stopPage) {
            $html  = "<a href='?" . http_build_query(array_merge($_GET, array('page' => $this->startPage))) . "'>&lt;&lt;</a> ";
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => ($this->page > $this->startPage ? $this->page - 1 : $this->startPage)))) . "'>&lt;</a> ";
    
            for($i=$this->startPage; $i<=$stopPage; $i++) {
                $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => $i))) . "'>$i</a> ";
            }
    
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => ($this->page < $stopPage ? $this->page + 1 : $stopPage)))) . "'>&gt;</a> ";
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => $stopPage))) . "'>&gt;&gt;</a> ";
        }
        return $html;
    }


    /**
     * Function to create links for sorting
     *
     * @param string $column the name of the database column to sort by
     * @return string with links to order by column.
     */
    public function ValidateParams($params) {
        $validated = null;
        foreach($params as $key => $val) {
            if ((in_array($key, array('id', 'fromYear', 'toYear', 'page', 'hits')) && (is_numeric($val))) ||
                (in_array($key, array('title')) && !empty($val)) ||
                (in_array($key, array('genre')) && (in_array($val, self::GetGenres()))) ||
                (in_array($key, array('orderby')) && (in_array($val, array('id', 'title', 'year'))) ||
                (in_array($key, array('order')) && (in_array($val, array('asc', 'desc')))))) {
                $validated[$key] = $val;
            }
        }
        if (!isset($validated['orderby'])) {
            $validated['orderby'] = $this->orderby;
        }
        if (!isset($validated['order'])) {
            $validated['order'] = $this->order;
        }
        if (!isset($validated['hits'])) {
            $validated['hits'] = $this->hitsPerPage;
        }
        if (!isset($validated['page'])) {
            $validated['page'] = $this->page;
        }
        return $validated;
    }


    /**
     * Function to create links for sorting
     *
     * @param string $column the name of the database column to sort by
     * @return string with links to order by column.
     */
    public function OrderBy($column) {
        return "<span class='orderby'><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"asc"))) . "'>&darr;</a><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"desc"))) . "'>&uarr;</a></span>";
    }
}