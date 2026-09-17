<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class ForoReaccion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Alternar o cambiar reacción de un usuario a una publicación (AJAX Toggle).
     * Retorna: 'added', 'removed', 'changed' o false en error.
     */
    public function toggleReaccion($foro_id, $usuario_id, $tipo_reaccion_codigo) {
        try {
            // Verificar si ya existe reacción
            $stmtCheck = $this->db->prepare("SELECT tipo_reaccion_codigo FROM foro_reaccion WHERE foro_id = :foro_id AND usuario_id = :usuario_id");
            $stmtCheck->execute([':foro_id' => $foro_id, ':usuario_id' => $usuario_id]);
            $actual = $stmtCheck->fetchColumn();

            if ($actual !== false) {
                if ($actual === $tipo_reaccion_codigo) {
                    // Mismo tipo: eliminar (toggle off)
                    $stmtDel = $this->db->prepare("DELETE FROM foro_reaccion WHERE foro_id = :foro_id AND usuario_id = :usuario_id");
                    $stmtDel->execute([':foro_id' => $foro_id, ':usuario_id' => $usuario_id]);
                    return 'removed';
                } else {
                    // Diferente tipo: cambiar
                    $stmtUpd = $this->db->prepare("UPDATE foro_reaccion SET tipo_reaccion_codigo = :tipo WHERE foro_id = :foro_id AND usuario_id = :usuario_id");
                    $stmtUpd->execute([':tipo' => $tipo_reaccion_codigo, ':foro_id' => $foro_id, ':usuario_id' => $usuario_id]);
                    return 'changed';
                }
            } else {
                // Nueva reacción: insertar
                $stmtIns = $this->db->prepare("INSERT INTO foro_reaccion (foro_id, usuario_id, tipo_reaccion_codigo) VALUES (:foro_id, :usuario_id, :tipo)");
                $stmtIns->execute([':foro_id' => $foro_id, ':usuario_id' => $usuario_id, ':tipo' => $tipo_reaccion_codigo]);
                return 'added';
            }
        } catch (\PDOException $e) {
            error_log("Error en ForoReaccion::toggleReaccion -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener conteos de reacciones agrupados por tipo para un foro específico.
     */
    public function obtenerConteoPorForo($foro_id) {
        $query = "SELECT tipo_reaccion_codigo, COUNT(*) as total 
                  FROM foro_reaccion 
                  WHERE foro_id = :foro_id 
                  GROUP BY tipo_reaccion_codigo";

        $resumen = [
            'TRFO001' => 0, // 👍 Me sirve
            'TRFO002' => 0, // ❤️ Gracias
            'TRFO003' => 0, // 🔥 Top recomendación
            'total'   => 0
        ];

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':foro_id' => $foro_id]);
            $filas = $stmt->fetchAll();

            foreach ($filas as $fila) {
                $tipo = $fila['tipo_reaccion_codigo'];
                $total = (int) $fila['total'];
                $resumen[$tipo] = $total;
                $resumen['total'] += $total;
            }
        } catch (\PDOException $e) {
            error_log("Error en ForoReaccion::obtenerConteoPorForo -> " . $e->getMessage());
        }

        return $resumen;
    }

    /**
     * Obtener el código de reacción que ha dado un usuario a un foro (si existe).
     */
    public function obtenerReaccionUsuario($foro_id, $usuario_id) {
        try {
            $stmt = $this->db->prepare("SELECT tipo_reaccion_codigo FROM foro_reaccion WHERE foro_id = :foro_id AND usuario_id = :usuario_id");
            $stmt->execute([':foro_id' => $foro_id, ':usuario_id' => $usuario_id]);
            $res = $stmt->fetchColumn();
            return $res !== false ? $res : null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}
