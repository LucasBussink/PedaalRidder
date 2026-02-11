<?php
require_once('../config/config.php');

class Database
{
    private $connectie;

    public function __construct()
    {
        $this->connectie = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    }

    public function voerQueryUit($query, $params = [])
    {

        $statement = $this->connectie->prepare($query);
        $statement->execute($params);
        if (str_contains($query, 'SELECT')) {
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            $result = $statement->rowCount();
            return $result;
        }
    }

    public function sluitVerbinding()
    {
        $this->connectie = null;
    }

    public function testVerbinding()
    {
        return (bool) $this->connectie;
    }

    public function __destruct()
    {
        $this->sluitVerbinding();
    }
}