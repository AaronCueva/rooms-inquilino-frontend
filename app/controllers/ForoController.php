<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Foro;
use App\Models\ForoComentario;
use App\Models\ForoReaccion;
use App\Models\Usuario;
use App\Models\UniversidadModel;
use App\Models\Catalogo;

class ForoController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Feed principal del foro estudiantil con selector de universidad y filtros por categoría.
     */
    public function index() {
        $foroModel = new Foro();
        $universidadModel = new UniversidadModel();
        $catalogoModel = new Catalogo();
        $usuarioModel = new Usuario();

        $usuario_id = $_SESSION['usuario_id'];
        $usuario = $usuarioModel->findById($usuario_id);

        // Todas las universidades activas para el selector de cabecera
        $universidades = $universidadModel->obtenerTodas();
        
        // Categorías del foro desde Catalogo (CATFR001 - CATFR007)
        $categorias = $catalogoModel->obtenerPorReferencia('CATEGORIA_FORO');

        // Determinar la universidad activa: parámetro GET o la universidad del perfil
        $uni_param = $_GET['uni'] ?? null;
        if ($uni_param !== null && $uni_param !== '') {
            $universidad_id = ($uni_param === 'ALL') ? null : $uni_param;
        } else {
            $universidad_id = !empty($usuario['universidad_id']) ? $usuario['universidad_id'] : null;
        }

        // Nombre de la comunidad universitaria activa para el Banner
        $nombre_comunidad = "Todas las Comunidades Universitarias";
        if (!empty($universidad_id)) {
            $uni_data = $universidadModel->findById($universidad_id);
            if ($uni_data) {
                $nombre_comunidad = "Comunidad " . $uni_data['nombre'];
            }
        }

        $pagina = (int)($_GET['pagina'] ?? 1);
        if ($pagina < 1) $pagina = 1;
        $por_pagina = 10;

        $filtros = [
            'categoria' => trim($_GET['categoria'] ?? ''),
            'busqueda'  => trim($_GET['busqueda'] ?? '')
        ];

        $foros = $foroModel->getAllByUniversidad($universidad_id, $filtros, $pagina, $por_pagina);
        $total_foros = $foroModel->contarByUniversidad($universidad_id, $filtros);
        $total_paginas = ceil($total_foros / $por_pagina);

        $data = [
            'foros'             => $foros,
            'universidades'     => $universidades,
            'categorias'        => $categorias,
            'universidad_id'    => $universidad_id,
            'nombre_comunidad'  => $nombre_comunidad,
            'filtros'           => $filtros,
            'pagina'            => $pagina,
            'total_paginas'     => $total_paginas,
            'total_foros'       => $total_foros,
            'usuario_actual'    => $usuario
        ];

        $this->render('inquilino/foro/index', $data, 'main');
    }

    /**
     * Ver el detalle de una publicación, su árbol de comentarios y reacciones.
     */
    public function ver() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/foros');
        }

        $foroModel = new Foro();
        $foroComentarioModel = new ForoComentario();
        $foroReaccionModel = new ForoReaccion();
        $catalogoModel = new Catalogo();

        $foro = $foroModel->findById($id);
        if (!$foro) {
            $this->setFlash('error', 'La publicación no existe o fue eliminada.');
            $this->redirect('/foros');
        }

        // Obtener comentarios activos
        $comentarios = $foroComentarioModel->getByForoId($id);

        // Obtener resumen de reacciones 👍 ❤️ 🔥
        $reacciones = $foroReaccionModel->obtenerConteoPorForo($id);
        $mi_reaccion = $foroReaccionModel->obtenerReaccionUsuario($id, $_SESSION['usuario_id']);

        // Categorías por si quiere crear un nuevo hilo desde la barra lateral o modal
        $categorias = $catalogoModel->obtenerPorReferencia('CATEGORIA_FORO');

        $data = [
            'foro'          => $foro,
            'comentarios'   => $comentarios,
            'reacciones'    => $reacciones,
            'mi_reaccion'   => $mi_reaccion,
            'categorias'    => $categorias,
            'usuario_id'    => $_SESSION['usuario_id']
        ];

        $this->render('inquilino/foro/view', $data, 'main');
    }

    /**
     * Almacenar una nueva publicación por POST.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/foros');
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria_codigo = trim($_POST['categoria_codigo'] ?? '');
        $universidad_id = trim($_POST['universidad_id'] ?? '');

        if (empty($titulo) || empty($categoria_codigo)) {
            $this->setFlash('error', 'El título y la categoría son obligatorios.');
            $this->redirect('/foros');
        }

        $foroModel = new Foro();
        $datos = [
            'titulo'            => $titulo,
            'descripcion'       => $descripcion,
            'categoria_codigo'  => $categoria_codigo,
            'universidad_id'    => !empty($universidad_id) ? $universidad_id : null,
            'usuario_id'        => $_SESSION['usuario_id']
        ];

        $foro_id = $foroModel->crear($datos);

        if ($foro_id) {
            $this->setFlash('success', '¡Tu publicación ha sido compartida con la comunidad!');
            $this->redirect('/foros/ver?id=' . urlencode($foro_id));
        } else {
            $this->setFlash('error', 'Ocurrió un error al publicar. Inténtalo de nuevo.');
            $this->redirect('/foros');
        }
    }

    /**
     * Almacenar un nuevo comentario o respuesta (hijo).
     */
    public function comentar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/foros');
        }

        $foro_id = trim($_POST['foro_id'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $comentario_padre_id = trim($_POST['comentario_padre_id'] ?? '');

        if (empty($foro_id) || empty($mensaje)) {
            $this->setFlash('error', 'Escribe un mensaje para comentar.');
            $this->redirect('/foros/ver?id=' . urlencode($foro_id));
        }

        $foroComentarioModel = new ForoComentario();
        $datos = [
            'foro_id'               => $foro_id,
            'usuario_id'            => $_SESSION['usuario_id'],
            'mensaje'               => $mensaje,
            'comentario_padre_id'   => !empty($comentario_padre_id) ? $comentario_padre_id : null
        ];

        $res = $foroComentarioModel->crear($datos);

        if ($res) {
            $this->setFlash('success', 'Tu comentario fue publicado.');
        } else {
            $this->setFlash('error', 'No se pudo publicar el comentario.');
        }

        $this->redirect('/foros/ver?id=' . urlencode($foro_id));
    }

    /**
     * Alternar reacción AJAX al presionar un botón (👍 ❤️ 🔥).
     */
    public function reaccionarAjax() {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $foro_id = $input['foro_id'] ?? $_POST['foro_id'] ?? null;
        $tipo_reaccion = $input['tipo'] ?? $_POST['tipo'] ?? null;

        if (!$foro_id || !$tipo_reaccion) {
            echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
            exit;
        }

        $foroReaccionModel = new ForoReaccion();
        $action = $foroReaccionModel->toggleReaccion($foro_id, $_SESSION['usuario_id'], $tipo_reaccion);

        if ($action !== false) {
            $conteos = $foroReaccionModel->obtenerConteoPorForo($foro_id);
            echo json_encode([
                'success' => true,
                'action'  => $action,
                'conteos' => $conteos,
                'mi_reaccion' => ($action === 'removed') ? null : $tipo_reaccion
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar reacción en la base de datos']);
        }
        exit;
    }

    /**
     * Eliminar publicación propia (Soft Delete).
     */
    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/foros');
        }

        $id = trim($_POST['id'] ?? '');
        if (!empty($id)) {
            $foroModel = new Foro();
            if ($foroModel->eliminar($id, $_SESSION['usuario_id'])) {
                $this->setFlash('success', 'Publicación eliminada.');
            } else {
                $this->setFlash('error', 'No tienes permiso para eliminar esta publicación.');
            }
        }
        $this->redirect('/foros');
    }

    /**
     * Eliminar comentario propio (Soft Delete).
     */
    public function eliminarComentario() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/foros');
        }

        $id = trim($_POST['id'] ?? '');
        $foro_id = trim($_POST['foro_id'] ?? '');

        if (!empty($id)) {
            $fcModel = new ForoComentario();
            if ($fcModel->eliminar($id, $_SESSION['usuario_id'])) {
                $this->setFlash('success', 'Comentario eliminado.');
            } else {
                $this->setFlash('error', 'No tienes permiso para eliminar este comentario.');
            }
        }
        $this->redirect('/foros/ver?id=' . urlencode($foro_id));
    }
}
