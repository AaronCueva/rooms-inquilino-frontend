<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Ubicacion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerDepartamentos() {
        $query = "SELECT ubicacion_id, nombre 
                  FROM ubicacion 
                  WHERE tipo_ubicacion_codigo = 'TPU002' 
                  ORDER BY nombre ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorReferencia($referencia_id) {
        $query = "SELECT ubicacion_id, nombre 
                  FROM ubicacion 
                  WHERE referencia_id = :referencia_id 
                  ORDER BY nombre ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':referencia_id', $referencia_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
