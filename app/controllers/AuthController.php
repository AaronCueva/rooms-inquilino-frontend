<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Catalogo;
use App\Models\Ubicacion;
use App\Models\UniversidadModel;
use PDO;

class AuthController extends Controller
{
    private $usuarioModel;
    private $rolModel;
    private $catalogoModel;
    private $ubicacionModel;
    private $universidadModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->rolModel = new Rol();
        $this->catalogoModel = new Catalogo();
        $this->ubicacionModel = new Ubicacion();
        $this->universidadModel = new UniversidadModel();
    }

    public function showLogin()
    {
        // Si ya está logueado, redirigir a la home
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [], 'auth');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (empty($correo) || empty($password)) {
                $this->setFlash('error', 'Todos los campos son obligatorios.');
                $this->redirect('/login');
            }

            $usuario = $this->usuarioModel->findByEmail($correo);

            if ($usuario) {
                if ($usuario['habilitado'] === false) {
                    $this->setFlash('error', 'Su cuenta ha sido deshabilitada.');
                    $this->redirect('/login');
                }

                if (password_verify($password, $usuario['password'])) {
                    // Validar que tenga el rol de inquilino (u otro permitido en esta app si aplica)
                    $rol_inquilino_id = $this->rolModel->obtenerIdPorCodigo('INQUILINO');

                    if ($usuario['rol_id'] != $rol_inquilino_id) {
                         $this->setFlash('error', 'No tiene permisos para acceder al portal de inquilinos.');
                         $this->redirect('/login');
                    }

                    // Iniciar sesión
                    $_SESSION['usuario_id'] = $usuario['usuario_id'];
                    $_SESSION['nombres'] = $usuario['nombres'];
                    $_SESSION['apellidos'] = $usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno'];
                    $_SESSION['correo'] = $usuario['correo'];
                    $_SESSION['rol_id'] = $usuario['rol_id'];
                    $_SESSION['url_foto'] = $usuario['url_foto'] ?? null;

                    $this->redirect('/dashboard');
                } else {
                    $this->setFlash('error', 'Contraseña incorrecta.');
                    $this->redirect('/login');
                }
            } else {
                $this->setFlash('error', 'No existe una cuenta con este correo.');
                $this->redirect('/login');
            }
        }
    }

    public function showRegister()
    {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect('/dashboard');
        }

        // 8.2 — Capturar código de referido desde la URL (?ref=CODIGO) y persistirlo en sesión
        if (isset($_GET['ref']) && $_GET['ref'] !== '') {
            $_SESSION['ref_pending'] = $_GET['ref'];
        }

        $tipos_documento = $this->catalogoModel->obtenerPorReferencia('TIPO_DOCUMENTO');
        $universidades = $this->universidadModel->obtenerTodas();
        $ref_pending = $_SESSION['ref_pending'] ?? '';

        $this->render('auth/register', [
            'tipos_documento' => $tipos_documento,
            'universidades' => $universidades,
            'ref_pending' => $ref_pending
        ], 'auth');
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Asignar rol INQUILINO automáticamente
            $rol_id = $this->rolModel->obtenerIdPorCodigo('INQUILINO');

            if (!$rol_id) {
                 $this->setFlash('error', 'Error del sistema: Rol de Inquilino no configurado en la base de datos.');
                 $this->redirect('/register');
            }

            $universidad_id = filter_input(INPUT_POST, 'universidad_id', FILTER_SANITIZE_STRING);
            $ubicacion_id = null;

            if ($universidad_id) {
                $universidad = $this->universidadModel->findById($universidad_id);
                if ($universidad && isset($universidad['ubicacion_id'])) {
                    $ubicacion_id = $universidad['ubicacion_id'];
                }
            }

            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            // 5.1 — Validar confirmación de contraseña
            if ($password !== $password_confirm) {
                $this->setFlash('error', 'Las contraseñas no coinciden.');
                $this->redirect('/register');
            }

            // 5.1 — Validar fortaleza de contraseña (no débil)
            $fortaleza = $this->evaluarFortalezaPassword($password);
            if ($fortaleza === 'debil') {
                $this->setFlash('error', 'La contraseña es demasiado débil. Use mínimo 8 caracteres con mayúsculas, minúsculas y números.');
                $this->redirect('/register');
            }

            // 5.1 — Términos y Condiciones obligatorio
            $terminos = isset($_POST['terminos']) && $_POST['terminos'];
            if (!$terminos) {
                $this->setFlash('error', 'Debe aceptar los Términos y Condiciones para registrarse.');
                $this->redirect('/register');
            }

            // 5.1 — Campos nuevos
            $carrera = filter_input(INPUT_POST, 'carrera', FILTER_SANITIZE_STRING);
            $anio_ingreso = filter_input(INPUT_POST, 'anio_ingreso', FILTER_VALIDATE_INT);
            $pais_origen = filter_input(INPUT_POST, 'pais_origen', FILTER_SANITIZE_STRING);

            // Validar rango de año de ingreso
            $anio_actual = (int)date('Y');
            if ($anio_ingreso !== false && $anio_ingreso !== null) {
                if ($anio_ingreso < 2000 || $anio_ingreso > $anio_actual) {
                    $this->setFlash('error', 'El año de ingreso debe estar entre 2000 y ' . $anio_actual . '.');
                    $this->redirect('/register');
                }
            } else {
                $anio_ingreso = null;
            }

            $datos = [
                'nombres' => filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_STRING),
                'apellido_paterno' => filter_input(INPUT_POST, 'apellido_paterno', FILTER_SANITIZE_STRING),
                'apellido_materno' => filter_input(INPUT_POST, 'apellido_materno', FILTER_SANITIZE_STRING),
                'tipo_documento_codigo' => filter_input(INPUT_POST, 'tipo_documento_codigo', FILTER_SANITIZE_STRING),
                'numero_documento' => filter_input(INPUT_POST, 'numero_documento', FILTER_SANITIZE_STRING),
                'correo' => filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL),
                'celular' => filter_input(INPUT_POST, 'celular', FILTER_SANITIZE_STRING),
                'ubicacion_id' => $ubicacion_id,
                'universidad_id' => $universidad_id,
                'password' => $password,
                'rol_id' => $rol_id
            ];

            // Validar que el correo no exista
            $existe = $this->usuarioModel->findByEmail($datos['correo']);
            if ($existe) {
                $this->setFlash('error', 'Ya existe un usuario registrado con este correo.');
                $this->redirect('/register');
            }

            if ($this->usuarioModel->create($datos)) {
                // Recuperar el nuevo usuario para obtener su id
                $nuevoUsuario = $this->usuarioModel->findByEmail($datos['correo']);
                $nuevo_usuario_id = $nuevoUsuario ? $nuevoUsuario['usuario_id'] : null;

                // 5.3 — Generar token de verificación (24h)
                $token = bin2hex(random_bytes(32));
                $expiracion = date('Y-m-d H:i:s', time() + 24 * 60 * 60);

                if ($nuevo_usuario_id) {
                    // 5.1 + 5.3 — Persistir campos nuevos y token
                    // (UPDATE directo: Usuario::create no soporta las columnas nuevas aún)
                    $this->persistirCamposRegistro(
                        $nuevo_usuario_id,
                        $carrera,
                        $anio_ingreso,
                        $pais_origen,
                        $terminos,
                        $token,
                        $expiracion
                    );

                    // 8.2 — Hook de código de referido
                    $codigoRef = $_POST['codigo_referido'] ?? $_SESSION['ref_pending'] ?? null;
                    if ($codigoRef && trim($codigoRef) !== '') {
                        try {
                            (new \App\Models\Referido())->aplicarCodigoEnRegistro(
                                $nuevo_usuario_id,
                                trim($codigoRef)
                            );
                        } catch (\Throwable $e) {
                            // Un fallo de referido no debe romper el registro.
                            error_log('Referido: fallo al aplicar código en registro: ' . $e->getMessage());
                        }
                        unset($_SESSION['ref_pending']);
                    }
                }

                // 5.3 — Stub de envío de email: en v1 no hay transport SMTP.
                // Se muestra el link de verificación en el flash (dev) y se loguea.
                $verifyUrl = $this->buildVerifyUrl($token);
                error_log('[VERIFY STUB] Link de verificación para ' . $datos['correo'] . ': ' . $verifyUrl);
                $this->setFlash(
                    'success',
                    'Cuenta creada exitosamente. Verifique su cuenta en: ' . $verifyUrl
                );
                $this->redirect('/login');
            } else {
                $this->setFlash('error', 'Ocurrió un error al crear la cuenta. Inténtelo de nuevo.');
                $this->redirect('/register');
            }
        }
    }

    /**
     * 5.3 — Verificación de cuenta por token.
     * GET /verificar?token=XXX  (NO requiere sesión)
     * Busca usuario con token válido (no expirado); si ok marca verificado=true y limpia token.
     */
    public function verificarCuenta()
    {
        $token = $_GET['token'] ?? '';

        if ($token === '') {
            $this->setFlash('error', 'Token de verificación inválido.');
            $this->redirect('/login');
        }

        $row = $this->buscarPorToken($token);
        if ($row) {
            $this->marcarVerificado($row['usuario_id']);
            $this->setFlash('success', 'Cuenta verificada exitosamente. Ya puede iniciar sesión.');
            $this->redirect('/login');
        } else {
            $this->setFlash('error', 'El enlace de verificación es inválido o ha expirado.');
            $this->redirect('/login');
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }

    /**
     * 5.1 — Evalúa la fortaleza de una contraseña.
     * Reglas: ≥8 chars, minúscula, mayúscula, número.
     * Devuelve 'fuerte' (4 reglas), 'media' (3 reglas) o 'debil' (≤2 reglas).
     */
    private function evaluarFortalezaPassword($pw)
    {
        $len = function_exists('mb_strlen') ? mb_strlen($pw) : strlen($pw);
        $score = 0;
        if ($len >= 8) {
            $score++;
        }
        if (preg_match('/[a-z]/', $pw)) {
            $score++;
        }
        if (preg_match('/[A-Z]/', $pw)) {
            $score++;
        }
        if (preg_match('/[0-9]/', $pw)) {
            $score++;
        }
        if ($score >= 4) {
            return 'fuerte';
        }
        if ($score === 3) {
            return 'media';
        }
        return 'debil';
    }

    /**
     * 5.1 + 5.3 — Persiste las columnas nuevas del registro y el token de verificación.
     * UPDATE directo porque Usuario::create() aún no soporta estas columnas.
     */
    private function persistirCamposRegistro($usuario_id, $carrera, $anio_ingreso, $pais_origen, $terminos, $token, $expiracion)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE usuario SET
                    carrera = :carrera,
                    anio_ingreso = :anio_ingreso,
                    pais_origen = :pais_origen,
                    terminos = :terminos,
                    token = :token,
                    fecha_expiracion_token = :exp
                WHERE usuario_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':carrera', $carrera ?: null);
        $stmt->bindValue(':anio_ingreso', $anio_ingreso !== null ? (int)$anio_ingreso : null);
        $stmt->bindValue(':pais_origen', $pais_origen ?: null);
        $stmt->bindValue(':terminos', $terminos ? true : false, PDO::PARAM_BOOL);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':exp', $expiracion);
        $stmt->bindValue(':id', $usuario_id);
        $stmt->execute();
    }

    /**
     * 5.3 — Busca un usuario por token no expirado.
     */
    private function buscarPorToken($token)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT usuario_id FROM usuario
                WHERE token = :t AND fecha_expiracion_token > now()
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':t', $token);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * 5.3 — Marca un usuario como verificado y limpia el token.
     */
    private function marcarVerificado($usuario_id)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE usuario SET
                    verificado = true,
                    token = NULL,
                    fecha_expiracion_token = NULL,
                    modificado = now()
                WHERE usuario_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $usuario_id);
        $stmt->execute();
    }

    /**
     * 5.3 — Construye la URL pública de verificación a partir del host actual.
     */
    private function buildVerifyUrl($token)
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/verificar?token=' . $token;
    }
}
