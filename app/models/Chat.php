<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Chat (chat inquilino↔propietario).
 * Tablas: public.chat, chat_usuario, mensaje (Supabase). Ver memory db-schema-digest.
 * No existe catálogo estado_lectura; se usan literales 'ENVIADO' / 'LEIDO'.
 */
class Chat
{
    /** Estado de mensaje no leído. */
    public const ESTADO_ENVIADO = 'ENVIADO';

    /** Estado de mensaje leído. */
    public const ESTADO_LEIDO = 'LEIDO';

    /** Longitud máxima del contenido de un mensaje (varchar). */
    public const MAX_LEN = 2000;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Devuelve el chat_id existente entre dos usuarios o crea uno nuevo.
     * El inquilino ($usuario_id) es quien inicia el chat (creado_por).
     */
    public function getOrCreateChat(string $usuario_id, string $propietario_id): ?string
    {
        if ($usuario_id === $propietario_id) {
            return null;
        }

        // Busca un chat_id que tenga ambos participantes.
        $sql = "SELECT cu1.chat_id
                FROM chat_usuario cu1
                JOIN chat_usuario cu2 ON cu1.chat_id = cu2.chat_id
                WHERE cu1.usuario_id = :u1 AND cu2.usuario_id = :u2
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u1', $usuario_id);
        $stmt->bindValue(':u2', $propietario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['chat_id'];
        }

        // Crea el chat y los dos participantes en transacción.
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO chat (habilitado, creado_por) VALUES (true, :u1) RETURNING chat_id"
            );
            $stmt->bindValue(':u1', $usuario_id);
            $stmt->execute();
            $chat = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$chat) {
                $this->db->rollBack();
                return null;
            }
            $chat_id = $chat['chat_id'];

            $stmt = $this->db->prepare(
                "INSERT INTO chat_usuario (chat_id, usuario_id, habilitado) VALUES (:c, :u, true)"
            );
            $stmt->bindValue(':c', $chat_id);
            $stmt->bindValue(':u', $usuario_id);
            $stmt->execute();

            $stmt->bindValue(':c', $chat_id);
            $stmt->bindValue(':u', $propietario_id);
            $stmt->execute();

            $this->db->commit();
            return $chat_id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return null;
        }
    }

    /**
     * Lista los hilos de chat de un usuario con último mensaje y no leídos.
     */
    public function getChatsByUsuario(string $usuario_id, string $busqueda = ''): array
    {
        $busqueda = trim($busqueda);
        $hasQ = $busqueda !== '';

        $whereExtra = '';
        if ($hasQ) {
            $whereExtra = " AND (otro.nombres ILIKE :q"
                . " OR otro.apellido_paterno ILIKE :q"
                . " OR EXISTS (SELECT 1 FROM mensaje m"
                . " WHERE m.chat_id = cu.chat_id AND m.contenido ILIKE :q))";
        }

        $sql = "SELECT cu.chat_id,
                       otro.usuario_id AS otro_id,
                       otro.nombres AS otro_nombre,
                       otro.apellido_paterno AS otro_apellido,
                       otro.url_foto AS otro_foto,
                       lm.contenido AS ultimo_contenido,
                       lm.fecha_envio AS ultimo_fecha,
                       (
                           SELECT COUNT(*) FROM mensaje m
                           WHERE m.chat_id = cu.chat_id
                             AND m.usuario_id <> :uid
                             AND m.estado_lectura_codigo <> 'LEIDO'
                             AND m.habilitado = true
                       ) AS no_leidos
                FROM chat_usuario cu
                JOIN chat ch ON cu.chat_id = ch.chat_id
                JOIN chat_usuario cu_otro ON cu_otro.chat_id = cu.chat_id AND cu_otro.usuario_id <> :uid
                JOIN usuario otro ON cu_otro.usuario_id = otro.usuario_id
                LEFT JOIN (
                    SELECT DISTINCT ON (chat_id) chat_id, contenido, fecha_envio
                    FROM mensaje
                    WHERE habilitado = true
                    ORDER BY chat_id, fecha_envio DESC
                ) lm ON lm.chat_id = cu.chat_id
                WHERE cu.usuario_id = :uid AND cu.habilitado = true
                $whereExtra
                ORDER BY ultimo_fecha DESC NULLS LAST";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $usuario_id);
        if ($hasQ) {
            $stmt->bindValue(':q', '%' . $busqueda . '%');
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['no_leidos'] = (int)$r['no_leidos'];
        }
        unset($r);
        return $rows;
    }

    /**
     * Verifica si un usuario es participante habilitado de un chat.
     */
    public function esParticipante(string $chat_id, string $usuario_id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM chat_usuario WHERE chat_id = :c AND usuario_id = :u AND habilitado = true LIMIT 1"
        );
        $stmt->bindValue(':c', $chat_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Mensajes de un chat ordenados cronológicamente. Normaliza es_mio a bool.
     */
    public function getMensajes(string $chat_id, string $usuario_id): array
    {
        if (!$this->esParticipante($chat_id, $usuario_id)) {
            return [];
        }

        $sql = "SELECT mensaje_id, contenido, fecha_envio, usuario_id,
                       (usuario_id = :u) AS es_mio
                FROM mensaje
                WHERE chat_id = :c AND habilitado = true
                ORDER BY fecha_envio ASC, creado ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $chat_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['es_mio'] = ($r['es_mio'] === true || $r['es_mio'] === 't'
                || $r['es_mio'] === 'true' || $r['es_mio'] === 1
                || $r['es_mio'] === '1');
        }
        unset($r);
        return $rows;
    }

    /**
     * Envía un mensaje en un chat. Devuelve la fila creada o null si inválido.
     */
    public function enviarMensaje(string $chat_id, string $usuario_id, string $contenido): ?array
    {
        if (!$this->esParticipante($chat_id, $usuario_id)) {
            return null;
        }

        $contenido = trim($contenido);
        $len = function_exists('mb_strlen') ? \mb_strlen($contenido) : strlen($contenido);
        if ($contenido === '' || $len > self::MAX_LEN) {
            return null;
        }

        $sql = "INSERT INTO mensaje (contenido, fecha_envio, estado_lectura_codigo, chat_id, usuario_id, habilitado, creado_por)
                VALUES (:c, now(), 'ENVIADO', :chat, :u, true, :upor)
                RETURNING mensaje_id, contenido, fecha_envio, usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $contenido);
        $stmt->bindValue(':chat', $chat_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':upor', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['es_mio'] = true;
        return $row;
    }

    /**
     * Marca como leídos los mensajes del otro participante en un chat.
     */
    public function marcarLeido(string $chat_id, string $usuario_id): void
    {
        $sql = "UPDATE mensaje
                SET estado_lectura_codigo = 'LEIDO', modificado = now(), modificado_por = :upor
                WHERE chat_id = :c AND usuario_id <> :u
                  AND estado_lectura_codigo <> 'LEIDO' AND habilitado = true";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $chat_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->bindValue(':upor', $usuario_id);
        $stmt->execute();
    }

    /**
     * Total de mensajes no leídos en todos los chats del usuario.
     */
    public function contarNoLeidos(string $usuario_id): int
    {
        $sql = "SELECT COUNT(*) FROM mensaje m
                WHERE m.usuario_id <> :u
                  AND m.estado_lectura_codigo <> 'LEIDO'
                  AND m.habilitado = true
                  AND EXISTS (
                      SELECT 1 FROM chat_usuario cu
                      WHERE cu.chat_id = m.chat_id AND cu.usuario_id = :u AND cu.habilitado = true
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Devuelve los datos del otro participante de un chat.
     */
    public function getOtroParticipante(string $chat_id, string $usuario_id): ?array
    {
        $sql = "SELECT u.usuario_id, u.nombres, u.apellido_paterno, u.url_foto
                FROM chat_usuario cu
                JOIN usuario u ON cu.usuario_id = u.usuario_id
                WHERE cu.chat_id = :c AND cu.usuario_id <> :u AND cu.habilitado = true
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $chat_id);
        $stmt->bindValue(':u', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Genera lista 'x','y' escapada para IN (...) con strings constantes. */
    private function inList(array $values): string
    {
        return implode(',', array_map(fn($v) => $this->db->quote($v), $values));
    }
}
