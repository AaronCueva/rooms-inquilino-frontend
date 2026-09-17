<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Foro {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener publicaciones de foro activas filtradas por universidad y categoría.
     */
    public function getAllByUniversidad($universidad_id = null, $filtros = [], $pagina = 1, $por_pagina = 10) {
        $condiciones = ["f.habilitado = true"];
        $params = [];

        if (!empty($universidad_id)) {
            $condiciones[] = "f.universidad_id = :universidad_id";
            $params[':universidad_id'] = $universidad_id;
        }

        if (!empty($filtros['categoria'])) {
            $condiciones[] = "f.categoria_codigo = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['busqueda'])) {
            $condiciones[] = "(f.titulo ILIKE :busqueda OR f.descripcion ILIKE :busqueda)";
            $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
        }

        $where = 'WHERE ' . implode(' AND ', $condiciones);
        $offset = ($pagina - 1) * $por_pagina;

        $query = "SELECT f.*, 
                         u.nombres, u.apellido_paterno, u.correo,
                         un.nombre as universidad_nombre,
                         c.nombre as categoria_nombre,
                         (SELECT COUNT(*) FROM foro_comentario fc WHERE fc.foro_id = f.foro_id AND fc.habilitado = true) as total_comentarios,
                         (SELECT COUNT(*) FROM foro_reaccion fr WHERE fr.foro_id = f.foro_id) as total_reacciones
                  FROM foro f
                  LEFT JOIN usuario u ON f.usuario_id = u.usuario_id
                  LEFT JOIN universidad un ON f.universidad_id = un.universidad_id
                  LEFT JOIN catalogo c ON f.categoria_codigo = c.codigo
                  $where
                  ORDER BY f.fecha_creacion DESC
                  LIMIT $por_pagina OFFSET $offset";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error en Foro::getAllByUniversidad -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de publicaciones para la paginación.
     */
    public function contarByUniversidad($universidad_id = null, $filtros = []) {
        $condiciones = ["f.habilitado = true"];
        $params = [];

        if (!empty($universidad_id)) {
            $condiciones[] = "f.universidad_id = :universidad_id";
            $params[':universidad_id'] = $universidad_id;
        }

        if (!empty($filtros['categoria'])) {
            $condiciones[] = "f.categoria_codigo = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['busqueda'])) {
            $condiciones[] = "(f.titulo ILIKE :busqueda OR f.descripcion ILIKE :busqueda)";
            $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
        }

        $where = 'WHERE ' . implode(' AND ', $condiciones);

        $query = "SELECT COUNT(*) FROM foro f $where";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Obtener el detalle de un hilo por ID.
     */
    public function findById($id) {
        $query = "SELECT f.*, 
                         u.nombres, u.apellido_paterno, u.correo,
                         un.nombre as universidad_nombre,
                         c.nombre as categoria_nombre,
                         (SELECT COUNT(*) FROM foro_comentario fc WHERE fc.foro_id = f.foro_id AND fc.habilitado = true) as total_comentarios,
                         (SELECT COUNT(*) FROM foro_reaccion fr WHERE fr.foro_id = f.foro_id) as total_reacciones
                  FROM foro f
                  LEFT JOIN usuario u ON f.usuario_id = u.usuario_id
                  LEFT JOIN universidad un ON f.universidad_id = un.universidad_id
                  LEFT JOIN catalogo c ON f.categoria_codigo = c.codigo
                  WHERE f.foro_id = :id AND f.habilitado = true
                  LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Crear una nueva publicación en el foro.
     */
    public function crear($datos) {
        $query = "INSERT INTO foro (titulo, descripcion, categoria_codigo, universidad_id, usuario_id, estado_codigo, habilitado, fecha_creacion) 
                  VALUES (:titulo, :descripcion, :categoria_codigo, :universidad_id, :usuario_id, 'ACT', true, NOW()) 
                  RETURNING foro_id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':titulo' => trim($datos['titulo']),
                ':descripcion' => trim($datos['descripcion'] ?? ''),
                ':categoria_codigo' => $datos['categoria_codigo'],
                ':universidad_id' => !empty($datos['universidad_id']) ? $datos['universidad_id'] : null,
                ':usuario_id' => $datos['usuario_id']
            ]);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error en Foro::crear -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar una publicación existente (solo el autor).
     */
    public function actualizar($id, $datos, $usuario_id) {
        $query = "UPDATE foro 
                  SET titulo = :titulo, descripcion = :descripcion, categoria_codigo = :categoria_codigo
                  WHERE foro_id = :id AND usuario_id = :usuario_id AND habilitado = true";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':titulo' => trim($datos['titulo']),
                ':descripcion' => trim($datos['descripcion'] ?? ''),
                ':categoria_codigo' => $datos['categoria_codigo'],
                ':id' => $id,
                ':usuario_id' => $usuario_id
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Eliminar publicación de manera lógica (soft delete, solo el autor).
     */
    public function eliminar($id, $usuario_id) {
        $query = "UPDATE foro SET habilitado = false WHERE foro_id = :id AND usuario_id = :usuario_id";

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
