<?php
namespace services\db;

use PDO;
use PDOException;

class DBconnect
{
    private $servername = "monorail.proxy.rlwy.net:43924";
    private $username = "root";
    private $password = "CCXVPgEFXNqHmEbmKEcmnvpJMbewtONQ";
    private $conn;

    public function conn()
    {
        try {
            if ($this->conn === null) {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 30,
                    PDO::ATTR_PERSISTENT => true
                ];
                $this->conn = new PDO("mysql:host=$this->servername;dbname=railway", $this->username, $this->password, $options);
                echo "Connected successfully";
            }
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            $this->conn = null; // Reset the connection on failure
        }
        return $this->conn;
    }
}