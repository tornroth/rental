<?php
/**
 * Database wrapper, provides a database API for the framework but hides details of implementation.
 *
 */
class CDatabase {

    /**
     * Members
     */
    private $options;                     // Options used when creating the PDO object
    private $db   = null;                 // The PDO object
    private $stmt = null;                 // The latest statement used to execute a query
    private static $numQueries = 0;       // Count all queries made
    private static $queries    = array(); // Save all queries for debugging purpose
    private static $params     = array(); // Save all parameters for debugging purpose


    /**
     * Constructor creating a PDO object connecting to a choosen database.
     *
     * @param array $options containing details for connecting to the database.
     *
     */
    public function __construct($options) {
        $default = array(
            'dsn'            => null,
            'username'       => null,
            'password'       => null,
            'driver_options' => null,
            'fetch_style'    => PDO::FETCH_OBJ,
        );
        $this->options = array_merge($default, $options);

        try {
            $this->db = new PDO($this->options['dsn'], $this->options['username'], $this->options['password'], $this->options['driver_options']);
        }
        catch(Exception $e) {
            //throw $e; // For debug purpose, shows all connection details
            throw new PDOException('Could not connect to database, hiding connection details.'); // Hide connection details.
        }

        $this->db->SetAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $this->options['fetch_style']); 
    }


    /**
     * Execute a SQL-query and ignore the resultset.
     *
     * @param string $query the SQL query with ?.
     * @param array $params array which contains the argument to replace ?.
     * @param boolean $debug defaults to false, set to true to print out the sql query before executing it.
     * @return boolean returns TRUE on success or FALSE on failure. 
     */
    public function ExecuteQuery($query, $params = array(), $debug=false) {

        self::$queries[] = $query; 
        self::$params[]  = $params; 
        self::$numQueries++;

        if($debug) {
            echo "<p>Query = <br/><pre>{$query}</pre></p><p>Num query = " . self::$numQueries . "</p><p><pre>".print_r($params, 1)."</pre></p>";
        }

        $this->stmt = $this->db->prepare($query);
        return $this->stmt->execute($params);
    }


    /**
     * Execute a select-query with arguments and return the resultset.
     * 
     * @param string $query the SQL query with ?.
     * @param array $params array which contains the argument to replace ?.
     * @param boolean $debug defaults to false, set to true to print out the sql query before executing it.
     * @return array with resultset.
     */
    public function ExecuteSelectQueryAndFetchAll($query, $params=array(), $debug=false) {
 
        self::$queries[] = $query; 
        self::$params[]  = $params; 
        self::$numQueries++;
 
        if($debug) {
           echo "<p>Query = <br/><pre>{$query}</pre></p><p>Num query = " . self::$numQueries . "</p><p><pre>".print_r($params, 1)."</pre></p>";
        }
 
        $this->stmt = $this->db->prepare($query);
        $this->stmt->execute($params);
        return $this->stmt->fetchAll();
    }


    /**
     * Save debug information in session, useful as a flashmemory when redirecting to another page.
     * 
     * @param string $debug enables to save some extra debug information.
     */
    public function SaveDebug($debug=null) {
        if($debug) {
            self::$queries[] = $debug;
            self::$params[] = null;
        }

        self::$queries[] = 'Saved debuginformation to session.';
        self::$params[] = null;

        $_SESSION['CDatabase']['numQueries'] = self::$numQueries;
        $_SESSION['CDatabase']['queries']    = self::$queries;
        $_SESSION['CDatabase']['params']     = self::$params;
    }


    /**
     * Return last insert id.
     */
    public function LastInsertId() {
        return $this->db->lastInsertid();
    }


    /**
     * Return rows affected of last INSERT, UPDATE, DELETE
     */
    public function RowCount() {
        return is_null($this->stmt) ? $this->stmt : $this->stmt->rowCount();
    }


    /**
     * Get a html representation of all queries made, for debugging and analysing purpose.
     * 
     * @return string with html.
     */
    public function Dump() {
        $html  = '<p><i>You have made ' . self::$numQueries . ' database queries.</i></p><pre>';
        foreach(self::$queries as $key => $val) {
            $params = empty(self::$params[$key]) ? null : htmlentities(print_r(self::$params[$key], 1)) . '<br/></br>';
            $html .= $val . '<br/></br>' . $params;
        }
        return $html . '</pre>';
    }
}