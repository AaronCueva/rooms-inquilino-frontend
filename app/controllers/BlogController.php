<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Blog;

/**
 * Blog / Guía del universitario (W8.1, ref §3.7.2).
 * Rutas públicas: no requieren sesión. Layout public.
 */
class BlogController extends Controller
{
    private const POR_PAGINA = 9;

    /**
     * GET /blog — listado paginado de posts publicados.
     */
    public function index()
    {
        $blogModel = new Blog();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = self::POR_PAGINA;

        $posts = $blogModel->getPublicados($pagina, $porPagina);
        $total = $blogModel->contarPublicados();
        $totalPaginas = (int)ceil($total / $porPagina);

        $this->render('blog/index', [
            'posts'        => $posts,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'total'        => $total,
        ], 'public');
    }

    /**
     * GET /blog/ver?id={id} — detalle de un post.
     */
    public function ver()
    {
        $id = $_GET['id'] ?? '';
        $blogModel = new Blog();
        $post = $blogModel->findById((string)$id);

        if (!$post) {
            http_response_code(404);
            $this->setFlash('error', 'Artículo no encontrado.');
            $this->redirect('/blog');
        }

        $this->render('blog/ver', ['post' => $post], 'public');
    }
}
