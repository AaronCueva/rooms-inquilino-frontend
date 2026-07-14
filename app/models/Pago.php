<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Pago
 * Gestiona las cuotas mensuales y transacciones simuladas de alquiler de los contratos del inquilino.
 */
class Pago
{
    public const EST_PENDIENTE   = 'ESPG001';
    public const EST_PROCESANDO  = 'ESPG002';
    public const EST_COMPLETADO  = 'ESPG003';
    public const EST_FALLIDO     = 'ESPG004';
    public const EST_REEMBOLSADO = 'ESPG005';

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los pagos/cuotas de los contratos de un inquilino.
     */
    public function obtenerPagosPorInquilino(string $usuario_id): array
    {
        $sql = "SELECT p.pago_id, p.contrato_id, p.numero_cuota, p.monto,
                       p.fecha_vencimiento, p.fecha_pago, p.metodo_pago_codigo,
                       p.referencia_externa, p.estado_codigo,
                       cat_est.nombre AS estado_nombre,
                       cat_met.nombre AS metodo_nombre,
                       a.titulo AS alojamiento_titulo, a.moneda_codigo,
                       c.fecha_inicio, c.fecha_fin
                FROM pago p
                JOIN contrato c ON p.contrato_id = c.contrato_id
                JOIN reserva r ON c.reserva_id = r.reserva_id
                JOIN alojamiento a ON r.alojamiento_id = a.alojamiento_id
                LEFT JOIN catalogo cat_est ON cat_est.codigo = p.estado_codigo AND cat_est.referencia_codigo = 'ESTADO_PAGO'
                LEFT JOIN catalogo cat_met ON cat_met.codigo = p.metodo_pago_codigo AND cat_met.referencia_codigo = 'METODO_PAGO'
                WHERE r.usuario_id = :u
                  AND p.habilitado = true
                  AND c.habilitado = true
                  AND (c.estado_codigo = 'ESCO001' OR p.estado_codigo = 'ESPG003')
                ORDER BY p.estado_codigo ASC, p.fecha_vencimiento ASC, p.numero_cuota ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un pago específico verificando que pertenezca al inquilino.
     */
    public function obtenerPorId(string $pago_id, string $usuario_id): ?array
    {
        $sql = "SELECT p.*,
                       a.titulo AS alojamiento_titulo, a.moneda_codigo,
                       cat_est.nombre AS estado_nombre
                FROM pago p
                JOIN contrato c ON p.contrato_id = c.contrato_id
                JOIN reserva r ON c.reserva_id = r.reserva_id
                JOIN alojamiento a ON r.alojamiento_id = a.alojamiento_id
                LEFT JOIN catalogo cat_est ON cat_est.codigo = p.estado_codigo AND cat_est.referencia_codigo = 'ESTADO_PAGO'
                WHERE p.pago_id = :p
                  AND r.usuario_id = :u
                  AND p.habilitado = true";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':p', $pago_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Genera automáticamente las cuotas de un contrato si aún no existen.
     */
    public function generarCuotasContrato(string $contrato_id, int $duracion_meses, float $monto_renta, string $fecha_inicio, string $usuario_id): int
    {
        // Verificar si ya tiene cuotas (activas o anuladas — no regenerar si ya existen)
        $chk = $this->db->prepare("SELECT COUNT(*) FROM pago WHERE contrato_id = :c");
        $chk->bindValue(':c', $contrato_id);
        $chk->execute();
        if ((int)$chk->fetchColumn() > 0) {
            return 0;
        }

        $creados = 0;
        $tsInicio = strtotime($fecha_inicio) ?: time();
        $totalCuotas = max(1, (int)$duracion_meses);

        $sql = "INSERT INTO pago
                    (contrato_id, numero_cuota, monto, fecha_vencimiento, estado_codigo, habilitado, creado, creado_por)
                VALUES
                    (:c, :nc, :monto, :fv, :est, true, now(), :cp)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':c', $contrato_id);
        $stmt->bindParam(':nc', $cuota, PDO::PARAM_INT);
        $stmt->bindValue(':monto', $monto_renta);
        $stmt->bindParam(':fv', $fechaVenc);
        $stmt->bindValue(':est', self::EST_PENDIENTE);
        $stmt->bindParam(':cp', $usuario_id);

        try {
            $this->db->beginTransaction();
            for ($cuota = 1; $cuota <= $totalCuotas; $cuota++) {
                $fechaVenc = date('Y-m-d H:i:s', strtotime("+" . ($cuota - 1) . " month", $tsInicio));
                if ($stmt->execute()) {
                    $creados++;
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }

        return $creados;
    }

    /**
     * Simula el pago exitoso de una cuota en la base de datos.
     */
    public function simularPago(string $pago_id, string $metodo_codigo, string $referencia_externa, string $usuario_id): bool
    {
        $sql = "UPDATE pago
                SET estado_codigo = :est,
                    fecha_pago = now(),
                    metodo_pago_codigo = :metodo,
                    referencia_externa = :ref,
                    modificado = now(),
                    modificado_por = :u
                WHERE pago_id = :pago_id
                  AND habilitado = true
                  AND estado_codigo <> :completado";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':est', self::EST_COMPLETADO);
        $stmt->bindValue(':metodo', $metodo_codigo);
        $stmt->bindValue(':ref', $referencia_externa);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':pago_id', $pago_id);
        $stmt->bindValue(':completado', self::EST_COMPLETADO);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
