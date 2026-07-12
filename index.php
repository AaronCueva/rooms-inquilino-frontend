<?php
session_start(); // Iniciar sesión al inicio de la aplicación

// Auto-carga de clases
spl_autoload_register(function ($class) {
    // Ejemplo de clase: App\Core\Router -> app/core/Router.php
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Convertir a minúsculas las carpetas (ej: Core -> core), asumiendo estructura de directorios en minúscula
    $parts = explode('/', $relative_class);
    $className = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    
    $file = $base_dir . ($dirPath ? $dirPath . '/' : '') . $className . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;

// Helper: primera letra UTF-8 segura (mbstring puede no estar cargado en CLI)
if (!function_exists('pd_initial')) {
    function pd_initial($s) {
        $s = (string)($s ?? '');
        if (function_exists('mb_substr')) { return mb_substr($s, 0, 1); }
        return preg_match('/^./u', $s, $m) ? $m[0] : substr($s, 0, 1);
    }
}

$router = new Router();

// Ruta pública (Home / Landing)
$router->get('/', 'HomeController', 'index');

// Búsqueda de alojamientos (W1)
$router->get('/buscar', 'AlojamientoController', 'buscar');

// Ficha del alojamiento (W2)
$router->get('/alojamiento/{id}', 'AlojamientoController', 'detalle');
$router->get('/alojamiento/{id}/resenas', 'AlojamientoController', 'resenas');
$router->post('/alojamiento/{id}/resena', 'AlojamientoController', 'guardarResena');

// Rutas de Autenticación
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register', 'AuthController', 'register');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/verificar', 'AuthController', 'verificarCuenta'); // W5.3 — token público, sin sesión

// Rutas de Aplicación
$router->get('/dashboard', 'PerfilController', 'dashboard'); // W5.10 — dashboard real (reemplaza mockup)

// Perfil + Verificación estudiantil (W5)
$router->get('/perfil', 'PerfilController', 'index');
$router->post('/perfil', 'PerfilController', 'guardar');
$router->get('/perfil/verificar', 'PerfilController', 'verificacion');
$router->post('/perfil/verificar/subir', 'PerfilController', 'subirDocumento');

// Puntos Nido + canje (W5.13)
$router->get('/puntos', 'PerfilController', 'puntos');
$router->post('/puntos/canjear', 'PerfilController', 'canjear');

// Blog / Guía del universitario (W8.1) — público, sin sesión
$router->get('/blog', 'BlogController', 'index');
$router->get('/blog/ver', 'BlogController', 'ver');

// Programa de referidos (W8.2) — requiere sesión
$router->get('/referidos', 'ReferidoController', 'index');
$router->post('/referidos/codigo', 'ReferidoController', 'generarCodigo');

// Mensajería / Chat inquilino↔propietario (W6)
$router->get('/mensajes', 'MensajeController', 'index');
$router->get('/mensajes/abrir', 'MensajeController', 'abrir');
$router->get('/mensajes/nuevo', 'MensajeController', 'nuevo');
$router->post('/mensajes/enviar', 'MensajeController', 'enviar');

// Reserva y pago (W3, ref §3.5)
$router->get('/reserva/crear', 'ReservaController', 'crear');
$router->post('/reserva/crear', 'ReservaController', 'crear');
$router->get('/reservas', 'ReservaController', 'misReservas');
$router->post('/reserva/cancelar', 'ReservaController', 'cancelar');
$router->get('/cron/reservas-expiradas', 'ReservaController', 'cronExpiradas'); // on-request, ?key=

// Contrato digital (W4, ref §3.6)
$router->get('/contratos', 'ContratoController', 'index');
$router->get('/contrato/{id}', 'ContratoController', 'ver');
$router->get('/contrato/{id}/pdf', 'ContratoController', 'pdf');
$router->post('/contrato/{id}/firmar', 'ContratoController', 'firmar');

// Pagos y Pasarela Simulada
$router->get('/pagos', 'PagoController', 'index');
$router->post('/pago/{id}/simular', 'PagoController', 'simular');

// Rutas de Comunidad / Foros de Discusión Estudiantil
$router->get('/foros', 'ForoController', 'index');
$router->get('/foros/ver', 'ForoController', 'ver');
$router->post('/foros/guardar', 'ForoController', 'guardar');
$router->post('/foros/comentar', 'ForoController', 'comentar');
$router->post('/foros/reaccionar', 'ForoController', 'reaccionarAjax');
$router->post('/foros/eliminar', 'ForoController', 'eliminar');
$router->post('/foros/eliminarComentario', 'ForoController', 'eliminarComentario');

// Rutas de API
$router->get('/api/ubicaciones', 'UbicacionController', 'obtenerPorReferencia');
$router->get('/api/universidades', 'HomeController', 'buscarUniversidades');

// Despachar la ruta
$router->dispatch();
