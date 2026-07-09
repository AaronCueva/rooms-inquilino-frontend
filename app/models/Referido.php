<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Referido — Programa de referidos (W8.2, ref §3.7.3).
 * Tabla: public.referido (Supabase). Ver memory db-schema-digest.
 * Catálogo ESTADO_REFERIDO: ESREF01=PENDIENTE, ESREF02=ACREDITADO, ESREF03=CANCELADO.
 * Consume App\Models\PuntosNido (W5.8 — contrato congelado).
 */
class Referido
{
    public const EST_PENDIENTE   = 'ESREF01';
    public const EST_ACREDITADO  = 'ESREF02';
    public const EST_CANCELADO   = 'ESREF03';
    public const PUNTOS_REFERIDO = 200; // valor v1, configurable

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Código shareable del usuario. Derivación determinista (D7):
     * toma el codigo de la fila referido más reciente del referidor; si no tiene
     * ninguna, genera NIDO-{INICIALES}{AÑO} (iniciales de nombres + apellido_paterno).
     */
    public function obtenerMiCodigo(string $usuario_id): string
    {
        $sql = "SELECT codigo FROM referido
                WHERE usuario_referidor_id = :uid AND habilitado = true
                ORDER BY fecha_creacion DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['codigo'])) {
            return $row['codigo'];
        }

        // Fallback: derivar iniciales desde usuario.nombres + apellido_paterno
        $stmtU = $this->db->prepare("SELECT nombres, apellido_paterno FROM usuario WHERE usuario_id = :uid LIMIT 1");
        $stmtU->bindValue(':uid', $usuario_id);
        $stmtU->execute();
        $u = $stmtU->fetch(PDO::FETCH_ASSOC);

        $nombres     = $u['nombres'] ?? '';
        $apellido    = $u['apellido_paterno'] ?? '';
        $ini1 = $this->initial($nombres);
        $ini2 = $this->initial($apellido);
        $iniciales = strtoupper($ini1 . $ini2);
        if ($iniciales === '') {
            $iniciales = 'NI';
        }
        return 'NIDO-' . $iniciales . date('Y');
    }

    /**
     * Mis referidos (filas donde el usuario es referidor).
     * JOIN usuario para datos del referido.
     */
    public function misReferidos(string $usuario_id): array
    {
        $sql = "SELECT r.referido_id, r.codigo, r.estado_codigo, r.fecha_creacion,
                       u.nombres AS referido_nombres, u.apellido_paterno AS referido_apellido,
                       u.correo AS referido_correo
                FROM referido r
                LEFT JOIN usuario u ON r.usuario_referido_id = u.usuario_id
                WHERE r.usuario_referidor_id = :uid AND r.habilitado = true
                ORDER BY r.fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteo de referidos ACREDITADOS (para dashboard / resumen).
     */
    public function contarActivos(string $usuario_id): int
    {
        $sql = "SELECT COUNT(*) FROM referido
                WHERE usuario_referidor_id = :uid AND estado_codigo = :est AND habilitado = true";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $usuario_id);
        $stmt->bindValue(':est', self::EST_ACREDITADO);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Conteo de referidos PENDIENTES.
     */
    public function contarPendientes(string $usuario_id): int
    {
        $sql = "SELECT COUNT(*) FROM referido
                WHERE usuario_referidor_id = :uid AND estado_codigo = :est AND habilitado = true";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $usuario_id);
        $stmt->bindValue(':est', self::EST_PENDIENTE);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Crea un referido PENDIENTE. Valida no duplicar el par referidor↔referido.
     * Devuelve referido_id (uuid) o null si ya existe / falla.
     */
    public function registrar(string $referidor_id, string $referido_id, string $codigo): ?string
    {
        // Validar duplicidad del par
        $stmt = $this->db->prepare(
            "SELECT 1 FROM referido
             WHERE usuario_referidor_id = :ref AND usuario_referido_id = :rec AND habilitado = true LIMIT 1"
        );
        $stmt->bindValue(':ref', $referidor_id);
        $stmt->bindValue(':rec', $referido_id);
        $stmt->execute();
        if ($stmt->fetchColumn()) {
            return null;
        }

        $sql = "INSERT INTO referido
                    (codigo, estado_codigo, fecha_creacion, usuario_referidor_id, usuario_referido_id, habilitado, creado_por)
                VALUES (:codigo, :est, now(), :ref, :rec, true, :creado_por)
                RETURNING referido_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->bindValue(':est', self::EST_PENDIENTE);
        $stmt->bindValue(':ref', $referidor_id);
        $stmt->bindValue(':rec', $referido_id);
        $stmt->bindValue(':creado_por', $referidor_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['referido_id'] : null;
    }

    /**
     * Acredita un referido PENDIENTE: marca ACREDITADO y suma puntos al referidor.
     * Idempotente: si ya ACREDITADO devuelve true; si CANCELADO o no existe devuelve false.
     * Transacción (begin/commit/rollback). Consume PuntosNido (W5.8).
     */
    public function acreditar(string $referido_id): bool
    {
        try {
            $this->db->beginTransaction();
        } catch (\Throwable $e) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT referido_id, estado_codigo, usuario_referidor_id, usuario_referido_id
                 FROM referido WHERE referido_id = :rid AND habilitado = true LIMIT 1"
            );
            $stmt->bindValue(':rid', $referido_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                return false;
            }
            if ($row['estado_codigo'] === self::EST_ACREDITADO) {
                $this->db->rollBack();
                return true; // idempotente
            }
            if ($row['estado_codigo'] !== self::EST_PENDIENTE) {
                $this->db->rollBack();
                return false;
            }

            // Marcar ACREDITADO
            $stmtUp = $this->db->prepare(
                "UPDATE referido SET estado_codigo = :est, modificado = now(), modificado_por = :mod
                 WHERE referido_id = :rid"
            );
            $stmtUp->bindValue(':est', self::EST_ACREDITADO);
            $stmtUp->bindValue(':mod', $row['usuario_referidor_id']);
            $stmtUp->bindValue(':rid', $referido_id);
            $stmtUp->execute();

            // Acreditar puntos al referidor (PuntosNido — W5.8, contrato congelado)
            $referidor_id = $row['usuario_referidor_id'];
            $descripcion = 'Bono por referir a ' . $referido_id;
            \App\Models\PuntosNido::acreditar(
                $referidor_id,
                \App\Models\PuntosNido::TMPT_REFERIDO,
                self::PUNTOS_REFERIDO,
                $descripcion
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            try { $this->db->rollBack(); } catch (\Throwable $e2) {}
            return false;
        }
    }

    /**
     * Aplica un código de referido durante el registro de un nuevo usuario.
     * Busca el referidor por el código (fila más reciente con ese código → usuario_referidor_id).
     * Si hay referidor y no es auto-referido → registrar() + acreditar() al instante (D5).
     * Silencioso: no rompe el registro si falla.
     */
    public function aplicarCodigoEnRegistro(string $referido_id_nuevo, string $codigo): void
    {
        try {
            $codigo = trim($codigo);
            if ($codigo === '') {
                return;
            }

            // Buscar referidor por código (alguien que ya usó ese código → identifica al referidor)
            $stmt = $this->db->prepare(
                "SELECT usuario_referidor_id FROM referido
                 WHERE codigo = :codigo AND habilitado = true
                 ORDER BY fecha_creacion DESC LIMIT 1"
            );
            $stmt->bindValue(':codigo', $codigo);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $referidor_id = $row ? $row['usuario_referidor_id'] : null;

            // Fallback opcional v1: derivar referidor por código NIDO-XXX matching iniciales
            if (!$referidor_id && preg_match('/^NIDO-([A-Z]{2})\d{4}$/', strtoupper($codigo), $m)) {
                $iniciales = $m[1];
                $ini1 = $iniciales[0];
                $ini2 = $iniciales[1];
                $stmtU = $this->db->prepare(
                    "SELECT usuario_id FROM usuario
                     WHERE UPPER(SUBSTRING(nombres FROM 1 FOR 1)) = :i1
                       AND UPPER(SUBSTRING(apellido_paterno FROM 1 FOR 1)) = :i2
                       AND habilitado = true
                     ORDER BY creado ASC LIMIT 1"
                );
                $stmtU->bindValue(':i1', $ini1);
                $stmtU->bindValue(':i2', $ini2);
                $stmtU->execute();
                $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
                $referidor_id = $uRow ? $uRow['usuario_id'] : null;
            }

            if (!$referidor_id) {
                return;
            }
            // Evitar auto-referido
            if ($referidor_id === $referido_id_nuevo) {
                return;
            }

            $nuevoReferidoId = $this->registrar($referidor_id, $referido_id_nuevo, $codigo);
            if ($nuevoReferidoId) {
                $this->acreditar($nuevoReferidoId);
            }
        } catch (\Throwable $e) {
            // Silencioso: no romper el registro
        }
    }

    /**
     * Primera letra de un string (mb_* con guard).
     */
    private function initial(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        return function_exists('mb_substr') ? mb_substr($s, 0, 1) : substr($s, 0, 1);
    }
}
