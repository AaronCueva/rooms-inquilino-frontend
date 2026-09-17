<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Reserva (W3 — Reserva y pago).
 * Tabla: public.reserva (Supabase). Ver memory db-schema-digest para columnas.
 *
 * Catálogo ESTADO_RESERVA (confirmado vía BD 2026-07-09):
 *   ESRE001=PENDIENTE, ESRE002=APROBADA, ESRE003=RECHAZADA, ESRE004=FINALIZADA,
 *   ESRE005=EN REVISIÓN, ESRE006=FORMALIZADO, ESRE007=CANCELADA (añadida para W3).
 * Catálogo METODO_PAGO: MPG001=TRANSFERENCIA, MPG002=TARJETA, MPG003=YAPE, MPG004=PLIN.
 */
class Reserva
{
    public const EST_PENDIENTE    = 'ESRE001';
    public const EST_APROBADA     = 'ESRE002';
    public const EST_RECHAZADA    = 'ESRE003';
    public const EST_FINALIZADA   = 'ESRE004';
    public const EST_EN_REVISION  = 'ESRE005';
    public const EST_FORMALIZADO  = 'ESRE006';
    public const EST_CANCELADA    = 'ESRE007';

    /** Estados que impiden crear otra reserva para el mismo alojamiento. */
    public const ESTADOS_ACTIVOS = [
        self::EST_PENDIENTE, self::EST_APROBADA, self::EST_EN_REVISION, self::EST_FORMALIZADO,
    ];

    /** Estados desde los que el inquilino puede cancelar. */
    public const ESTADOS_CANCELABLES = [
        self::EST_PENDIENTE, self::EST_APROBADA, self::EST_EN_REVISION,
    ];

    public const CARGO_PLATAFORMA_PCT = 0.05;

    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Costos de la reserva (single source of truth server-side; replica el JS del sidebar).
     * alquiler = precio * duracion; cargo = alquiler * 5%; total = alquiler + garantia + cargo.
     */
    public function calcularCostos(float $precio_mensual, float $garantia, int $duracion_meses): array
    {
        $duracion_meses = max(1, $duracion_meses);
        $alquiler = $precio_mensual * $duracion_meses;
        $cargo = $alquiler * self::CARGO_PLATAFORMA_PCT;
        $total = $alquiler + $garantia + $cargo;
        return [
            'alquiler' => $alquiler,
            'garantia' => $garantia,
            'cargo'    => $cargo,
            'total'    => $total,
        ];
    }

