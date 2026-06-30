<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $conn;

    private $host = "aws-1-us-east-2.pooler.supabase.com";
    private $db_name = "postgres";
    private $port = "6543";
    private $username = "postgres.lokjiueialuwrulybgut";
    private $password = "g0UNVXoLuA8uaPtH";

    private function __construct()
    {
        try {
            $this->conn = new PDO("pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            die("Error de conexión a la base de datos: " . $exception->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
