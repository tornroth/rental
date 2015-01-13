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
            'orderby'   => 'added',
            'order'     => 'desc',
            'hits'      => 5,
            'page'      => 1
        );
    private $params = array();


    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options Containing details for connecting to the database.
     */
    public function __construct($options) {
        parent::__construct($options);

        // Get and validate parameters
        parse_str($_SERVER['QUERY_STRING'], $params);
        $this->params = self::ValidateParams($params);

        // Set view for page
        $this->SetPageView();

        // Set admin
        $this->settings['admin'] = ( isset($_SESSION['auth']) && $_SESSION['auth']->IsAuth() ) ? true : false;
    }


    /**
     * Validate incoming parameters.
     *
     * @param array $params Incomming parameters to validate.
     * @return array Validated parameters.
     */
    private function ValidateParams($params) {
        $validated = array();
        $numericParams = array('show', 'edit', 'remove', 'fromYear', 'toYear', 'page', 'hits');
        foreach($params as $key => $val) {
            if ( in_array($key, array('add', 'save')) ||
                (in_array($key, $numericParams)   && is_numeric($val)) ||
                (in_array($key, array('title'))   && !empty($val)) ||
                (in_array($key, array('genre'))   && in_array($val, self::GetGenres())) ||
                (in_array($key, array('orderby')) && in_array($val, array('title', 'year'))) ||
                (in_array($key, array('order'))   && in_array($val, array('asc', 'desc'))) )
                $validated[$key] = $val;
        }
        return array_merge($this->paramsDefault, $validated);
    }


    /**
     * Set the view and title of the page
     */
    private function SetPageView() {
        $this->settings['pageView'] = 'table';
        $this->params['pageTitle'] = 'Filmer';
        $views = array(
            'edit'   => 'Redigera film',
            'remove' => 'Ta bort film',
            'add'    => 'Lägg till film',
            'save'   => 'Filmen sparas...',
            'show'   => $this->GetParam('show')
            );
        foreach (array_intersect_key($views, $this->params) as $key => $val) {
            $this->settings['pageView'] = $key;
            $this->params['id'] = $this->GetParam($key);
            if ( is_numeric($val) )
                $this->params['pageTitle'] = $this->GetMovieTitle($val);
            else
                $this->params['pageTitle'] = $val;
        }
    }


    /**
     * Get the title of the page
     *
     * @return string The pagetitle.
     */
    public function GetPageTitle() {
        return $this->GetParam('pageTitle');
    }


    /**
     * Get the content of the page
     *
     * @return string The content.
     */
    public function GetPageContent() {
        switch ($this->settings['pageView']) {
            case 'table':
                $html = $this->GetTable();
                break;
            case 'show':
                $html = $this->GetMovie();
                break;
            case 'edit':
            case 'add':
                $html = isset($_POST['save']) ? $this->SaveMovie() : $this->GetMovieForm();
                break;
            case 'remove':
                $html = $this->RemoveMovie();
                break;
            default:
                return;
        }

        return $html;
    }


    /**
     * It set, get the parameter, otherwise an empty string.
     *
     * @return string The parameter or just an empty string.
     */
    private function GetParam($param) {
        return isset($this->params[$param]) ? $this->params[$param] : '';
    }


    /**
     * Get the title of the movie
     *
     * @param int $id Id of the movie to get as title.
     * @return string Title of the movie.
     */
    private function GetMovieTitle($id) {
        if ( is_numeric($id) ) {
            $sql = "SELECT title FROM rm_Movie WHERE id = ".$id;
            $res = parent::ExecuteSelectQueryAndFetchAll($sql);
            return $res[0]->title;
        }
        else
            return null;
    }


    /**
     * Get the form to search for movies.
     *
     * @return string The form in HTML.
     */
    private function GetSearchForm() {
        // Generate form as HTML
        $html = "<form><fieldset><legend>Sök</legend>".PHP_EOL.
            "<p><label>Sök film: <input type='search' name='title' value='{$this->GetParam('title')}'/></label>".PHP_EOL.
            "<label>Genre: <select name='genre'><option value=''>-- Välj genre --</option>".PHP_EOL;
        // Get all genres in use as options
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
     * Get the form to search for movies.
     *
     * @return string The form in HTML.
     */
    private function GetMovieForm() {
        // Prepare values, either from movie or all is empty
        if ( $this->settings['pageView'] == 'edit' ) {
            $movie = (array)$this->GetMovieFromDatabase();
            $genres = $this->GetGenres($movie['id']);
            $legend = "Uppdatera info";
        }
        else {
            $values = array('id', 'title', 'year', 'imdb', 'youtube', 'image', 'plot', 'price');
            foreach ($values as $val)
                $movie[$val] = null;
            $genres = array();
            $legend = "Skriv in info";
        }
        if ( !isset($output) )
            $output = null;

        // Generate form as HTML
        $html = "<form method=post><fieldset><legend>$legend</legend>".PHP_EOL.
            "<input type='hidden' name='id' value='{$movie['id']}'/>".PHP_EOL.
            "<output>$output</output>".PHP_EOL.
            "<p><label>Titel:<br/><input type='text' name='title' value='{$movie['title']}'/></label></p>".PHP_EOL.
            "<p><label>År:<br/><input type='text' name='year' value='{$movie['year']}'/></label></p>".PHP_EOL.
            "<p><label>IMDB:<br/>http://www.imdb.com/title/<input type='text' name='imdb' value='{$movie['imdb']}'/>/</label></p>".PHP_EOL.
            "<p><label>Youtube:<br/>http://youtu.be/<input type='text' name='youtube' value='{$movie['youtube']}'/></label></p>".PHP_EOL.
            "<p><label>Bild:<br/><input type='text' name='image' value='{$movie['image']}'/>.jpg</label></p>".PHP_EOL.
            "<p><label>Synopsis:<br/><textarea name='plot' rows='4' cols='50'>{$movie['plot']}</textarea></label></p>".PHP_EOL.
            "<p><label>Pris:<br/><input type='text' name='price' value='{$movie['price']}'/> kr</label></p>".PHP_EOL.
            "<p><label>Genre:<br/><select name='genre[]' multiple='multiple' size='8'>".PHP_EOL;
        // Get genres as options
        foreach($this->GetGenres('all') as $val) {
            $html .= "<option value='$val'" . (in_array($val, $genres) ? " selected" : "") . ">$val</option>".PHP_EOL; // " . ($val==$this->GetParam('genre') ? " selected" : "") . "
        }
        $html .= "</select><br/><span style='font-size:small;'>Välj flera genom att hålla inne CTRL (Win) eller Command (Mac).</span></label></p>".PHP_EOL.
            "<p><input type='submit' name='save' value='Spara'/> <input type='reset' value='Återställ'/> <input type='button' onClick='javascript:window.history.back();return false;' value='Gå tillbaka'/></p>".PHP_EOL.
            "</fieldset></form>".PHP_EOL;

        return $html;
    }


    /**
     * Function to create movietable.
     *
     * @return string List of movies in a HTML-table.
     */
    private function GetTable() {
        $paramsOut = array();

        // SELECT
        $sql = "SELECT * FROM rm_VMovie";

        // Search by title
        if (isset($this->params['title'])) {
            $sql .= " WHERE title LIKE :title";
            $paramsOut[':title'] = "%".$this->params['title']."%";
        }

        // Search by genre
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
            $html .= "<table style='width:100%;'><tr><th></th>".
                "<th style='width:50%;'>Titel " . self::OrderBy('title') . "</th>".
                "<th></th>".
                "<th>År " . self::OrderBy('year') . "</th>".
                "<th style='width:25%;'>Genre</th></tr>".PHP_EOL;
            foreach($res AS $key => $val) {
                $image = (file_exists(realpath(dirname(__FILE__)."/../../webroot/img/movies/.")."/{$val->image}.jpg") ) ? $val->image.".jpg" : "default.jpg";
                $imdb = empty($val->imdb) ? "" : "<a href='http://www.imdb.com/title/{$val->imdb}/'><img src='img/img.php?src=imdb.png&height=18'></a>";
                $youtube = empty($val->youtube) ? "" : "<a href='http://youtu.be/{$val->youtube}'><img src='img/img.php?src=youtube.png&height=18'></a>";
                $genres = $this->GetGenresAsLinks($val->id);
                $html .= "<tr><td><a href='movies.php?show={$val->id}'><img src='img/img.php?src=movies/{$image}&width=80' alt='{$val->title}' /></a></td>".
                    "<td><a href='movies.php?show={$val->id}'>{$val->title}</a></td>".
                    "<td>$imdb $youtube</td>".
                    "<td>{$val->year}</td>".
                    "<td>$genres</td></tr>".PHP_EOL;
            }
            $html .= "</table>" . PHP_EOL;
            $html .= $this->GetPageNavigation();
        }

        // Get searchpanel
        $html = $this->GetSearchForm() . $html;

        // Create adminpanel
        if ( $this->settings['admin'] ) {
            $admin = "<p>Admin: <a href='movies.php?add'>Lägg till film</a></p>".PHP_EOL;
            $html = $admin . $html;
        }

        return $html;
    }


    /**
     * Function to get info for a movie
     *
     * @return string Info of a movie as HTML
     */
    private function GetMovie() {
        // Get data from database
        $info = $this->GetMovieFromDatabase();
        $image = (file_exists(realpath(dirname(__FILE__)."/../../webroot/img/movies/.")."/{$info->image}.jpg") ) ? $info->image.".jpg" : "default.jpg";
        $imdb = empty($info->imdb) ? "" : "<a href='http://www.imdb.com/title/{$info->imdb}/'><img src='img/img.php?src=imdb.png&height=14'></a>";
        $youtube = empty($info->youtube) ? "" : "<iframe width='560' height='315' src='//www.youtube.com/embed/{$info->youtube}' frameborder='0' allowfullscreen></iframe>";

        // Create HTML for movie
        $html = "<div style='float:left;width:25%;'><img src='img/img.php?src=movies/$image&width=214' /></div>".PHP_EOL.
            "<div style='float:left;width:75%;'><p>{$info->plot}</p>".PHP_EOL.
            "<p style='font-size:small;'>({$info->year}) {$this->GetGenresAsLinks($this->GetParam('id'))} $imdb</p>".PHP_EOL.
            "<p>Pris: {$info->price} kr</p>".PHP_EOL.
            "$youtube</div>".PHP_EOL.
            "<div style='clear:both;'></div>".PHP_EOL;

        // Create adminpanel
        if ( $this->settings['admin'] ) {
            $admin = "<p>Admin: <a href='movies.php?edit={$this->params['id']}'>Redigera film</a> | <a href='movies.php?remove={$this->params['id']}'>Radera film</a> | <a href='movies.php?add'>Lägg till film</a></p>".PHP_EOL;
            $html = $admin . $html;
        }

        return $html;
    }


    /**
     * Function to get info for a movie.
     *
     * @return object Info of a movie in a stdClass object.
     */
    private function GetMovieFromDatabase() {
        // Get data from database
        $sql = "SELECT * FROM rm_Movie WHERE id = ".$this->GetParam('id');
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        return $res[0];
    }


    /**
     * Save data for a movie
     *
     * @return string Info in HTML
     */
    private function SaveMovie() {
        // Movie must have title and at least one genre
        if ( empty($_POST['title']) )
            return "<p>Filmen måste ha en titel. <a href='#' onclick='javascript:history.back();return false;'>Gå tillbaka</a></p>";
        if ( empty($_POST['genre']) )
            return "<p>Filmen måste minst ha en genre vald. <a href='#' onclick='javascript:history.back();return false;'>Gå tillbaka</a></p>";

        // Get values
        $values = $_POST;
        $genres = $values['genre'];
        unset($values['genre']);

        // Unset some values, prepare as string for some others
        foreach ($values as $key => $val) {
            if ( (in_array($key, array('save', 'year', 'price')) && !is_numeric($val)) || empty($val) )
                unset($values[$key]);
            elseif ( !is_numeric($val) )
                $values[$key] = "'".htmlentities(strip_tags($val), ENT_QUOTES)."'";
        }

        // SQL for insert new movie
        if ( empty($values['id']) ) {
            unset($values['id']);
            $sql = "INSERT INTO rm_Movie (".implode(", ", array_keys($values)).") VALUES ".
                "(".implode(", ", array_values($values)).")";
            // $id = parent::LastInsertId();
        }
        // SQL for update movie
        else {
            $id = $values['id'];
            unset($values['id']);
            $sql = "UPDATE rm_Movie SET ";
            foreach ($values as $key => $val) {
                $sql .= $key ." = ". $val .", " ;
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = $id";
        }

        // Execute the SQL
        parent::ExecuteQuery($sql);

        // Get id for new movie ...
        if ( !isset($id) )
            $id = parent::LastInsertId();
        // ... or remove old genres for existing movie
        else {
            $sql = "DELETE FROM rm_Movie2Genre WHERE idMovie = $id";
            parent::ExecuteQuery($sql);
        }

        // Make array of all genres with their id's
        $sql = "SELECT * FROM rm_Genre";
        $allGenres = parent::ExecuteSelectQueryAndFetchAll($sql);
        foreach ($allGenres as $val)
            $genreId[$val->id] = $val->name;

        // Make array with genreId for current movie
        foreach ($genres as $val)
            $insVal[] = "($id, ".array_search($val, $genreId).")";

        // Insert genres into database
        $sql = "INSERT INTO rm_Movie2Genre (idMovie, idGenre) VALUES " . implode(", ", $insVal);
        parent::ExecuteQuery($sql);
        
        // Return some content
        $html = "<p>Kanon, det fungerade!</p>".
            "<p>Du kan nu <a href='./movies.php?show=$id'>visa</a> eller <a href='./movies.php?edit=$id'>redigera</a> filmen, <a href='./movies.php?add'>lägga till</a> ny film eller gå tillbaka till <a href='./movies.php'>filmlistan</a>.</p>";
        return $html;
    }


    /**
     * Delete a movie
     *
     * @return string Info in HTML
     */
    private function RemoveMovie() {
        // Remove movie
        if ( isset($_POST['remove']) ) {
            // Delete genres for movie
            $sql = "DELETE FROM rm_Movie2Genre WHERE idMovie = {$this->GetParam('id')}";
            parent::ExecuteQuery($sql);

            // Delete movie
            $sql = "DELETE FROM rm_Movie WHERE id = {$this->GetParam('id')}";
            parent::ExecuteQuery($sql);

            $html = "<p>Filmen är nu borttagen.</p>".
                "<p>Du kan nu <a href='./movies.php?add'>lägga till</a> ny film eller gå tillbaka till <a href='./movies.php'>filmlistan</a>.</p>";
        }
        // Remove form
        else {
            $html = "<form method=post><fieldset><legend>Radera film</legend>".
                "<p>Vill du verkligen ta bort filmen \"{$this->GetMovieTitle($this->GetParam('id'))}\"?</p>".
                "<input type='submit' name='remove' value='Ta bort'/>".
                " <input type='button' onClick='javascript:window.history.back();return false;' value='Gå tillbaka'/>".
                "</fieldset></form>";
        }
        return $html;
    }


    /**
     * Function to get genres
     *
     * @param mixed $get Determines which genres to get.
     * @return array Genres of choice.
     */
    private function GetGenres($get = null) {
        // SQL to get all genres in use
        $sql = "SELECT DISTINCT G.name FROM rm_Genre AS G
            INNER JOIN rm_Movie2Genre AS M2G ON G.id = M2G.idGenre";

        // SQL to get genre(s) for a movie
        if (is_numeric($get))
            $sql = "SELECT DISTINCT G.name FROM rm_Movie2Genre AS M2G
                INNER JOIN rm_Genre AS G ON M2G.idGenre = G.id
                WHERE M2G.idMovie = $get";

        // SQL to get all genres
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
    private function GetGenresAsLinks($get = null) {
        $html = "";
        $genres = $this->GetGenres($get);
        foreach($genres as $genre) {
            $html .= "<a href='?genre=$genre'>$genre</a> ";
        }
        return $html;
    }


    /**
     * Create links for hits per page.
     *
     * @param array $hits a list of hits-options to display.
     * @return string as a link to this page.
     */
    private function GetHitsPerPageLink() {
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
    private function GetPageNavigation() {
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
    private function OrderBy($column) {
        return "<span class='orderby'><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"asc"))) . "'>&darr;</a><a href='?" . http_build_query(array_merge($_GET, array("orderby"=>$column, "order"=>"desc"))) . "'>&uarr;</a></span>";
    }
}