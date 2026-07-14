<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Pago;
use App\Models\Contrato;

/**
 * PagoController
 * Módulo de Pagos y Simulación de Pasarela Interactiva (Tarjeta, Yape/Plin, Transferencia).
 *
 * Rutas:
 *   GET  /pagos             → index   (panel de mis cuotas y pagos)
 *   POST /pago/{id}/simular → simular (procesa pago simulado interactivo)
 */
class PagoController extends Controller
{
    /** Métodos de pago válidos (catálogo METODO_PAGO). */
    private const METODOS_VALIDOS = ['MPG001', 'MPG002', 'MPG003', 'MPG004'];

    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET /pagos — Panel principal de pagos del inquilino.
     */
    public function index()
    {
        $uid = $_SESSION['usuario_id'];
        $pagoModel = new Pago();
        $contratoModel = new Contrato();

        // 1. Asegurar que cada contrato ACTIVO del usuario tenga sus cuotas mensuales generadas
        $contratos = $contratoModel->misContratos($uid, 1, 50);
        foreach ($contratos as $c) {
            // No generar cuotas para contratos finalizados/cancelados
            if (($c['estado_codigo'] ?? '') !== Contrato::EST_ACTIVO) {
                continue;
            }
            $duracion = $this->calcularDuracionMeses($c['fecha_inicio'] ?? null, $c['fecha_fin'] ?? null);
            $pagoModel->generarCuotasContrato(
                $c['contrato_id'],
                $duracion,
                (float)($c['monto_renta'] ?? 0),
                $c['fecha_inicio'] ?? date('Y-m-d'),
                $uid
            );
        }

        // 2. Obtener todas las cuotas del inquilino
        $pagos = $pagoModel->obtenerPagosPorInquilino($uid);

        // 3. Calcular estadísticas rápidas
        $cuotasPendientes = 0;
        $proximaCuota = null;
        $totalesPorMoneda = []; // ['S/' => 123.45, 'US$' => 67.89]

        foreach ($pagos as $p) {
            $simbolo = ($p['moneda_codigo'] === 'TPM002') ? 'US$' : 'S/';
            if ($p['estado_codigo'] === Pago::EST_COMPLETADO) {
                if (!isset($totalesPorMoneda[$simbolo])) {
                    $totalesPorMoneda[$simbolo] = 0.0;
                }
                $totalesPorMoneda[$simbolo] += (float)$p['monto'];
            } elseif (in_array($p['estado_codigo'], [Pago::EST_PENDIENTE, Pago::EST_FALLIDO])) {
                $cuotasPendientes++;
                if ($proximaCuota === null) {
                    $proximaCuota = $p;
                }
            }
        }

        $this->render('pago/index', [
            'pagos'            => $pagos,
            'totalesPorMoneda' => $totalesPorMoneda,
            'cuotasPendientes' => $cuotasPendientes,
            'proximaCuota'     => $proximaCuota,
        ], 'main');
    }

    /**
     * Calcula la duración en meses entre dos fechas (inclusive el mes final).
     * Devuelve al menos 1. Si las fechas son inválidas, usa 6 como valor por defecto.
     */
    private function calcularDuracionMeses(?string $fechaInicio, ?string $fechaFin): int
    {
        if (empty($fechaInicio) || empty($fechaFin)) {
            return 6;
        }
        $t1 = strtotime($fechaInicio);
        $t2 = strtotime($fechaFin);
        if ($t1 === false || $t2 === false || $t2 <= $t1) {
            return 6;
        }
        $d1 = new \DateTime($fechaInicio);
        $d2 = new \DateTime($fechaFin);
        $meses = ($d2->format('Y') - $d1->format('Y')) * 12 + ($d2->format('m') - $d1->format('m'));
        $meses = (int)$meses + 1; // contar el mes de inicio como primera cuota
        return max(1, $meses);
    }

    /**
     * POST /pago/{id}/simular — Simulación de transacción de pago.
     */
    public function simular($id = null)
    {
        $uid = $_SESSION['usuario_id'];
        $pagoModel = new Pago();

        $pago_id = $id ?? ($_GET['id'] ?? null);
        if (!$pago_id) {
            $this->devolverRespuesta(false, 'Cuota no encontrada o no autorizada.');
            return;
        }

        $pago = $pagoModel->obtenerPorId($pago_id, $uid);
        if (!$pago) {
            $this->devolverRespuesta(false, 'Cuota no encontrada o no autorizada.');
            return;
        }

        if ($pago['estado_codigo'] === Pago::EST_COMPLETADO) {
            $this->devolverRespuesta(false, 'Esta cuota ya ha sido pagada previamente.');
            return;
        }

        $metodo = trim($_POST['metodo_pago'] ?? '');
        if (!in_array($metodo, self::METODOS_VALIDOS, true)) {
            $this->devolverRespuesta(false, 'Método de pago no válido.');
            return;
        }

        $referenciaIngresada = trim($_POST['referencia'] ?? '');
        $codigoBancario = 'OPER-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

        $referenciaFinal = $referenciaIngresada !== '' ? $referenciaIngresada : $codigoBancario;

        $ok = $pagoModel->simularPago($pago_id, $metodo, $referenciaFinal, $uid);

        if ($ok) {
            $this->devolverRespuesta(true, '¡Pago procesado con éxito!', [
                'comprobante' => $referenciaFinal,
                'pago_id'     => $pago_id
            ]);
        } else {
            $this->devolverRespuesta(false, 'Ocurrió un error al procesar el pago bancario simulado.');
        }
    }

    private function devolverRespuesta(bool $exito, string $mensaje, array $extra = [])
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_merge([
                'success' => $exito,
                'message' => $mensaje,
            ], $extra));
            exit;
        }

        if ($exito) {
            $this->setFlash('success', $mensaje . (!empty($extra['comprobante']) ? ' Nro. Operación: ' . $extra['comprobante'] : ''));
        } else {
            $this->setFlash('error', $mensaje);
        }
        $this->redirect('/pagos');
    }
}
