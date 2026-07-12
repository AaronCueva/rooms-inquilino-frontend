<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo VerificacionEstudiantil (W5.4 + W5.6).
 * Helper para verificación de identidad estudiantil y guardado de perfil.
 *
 * - esCorreoInstitucional: heurística v1 sobre el dominio del correo.
 * - obtenerDocumentos / subirDocumento: carnet/constancia en tabla multimedia.
 * - guardarPerfil: UPDATE dinámico con whitelist (campos editables del estudiante).
 * - marcarVerificacionSolicitada: v1 no-op (la aprobación la hace admin en otro portal).
 *
 * No edita Usuario.php: los métodos de guardado viven aquí con UPDATE directo.
 */
class VerificacionEstudiantil
{
    /** Tipos de multimedia que cuentan como documentos de verificación. */
    public const TIPOS_DOCUMENTO = ['DOCUMENTO', 'DOC'];

    /** Sufijos de dominio considerados institucionales (heurística v1). */
    public const SUFIJOS_INSTITUCIONALES = ['.edu', '.edu.pe', '.gob.pe'];

    /** Palabras clave de institución en el dominio (heurística v1). */
    public const KEYWORDS_INSTITUCION = ['universidad', 'uni', 'utp', 'upn'];

    /** Columnas editables del perfil (whitelist de seguridad). */
    public const CAMPOS_PERFIL = [
        'nombres', 'apellido_paterno', 'apellido_materno',
        'celular', 'telefono', 'url_foto', 'descripcion',
        'genero_codigo', 'universidad_id',
        'carrera', 'anio_ingreso', 'pais_origen',
    ];

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ¿El correo pertenece a un dominio institucional de educación?
     * Heurística v1: true si el dominio termina en un sufijo institucional
     * (.edu, .edu.pe, .gob.pe) o contiene una keyword de institución
     * (universidad, uni, utp, upn).
     */
    public function esCorreoInstitucional(string $correo): bool
    {
        $correo = strtolower(trim($correo));
        $pos = strrpos($correo, '@');
        if ($pos === false) {
            return false;
        }
        $dominio = substr($correo, $pos + 1);
        if ($dominio === '') {
            return false;
        }

        foreach (self::SUFIJOS_INSTITUCIONALES as $suf) {
            if (substr($dominio, -strlen($suf)) === $suf) {
                return true;
            }
        }

        foreach (self::KEYWORDS_INSTITUCION as $kw) {
            if (strpos($dominio, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Documentos de verificación subidos por el estudiante.
     * SELECT FROM multimedia WHERE usuario_id=:u AND tipo_codigo IN ('DOCUMENTO','DOC')
     * AND habilitado=true ORDER BY orden, creado.
     */
    public function obtenerDocumentos(string $usuario_id): array
    {
        $tipos = implode(',', array_map(fn($t) => $this->db->quote($t), self::TIPOS_DOCUMENTO));
        $sql = "SELECT multimedia_id, url, nombre, tipo_codigo, orden, creado
                FROM multimedia
                WHERE usuario_id = :u
                  AND tipo_codigo IN ($tipos)
                  AND habilitado = true
                ORDER BY orden ASC NULLS LAST, creado ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un documento de verificación (carnet/constancia) en multimedia.
     * INSERT multimedia (url, nombre, tipo_codigo=:tipo, usuario_id, habilitado=true,
     * creado_por=:u) RETURNING multimedia_id.
     *
     * v1: la URL la pasa el controller. El upload real (move_uploaded_file) se hace
     * en el controller; aquí solo se persiste el registro. Si el controller no tiene
     * storage, puede pasar una URL stub o un data URI.
     *
     * @return string|null multimedia_id o null si falla.
     */
    public function subirDocumento(string $usuario_id, string $url, string $nombre, string $tipo = 'DOCUMENTO'): ?string
    {
        $sql = "INSERT INTO multimedia (url, tipo_codigo, nombre, orden, usuario_id, habilitado, creado, creado_por)
                VALUES (:url, :tipo, :nombre, 1, :u, true, now(), :u)
                RETURNING multimedia_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['multimedia_id'] : null;
    }

    /**
     * Guarda los campos editables del perfil del estudiante.
     * UPDATE usuario SET (campos permitidos por whitelist) WHERE usuario_id=:u.
     * Construye SET dinámico solo con columnas presentes en $campos y en la whitelist.
     *
     * @param string $usuario_id UUID del usuario.
     * @param array  $campos     Campos a actualizar (solo se respetan los de CAMPOS_PERFIL).
     * @return bool True si el UPDATE se ejecutó sin error.
     */
    public function guardarPerfil(string $usuario_id, array $campos): bool
    {
        $sets = [];
        $binds = [];
        foreach (self::CAMPOS_PERFIL as $col) {
            if (!array_key_exists($col, $campos)) {
                continue;
            }
            $sets[] = "$col = :$col";
            $binds[":$col"] = $campos[$col];
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = "modificado = now()";
        $sql = "UPDATE usuario SET " . implode(', ', $sets) . " WHERE usuario_id = :u";
        $stmt = $this->db->prepare($sql);
        foreach ($binds as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':u', $usuario_id);
        return $stmt->execute();
    }

    /**
     * Guarda la URL del documento de verificación en usuario.url_verificacion_estudiante.
     * Se llama al subir un carnet/constancia: deja constancia de qué documento está
     * en revisión (la aprobación la hace admin en rooms-frontend).
     */
    public function guardarUrlVerificacion(string $usuario_id, string $url): bool
    {
        $sql = "UPDATE usuario SET url_verificacion_estudiante = :url, modificado = now()
                WHERE usuario_id = :u";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':u', $usuario_id);
        return $stmt->execute();
    }

    /**
     * Estado de verificación para la vista:
     *  - 'verificado'   : usuario.verificado = true
     *  - 'en_revision'  : tiene url_verificacion_estudiante pero verificado = false
     *  - 'pendiente'    : sin documento y sin verificar
     */
    public function estadoVerificacion(array $usuario): string
    {
        if (!empty($usuario['verificado'])) {
            return 'verificado';
        }
        if (!empty($usuario['url_verificacion_estudiante'])) {
            return 'en_revision';
        }
        return 'pendiente';
    }

    /**
     * Marca que el estudiante solicitó verificación de identidad.
     *
     * v1: no hay columna de "solicitud pendiente" en usuario. La verificación la
     * aprueba un administrador en otro portal (panel admin); aquí solo se suben
     * documentos (multimedia) que el admin revisa. El estado pendiente es implícito:
     * usuario.verificado sigue false hasta la aprobación admin externa.
     *
     * Si el correo del usuario es institucional, el controller puede marcar
     * verificado=true automático (ver PerfilController::subirDocumento).
     */
    public function marcarVerificacionSolicitada(string $usuario_id): void
    {
        // No-op en v1. Documentado arriba.
    }
}
