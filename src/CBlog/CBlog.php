<?php
/**
 * Blog wrapper, provides a blog for the framework
 *
 */
class CBlog extends CDatabase
{

    /**
     * Members
     */
    private $settings = array(
            'startPage' => 1
        );
    private $paramsDefault = array(
            'hits'      => 7,
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
        foreach($params as $key => $val) {
            if ( in_array($key, array('add', 'save')) ||
                (in_array($key, array('show', 'category', 'edit', 'remove', 'page')) && is_numeric($val)) )
                $validated[$key] = $val;
        }
        return array_merge($this->paramsDefault, $validated);
    }


    /**
     * Set the view and title of the page
     */
    private function SetPageView() {
        $this->settings['pageView'] = 'news';
        $this->params['pageTitle']  = 'Nyheter';
        $views = array(
            'category' => 'Kategori'.(is_numeric($this->GetParam('category')) ? ": {$this->GetCategory($this->GetParam('category'))}" : ''),
            'edit'     => 'Redigera inlägg',
            'remove'   => 'Ta bort inlägg',
            'add'      => 'Lägg till inlägg',
            'save'     => 'Inlägget sparas...',
            'show'     => (is_numeric($this->GetParam('show')) ? $this->GetPostTitle($this->GetParam('show')) : 'Nyhet')
            );
        foreach (array_intersect_key($views, $this->params) as $key => $val) {
            $this->settings['pageView'] = $key;
            $this->params['id'] = $this->GetParam($key);
            if ( is_numeric($val) )
                $this->params['pageTitle'] = $this->GetPostTitle($val);
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
            case 'news':
            case 'category':
                $html = $this->GetPosts();
                break;
            case 'show':
                $html = $this->GetPost();
                break;
            case 'edit':
            case 'add':
                $html = isset($_POST['save']) ? $this->SavePost() : $this->GetPostForm();
                break;
            case 'remove':
                $html = $this->RemovePost();
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
     * Get the title of the post
     *
     * @param int $id Id of the post to get as title.
     * @return string Title of the post.
     */
    private function GetPostTitle($id) {
        if ( is_numeric($id) ) {
            $sql = "SELECT title FROM rm_BlogPost WHERE postId = ".$id;
            $res = parent::ExecuteSelectQueryAndFetchAll($sql);
            return $res[0]->title;
        }
        else
            return null;
    }


    /**
     * Get the form to add/edit post.
     *
     * @return string The form in HTML.
     */
    private function GetPostForm() {
        // Prepare values, either from post or empty
        if ( $this->settings['pageView'] == 'edit' ) {
            $post = (array)$this->GetPostFromDatabase();
            $legend = "Uppdatera inlägg";
        }
        else {
            $post['authorId'] = $_SESSION['auth']->GetId();
            foreach (array('postId', 'categoryId', 'title', 'content') as $val)
                $post[$val] = null;
            $legend = "Skriv inlägg";
        }
        if ( !isset($output) )
            $output = null;

        // Generate form as HTML
        $html = "<form method=post><fieldset><legend>$legend</legend>".PHP_EOL.
            "<input type='hidden' name='postId' value='{$post['postId']}'/>".PHP_EOL.
            "<input type='hidden' name='authorId' value='{$post['authorId']}'/>".PHP_EOL.
            "<output>$output</output>".PHP_EOL.
            "<p><label>Titel:<br/><input type='text' name='title' value='{$post['title']}'/></label></p>".PHP_EOL.
            "<p><label>Kategori:<br/><select name='categoryId'>".PHP_EOL;
        // Get categories as options
        foreach($this->GetCategory() as $key => $val) {
            $html .= "<option value='$key'" . ($key == $post['categoryId'] ? " selected" : "") . ">$val</option>".PHP_EOL;
        }
        $html .= "</select></label></p>".PHP_EOL.
            "<p><label>Synopsis:<br/><textarea name='content' rows='8' cols='80'>{$post['content']}</textarea></label></p>".PHP_EOL.
            "<p><input type='submit' name='save' value='Spara'/> <input type='reset' value='Återställ'/> <input type='button' onClick='javascript:window.history.back();return false;' value='Gå tillbaka'/></p>".PHP_EOL.
            "</fieldset></form>".PHP_EOL;

        return $html;
    }


    /**
     * Function to get posts.
     *
     * @return string News in HTML.
     */
    private function GetPosts() {
        $paramsOut = array();

        // SELECT
        $sql = "SELECT * FROM rm_VBlogPost";

        // Search by category
        if (isset($this->params['category'])) {
            $sql .= " WHERE categoryId = :category";
            $paramsOut[':category'] = $this->params['category'];
        }

        // Set up for pagination
        $this->settings['hitsSum'] = count(parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut));
        $startNo = ($this->params['page'] - 1) * $this->params['hits'];
        $sql .= " LIMIT {$this->params['hits']} OFFSET $startNo";

        // Get query
        $res = parent::ExecuteSelectQueryAndFetchAll($sql, $paramsOut);
        if ($this->settings['hitsSum'] < 1) {
            $html = "<p>Inga nyheter att visa. :(</p>";
        }
        else {
            $html = "<div style='width:75%;' >".PHP_EOL;
            foreach($res AS $val) {
                $html .= "<div><h2 style='margin-bottom:6px;'><a href='?show={$val->postId}' style='text-decoration:none;'>{$val->title}</a></h2>".PHP_EOL.
                "<p style='font-size:small;margin:0;'>{$val->added} | {$val->name} | <a href='?category={$val->categoryId}'>{$val->catName}</a></p>".PHP_EOL.
                "<p>". ((strlen(strip_tags($val->content)) > 180) ? substr(strip_tags($val->content,'<br><p>'), 0, 180)."... <a href='?show={$val->postId}' style='font-size:small;font-style:italic;text-decoration:none;'>Läs mer &raquo;</a>" : $val->content ) ."</p>".PHP_EOL.
                "</div>".PHP_EOL;
            }
            $html .= $this->GetPageNavigation();
            $html .= "</div>" . PHP_EOL;
        }

        // Create adminpanel
        if ( $this->settings['admin'] ) {
            $admin = "<p>Admin: <a href='news.php?add'>Skriv inlägg</a></p>".PHP_EOL;
            $html = $admin . $html;
        }

        return $html;
    }


    /**
     * Function to get post
     *
     * @return string The post as HTML
     */
    private function GetPost() {
        // Get data from database
        $info = $this->GetPostFromDatabase();

        // Create HTML for post view
        $html = "<div style='float:left;width:25%;'><img src='img/img.php?src=news.jpg' /></div>".PHP_EOL.
            "<div style='float:left;width:75%;'><p style='font-size:small;'>{$info->added} | Av: {$info->name} | ".
            "Kategori: <a href='?category={$info->categoryId}'>{$info->catName}</a></p>".PHP_EOL.
            "<p>{$info->content}</p></div>".PHP_EOL.
            "<div style='clear:both;'></div>".PHP_EOL;

        // Create adminpanel
        if ( $this->settings['admin'] ) {
            $admin = "<p>Admin: <a href='news.php?edit={$this->params['id']}'>Redigera inlägg</a> | <a href='news.php?remove={$this->params['id']}'>Radera inlägg</a> | <a href='news.php?add'>Skriv inlägg</a></p>".PHP_EOL;
            $html = $admin . $html;
        }

        return $html;
    }


    /**
     * Function to get data for a post from database.
     *
     * @return object The post in a stdClass object.
     */
    private function GetPostFromDatabase() {
        // Get data from database
        $sql = "SELECT * FROM rm_VBlogPost WHERE postId = ".$this->GetParam('id');
        $res = parent::ExecuteSelectQueryAndFetchAll($sql);
        return $res[0];
    }


    /**
     * Save data for a post
     *
     * @return string Info in HTML
     */
    private function SavePost() {
        // The post must have a title
        if ( empty($_POST['title']) )
            return "<p>Inlägget måste ha en titel. <a href='#' onclick='javascript:history.back();return false;'>Gå tillbaka</a></p>";

        // Get and prepare values
        $values = $_POST;
        unset($values['save']);
        $values['title']   = "'".htmlentities(strip_tags($values['title']), ENT_QUOTES)."'";
        $values['content'] = "'".htmlentities($values['content'], ENT_QUOTES)."'";

        // SQL for insert new post
        if ( empty($values['postId']) ) {
            unset($values['postId']);
            $sql = "INSERT INTO rm_BlogPost (".implode(", ", array_keys($values)).") VALUES ".
                "(".implode(", ", array_values($values)).")";
        }
        // SQL for update post
        else {
            $postId = $values['postId'];
            unset($values['postId']);
            $sql = "UPDATE rm_BlogPost SET ";
            foreach ($values as $key => $val) {
                $sql .= $key ." = ". $val .", " ;
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE postId = $postId";
        }

        // Execute the SQL
        parent::ExecuteQuery($sql);

        // Get id for new post
        if ( !isset($postId) )
            $postId = parent::LastInsertId();

        // Return some content
        $html = "<p>Kanon, det fungerade!</p>".
            "<p>Du kan nu <a href='./news.php?show=$postId'>visa</a> eller <a href='./news.php?edit=$postId'>redigera</a> inlägget, <a href='./news.php?add'>skriva nytt</a> inlägg eller gå tillbaka till <a href='./news.php'>nyhetssidan</a>.</p>";
        return $html;
    }


    /**
     * Remove a post
     *
     * @return string Info in HTML
     */
    private function RemovePost() {
        // Remove post
        if ( isset($_POST['remove']) ) {
            $sql = "DELETE FROM rm_BlogPost WHERE postId = {$this->GetParam('id')}";
            parent::ExecuteQuery($sql);

            $html = "<p>Inlägget är nu borttaget.</p>".
                "<p>Du kan nu <a href='?add'>lägga till</a> nytt inlägg eller gå tillbaka till <a href='./news.php'>nyhetslistan</a>.</p>";
        }
        // Form for remove
        else {
            $html = "<form method=post><fieldset><legend>Radera inlägg</legend>".
                "<p>Vill du verkligen ta bort inlägget \"{$this->GetPostTitle($this->GetParam('id'))}\"?</p>".
                "<input type='submit' name='remove' value='Ta bort'/>".
                " <input type='button' onClick='javascript:window.history.back();return false;' value='Gå tillbaka'/>".
                "</fieldset></form>";
        }
        return $html;
    }


    /**
     * Function to get category name by id, id by name.
     * If no parameter is given, an array with all categories will be returned.
     *
     * @param mixed $get Determines which categories to get.
     * @return string|int|array Name of category in a string, category id as int or names of categories in an array.
     */
    private function GetCategory($get = null) {
        // SQL to get categorynames to an array
        $sql = "SELECT catId, catName FROM rm_BlogCategory";
        foreach(parent::ExecuteSelectQueryAndFetchAll($sql) as $val)
            $categories[$val->catId] = $val->catName;

        // Return name for a specific category id
        if (is_numeric($get) && isset($categories[$get]))
            return $categories[$get];

        // Find id for a category name
        $catSearch = array_search($get, $categories);

        // Return found id or whole array
        return $catSearch ? $catSearch : $categories;
    }


    /**
     * Create navigation among pages.
     *
     * @return string Navigation in HTML.
     */
    private function GetPageNavigation() {
        $html = "";
        if ( $this->GetParam('page') > 1 ) {
            $prev = $this->GetParam('page')-1;
            $html = "<div style='float:left;width:40%;'><a href='?page=$prev' style='text-decoration:none;'>&laquo; Nyare nyheter</a></div>".PHP_EOL;
        }
        $last = ceil($this->settings['hitsSum']/$this->params['hits']);
        if ( $this->GetParam('page') < $last) {
            $next = $this->GetParam('page')+1;
            $html .= "<div style='float:right;width:40%;text-align:right;' ><a href='?page=$next' style='text-decoration:none;'>Äldre nyheter &raquo;</a></div>".PHP_EOL;
        }
        $html .= "<div style='clear:both;'></div>".PHP_EOL;
        return $html;
    }
}