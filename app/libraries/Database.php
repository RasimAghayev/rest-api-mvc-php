<?php

/*
*  PDO DATABASE CLASS
*  Connects Database Using PDO
 *  Creates Prepeared Statements
 * 	Binds params to values
 *  Returns rows and results
*/

class Database
{
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = CHARSET;

    private $dbh;
    private $error;
    private $stmt;

    public function __construct()
    {
        // Create a new PDO instanace
        try {
//            $instance = $this->dbh;
            if ($this->dbh === null) {
                $opt = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => FALSE,
                    PDO::ATTR_PERSISTENT => true
                );
                $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=' . $this->charset;
                $this->dbh = new PDO($dsn, $this->user, $this->pass, $opt);
            }
            return $this->dbh;
        }
        // Catch any errors
        catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }
    function __destruct()
    {
        $this->dbh = null;
    }
    // Prepare statement with query
    public function query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    // Bind values
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value) :
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value) :
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value) :
                    $type = PDO::PARAM_NULL;
                    break;
                default :
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute()
    {
        return $this->stmt->execute();
    }

    // Get result set as array of objects
    public function singleOBJ()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Get single record as object
    public function singleASS()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get record row count
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    // Returns the last inserted ID
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
    public function selectByOne(){

    }
    function insertRecord($table, $fields, $values, $return = false){
        $newFields = "(`" . implode("`, `", $fields) . "`)";
        $newValues = "('" . implode("', '", $values) . "')";
        $insertRecord = $this->query("INSERT INTO `{$table}` {$newFields} VALUES {$newValues}");
        if($insertRecord){
            if($return){
                return $this->lastInsertId();
            }else{
                return true;
            }
        }else{
            return false;
        }
    }
}