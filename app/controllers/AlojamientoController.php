<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Alojamiento;
use App\Models\Catalogo;

/**
 * Búsqueda y filtros de alojamientos (W1, ref §3.2).
 * Ruta pública: no requiere sesión.
 */
class AlojamientoController extends Controller
{
    private const ORDENES = ['recientes', 'precio_asc', 'precio_desc', 'calificados', 'cercanos'];
    private const POR_PAGINA = 12;

    /**
     * GET /buscar
     * Renderiza la página de resultados (layout public) o, si es AJAX (?ajax=1),
     * devuelve JSON {html, hayMas} con el fragment de cards para "Ver más".
     */
    public function buscar()
    {
        $filtros = $this->leerFiltros();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $vista = ($_GET['vista'] ?? 'grilla') === 'mapa' ? 'mapa' : 'grilla';

        $modelo = new Alojamiento();
        $alojamientos = $modelo->getAll($filtros, $pagina, self::POR_PAGINA);
        $total = $modelo->contar($filtros);

        $ajax = (($_GET['ajax'] ?? '') === '1')
             || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');
        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            $reveal = false;
            ob_start();
            require __DIR__ . '/../views/buscar/_cards.php';
            $html = ob_get_clean();
            $hayMas = ($pagina * self::POR_PAGINA) < $total;
            echo json_encode(['html' => $html, 'hayMas' => $hayMas, 'total' => $total]);
            exit;
        }

        $tipos = $modelo->getTipos();
        $coords = $modelo->getCoordenadas($filtros);

        $this->render('buscar/index', [
            'alojamientos'   => $alojamientos,
            'total'          => $total,
            'pagina'         => $pagina,
            'filtros'        => $filtros,
            'vista'          => $vista,
            'tipos'          => $tipos,
            'coords'         => $coords,
            'porPagina'      => self::POR_PAGINA,
            'hayMas'         => ($pagina * self::POR_PAGINA) < $total,
        ], 'public');
    }

    /**
     * GET /alojamiento/{id} — ficha completa del alojamiento (W2, ref §3.3).
     */
    public function detalle($id = null)
    {
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) { $this->redirect('/buscar'); }

        $modelo = new Alojamiento();
        $a = $modelo->findById($id);
        if (!$a) {
            $this->setFlash('error', 'El alojamiento no existe o no está disponible.');
            $this->redirect('/buscar');
        }

        $resenas = $modelo->getResenas($id, 1, 5);
        $totalResenas = $modelo->contarResenas($id);
        $distribucion = $modelo->getDistribucionResenas($id);
        $totalAlojamientosProp = $modelo->getConteoAlojamientosPropietario($a['propietario_id'] ?? null);

        $monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
        $tipoLabels = ['TPA001' => 'Cuarto', 'TPA002' => 'Mini-depto', 'TPA003' => 'Depto completo'];
        $generoLabels = ['GEXA001' => 'Hombres', 'GEXA002' => 'Mujeres', 'GEXA003' => 'Mixto'];

        $this->render('alojamiento/detalle', [
            'a'                    => $a,
            'resenas'              => $resenas,
            'totalResenas'         => $totalResenas,
            'distribucion'         => $distribucion,
            'totalAlojamientosProp'=> $totalAlojamientosProp,
            'monedaLabels'         => $monedaLabels,
            'tipoLabels'           => $tipoLabels,
            'generoLabels'         => $generoLabels,
            'logueado'             => isset($_SESSION['usuario_id']),
            'paginaResenas'        => 1,
            'porPaginaResenas'     => 5,
            'hayMasResenas'        => $totalResenas > 5,
        ], 'public');
    }

    /**
     * GET /alojamiento/{id}/resenas — AJAX, página de reseñas (JSON {html, hayMas}).
     */
    public function resenas($id = null)
    {
        $id = $id ?? $_GET['id'] ?? null;
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        header('Content-Type: application/json; charset=utf-8');
        if (!$id) { echo json_encode(['html' => '', 'hayMas' => false]); exit; }

        $modelo = new Alojamiento();
        $resenas = $modelo->getResenas($id, $pagina, 5);
        $total = $modelo->contarResenas($id);

        ob_start();
        require __DIR__ . '/../views/alojamiento/_resenas.php';
        $html = ob_get_clean();

        echo json_encode(['html' => $html, 'hayMas' => ($pagina * 5) < $total]);
        exit;
    }

    /**
     * Lee, sanitiza y valida los filtros del GET.
     */
    private function leerFiltros(): array
    {
        // tipos: whitelist contra catálogo
        $tipos = $_GET['tipo'] ?? [];
        if (!is_array($tipos)) { $tipos = [$tipos]; }
        $tiposValidos = array_column((new Catalogo())->obtenerPorReferencia('TIPO_PUBLICACION_ALOJAMIENTO'), 'codigo');
        $tipos = array_values(array_filter($tipos, fn($t) => in_array($t, $tiposValidos, true)));

        $orden = $_GET['orden'] ?? 'recientes';
        if (!in_array($orden, self::ORDENES, true)) { $orden = 'recientes'; }

        $presupuesto = trim($_GET['presupuesto'] ?? '');
        if ($presupuesto !== '' && !is_numeric($presupuesto)) { $presupuesto = ''; }

        $fecha = trim($_GET['fecha'] ?? '');
        if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $fecha = ''; }

        return [
            'q'                => trim($_GET['q'] ?? ''),
            'tipos'            => $tipos,
            'presupuesto'      => $presupuesto,
            'amoblado'         => !empty($_GET['amoblado']),
            'mascotas'         => !empty($_GET['mascotas']),
            'solo_verificados' => !empty($_GET['solo_verificados']),
            'fecha'            => $fecha,
            'orden'            => $orden,
        ];
    }

    /**
     * Endpoint AJAX para crear o actualizar una reseña sobre un alojamiento.
     */
    public function guardarResena($params = [])
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

        if (empty($usuarioId)) {
            echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión como estudiante para publicar una reseña.']);
            exit;
        }
        $alojamientoId = $params['id'] ?? ($_POST['alojamiento_id'] ?? '');
        $calificacion = intval($_POST['calificacion'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');

        if (empty($alojamientoId)) {
            echo json_encode(['success' => false, 'error' => 'ID de alojamiento inválido.']);
            exit;
        }

        if ($calificacion < 1 || $calificacion > 5) {
            echo json_encode(['success' => false, 'error' => 'Por favor selecciona una calificación válida entre 1 y 5 estrellas.']);
            exit;
        }

        if (empty($comentario) || mb_strlen($comentario) < 5) {
            echo json_encode(['success' => false, 'error' => 'Por favor escribe un comentario de al menos 5 caracteres.']);
            exit;
        }

        $alojamientoModel = new Alojamiento();
        $exito = $alojamientoModel->crearOActualizarResenia($alojamientoId, $usuarioId, $calificacion, $comentario);

        if ($exito) {
            echo json_encode(['success' => true, 'message' => '¡Tu reseña ha sido publicada y guardada exitosamente!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ocurrió un error al guardar la reseña. Inténtalo nuevamente.']);
        }
        exit;
    }
}
