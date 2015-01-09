<?php
/**
 * Wrapper to handle users with login/logout
 *
 */
class CUser {

    /**
     * Members
     */
    private $options;   // Options used when creating the PDO object
    private $db = null; // The PDO object
    private $acronym;   // Users acronym, if user is logged in
    private $name;      // Users name, if user... You know...


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
    }


    /**
     * Creating a PDO object connecting to a choosen database.
     *
     */
    private function ConnectDB() {
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
     * Removes the PDO object and the connect to the database.
     *
     */
    private function DisconnectDB() {
        unset($this->db);
    }


    /**
     * Login user
     *
     * @param string $user username
     * @param string $password
     * @return boolean returns TRUE on success or FALSE on failure. 
     */
    public function Login($user, $password) {
        $success = false;
        $this->ConnectDB();
        $sql = "SELECT acronym, name FROM rm_Users WHERE acronym = ? AND password = md5(concat(?, salt))";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($user, $password));
        $res = $stmt->fetchAll();
        if (!empty($res)) {
            $success = true;
            $this->acronym = $user;
            $this->name = $res[0]->name;
        }
        $this->DisconnectDB();
        return $success;
    }


    /**
     * Logout user
     * 
     * @return boolean returns TRUE on success or FALSE on failure. 
     */
    public function Logout() {
        $this->acronym = null;
        $this->name = null;
        return true;
    }


    /**
     * Returns true if user is logged in, otherwise false.
     * 
     * @return boolean
     */
    public function IsAuth() {
        return (empty($this->acronym)) ? false : true;
    }


    /**
     * Return logged in users acronym
     *
     * @return string with acronym
     */
    public function GetAcronym() {
        return $this->acronym;
    }


    /**
     * Return logged in users name
     *
     * @return string with name
     */
    public function GetName() {
        return $this->name;
    }
}