<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Referido;

/**
 * Programa de referidos (W8.2, ref §3.7.3).
 * Requiere sesión (app-shell inquilino, layout main).
 */
class ReferidoController extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET /referidos — Mis referidos: código shareable, lista, resumen.
     */
    public function index()
    {
        $uid = $_SESSION['usuario_id'];
        $referidoModel = new Referido();

        $codigo          = $referidoModel->obtenerMiCodigo($uid);
        $referidos       = $referidoModel->misReferidos($uid);
        $totalAcreditados = $referidoModel->contarActivos($uid);
        $totalPendientes  = $referidoModel->contarPendientes($uid);
        $puntosGanados   = $totalAcreditados * Referido::PUNTOS_REFERIDO;

        // Puntos actuales del usuario (PuntosNido — W5.8). Try/catch: puede no estar listo.
        $puntosActuales = 0;
        try {
            $puntosActuales = (int)\App\Models\PuntosNido::obtener($uid);
        } catch (\Throwable $e) {
            $puntosActuales = 0;
        }

        $this->render('inquilino/referidos/index', [
            'codigo'           => $codigo,
            'referidos'        => $referidos,
            'totalAcreditados' => $totalAcreditados,
            'totalPendientes'  => $totalPendientes,
            'totalReferidos'   => count($referidos),
            'puntosGanados'    => $puntosGanados,
            'puntosActuales'   => $puntosActuales,
            'puntosPorReferido' => Referido::PUNTOS_REFERIDO,
        ], 'main');
    }

    /**
     * POST /referidos/codigo — JSON: devuelve/regenera el código shareable del usuario.
     */
    public function generarCodigo()
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid = $_SESSION['usuario_id'];
        $referidoModel = new Referido();
        $codigo = $referidoModel->obtenerMiCodigo($uid);
        echo json_encode(['ok' => true, 'codigo' => $codigo]);
        exit;
    }
}
