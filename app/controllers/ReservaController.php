<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Reserva;
use App\Models\Alojamiento;
use App\Models\Catalogo;

/**
 * ReservaController (W3 — Reserva y pago, ref §3.5).
 *
 * Rutas (registradas en index.php):
 *   GET  /reserva/crear              → crear         (form solicitud)
 *   POST /reserva/crear              → crear         (valida + crea reserva PENDIENTE)
 *   GET  /reservas                   → misReservas   (lista paginada)
 *   POST /reserva/cancelar           → cancelar      (cancela con reembolso informativo)
 *   GET  /cron/reservas-expiradas    → cronExpiradas (job on-request, cancela PENDIENTE >48h)
 *
 * Acciones de reserva requieren sesión (constructor → /login). cronExpiradas no.
 * Layout: main. Vistas: reserva/crear, reservas/index.
 *
 * Pago v1 = stub: el método de pago se registra como referencia en reserva.observacion
 * (no hay cobro real ni fila en tabla pago). Ver PLAN-INQUILINO.md W3.
 */
class ReservaController extends Controller
{
    /** Key simple para el endpoint cron on-request (v1 — sin .env). */
    private const CRON_KEY = 'W3CRON_NIDO_2026';

    /** Mensaje de presentación: mínimo de caracteres. */
    private const MSG_MIN_CHARS = 50;

    /** Paginación de "Mis reservas". */
    private const POR_PAGINA = 10;

    public function __construct()
    {
        // Las acciones de reserva exigen sesión; cronExpiradas es público.
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if ($uri !== '/cron/reservas-expiradas' && !isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET/POST /reserva/crear
     * GET  ?alojamiento=<id>[&fecha=Y-m-d][&meses=N]  → muestra formulario.
     * POST alojamiento_id, fecha_ingreso, duracion_meses, mensaje_presentacion,
     *      metodo_pago, observacion → crea reserva PENDIENTE.
     */
    public function crear()
    {
        $uid = $_SESSION['usuario_id'];
        $reservaModel = new Reserva();
        $alojModel = new Alojamiento();

        // ---- Verificación de identidad (W5): requisito para reservar ----
        $puede = $reservaModel->validarPuedeReservar($uid);
        if (!$puede['ok']) {
            $this->setFlash('warning', $puede['reason']);
            $this->redirect('/perfil/verificar');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->crearPost($uid, $reservaModel, $alojModel);
            return; // crearPost siempre termina con redirect/render
        }

        $this->crearGet($uid, $reservaModel, $alojModel);
    }

    /**
     * GET /reserva/crear — prepara y renderiza el formulario.
     */
    private function crearGet(string $uid, Reserva $reservaModel, Alojamiento $alojModel)
    {
        $alojId = trim($_GET['alojamiento'] ?? '');
        $a = $alojId ? $alojModel->findById($alojId) : null;

        if (!$a || empty($a['habilitado']) || !in_array($a['estado_codigo'], Alojamiento::ESTADOS_VISIBLES, true)) {
            $this->setFlash('error', 'El alojamiento no existe o no está disponible para reserva.');
            $this->redirect('/buscar');
        }

        $minDur = max(1, (int)($a['duracion_minima_meses'] ?? 1));
        $precio = (float)($a['precio_mensual'] ?? 0);
        $garantia = (float)($a['garantia'] ?? 0);

        // Pre-selección desde el sidebar de la ficha
        $fechaPre = trim($_GET['fecha'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPre)) { $fechaPre = ''; }
        $mesesPre = (int)($_GET['meses'] ?? 0);
        if ($mesesPre < $minDur || $mesesPre > 12) { $mesesPre = $minDur; }

        $costos = $reservaModel->calcularCostos($precio, $garantia, $mesesPre);
        $metodosPago = (new Catalogo())->obtenerPorReferencia('METODO_PAGO');

        $monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
        $mon = $monedaLabels[$a['moneda_codigo'] ?? ''] ?? 'S/';

        $this->render('reserva/crear', [
            'a'           => $a,
            'mon'         => $mon,
            'monedaLabels'=> $monedaLabels,
            'minFecha'    => date('Y-m-d', strtotime('+3 days')),
            'minDur'      => $minDur,
            'fechaPre'    => $fechaPre,
            'mesesPre'    => $mesesPre,
            'precio'      => $precio,
            'garantia'    => $garantia,
            'costos'      => $costos,
            'metodosPago' => $metodosPago,
            'msgMinChars' => self::MSG_MIN_CHARS,
        ], 'main');
    }

    /**
     * POST /reserva/crear — valida y crea la solicitud de reserva.
     */
    private function crearPost(string $uid, Reserva $reservaModel, Alojamiento $alojModel)
    {
        $alojId = trim($_POST['alojamiento_id'] ?? '');
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
        $duracion = (int)($_POST['duracion_meses'] ?? 0);
        $mensaje = trim($_POST['mensaje_presentacion'] ?? '');
        $metodo = trim($_POST['metodo_pago'] ?? '');
        $observacion = trim($_POST['observacion'] ?? '');

        $a = $alojId ? $alojModel->findById($alojId) : null;
        if (!$a || empty($a['habilitado']) || !in_array($a['estado_codigo'], Alojamiento::ESTADOS_VISIBLES, true)) {
            $this->setFlash('error', 'El alojamiento no existe o no está disponible para reserva.');
            $this->redirect('/buscar');
        }

        $minDur = max(1, (int)($a['duracion_minima_meses'] ?? 1));
        $minFecha = date('Y-m-d', strtotime('+3 days'));
        $metodosValidos = array_column((new Catalogo())->obtenerPorReferencia('METODO_PAGO'), 'codigo');

        // ---- Validaciones ----
        $errores = [];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaIngreso) || $fechaIngreso < $minFecha) {
            $errores[] = 'La fecha de ingreso debe ser a partir de ' . $minFecha . '.';
        }
        if ($duracion < $minDur || $duracion > 12) {
            $errores[] = 'La duración debe ser entre ' . $minDur . ' y 12 meses.';
        }
        $msgLen = function_exists('mb_strlen') ? \mb_strlen($mensaje) : strlen($mensaje);
        if ($msgLen < self::MSG_MIN_CHARS) {
            $errores[] = 'El mensaje de presentación debe tener al menos ' . self::MSG_MIN_CHARS . ' caracteres.';
        }
        if (!in_array($metodo, $metodosValidos, true)) {
            $errores[] = 'Selecciona un método de pago válido.';
        }

        if ($errores) {
            $precio = (float)($a['precio_mensual'] ?? 0);
            $garantia = (float)($a['garantia'] ?? 0);
            $costos = $reservaModel->calcularCostos($precio, $garantia, max($duracion, $minDur));
            $monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
            $mon = $monedaLabels[$a['moneda_codigo'] ?? ''] ?? 'S/';

            $this->render('reserva/crear', [
                'a'           => $a,
                'mon'         => $mon,
                'monedaLabels'=> $monedaLabels,
                'minFecha'    => $minFecha,
                'minDur'      => $minDur,
                'fechaPre'    => $fechaIngreso,
                'mesesPre'    => max($duracion, $minDur),
                'precio'      => $precio,
                'garantia'    => $garantia,
                'costos'      => $costos,
                'metodosPago' => (new Catalogo())->obtenerPorReferencia('METODO_PAGO'),
                'msgMinChars' => self::MSG_MIN_CHARS,
                'errores'     => $errores,
                'old'         => ['mensaje_presentacion' => $mensaje, 'metodo_pago' => $metodo, 'observacion' => $observacion],
            ], 'main');
            return;
        }

        // ---- Costos server-side (source of truth) ----
        $precio = (float)($a['precio_mensual'] ?? 0);
        $garantia = (float)($a['garantia'] ?? 0);
        $montoTotal = $reservaModel->calcularCostos($precio, $garantia, $duracion)['total'];

        $fila = $reservaModel->crear([
            'usuario_id'          => $uid,
            'alojamiento_id'      => $alojId,
            'fecha_ingreso'       => $fechaIngreso,
            'duracion_meses'      => $duracion,
            'monto_total'         => $montoTotal,
            'mensaje_presentacion'=> $mensaje,
            'metodo_pago'         => $metodo,
            'observacion'         => $observacion,
        ]);

        if (!$fila) {
            $this->setFlash('error', 'No se pudo crear la solicitud. Es posible que ya tengas una reserva activa para este alojamiento.');
            $this->redirect('/reserva/crear?alojamiento=' . urlencode($alojId));
        }

        $this->setFlash('success', 'Solicitud de reserva enviada. El propietario tiene 48h para responder.');
        $this->redirect('/reservas');
    }

