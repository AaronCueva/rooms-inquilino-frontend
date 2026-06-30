<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Usuario
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($correo)
    {
        $query = "SELECT * FROM usuario WHERE usuario = :usuario LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario', $correo);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($datos)
    {
        $query = "INSERT INTO usuario (usuario,
                    correo, password, nombres, apellido_paterno, apellido_materno, 
                    tipo_documento_codigo, numero_documento, celular, telefono,
                    rol_id, ubicacion_id, universidad_id, estado_codigo, habilitado
                  ) 
                  VALUES (
                    :usuario, :correo, :password, :nombres, :apellido_paterno, :apellido_materno, 
                    :tipo_documento_codigo, :numero_documento, :celular, :telefono,
                    :rol_id, :ubicacion_id, :universidad_id, 'ESU001', true
                  )";
        $stmt = $this->db->prepare($query);

        // Encriptar password
        $password_hash = password_hash($datos['password'], PASSWORD_BCRYPT);

        $stmt->bindParam(':usuario', $datos['correo']);
        $stmt->bindParam(':correo', $datos['correo']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':nombres', $datos['nombres']);
        $stmt->bindParam(':apellido_paterno', $datos['apellido_paterno']);
        $stmt->bindParam(':apellido_materno', $datos['apellido_materno']);
        $stmt->bindParam(':tipo_documento_codigo', $datos['tipo_documento_codigo']);
        $stmt->bindParam(':numero_documento', $datos['numero_documento']);
        $stmt->bindParam(':celular', $datos['celular']);
        $stmt->bindValue(':telefono', $datos['telefono'] ?? null);
        $stmt->bindParam(':rol_id', $datos['rol_id']);
        $stmt->bindValue(':ubicacion_id', $datos['ubicacion_id'] ?? null);
        $stmt->bindValue(':universidad_id', $datos['universidad_id'] ?? null);

        return $stmt->execute();
    }

    public function findById($id)
    {
        $query = "SELECT u.*, ub.nombre AS distrito_nombre, uni.nombre AS universidad_nombre
                  FROM usuario u 
                  LEFT JOIN ubicacion ub ON u.ubicacion_id = ub.ubicacion_id 
                  LEFT JOIN universidad uni ON u.universidad_id = uni.universidad_id
                  WHERE u.usuario_id = :id 
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPerfil($id, $datos)
    {
        $query = "UPDATE usuario SET 
                    nombres = :nombres,
                    apellido_paterno = :apellido_paterno,
                    apellido_materno = :apellido_materno,
                    correo = :correo,
                    celular = :celular,
                    telefono = :telefono,
                    descripcion = :descripcion,
                    genero_codigo = :genero_codigo,
                    ubicacion_id = :ubicacion_id,
                    universidad_id = :universidad_id,
                    modificado = CURRENT_TIMESTAMP";

        if (isset($datos['url_foto'])) {
            $query .= ", url_foto = :url_foto";
        }

        $query .= " WHERE usuario_id = :id";

        $stmt = $this->db->prepare($query);

        $stmt->bindValue(':nombres', $datos['nombres'] ?? null);
        $stmt->bindValue(':apellido_paterno', $datos['apellido_paterno'] ?? null);
        $stmt->bindValue(':apellido_materno', $datos['apellido_materno'] ?? null);
        $stmt->bindValue(':correo', $datos['correo'] ?? null);
        $stmt->bindValue(':celular', $datos['celular'] ?? null);
        $stmt->bindValue(':telefono', $datos['telefono'] ?? null);
        $stmt->bindValue(':descripcion', $datos['descripcion'] ?? null);
        $stmt->bindValue(':genero_codigo', $datos['genero_codigo'] ?? null);
        $stmt->bindValue(':ubicacion_id', !empty($datos['ubicacion_id']) ? $datos['ubicacion_id'] : null);
        $stmt->bindValue(':universidad_id', !empty($datos['universidad_id']) ? $datos['universidad_id'] : null);
        $stmt->bindValue(':id', $id);

        if (isset($datos['url_foto'])) {
            $stmt->bindValue(':url_foto', $datos['url_foto']);
        }

        return $stmt->execute();
    }

    public function obtenerPasswordHash($id)
    {
        $query = "SELECT password FROM usuario WHERE usuario_id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row['password'] : null;
    }

    public function actualizarPassword($id, $nuevo_password_hash)
    {
        $query = "UPDATE usuario SET password = :password, modificado = CURRENT_TIMESTAMP WHERE usuario_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':password', $nuevo_password_hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
