<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Rol {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene el rol_id buscando por el código del rol
     */
    public function obtenerIdPorCodigo($codigo) {
        $query = "SELECT rol_id FROM rol WHERE codigo = :codigo AND habilitado = true LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['rol_id'] : null;
    }

    public function findById($id) {
        $query = "SELECT * FROM rol WHERE rol_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
