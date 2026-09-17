<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class ForoComentario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los comentarios activos de una publicación del foro.
     * Ordenados cronológicamente para reconstruir el árbol (padres e hijos).
     */
    public function getByForoId($foro_id) {
        $query = "SELECT fc.*, 
                         u.nombres, u.apellido_paterno, u.correo
                  FROM foro_comentario fc
                  LEFT JOIN usuario u ON fc.usuario_id = u.usuario_id
                  WHERE fc.foro_id = :foro_id AND fc.habilitado = true
                  ORDER BY fc.fecha_envio ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':foro_id' => $foro_id]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error en ForoComentario::getByForoId -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un nuevo comentario o respuesta (hijo).
     */
    public function crear($datos) {
        $query = "INSERT INTO foro_comentario (foro_id, usuario_id, mensaje, comentario_padre_id, habilitado, fecha_envio) 
                  VALUES (:foro_id, :usuario_id, :mensaje, :comentario_padre_id, true, NOW()) 
                  RETURNING foro_comentario_id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':foro_id' => $datos['foro_id'],
                ':usuario_id' => $datos['usuario_id'],
                ':mensaje' => trim($datos['mensaje']),
                ':comentario_padre_id' => !empty($datos['comentario_padre_id']) ? $datos['comentario_padre_id'] : null
            ]);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error en ForoComentario::crear -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar comentario de manera lógica (soft delete, solo por el autor).
     */
    public function eliminar($id, $usuario_id) {
        $query = "UPDATE foro_comentario 
                  SET habilitado = false 
                  WHERE foro_comentario_id = :id AND usuario_id = :usuario_id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':usuario_id' => $usuario_id
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
