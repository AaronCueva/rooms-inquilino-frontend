<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Alojamiento (lectura).
 * Tabla: public.alojamiento (Supabase). Ver memory db-schema-digest para columnas y catálogos.
 */
class Alojamiento
{
    /** Estados de publicación visibles en la web pública (ACTIVO / APROBADO). */
    public const ESTADOS_VISIBLES = ['EPA001', 'EPA003'];

    /** Tipos de multimedia que cuentan como fotos. */
    public const TIPOS_FOTO = ['FOTO', 'IMAGEN'];

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Alojamientos destacados para el carrusel del home.
     * Trae foto principal (MIN orden entre FOTO/IMAGEN), distrito y propietario.
     */
    public function getDestacados(int $n = 8): array
    {
        $placeholders = $this->inList(self::ESTADOS_VISIBLES);
        $fotoTipos = $this->inList(self::TIPOS_FOTO);

        $sql = "SELECT a.alojamiento_id, a.codigo, a.titulo, a.tipo_codigo,
                       a.precio_mensual, a.moneda_codigo, a.calificacion,
                       a.ubicacion_id, ub.nombre AS distrito,
                       u.nombres AS propietario_nombres,
                       u.apellido_paterno AS propietario_apellido,
                       u.verificado AS propietario_verificado,
                       (
                           SELECT m.url FROM multimedia m
                           WHERE m.alojamiento_id = a.alojamiento_id
                             AND m.tipo_codigo IN ($fotoTipos)
                             AND m.habilitado = true
                           ORDER BY m.orden ASC NULLS LAST, m.creado ASC
                           LIMIT 1
                       ) AS foto_principal
                FROM alojamiento a
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
                WHERE a.habilitado = true
                  AND a.estado_codigo IN ($placeholders)
                  AND NOT EXISTS (
                      SELECT 1 FROM contrato c
                      JOIN reserva r ON c.reserva_id = r.reserva_id
                      WHERE r.alojamiento_id = a.alojamiento_id
                        AND c.estado_codigo = 'ESCO001'
                  )
                ORDER BY a.calificacion DESC NULLS LAST, a.creado DESC
                LIMIT :n";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':n', $n, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tipos de alojamiento para chips del buscador (catálogo).
     */
    public function getTipos(): array
    {
        return (new Catalogo())->obtenerPorReferencia('TIPO_PUBLICACION_ALOJAMIENTO');
    }

    /**
     * Detalle completo de un alojamiento (lo usa W2 - ficha).
     * Incluye ubicación, propietario, universidades cercanas, servicios, fotos y políticas.
     */
    public function findById($id): ?array
    {
        $fotoTipos = $this->inList(self::TIPOS_FOTO);

        $sql = "SELECT a.*, ub.nombre AS distrito, ub.ubicacion_id AS distrito_id,
                       u.usuario_id AS propietario_id, u.nombres AS propietario_nombres,
                       u.apellido_paterno AS propietario_apellido, u.url_foto AS propietario_foto,
                       u.verificado AS propietario_verificado, u.calificacion AS propietario_calificacion,
                       u.creado AS propietario_miembro_desde
                FROM alojamiento a
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
                WHERE a.alojamiento_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alojamiento) {
            return null;
        }

        $alojamiento['universidades'] = $this->getUniversidadesCercanas($id);
        $alojamiento['servicios'] = $this->getServicios($id);
        $alojamiento['fotos'] = $this->getFotos($id);
        $alojamiento['politicas'] = $this->getPoliticas($id);

        return $alojamiento;
    }