    /**
     * GET /reservas — mis reservas paginadas.
     */
    public function misReservas()
    {
        $uid = $_SESSION['usuario_id'];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $modelo = new Reserva();

        $reservas = $modelo->misReservas($uid, $pagina, self::POR_PAGINA);
        $total = $modelo->contarMisReservas($uid);
        $totalPaginas = (int)ceil($total / self::POR_PAGINA);

        $this->render('reservas/index', [
            'reservas'    => $reservas,
            'total'       => $total,
            'pagina'      => $pagina,
            'totalPaginas'=> $totalPaginas,
            'porPagina'   => self::POR_PAGINA,
            'estadosCancelables' => Reserva::ESTADOS_CANCELABLES,
            'monedaLabels'=> ['TPM001' => 'S/', 'TPM002' => 'US$'],
        ], 'main');
    }

    /**
     * POST /reserva/cancelar — cancela una reserva del usuario (con reembolso informativo).
     */
    public function cancelar()
    {
        $uid = $_SESSION['usuario_id'];
        $reservaId = trim($_POST['reserva_id'] ?? '');
        if ($reservaId === '') {
            $this->setFlash('error', 'Reserva no especificada.');
            $this->redirect('/reservas');
        }

        $res = (new Reserva())->cancelar($reservaId, $uid);
        if (!$res['ok']) {
            $this->setFlash('error', $res['reason']);
            $this->redirect('/reservas');
        }

        $msg = 'Reserva cancelada. Reembolso estimado: ' . $res['reembolso_pct'] . '%.';
        $this->setFlash('success', $msg);
        $this->redirect('/reservas');
    }

    /**
     * GET /cron/reservas-expiradas?key=... — job on-request: cancela PENDIENTE >48h.
     * No requiere sesión. Devuelve texto plano.
     */
    public function cronExpiradas()
    {
        // El constructor exime este endpoint del chequeo de sesión.
        $key = $_GET['key'] ?? '';
        header('Content-Type: text/plain; charset=utf-8');
        if (!hash_equals(self::CRON_KEY, $key)) {
            http_response_code(403);
            echo "Forbidden\n";
            exit;
        }
        $n = (new Reserva())->cancelarExpiradas();
        echo "Reservas expiradas canceladas: {$n}\n";
        exit;
    }
}
