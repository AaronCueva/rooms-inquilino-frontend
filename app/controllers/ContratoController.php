<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contrato;

/**
 * ContratoController (W4 — Contrato digital, lado inquilino, ref §3.6).
 *
 * Rutas (registradas en index.php):
 *   GET  /contratos             → index   (lista mis contratos)
 *   GET  /contrato/{id}         → ver     (detalle + PDF embebido + firma)
 *   GET  /contrato/{id}/pdf     → pdf     (redirect al PDF subido por el propietario)
 *   POST /contrato/{id}/firmar  → firmar  (firma stub v1)
 *
 * Requiere sesión (constructor → /login). Layout: main.
 * El PDF lo carga el propietario desde su admin (contrato.multimedia_id → multimedia).
 */
class ContratoController extends Controller
{
    private const POR_PAGINA = 10;

    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET /contratos — lista paginada de contratos del inquilino.
     */
    public function index()
    {
        $uid = $_SESSION['usuario_id'];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $modelo = new Contrato();

        $contratos = $modelo->misContratos($uid, $pagina, self::POR_PAGINA);
        $total = $modelo->contarMisContratos($uid);
        $totalPaginas = (int)ceil($total / self::POR_PAGINA);

        $this->render('contratos/index', [
            'contratos'    => $contratos,
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'monedaLabels' => ['TPM001' => 'S/', 'TPM002' => 'US$'],
        ], 'main');
    }

    /**
     * GET /contrato/{id} — detalle del contrato.
     */
    public function ver($id = null)
    {
        $uid = $_SESSION['usuario_id'];
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) { $this->redirect('/contratos'); }

        $modelo = new Contrato();
        $c = $modelo->findById($id, $uid);
        if (!$c) {
            $this->setFlash('error', 'El contrato no existe o no te pertenece.');
            $this->redirect('/contratos');
        }

        $estados = [
            'ESCO001' => ['label' => 'Activo',    'bg' => '#E8FB9E', 'color' => '#1A2FB0'],
            'ESCO002' => ['label' => 'Finalizado', 'bg' => '#F1F5F9', 'color' => '#6B6F7A'],
            'ESCO003' => ['label' => 'Cancelado',  'bg' => '#FDECEC', 'color' => '#B23B3B'],
        ];

        $this->render('contrato/ver', [
            'c'           => $c,
            'estadosMap'  => $estados,
            'monedaLabels'=> ['TPM001' => 'S/', 'TPM002' => 'US$'],
            'tienePdf'    => !empty($c['multimedia_id']),
            'firmado'     => !empty($c['fecha_firma_inquilino']),
        ], 'main');
    }

    /**
     * GET /contrato/{id}/pdf — redirect al PDF subido por el propietario.
     */
    public function pdf($id = null)
    {
        $uid = $_SESSION['usuario_id'];
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) { $this->redirect('/contratos'); }

        $c = (new Contrato())->findById($id, $uid);
        if (!$c) {
            $this->setFlash('error', 'El contrato no existe o no te pertenece.');
            $this->redirect('/contratos');
        }
        if (empty($c['pdf_url'])) {
            $this->setFlash('warning', 'El propietario aún no carga el documento del contrato.');
            $this->redirect('/contrato/' . $id);
        }

        $this->redirect($c['pdf_url']);
    }

    /**
     * POST /contrato/{id}/firmar — firma del inquilino (stub v1).
     */
    public function firmar($id = null)
    {
        $uid = $_SESSION['usuario_id'];
        $id = $id ?? $_POST['id'] ?? null;
        if (!$id) { $this->redirect('/contratos'); }

        $res = (new Contrato())->firmar($id, $uid);
        if (!$res['ok']) {
            $this->setFlash('error', $res['reason']);
        } else {
            $this->setFlash('success', 'Contrato firmado correctamente.');
        }
        $this->redirect('/contrato/' . $id);
    }
}