    /**
     * ¿El usuario puede reservar? Requiere cuenta verificada.
     * Devuelve ['ok'=>bool, 'reason'=>string].
     */
    public function validarPuedeReservar(string $usuario_id): array
    {
        $stmt = $this->db->prepare("SELECT verificado FROM usuario WHERE usuario_id = :u");
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['ok' => false, 'reason' => 'Usuario no encontrado.'];
        }
        if (empty($row['verificado'])) {
            return ['ok' => false, 'reason' => 'Debes verificar tu identidad antes de reservar.'];
        }
        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Crea una solicitud de reserva (estado PENDIENTE).
     * $data: usuario_id, alojamiento_id, fecha_ingreso (Y-m-d), duracion_meses, monto_total,
     *        mensaje_presentacion, metodo_pago, observacion (opcional).
     * Valida que no exista reserva activa para el par. Devuelve la fila o null.
     */
    public function crear(array $data): ?array
    {
        $activos = $this->inList(self::ESTADOS_ACTIVOS);

        try {
            $this->db->beginTransaction();

            // Duplicado: reserva en trámite o con contrato activo para el mismo par
            $chk = $this->db->prepare(
                "SELECT 1 FROM reserva r
                 WHERE r.usuario_id = :u AND r.alojamiento_id = :a AND r.habilitado = true
                   AND (
                       r.estado_codigo IN ('ESRE001', 'ESRE002', 'ESRE005')
                       OR (
                           r.estado_codigo = 'ESRE006'
                           AND EXISTS (
                               SELECT 1 FROM contrato c
                               WHERE c.reserva_id = r.reserva_id
                                 AND c.estado_codigo = 'ESCO001'
                           )
                       )
                   )
                 LIMIT 1"
            );
            $chk->bindValue(':u', $data['usuario_id']);
            $chk->bindValue(':a', $data['alojamiento_id']);
            $chk->execute();
            if ($chk->fetch()) {
                $this->db->rollBack();
                return null;
            }

            $obs = trim($data['observacion'] ?? '');
            $metodo = trim($data['metodo_pago'] ?? '');
            $obsFull = $obs;
            if ($metodo !== '') {
                $obsFull = ($obsFull !== '' ? $obsFull . ' | ' : '') . 'Método de pago (stub): ' . $metodo;
            }

            $sql = "INSERT INTO reserva
                        (fecha_solicitud, fecha_ingreso, duracion_meses, monto_total,
                         mensaje_presentacion, estado_codigo, usuario_id, alojamiento_id,
                         observacion, habilitado, creado, creado_por)
                    VALUES (now(), :fi, :dur, :mt, :msg, :est, :u, :a, :obs, true, now(), :cp)
                    RETURNING *";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fi', $data['fecha_ingreso']);
            $stmt->bindValue(':dur', (int)$data['duracion_meses'], PDO::PARAM_INT);
            $stmt->bindValue(':mt', $data['monto_total']);
            $stmt->bindValue(':msg', $data['mensaje_presentacion']);
            $stmt->bindValue(':est', self::EST_PENDIENTE);
            $stmt->bindValue(':u', $data['usuario_id']);
            $stmt->bindValue(':a', $data['alojamiento_id']);
            $stmt->bindValue(':obs', $obsFull);
            $stmt->bindValue(':cp', $data['usuario_id']); // creado_por es varchar
            $stmt->execute();
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->db->commit();
            return $fila ?: null;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return null;
        }
    }

