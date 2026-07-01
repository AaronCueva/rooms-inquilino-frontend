<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class UniversidadModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodas() {
        $query = "SELECT universidad_id, nombre FROM universidad WHERE habilitado = true ORDER BY nombre ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorNombre($termino) {
        $query = "SELECT universidad_id, nombre FROM universidad WHERE habilitado = true AND LOWER(nombre) LIKE :termino ORDER BY nombre ASC LIMIT 20";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':termino' => '%' . strtolower($termino) . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
