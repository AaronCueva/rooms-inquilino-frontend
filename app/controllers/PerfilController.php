<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuario;
use App\Models\UniversidadModel;
use App\Models\Catalogo;
use App\Models\VerificacionEstudiantil;
use App\Models\PuntosNido;
use App\Models\Chat;
use App\Models\Referido;
use App\Models\Blog;

/**
 * PerfilController (W5.6 edición de perfil + W5.4 verificación de identidad).
 *
 * Rutas (registradas en index.php por el usuario):
 *   GET  /perfil                    → index          (form edición perfil)
 *   POST /perfil                    → guardar        (guardarPerfil + redirect)
 *   GET  /perfil/verificacion       → verificacion   (estado + form subida carnet)
 *   POST /perfil/verificacion/subir → subirDocumento (guarda multimedia DOCUMENTO)
 *
 * Todas las acciones requieren sesión (constructor → redirect /login).
 * Layout: main. Vistas: inquilino/perfil/index, inquilino/perfil/verificacion.
 */
class PerfilController extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET /perfil — formulario de edición de perfil.
     * Carga usuario, universidades y catálogos (tipo documento, género).
     */
    public function index()
    {
        $uid = $_SESSION['usuario_id'];
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById($uid);

        $universidades = (new UniversidadModel())->obtenerTodas();
        $catalogo = new Catalogo();
        $tiposDocumento = $catalogo->obtenerPorReferencia('TIPO_DOCUMENTO');
        $generos = $catalogo->obtenerPorReferencia('GENERO');

        $this->render('inquilino/perfil/index', [
            'usuario'        => $usuario,
            'universidades'  => $universidades,
            'tiposDocumento' => $tiposDocumento,
            'generos'        => $generos,
        ], 'main');
    }

    /**
     * POST /perfil — guardar cambios del perfil.
     * Lee solo campos del whitelist, delega a VerificacionEstudiantil::guardarPerfil.
     */
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil');
        }

        $uid = $_SESSION['usuario_id'];
        $campos = $this->leerCamposPerfil($_POST);

        $modelo = new VerificacionEstudiantil();
        $ok = $modelo->guardarPerfil($uid, $campos);

        if ($ok) {
            $this->setFlash('success', 'Tu perfil se actualizó correctamente.');
        } else {
            $this->setFlash('error', 'No se pudo guardar el perfil. Inténtalo de nuevo.');
        }
        $this->redirect('/perfil');
    }

    /**
     * GET /perfil/verificacion — estado de verificación de identidad estudiantil.
     * Carga documentos subidos y check de correo institucional.
     */
    public function verificacion()
    {
        $uid = $_SESSION['usuario_id'];
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById($uid);

        $verifModel = new VerificacionEstudiantil();
        $documentos = $verifModel->obtenerDocumentos($uid);
        $esInstitucional = $verifModel->esCorreoInstitucional($usuario['correo'] ?? '');

        $this->render('inquilino/perfil/verificacion', [
            'usuario'         => $usuario,
            'documentos'      => $documentos,
            'esInstitucional' => $esInstitucional,
        ], 'main');
    }

    /**
     * POST /perfil/verificacion/subir — subir carnet/constancia.
     * v1: acepta $_FILES['documento'] (move_uploaded_file a /public/uploads/verificacion/)
     * o $_POST['url'] (stub). Guarda registro en multimedia (tipo DOCUMENTO).
     * Si el correo es institucional, marca verificado=true automático.
     */
    public function subirDocumento()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil/verificacion');
        }

        $uid = $_SESSION['usuario_id'];
        $verifModel = new VerificacionEstudiantil();
        $url = null;
        $nombre = null;

        // Upload real vía $_FILES
        if (!empty($_FILES['documento']['name']) && ($_FILES['documento']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../public/uploads/verificacion';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $ext = pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION);
            $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            $basename = pathinfo($_FILES['documento']['name'], PATHINFO_FILENAME);
            $nombre = $basename . '.' . $ext;
            $filename = $uid . '_' . time() . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($_FILES['documento']['tmp_name'], $dest)) {
                $url = '/public/uploads/verificacion/' . $filename;
            }
        }

        // Stub: URL pasada directamente en $_POST
        if (!$url && !empty($_POST['url'])) {
            $url = trim($_POST['url']);
            $nombre = trim($_POST['nombre'] ?? 'documento');
        }

        if (!$url) {
            $this->setFlash('error', 'No se recibió ningún documento.');
            $this->redirect('/perfil/verificacion');
        }

        $id = $verifModel->subirDocumento($uid, $url, $nombre ?: 'documento');
        if ($id) {
            $this->setFlash('success', 'Documento subido. Queda pendiente de revisión por el equipo Nido.');
        } else {
            $this->setFlash('error', 'No se pudo registrar el documento. Inténtalo de nuevo.');
        }
        $this->redirect('/perfil/verificacion');
    }

    /**
     * GET /dashboard — dashboard real del inquilino (W5.10).
     * Reemplaza al mockup. 11 secciones; las de módulos no aterrizados quedan como stub.
     */
    public function dashboard()
    {
        $uid = $_SESSION['usuario_id'];
        $usuario = (new Usuario())->findById($uid);

        $puntosModel = new PuntosNido();
        $saldo = $puntosModel->obtener($uid);
        $nivel = $puntosModel->nivel($saldo);
        $racha = $puntosModel->racha($uid);
        $ultMov = $puntosModel->movimientos($uid, 1, 5);

        // W6 — mensajes no leídos
        $noLeidos = 0;
        try {
            $noLeidos = (new Chat())->contarNoLeidos($uid);
        } catch (\Throwable $e) {
            $noLeidos = 0;
        }

        // W8.2 — resumen referidos (si aterrizó)
        $refActivos = 0;
        $refPendientes = 0;
        try {
            $refModel = new Referido();
            $refActivos    = $refModel->contarActivos($uid);
            $refPendientes = $refModel->contarPendientes($uid);
        } catch (\Throwable $e) {
            // W8.2 no disponible → la vista muestra stub
        }

        // W8.1 — últimos posts del blog (si aterrizó)
        $blogRecientes = [];
        try {
            $blogRecientes = (new Blog())->getRecientes(3);
        } catch (\Throwable $e) {
            $blogRecientes = [];
        }

        $descuentos = $this->listarDescuentos();
        $beneficios = $this->listarBeneficios();

        $this->render('inquilino/perfil/dashboard', [
            'usuario'        => $usuario,
            'saldo'          => $saldo,
            'nivel'          => $nivel,
            'racha'          => $racha,
            'ultMov'         => $ultMov,
            'noLeidos'       => $noLeidos,
            'refActivos'     => $refActivos,
            'refPendientes'  => $refPendientes,
            'blogRecientes'  => $blogRecientes,
            'descuentos'     => $descuentos,
            'beneficios'     => $beneficios,
        ], 'main');
    }

    /**
     * GET /puntos — mis puntos Nido: saldo, nivel, racha, historial, form canje (W5.13).
     */
    public function puntos()
    {
        $uid = $_SESSION['usuario_id'];
        $usuario = (new Usuario())->findById($uid);

        $puntosModel = new PuntosNido();
        $saldo = $puntosModel->obtener($uid);
        $nivel = $puntosModel->nivel($saldo);
        $racha = $puntosModel->racha($uid);

        $pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $movimientos = $puntosModel->movimientos($uid, $pagina, 15);

        $this->render('inquilino/perfil/puntos', [
            'usuario'     => $usuario,
            'saldo'       => $saldo,
            'nivel'       => $nivel,
            'racha'       => $racha,
            'movimientos' => $movimientos,
            'pagina'      => $pagina,
        ], 'main');
    }

    /**
     * POST /puntos/canjear — canje de puntos (W5.13).
     * Acepta AJAX (devuelve JSON) o form normal (flash + redirect /puntos).
     */
    public function canjear()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/puntos');
        }

        $uid = $_SESSION['usuario_id'];
        $puntos = (int)($_POST['puntos_a_canjear'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? 'Canje de puntos Nido');

        $esAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if ($puntos <= 0) {
            if ($esAjax) { $this->json(['ok' => false, 'error' => 'Cantidad inválida.']); }
            $this->setFlash('error', 'La cantidad a canjear debe ser mayor a 0.');
            $this->redirect('/puntos');
        }

        $modelo = new PuntosNido();
        if ($modelo->obtener($uid) < $puntos) {
            if ($esAjax) { $this->json(['ok' => false, 'error' => 'Saldo insuficiente.']); }
            $this->setFlash('error', 'No tienes suficientes puntos para este canje.');
            $this->redirect('/puntos');
        }

        $ok = $modelo->canjear($uid, $puntos, $descripcion);
        if ($ok) {
            if ($esAjax) { $this->json(['ok' => true, 'nuevo_saldo' => $modelo->obtener($uid)]); }
            $this->setFlash('success', 'Canje realizado por ' . $puntos . ' puntos.');
        } else {
            if ($esAjax) { $this->json(['ok' => false, 'error' => 'No se pudo procesar el canje.']); }
            $this->setFlash('error', 'No se pudo procesar el canje. Inténtalo de nuevo.');
        }
        $this->redirect('/puntos');
    }

    /**
     * SELECT simple de descuentos habilitados (solo lectura). Devuelve [] si la tabla/columna falla.
     */
    private function listarDescuentos(int $limite = 6): array
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM descuento WHERE habilitado = true ORDER BY creado DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limite, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * SELECT simple de beneficios habilitados (solo lectura). Devuelve [] si la tabla/columna falla.
     */
    private function listarBeneficios(int $limite = 6): array
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM beneficio WHERE habilitado = true ORDER BY creado DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limite, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Devuelve JSON y termina.
     */
    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    /**
     * Lee y normaliza los campos editables del perfil desde $source ($_POST).
     * Solo respeta columnas en VerificacionEstudiantil::CAMPOS_PERFIL (whitelist).
     */
    private function leerCamposPerfil(array $source): array
    {
        $campos = [];
        foreach (VerificacionEstudiantil::CAMPOS_PERFIL as $col) {
            if (!array_key_exists($col, $source)) {
                continue;
            }
            $campos[$col] = trim($source[$col]);
        }

        // anio_ingreso: entero o null
        if (array_key_exists('anio_ingreso', $campos)) {
            $campos['anio_ingreso'] = $campos['anio_ingreso'] !== '' ? (int)$campos['anio_ingreso'] : null;
        }
        // universidad_id vacío → null (UUID)
        if (array_key_exists('universidad_id', $campos) && $campos['universidad_id'] === '') {
            $campos['universidad_id'] = null;
        }
        // genero_codigo vacío → null (varchar catálogo)
        if (array_key_exists('genero_codigo', $campos) && $campos['genero_codigo'] === '') {
            $campos['genero_codigo'] = null;
        }

        return $campos;
    }
}