    /**
     * Mis reservas paginadas, recientes primero.
     * JOIN alojamiento (titulo, precio) + foto principal + catálogo (nombre estado).
     */
    public function misReservas(string $usuario_id, int $pagina = 1, int $porPagina = 10): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);
        $offset = ($pagina - 1) * $porPagina;
        $fotoTipos = $this->inList(['FOTO', 'IMAGEN']);

        $sql = "SELECT r.reserva_id, r.fecha_solicitud, r.fecha_ingreso, r.duracion_meses,
                       r.monto_total, r.estado_codigo, r.observacion,
                       a.titulo AS alojamiento_titulo, a.precio_mensual, a.moneda_codigo,
                       a.alojamiento_id,
                       cat.nombre AS estado_nombre,
                       ct.contrato_id,
                       ct.estado_codigo AS contrato_estado_codigo,
                       cat_ct.nombre AS contrato_estado_nombre,
                       (
                           SELECT m.url FROM multimedia m
                           WHERE m.alojamiento_id = a.alojamiento_id
                             AND m.tipo_codigo IN ($fotoTipos) AND m.habilitado = true
                           ORDER BY m.orden ASC NULLS LAST, m.creado ASC LIMIT 1
                       ) AS foto_principal
                FROM reserva r
                JOIN alojamiento a ON r.alojamiento_id = a.alojamiento_id
                LEFT JOIN catalogo cat ON cat.codigo = r.estado_codigo AND cat.referencia_codigo = 'ESTADO_RESERVA'
                LEFT JOIN contrato ct ON ct.reserva_id = r.reserva_id AND ct.habilitado = true
                LEFT JOIN catalogo cat_ct ON cat_ct.codigo = ct.estado_codigo AND cat_ct.referencia_codigo = 'ESTADO_CONTRATO'
                WHERE r.usuario_id = :u AND r.habilitado = true
                ORDER BY r.fecha_solicitud DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cuenta reservas del usuario (para paginación). */
    public function contarMisReservas(string $usuario_id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reserva WHERE usuario_id = :u AND habilitado = true");
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca una reserva del usuario (ownership check). Devuelve la fila o null.
     */
    public function findById(string $reserva_id, string $usuario_id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reserva WHERE reserva_id = :r AND usuario_id = :u AND habilitado = true"
        );
        $stmt->bindValue(':r', $reserva_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cancela una reserva del usuario. Calcula reembolso según política.
     * Devuelve ['ok'=>bool, 'reembolso_pct'=>int, 'reembolso_monto'=>float, 'reason'=>str].
     */
    public function cancelar(string $reserva_id, string $usuario_id): array
    {
        $reserva = $this->findById($reserva_id, $usuario_id);
        if (!$reserva) {
            return ['ok' => false, 'reembolso_pct' => 0, 'reembolso_monto' => 0, 'reason' => 'Reserva no encontrada.'];
        }
        if (!in_array($reserva['estado_codigo'], self::ESTADOS_CANCELABLES, true)) {
            return ['ok' => false, 'reembolso_pct' => 0, 'reembolso_monto' => 0, 'reason' => 'Esta reserva no se puede cancelar en su estado actual.'];
        }

        [$pct, $monto] = $this->calcularReembolso($reserva);

        try {
            $upd = $this->db->prepare(
                "UPDATE reserva
                 SET estado_codigo = :est, fecha_respuesta = now(),
                     modificado = now(), modificado_por = :mp
                 WHERE reserva_id = :r AND usuario_id = :u"
            );
            $upd->bindValue(':est', self::EST_CANCELADA);
            $upd->bindValue(':u', $usuario_id);
            $upd->bindValue(':r', $reserva_id);
            $upd->bindValue(':mp', $usuario_id); // modificado_por es varchar
            $upd->execute();

            return ['ok' => true, 'reembolso_pct' => $pct, 'reembolso_monto' => $monto, 'reason' => ''];
        } catch (\PDOException $e) {
            return ['ok' => false, 'reembolso_pct' => 0, 'reembolso_monto' => 0, 'reason' => 'No se pudo cancelar.'];
        }
    }

    /**
     * Política de reembolso (v1 — sin cobro real, solo cálculo informativo).
     * PENDIENTE → 100% (no se cobró).
     * APROBADA/EN_REVISION → basado en días desde fecha_respuesta (o fecha_solicitud):
     *   <24h → 100%, 24h–7d → 50%, >7d → 0%.
     * Devuelve [pct, monto].
     */
    private function calcularReembolso(array $reserva): array
    {
        $monto = (float)($reserva['monto_total'] ?? 0);

        if ($reserva['estado_codigo'] === self::EST_PENDIENTE) {
            return [100, $monto];
        }

        $ref = $reserva['fecha_respuesta'] ?: $reserva['fecha_solicitud'];
        $horas = 0;
        if ($ref) {
            $ts = strtotime($ref);
            if ($ts !== false) {
                $horas = (time() - $ts) / 3600;
            }
        }

        if ($horas < 24) {
            return [100, $monto];
        } elseif ($horas < 168) { // 7 * 24
            return [50, $monto * 0.50];
        }
        return [0, 0.0];
    }

    /**
     * Cron 48h: cancela solicitudes PENDIENTE sin respuesta >48h.
     * Devuelve el número de filas afectadas.
     */
    public function cancelarExpiradas(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE reserva
             SET estado_codigo = :est, fecha_respuesta = now(),
                 modificado = now(), modificado_por = NULL
             WHERE estado_codigo = :pend AND habilitado = true
               AND fecha_solicitud < now() - interval '48 hours'"
        );
        $stmt->bindValue(':est', self::EST_CANCELADA);
        $stmt->bindValue(':pend', self::EST_PENDIENTE);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** Genera lista 'x','y' escapada para IN (...) con strings constantes. */
    private function inList(array $values): string
    {
        return implode(',', array_map(fn($v) => $this->db->quote($v), $values));
    }
}
