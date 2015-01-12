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
    private $settings = array(
            'hitsAlt'   => array(5,10),
            'startPage' => 1
        );
    private $paramsDefault = array(
            'orderby' => 'id',
            'order'   => 'asc',
            'hits'    => 5,
            'page'    => 1
        );
    private $params = array();


    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options Containing details for connecting to the database.
     */
    public function __construct($options) {
        parent::__construct($options);
        parse_str($_SERVER['QUERY_STRING'], $params);
        $this->params = self::ValidateParams($params);
    }


    /**
     * Validate incomming parameters.
     *
     * @param array $params Incomming parameters to validate.
     * @return array Validated parameters.
     */
    private function ValidateParams($params) {
        $validated = array();
        foreach($params as $key => $val) {
            if ((in_array($key, array('id', 'fromYear', 'toYear', 'page', 'hits')) && (is_numeric($val))) ||
                    (in_array($key, array('title'))   && !empty($val)) ||
                    (in_array($key, array('genre'))   && (in_array($val, self::GetGenres()))) ||
                    (in_array($key, array('orderby')) && (in_array($val, array('id', 'title', 'year'))) ||
                    (in_array($key, array('order'))   && (in_array($val, array('asc', 'desc'))))))
                $validated[$key] = $val;
        }
        return array_merge($this->paramsDefault, $validated);
    }


    /**
     * It set, get the parameter, otherwise an empty string.
     *
     * @return string The parameter or just an empty string.
     */
    private function GetParam($param)
    {
        return ( isset($this->params[$param]) ) ? $this->params[$param] : '';
    }



    /**
     * Get the form to search for movies.
     *
     * @return string The form in HTML.
     */
    public function GetSearchForm()
    {
        $html = "<form><fieldset><legend>Sök</legend>".PHP_EOL.
            "<p><label>Sök film: <input type='search' name='title' value='{$this->GetParam('title')}'/></label>".PHP_EOL.
            "<label>Genre: <select name='genre'><option value=''>-- Välj genre --</option>".PHP_EOL;
        foreach($this->GetGenres() as $val) {
            $html .= "<option value='$val'" . ($val==$this->GetParam('genre') ? " selected" : "") . ">$val</option>".PHP_EOL;
        }
        $html .= "</select></label>".PHP_EOL.
            "<label>Från år: <input type='search' name='fromYear' size='6' value='{$this->GetParam('fromYear')}'/></label>".PHP_EOL.
            "<label>Till år: <input type='search' name='toYear' size='6' value='{$this->GetParam('toYear')}'/></label>".PHP_EOL.
            "<input type='submit' name='submit' value='Sök'/>".PHP_EOL.
            "<a href='?'>Rensa sök</a></p>".PHP_EOL.
            "</fieldset></form>".PHP_EOL;
        return $html;
    }


    /**
     * Function to create movietable.
     *
     * @param object $res Result from the SQL-query.
     * @return string List of movies in a HTML-table.
     */
    public function GetTable() {
        $paramsOut = array();

        // SELECT
        $sql = "SELECT * FROM rm_VMovie";

        // Search by title
        if (isset($this->params['title'])) {
            $sql .= " WHERE title LIKE :title";
            $paramsOut[':title'] = "%".$this->params['title']."%";
        }

        // Search by title
        if (isset($this->params['genre'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "genre LIKE :genre";
            $paramsOut[':genre'] = "%".$this->params['genre']."%";
        }

        // Search from year
        if (isset($this->params['fromYear'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "year >= :fromYear";
            $paramsOut[':fromYear'] = $this->params['fromYear'];
        }

        // Search to year
        if (isset($this->params['toYear'])) {
            $sql .= empty($paramsOut) ? " WHERE " : " AND ";
            $sql .= "year <= :toYear";
            $paramsOut[':toYear'] = $this->params['toYear'];
        }

        // Set up for pagination
        $this->settings['hitsSum'] = count(parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut));
        $startNo = ($this->params['page'] - 1) * $this->params['hits'];
        $sql .= " ORDER BY {$this->params['orderby']} {$this->params['order']}";
        $sql .= " LIMIT {$this->params['hits']} OFFSET $startNo";

        // Get query
        $res = parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut);
        if ($this->settings['hitsSum'] < 1) {
            $html = "<p>Inga filmer att visa. :(</p>";
        }
        else {
            $html = "<p>Antal filmer: {$this->settings['hitsSum']}</p>" . PHP_EOL;
            $html .= $this->GetHitsPerPageLink() . PHP_EOL;
            $html .= "<table style='width:100%;'><tr><th></th><th style='width:50%;'>Titel " . self::OrderBy('title') . "</th><th></th><th>År " . self::OrderBy('year') . "</th><th style='width:25%;'>Genre</th></tr>" . PHP_EOL;
            foreach($res AS $key => $val) {
                $image = (isset($val->image)) ? $val->image.".jpg" : "default.jpg";
                $genres = $this->GetGenresAsLinks($val->id);
                $html .= "<tr><td><a href='movies.php?show={$val->id}'><img src='img/img.php?src=movies/{$image}&width=80' alt='{$val->title}' /></a></td><td><a href='movies.php?show={$val->id}'>{$val->title}</a></td><td><a href='http://www.imdb.com/title/{$val->imdb}/'><img src='img/img.php?src=imdb.png&height=18'></a> <a href='http://youtu.be/{$val->youtube}'><img src='img/img.php?src=youtube.png&height=18'></a></td><td>{$val->year}</td><td>$genres</td></tr>" . PHP_EOL;
            }
            $html .= "</table>" . PHP_EOL;
            $html .= $this->GetPageNavigation();
        }
        return $html;
    }


    /**
     * Function to get genres
     *
     * @param mixed $get Determines which genres to get.
     * @return array Genres of choice.
     */
    public function GetGenres($get = null) {
        $sql = "SELECT DISTINCT G.name FROM rm_Genre AS G
            INNER JOIN rm_Movie2Genre AS M2G ON G.id = M2G.idGenre";
        if (is_numeric($get))
            $sql = "SELECT DISTINCT G.name FROM rm_Movie2Genre AS M2G
                INNER JOIN rm_Genre AS G ON M2G.idGenre = G.id
                WHERE M2G.idMovie = $get";
        elseif ($get == "all")
            $sql = "SELECT name FROM rm_Genre";
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        foreach($res as $val) {
            $genres[] = $val->name;
        }
        return $genres;
    }


    /**
     * Function to get genres as links.
     *
     * @param mixed $get Determines which genres to get.
     * @return string Genres of choice as links.
     */
    public function GetGenresAsLinks($get = null) {
        $html = "";
        $genres = $this->GetGenres($get);
        foreach($genres as $genre) {
            $html .= "<a href='?genre=$genre'>$genre</a> ";
        }
        return $html;
    }


    /**
     * Function to get info for a movie
     *
     * @param int $id id of movie
     * @return array info of a movie
     */
    public function GetMovie($id) {
        $sql = "SELECT * FROM rm_Movie WHERE id = ".$id;
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
        foreach($this->settings['hitsAlt'] AS $hitsVal) {
            $pageVal = ceil((($this->params['page']-1)*$this->params['hits']+1)/$hitsVal);
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
        $stopPage = ceil($this->settings['hitsSum']/$this->params['hits']);
        if ($this->settings['startPage'] <> $stopPage) {
            $html  = "<a href='?" . http_build_query(array_merge($_GET, array('page' => $this->settings['startPage']))) . "'>&lt;&lt;</a> ";
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => ($this->params['page'] > $this->settings['startPage'] ? $this->params['page'] - 1 : $this->settings['startPage'])))) . "'>&lt;</a> ";
    
            for($i=$this->settings['startPage']; $i<=$stopPage; $i++) {
                $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => $i))) . "'>$i</a> ";
            }
    
            $html .= "<a href='?" . http_build_query(array_merge($_GET, array('page' => ($this->params['page'] < $stopPage ? $this->params['page'] + 1 : $stopPage)))) . "'>&gt;</a> ";
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
    public function OrderBy($column) {
        return "<span class='orderby'><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"asc"))) . "'>&darr;</a><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"desc"))) . "'>&uarr;</a></span>";
    }
}