    public function getUniversidadesCercanas($id): array
    {
        $sql = "SELECT uni.universidad_id, uni.nombre, au.distancia_km
                FROM alojamiento_universidad au
                JOIN universidad uni ON au.universidad_id = uni.universidad_id
                WHERE au.alojamiento_id = :id AND au.habilitado = true AND uni.habilitado = true
                ORDER BY au.distancia_km ASC NULLS LAST";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServicios($id): array
    {
        $sql = "SELECT s.servicio_id, s.nombre, s.descripcion, als.precio
                FROM alojamiento_servicio als
                JOIN servicio s ON als.servicio_id = s.servicio_id
                WHERE als.alojamiento_id = :id AND als.habilitado = true
                ORDER BY s.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Fotos ordenadas (FOTO/IMAGEN). */
    public function getFotos($id): array
    {
        $fotoTipos = $this->inList(self::TIPOS_FOTO);
        $sql = "SELECT multimedia_id, url, nombre, orden
                FROM multimedia
                WHERE alojamiento_id = :id AND tipo_codigo IN ($fotoTipos) AND habilitado = true
                ORDER BY orden ASC NULLS LAST";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPoliticas($id): array
    {
        $sql = "SELECT pc.codigo, pc.nombre, pc.descripcion
                FROM alojamiento_politica ap
                JOIN politica_casa pc ON ap.politica_casa_id = pc.politica_casa_id
                WHERE ap.alojamiento_id = :id AND ap.habilitado = true
                ORDER BY pc.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Reseñas visibles (ESRA001=ACTIVO) de un alojamiento, paginado. */
    public function getResenas($alojamiento_id, int $pagina = 1, int $porPagina = 5): array
    {
        $offset = max(0, ($pagina - 1) * $porPagina);
        $sql = "SELECT r.resenia_alojamiento_id, r.calificacion, r.comentario, r.respuesta_propietario, r.creado,
                       u.nombres AS estudiante_nombres, u.apellido_paterno AS estudiante_apellido,
                       u.url_foto AS estudiante_foto
                FROM resenia_alojamiento r
                JOIN usuario u ON r.estudiante_id = u.usuario_id
                WHERE r.alojamiento_id = :id AND r.estado_codigo = 'ESRA001' AND r.habilitado = true
                ORDER BY r.creado DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $alojamiento_id);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarResenas($alojamiento_id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM resenia_alojamiento WHERE alojamiento_id = :id AND estado_codigo = 'ESRA001' AND habilitado = true");
        $stmt->bindValue(':id', $alojamiento_id);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /** Distribución de calificaciones [1..5] => count. */
    public function getDistribucionResenas($alojamiento_id): array
    {
        $stmt = $this->db->prepare("SELECT calificacion, COUNT(*) AS total FROM resenia_alojamiento WHERE alojamiento_id = :id AND estado_codigo = 'ESRA001' AND habilitado = true GROUP BY calificacion");
        $stmt->bindValue(':id', $alojamiento_id);
        $stmt->execute();
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $dist[(int)$r['calificacion']] = (int)$r['total']; }
        return $dist;
    }

    public function getConteoAlojamientosPropietario($propietario_id): int
    {
        $estados = $this->inList(self::ESTADOS_VISIBLES);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM alojamiento WHERE usuario_id = :id AND habilitado = true AND estado_codigo IN ($estados)");
        $stmt->bindValue(':id', $propietario_id);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Listado paginado con filtros (W1). Binds parametrizados.
     */
    public function getAll(array $filtros = [], int $pagina = 1, int $porPagina = 12): array
    {
        [$where, $binds, $uniRef] = $this->buildWhere($filtros);
        $orden = $this->buildOrden($filtros['orden'] ?? 'recientes', $uniRef !== null);
        $offset = max(0, ($pagina - 1) * $porPagina);
        $fotoTipos = $this->inList(self::TIPOS_FOTO);

        $auJoin = $uniRef !== null
            ? "LEFT JOIN alojamiento_universidad au ON au.alojamiento_id = a.alojamiento_id AND au.universidad_id = :__uni_ref"
            : "";

        $sql = "SELECT a.alojamiento_id, a.titulo, a.tipo_codigo, a.precio_mensual, a.moneda_codigo,
                       a.calificacion, a.amoblado, a.mascotas_permitidas, a.fecha_disponible,
                       a.latitud, a.longitud,
                       ub.nombre AS distrito,
                       u.nombres AS propietario_nombres, u.apellido_paterno AS propietario_apellido,
                       u.verificado AS propietario_verificado,
                       (
                           SELECT m.url FROM multimedia m
                           WHERE m.alojamiento_id = a.alojamiento_id
                             AND m.tipo_codigo IN ($fotoTipos)
                             AND m.habilitado = true
                           ORDER BY m.orden ASC NULLS LAST, m.creado ASC
                           LIMIT 1
                       ) AS foto_principal
                FROM alojamiento a
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
                $auJoin
                $where
                ORDER BY $orden
                LIMIT :__limit OFFSET :__offset";

        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) { $stmt->bindValue($k, $v); }
        if ($uniRef !== null) { $stmt->bindValue(':__uni_ref', $uniRef); }
        $stmt->bindValue(':__limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':__offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteo total con filtros (W1).
     */
    public function contar(array $filtros = []): int
    {
        [$where, $binds] = $this->buildWhere($filtros);
        $sql = "SELECT COUNT(DISTINCT a.alojamiento_id)
                FROM alojamiento a
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
                $where";
        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Coordenadas para los pines del mapa (W1). Mismo WHERE, sin paginar, cap 200.
     */
    public function getCoordenadas(array $filtros = []): array
    {
        [$where, $binds] = $this->buildWhere($filtros);
        $sql = "SELECT a.alojamiento_id, a.titulo, a.precio_mensual, a.moneda_codigo, a.latitud, a.longitud
                FROM alojamiento a
                LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
                LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
                $where AND a.latitud IS NOT NULL AND a.longitud IS NOT NULL
                LIMIT 200";
        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Construye la cláusula WHERE + binds a partir de $filtros.
     * Devuelve [$whereSql, $binds, $uniRef] (uniRef = universidad_id si q matchea una uni).
     */
    private function buildWhere(array $filtros): array
    {
        $where = "WHERE a.habilitado = true AND a.estado_codigo IN (" . $this->inList(self::ESTADOS_VISIBLES) . ")"
               . " AND NOT EXISTS ("
               . "     SELECT 1 FROM contrato c"
               . "     JOIN reserva r ON c.reserva_id = r.reserva_id"
               . "     WHERE r.alojamiento_id = a.alojamiento_id"
               . "       AND c.estado_codigo = 'ESCO001'"
               . " )";
        $binds = [];
        $uniRef = null;

        // q → titulo, direccion, distrito, o universidad cercana (EXISTS)
        $q = trim($filtros['q'] ?? '');
        if ($q !== '') {
            $where .= " AND (a.titulo ILIKE :q OR a.direccion ILIKE :q OR ub.nombre ILIKE :q"
                    . " OR EXISTS (SELECT 1 FROM alojamiento_universidad auq"
                    . " JOIN universidad uniq ON auq.universidad_id = uniq.universidad_id"
                    . " WHERE auq.alojamiento_id = a.alojamiento_id AND uniq.nombre ILIKE :q))";
            $binds[':q'] = '%' . $q . '%';
            $uniRef = $this->resolverUniversidadRef($q);
        }

        // tipos (whitelist de strings constantes → inList quoted)
        $tipos = array_values(array_filter((array)($filtros['tipos'] ?? []), 'strlen'));
        if ($tipos) {
            $where .= " AND a.tipo_codigo IN (" . $this->inList($tipos) . ")";
        }

        if (isset($filtros['presupuesto']) && $filtros['presupuesto'] !== '' && $filtros['presupuesto'] !== null) {
            $where .= " AND a.precio_mensual <= :presupuesto";
            $binds[':presupuesto'] = (float)$filtros['presupuesto'];
        }
        if (!empty($filtros['amoblado'])) {
            $where .= " AND a.amoblado = true";
        }
        if (!empty($filtros['mascotas'])) {
            $where .= " AND a.mascotas_permitidas = true";
        }
        if (!empty($filtros['solo_verificados'])) {
            $where .= " AND u.verificado = true";
        }
        $fecha = trim($filtros['fecha'] ?? '');
        if ($fecha !== '') {
            $where .= " AND (a.fecha_disponible IS NULL OR a.fecha_disponible <= :fecha)";
            $binds[':fecha'] = $fecha;
        }

        return [$where, $binds, $uniRef];
    }

    /** Orden SQL con whitelist. "cercanos" requiere uniRef; si no, fallback a recientes. */
    private function buildOrden(string $orden, bool $hasUniRef): string
    {
        $map = [
            'recientes'   => 'a.creado DESC',
            'precio_asc'  => 'a.precio_mensual ASC NULLS LAST',
            'precio_desc' => 'a.precio_mensual DESC NULLS LAST',
            'calificados' => 'a.calificacion DESC NULLS LAST',
            'cercanos'    => $hasUniRef ? 'au.distancia_km ASC NULLS LAST' : 'a.creado DESC',
        ];
        return $map[$orden] ?? $map['recientes'];
    }

    /** Devuelve universidad_id si $q ILIKE matchea exactamente una universidad habilitada. */
    private function resolverUniversidadRef(string $q): ?string
    {
        $stmt = $this->db->prepare("SELECT universidad_id FROM universidad WHERE nombre ILIKE :q AND habilitado = true ORDER BY nombre LIMIT 1");
        $stmt->bindValue(':q', '%' . $q . '%');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['universidad_id'] : null;
    }

    /** Genera lista 'x','y' escapada para IN (...) con strings constantes. */
    private function inList(array $values): string
    {
        return implode(',', array_map(fn($v) => $this->db->quote($v), $values));
    }

    /**
     * Verifica si un estudiante tiene o ha tenido contrato/reserva en el alojamiento.
     */
    public function verificarEstudianteResidente(string $alojamientoId, string $usuarioId): bool
    {
        try {
            $sql = "SELECT 1 FROM contrato 
                    WHERE alojamiento_id = :aid AND inquilino_id = :uid 
                      AND estado_contrato_id IN ('ESCT001', 'ESCT002', 'ESCT003', 'ACTIVO', 'COMPLETADO') 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':aid' => $alojamientoId, ':uid' => $usuarioId]);
            if ($stmt->fetch()) {
                return true;
            }

            // Fallback: verificar si tiene reserva aprobada
            $sqlRes = "SELECT 1 FROM reserva 
                       WHERE alojamiento_id = :aid AND usuario_id = :uid 
                         AND estado_reserva_id IN ('ESRS002', 'APROBADA') 
                       LIMIT 1";
            $stmtRes = $this->db->prepare($sqlRes);
            $stmtRes->execute([':aid' => $alojamientoId, ':uid' => $usuarioId]);
            return (bool) $stmtRes->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crea o actualiza una reseña del usuario sobre un alojamiento.
     */
    public function crearOActualizarResenia(string $alojamientoId, string $usuarioId, int $calificacion, string $comentario): bool
    {
        try {
            // Verificar si ya existe
            $stmtCheck = $this->db->prepare("SELECT resena_id FROM resena WHERE alojamiento_id = :aid AND usuario_id = :uid LIMIT 1");
            $stmtCheck->execute([':aid' => $alojamientoId, ':uid' => $usuarioId]);
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $esVerificado = $this->verificarEstudianteResidente($alojamientoId, $usuarioId) ? 'true' : 'false';

            if ($existente) {
                // Actualizar
                $sql = "UPDATE resena 
                        SET calificacion = :calif, comentario = :coment, modificado = NOW() 
                        WHERE resena_id = :rid";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':calif'  => $calificacion,
                    ':coment' => $comentario,
                    ':rid'    => $existente['resena_id']
                ]);
            } else {
                // Insertar nueva reseña
                $resenaId = 'RES' . strtoupper(substr(md5(uniqid('', true)), 0, 7));
                $sql = "INSERT INTO resena (resena_id, alojamiento_id, usuario_id, calificacion, comentario, verificado, creado)
                        VALUES (:rid, :aid, :uid, :calif, :coment, {$esVerificado}, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':rid'    => $resenaId,
                    ':aid'    => $alojamientoId,
                    ':uid'    => $usuarioId,
                    ':calif'  => $calificacion,
                    ':coment' => $comentario
                ]);
            }

            // Actualizar la calificación del alojamiento si existen las columnas correspondientes
            try {
                $sqlAvg = "UPDATE alojamiento 
                           SET calificacion = (SELECT ROUND(AVG(calificacion)::numeric, 1) FROM resena WHERE alojamiento_id = :aid),
                               resenas_count = (SELECT COUNT(*) FROM resena WHERE alojamiento_id = :aid2)
                           WHERE alojamiento_id = :aid3";
                $stmtAvg = $this->db->prepare($sqlAvg);
                $stmtAvg->execute([':aid' => $alojamientoId, ':aid2' => $alojamientoId, ':aid3' => $alojamientoId]);
            } catch (\Exception $exAvg) {
                // Si alguna columna no existe en alojamiento, ignoramos el error de actualización agregada
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error crearOActualizarResenia: " . $e->getMessage());
            return false;
        }
    }
}
