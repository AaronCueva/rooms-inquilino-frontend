<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Catalogo;
use App\Models\Ubicacion;
use App\Models\UniversidadModel;

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
        // Si ya está logueado, redirigir al dashboard
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

        $tipos_documento = $this->catalogoModel->obtenerPorReferencia('TIPO_DOCUMENTO');
        $universidades = $this->universidadModel->obtenerTodas();

        $this->render('auth/register', [
            'tipos_documento' => $tipos_documento,
            'universidades' => $universidades
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
                'password' => $_POST['password'] ?? '',
                'rol_id' => $rol_id
            ];

            // Validar que el correo no exista
            $existe = $this->usuarioModel->findByEmail($datos['correo']);
            if ($existe) {
                $this->setFlash('error', 'Ya existe un usuario registrado con este correo.');
                $this->redirect('/register');
            }

            if ($this->usuarioModel->create($datos)) {
                $this->setFlash('success', 'Cuenta creada exitosamente. Ahora puede iniciar sesión.');
                $this->redirect('/login');
            } else {
                $this->setFlash('error', 'Ocurrió un error al crear la cuenta. Inténtelo de nuevo.');
                $this->redirect('/register');
            }
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }
}
