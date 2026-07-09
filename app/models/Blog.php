<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo Blog (lectura) — Guía del universitario (W8.1, ref §3.7.2).
 * Tabla: public.blog (Supabase). Posts publicados = habilitado=true AND estado_codigo='ESBL002'.
 * El contenido lo crea admin en otro portal; aquí solo lectura.
 */
class Blog
{
    /** Estado de blog publicado (catálogo ESTADO_BLOG). */
    public const EST_PUBLICADO = 'ESBL002';

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Listado paginado de posts publicados con autor (JOIN usuario).
     * Orden: fecha_publicacion DESC.
     */
    public function getPublicados(int $pagina = 1, int $porPagina = 9): array
    {
        $offset = max(0, ($pagina - 1) * $porPagina);
        $sql = "SELECT b.blog_id, b.titulo, b.contenido, b.fecha_publicacion,
                       u.nombres AS autor_nombres, u.apellido_paterno AS autor_apellido
                FROM blog b
                LEFT JOIN usuario u ON b.usuario_id = u.usuario_id
                WHERE b.habilitado = true AND b.estado_codigo = :est
                ORDER BY b.fecha_publicacion DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':est', self::EST_PUBLICADO);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteo total de posts publicados (para paginación).
     */
    public function contarPublicados(): int
    {
        $sql = "SELECT COUNT(*) FROM blog WHERE habilitado = true AND estado_codigo = :est";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':est', self::EST_PUBLICADO);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Un post publicado por id, con autor. Devuelve null si no existe/no está publicado.
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT b.blog_id, b.titulo, b.contenido, b.fecha_publicacion,
                       b.usuario_id, b.creado,
                       u.nombres AS autor_nombres, u.apellido_paterno AS autor_apellido
                FROM blog b
                LEFT JOIN usuario u ON b.usuario_id = u.usuario_id
                WHERE b.blog_id = :id AND b.habilitado = true AND b.estado_codigo = :est
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':est', self::EST_PUBLICADO);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        return $post ?: null;
    }

    /**
     * Posts recientes para widget dashboard (W5.5). Mismo filtro publicado, sin paginar.
     * Devuelve blog_id, titulo, fecha_publicacion.
     */
    public function getRecientes(int $n = 3): array
    {
        $sql = "SELECT b.blog_id, b.titulo, b.fecha_publicacion
                FROM blog b
                WHERE b.habilitado = true AND b.estado_codigo = :est
                ORDER BY b.fecha_publicacion DESC
                LIMIT :n";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':est', self::EST_PUBLICADO);
        $stmt->bindValue(':n', $n, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
