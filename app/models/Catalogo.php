<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Catalogo {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerPorReferencia($referencia_codigo) {
        $query = "SELECT * FROM catalogo WHERE referencia_codigo = :referencia_codigo ORDER BY orden ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':referencia_codigo', $referencia_codigo);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}
