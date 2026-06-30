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

$router = new Router();

// Rutas de Autenticación
$router->get('/', 'AuthController', 'showLogin');
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register', 'AuthController', 'register');
$router->get('/logout', 'AuthController', 'logout');

// Rutas de Aplicación
$router->get('/dashboard', 'DashboardController', 'index');

// Despachar la ruta
$router->dispatch();
