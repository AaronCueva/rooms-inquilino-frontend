<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Contrato (W4 — Contrato digital, lado inquilino).
 * Tabla: public.contrato (Supabase). Ver memory db-schema-digest para columnas.
 *
 * El propietario carga el PDF del contrato desde su admin (multimedia tipo DOC,
 * enlazado vía contrato.multimedia_id). El inquilino solo ve/descarga/firma.
 *
 * Catálogo ESTADO_CONTRATO: ESCO001=ACTIVO, ESCO002=FINALIZADO, ESCO003=CANCELADO.
 * Columna firma (añadida W4): fecha_firma_inquilino timestamp NULL.
 */
class Contrato
{
    public const EST_ACTIVO    = 'ESCO001';
    public const EST_FINALIZADO = 'ESCO002';
    public const EST_CANCELADO = 'ESCO003';

    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca un contrato del inquilino (ownership vía contrato→reserva.usuario_id).
     * JOIN reserva, alojamiento, propietario, inquilino, multimedia (PDF), ubicación.
     * Devuelve la fila o null.
     */
    public function findById(string $contrato_id, string $usuario_id): ?array
    {
        $sql = "SELECT c.contrato_id, c.fecha_inicio, c.fecha_fin, c.monto_renta,
                       c.monto_garantia, c.cargo_plataforma, c.estado_codigo,
                       c.fecha_pago_mensual, c.reserva_id, c.multimedia_id,
                       c.fecha_firma_inquilino, c.creado,
                       r.duracion_meses, r.monto_total,
                       a.alojamiento_id, a.titulo AS alojamiento_titulo, a.direccion,
                       a.precio_mensual, a.moneda_codigo,
                       ub.nombre AS distrito,
                       p.nombres AS propietario_nombres, p.apellido_paterno AS propietario_apellido,
                       p.correo AS propietario_correo, p.celular AS propietario_celular,
                       i.nombres AS inquilino_nombres, i.apellido_paterno AS inquilino_apellido,
                       i.correo AS inquilino_correo, i.celular AS inquilino_celular,
                       m.url AS pdf_url, m.nombre AS pdf_nombre
                FROM contrato c
                JOIN reserva r ON c.reserva_id = r.reserva_id
                JOIN alojamiento a ON r.alojamiento_id = a.alojamiento_id
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario p ON a.usuario_id = p.usuario_id
                LEFT JOIN usuario i ON r.usuario_id = i.usuario_id
                LEFT JOIN multimedia m ON c.multimedia_id = m.multimedia_id
                WHERE c.contrato_id = :c AND r.usuario_id = :u
                  AND c.habilitado = true AND r.habilitado = true
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $contrato_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Mis contratos paginados (vía reserva), recientes primero.
     */
    public function misContratos(string $usuario_id, int $pagina = 1, int $porPagina = 10): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);
        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT c.contrato_id, c.fecha_inicio, c.fecha_fin, c.monto_renta,
                       c.estado_codigo, c.multimedia_id, c.fecha_firma_inquilino, c.creado,
                       a.titulo AS alojamiento_titulo, a.alojamiento_id, a.moneda_codigo,
                       cat.nombre AS estado_nombre,
                       m.url AS pdf_url
                FROM contrato c
                JOIN reserva r ON c.reserva_id = r.reserva_id
                JOIN alojamiento a ON r.alojamiento_id = a.alojamiento_id
                LEFT JOIN catalogo cat ON cat.codigo = c.estado_codigo AND cat.referencia_codigo = 'ESTADO_CONTRATO'
                LEFT JOIN multimedia m ON c.multimedia_id = m.multimedia_id
                WHERE r.usuario_id = :u AND c.habilitado = true AND r.habilitado = true
                ORDER BY c.creado DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cuenta contratos del inquilino (para paginación). */
    public function contarMisContratos(string $usuario_id): int
    {
        $sql = "SELECT COUNT(*) FROM contrato c
                JOIN reserva r ON c.reserva_id = r.reserva_id
                WHERE r.usuario_id = :u AND c.habilitado = true AND r.habilitado = true";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Firma del inquilino (stub v1). Valida ownership, que no esté firmado,
     * y que el PDF exista. Devuelve ['ok'=>bool, 'reason'=>str].
     */
    public function firmar(string $contrato_id, string $usuario_id): array
    {
        $c = $this->findById($contrato_id, $usuario_id);
        if (!$c) {
            return ['ok' => false, 'reason' => 'Contrato no encontrado.'];
        }
        if (!empty($c['fecha_firma_inquilino'])) {
            return ['ok' => false, 'reason' => 'Ya firmaste este contrato.'];
        }
        if (empty($c['multimedia_id'])) {
            return ['ok' => false, 'reason' => 'El propietario aún no carga el documento del contrato.'];
        }

        try {
            // Placeholders separados: :u uuid (subquery), :mp varchar (modificado_por)
            $upd = $this->db->prepare(
                "UPDATE contrato
                 SET fecha_firma_inquilino = now(), modificado = now(), modificado_por = :mp
                 WHERE contrato_id = :c
                   AND reserva_id IN (SELECT reserva_id FROM reserva WHERE usuario_id = :u)"
            );
            $upd->bindValue(':mp', $usuario_id); // varchar
            $upd->bindValue(':c', $contrato_id);
            $upd->bindValue(':u', $usuario_id);  // uuid
            $upd->execute();

            if ($upd->rowCount() === 0) {
                return ['ok' => false, 'reason' => 'No se pudo firmar el contrato.'];
            }
            return ['ok' => true, 'reason' => ''];
        } catch (\PDOException $e) {
            return ['ok' => false, 'reason' => 'No se pudo firmar el contrato.'];
        }
    }
}
