<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo PuntosNido (gamificación del inquilino).
 * Tabla: public.punto_movimiento (Supabase). Saldo en usuario.puntos_acumulados.
 * Contrato congelado W5↔W8 — ver PLAN-W5-PERFIL-VERIFICACION-GAMIFICACION.md §6.
 */
class PuntosNido
{
    // Catálogo TIPO_MOVIMIENTO_PUNTO
    public const TMPT_REFERIDO        = 'TMPT001';
    public const TMPT_RESENA          = 'TMPT002';
    public const TMPT_PAGO_PUNTUAL    = 'TMPT003';
    public const TMPT_PARTICIPACION   = 'TMPT004';
    public const TMPT_CANJE           = 'TMPT005';
    public const TMPT_AJUSTE          = 'TMPT006';
    public const TMPT_BONO_BIENVENIDA = 'TMPT007';

    // Niveles (threshold de puntos_acumulados)
    public const NIVEL_NOVATO            = ['codigo' => 'NV1', 'nombre' => 'Novato',             'min' => 0];
    public const NIVEL_CONFIABLE         = ['codigo' => 'NV2', 'nombre' => 'Inquilino Confiable', 'min' => 1000];
    public const NIVEL_GOLD              = ['codigo' => 'NV3', 'nombre' => 'Nido Gold',          'min' => 3000];

    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Saldo actual de puntos del usuario.
     * Devuelve 0 si el usuario no existe o la columna es NULL.
     */
    public function obtener(string $usuario_id): int
    {
        $stmt = $this->db->prepare("SELECT puntos_acumulados FROM usuario WHERE usuario_id = :u");
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val === false || $val === null ? 0 : (int)$val;
    }

    /**
     * Historial paginado de movimientos de puntos (habilitados), recientes primero.
     */
    public function movimientos(string $usuario_id, int $pagina = 1, int $porPagina = 20): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);
        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT punto_movimiento_id, tipo_movimiento_codigo, puntos, descripcion,
                       fecha_creacion, puntos_otorgados
                FROM punto_movimiento
                WHERE usuario_id = :u AND habilitado = true
                ORDER BY fecha_creacion DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Acredita un movimiento de puntos y actualiza el saldo del usuario en una transacción.
     * $puntos puede ser negativo (canje / ajuste). Devuelve la fila insertada o null si falla.
     */
    public function acreditar(string $usuario_id, string $tipo_codigo, int $puntos, string $descripcion): ?array
    {
        try {
            $this->db->beginTransaction();
        } catch (\PDOException $e) {
            // Ya hay una transacción activa: no la gestionamos nosotros.
            $inExternal = true;
        }
        $inExternal = $inExternal ?? false;

        try {
            // 1) INSERT punto_movimiento (RETURNING *)
            $sql = "INSERT INTO punto_movimiento
                        (tipo_movimiento_codigo, puntos, descripcion, fecha_creacion,
                         puntos_otorgados, usuario_id, habilitado, creado, creado_por)
                    VALUES (:tipo, :puntos, :desc, now(), :puntos, :u, true, now(), :upor)
                    RETURNING *";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $tipo_codigo);
            $stmt->bindValue(':puntos', $puntos, PDO::PARAM_INT);
            $stmt->bindValue(':desc', $descripcion);
            $stmt->bindValue(':u', $usuario_id);
            $stmt->bindValue(':upor', $usuario_id);
            $stmt->execute();
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                if (!$inExternal && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return null;
            }

            // 2) UPDATE usuario.puntos_acumulados
            $upd = "UPDATE usuario
                    SET puntos_acumulados = puntos_acumulados + :puntos,
                        modificado = now(), modificado_por = :upor
                    WHERE usuario_id = :u";
            $stmt2 = $this->db->prepare($upd);
            $stmt2->bindValue(':puntos', $puntos, PDO::PARAM_INT);
            $stmt2->bindValue(':upor', $usuario_id);
            $stmt2->bindValue(':u', $usuario_id);
            $stmt2->execute();

            if (!$inExternal && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return $fila;
        } catch (\PDOException $e) {
            if (!$inExternal && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return null;
        }
    }

    /**
     * Calcula el nivel del usuario a partir de sus puntos.
     * Devuelve ['codigo','nombre','proximo_nombre','falta'].
     * 'falta' = puntos que faltan para el siguiente nivel (0 si ya está en Gold).
     */
    public function nivel(int $puntos): array
    {
        $niveles = [self::NIVEL_NOVATO, self::NIVEL_CONFIABLE, self::NIVEL_GOLD];

        $actual = $niveles[0];
        $siguiente = null;
        for ($i = 0, $n = count($niveles); $i < $n; $i++) {
            if ($puntos >= $niveles[$i]['min']) {
                $actual = $niveles[$i];
                $siguiente = $i + 1 < $n ? $niveles[$i + 1] : null;
            } else {
                break;
            }
        }

        if ($siguiente === null) {
            // Ya en el nivel tope (Gold).
            return [
                'codigo'         => $actual['codigo'],
                'nombre'         => $actual['nombre'],
                'proximo_nombre' => null,
                'falta'          => 0,
            ];
        }

        $falta = $siguiente['min'] - $puntos;
        return [
            'codigo'         => $actual['codigo'],
            'nombre'         => $actual['nombre'],
            'proximo_nombre' => $siguiente['nombre'],
            'falta'          => $falta > 0 ? $falta : 0,
        ];
    }

    /**
     * Racha de meses consecutivos con al menos un movimiento TMPT003 (pago puntual)
     * contando hacia atrás desde el mes actual. v1 simple.
     */
    public function racha(string $usuario_id): int
    {
        $sql = "SELECT fecha_creacion
                FROM punto_movimiento
                WHERE usuario_id = :u AND tipo_movimiento_codigo = 'TMPT003' AND habilitado = true
                ORDER BY fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (empty($fechas)) {
            return 0;
        }

        // Conjunto de meses (año-mes) con movimiento.
        $mesesConMov = [];
        foreach ($fechas as $f) {
            $ts = strtotime($f);
            if ($ts === false) {
                continue;
            }
            $key = date('Y-m', $ts);
            $mesesConMov[$key] = true;
        }

        // Contar meses consecutivos hacia atrás desde el mes actual.
        $racha = 0;
        $cursor = strtotime(date('Y-m-01')); // primer día del mes actual
        while (true) {
            $key = date('Y-m', $cursor);
            if (isset($mesesConMov[$key])) {
                $racha++;
                // Retroceder un mes.
                $cursor = strtotime('-1 month', $cursor);
            } else {
                break;
            }
        }

        return $racha;
    }

    /**
     * Canjea puntos si el saldo es suficiente. Devuelve true si se acreditó, false en caso contrario.
     */
    public function canjear(string $usuario_id, int $puntos_a_canjear, string $descripcion): bool
    {
        if ($puntos_a_canjear <= 0) {
            return false;
        }
        if ($this->obtener($usuario_id) < $puntos_a_canjear) {
            return false;
        }
        $fila = $this->acreditar($usuario_id, self::TMPT_CANJE, -$puntos_a_canjear, $descripcion);
        return $fila !== null;
    }
}
