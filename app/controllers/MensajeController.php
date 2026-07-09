<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Chat;
use App\Models\Alojamiento;

/**
 * Chat inquilino ↔ propietario (W6).
 * Requiere sesión: el constructor redirige a /login si no hay usuario.
 */
class MensajeController extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * GET /mensajes — bandeja de chats + conversación activa.
     */
    public function index(): void
    {
        $uid = $_SESSION['usuario_id'];
        $chatActivo = isset($_GET['chat']) ? (string)$_GET['chat'] : null;
        $busqueda = trim($_GET['q'] ?? '');

        $chatModel = new Chat();
        $chats = $chatModel->getChatsByUsuario($uid, $busqueda);

        $mensajes = [];
        $otro = null;

        if ($chatActivo !== null && $chatModel->esParticipante($chatActivo, $uid)) {
            $mensajes = $chatModel->getMensajes($chatActivo, $uid);
            $chatModel->marcarLeido($chatActivo, $uid);
            $otro = $chatModel->getOtroParticipante($chatActivo, $uid);
        }
        // Si $chatActivo no es participante: no exponer datos ajenos.

        $totalNoLeidos = $chatModel->contarNoLeidos($uid);

        require_once __DIR__ . '/../config/supabase.php';

        $this->render('mensajes/index', [
            'chats'           => $chats,
            'chatActivo'      => $chatActivo,
            'mensajes'        => $mensajes,
            'otro'            => $otro,
            'busqueda'        => $busqueda,
            'totalNoLeidos'   => $totalNoLeidos,
            'supabaseUrl'     => SUPABASE_URL,
            'supabaseAnonKey' => SUPABASE_ANON_KEY,
            'uid'             => $uid,
        ], 'public');
    }

    /**
     * GET /mensajes/abrir?alojamiento={id} — crea/obtiene el chat con el
     * propietario del alojamiento y redirige a la conversación.
     */
    public function abrir(): void
    {
        $alojamientoId = $_GET['alojamiento'] ?? null;
        if (!$alojamientoId) {
            $this->setFlash('error', 'Alojamiento inválido.');
            $this->redirect('/buscar');
        }

        $a = (new Alojamiento())->findById($alojamientoId);
        if (!$a) {
            $this->setFlash('error', 'Alojamiento inválido.');
            $this->redirect('/buscar');
        }

        $propietarioId = $a['propietario_id'] ?? null;
        if (!$propietarioId) {
            $this->setFlash('error', 'Alojamiento inválido.');
            $this->redirect('/buscar');
        }

        $uid = $_SESSION['usuario_id'];
        if ($propietarioId === $uid) {
            $this->setFlash('error', 'No puedes contactarte a ti mismo.');
            $this->redirect('/alojamiento/' . $alojamientoId);
        }

        $chatId = (new Chat())->getOrCreateChat($uid, $propietarioId);
        if ($chatId === null) {
            $this->setFlash('error', 'No se pudo iniciar la conversación.');
            $this->redirect('/alojamiento/' . $alojamientoId);
        }

        $this->redirect('/mensajes?chat=' . $chatId);
    }

    /**
     * POST /mensajes/enviar — envía un mensaje; responde JSON.
     */
    public function enviar(): void
    {
        $uid = $_SESSION['usuario_id'];
        $chatId = (string)($_POST['chat_id'] ?? '');
        $contenido = (string)($_POST['contenido'] ?? '');

        header('Content-Type: application/json; charset=utf-8');

        if ($chatId === '') {
            echo json_encode(['ok' => false, 'error' => 'chat inválido']);
            exit;
        }

        $chatModel = new Chat();
        if (!$chatModel->esParticipante($chatId, $uid)) {
            echo json_encode(['ok' => false, 'error' => 'no autorizado']);
            exit;
        }

        $msg = $chatModel->enviarMensaje($chatId, $uid, $contenido);
        if ($msg === null) {
            echo json_encode(['ok' => false, 'error' => 'mensaje inválido (vacío o >2000 chars)']);
            exit;
        }

        echo json_encode(['ok' => true, 'mensaje' => $msg]);
        exit;
    }

    /**
     * GET /mensajes/nuevo?chat={id}&ultimo={mensaje_id} — AJAX polling.
     * Devuelve JSON con los mensajes posteriores a `ultimo` (o todos si vacío).
     */
    public function nuevo(): void
    {
        $uid = $_SESSION['usuario_id'];
        $chatId = (string)($_GET['chat'] ?? '');
        $ultimo = $_GET['ultimo'] ?? '';

        header('Content-Type: application/json; charset=utf-8');

        $chatModel = new Chat();
        if ($chatId === '' || !$chatModel->esParticipante($chatId, $uid)) {
            echo json_encode(['ok' => false, 'mensajes' => []]);
            exit;
        }

        $todos = $chatModel->getMensajes($chatId, $uid);

        $nuevos = [];
        if ($ultimo === '') {
            $nuevos = $todos;
        } else {
            $encontrado = false;
            foreach ($todos as $m) {
                if ($encontrado) {
                    $nuevos[] = $m;
                    continue;
                }
                if (($m['mensaje_id'] ?? null) === $ultimo) {
                    $encontrado = true;
                }
            }
        }

        echo json_encode(['ok' => true, 'mensajes' => $nuevos]);
        exit;
    }
}
