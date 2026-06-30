<?php
namespace App\Core;

class Router {
    protected $routes = [];

    private function addRoute($route, $controller, $action, $method) {
        $this->routes[$method][$route] = ['controller' => $controller, 'action' => $action];
    }

    public function get($route, $controller, $action) {
        $this->addRoute($route, $controller, $action, "GET");
    }

    public function post($route, $controller, $action) {
        $this->addRoute($route, $controller, $action, "POST");
    }

    public function dispatch() {
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        
        // Remover el posible directorio base si se ejecuta en localhost subfolder
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && $scriptName !== '\\') {
            $uri = str_replace($scriptName, '', $uri);
        }

        // Si el URI contiene /index.php, lo removemos para el ruteo interno
        if (str_ends_with($uri, '/index.php')) {
            $uri = substr($uri, 0, -10);
        } elseif ($uri === 'index.php') {
            $uri = '';
        }
        
        if ($uri === '') {
            $uri = '/';
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if (array_key_exists($uri, $this->routes[$method])) {
            $controllerName = "App\\Controllers\\" . $this->routes[$method][$uri]['controller'];
            $action = $this->routes[$method][$uri]['action'];

            $controllerFile = __DIR__ . '/../controllers/' . $this->routes[$method][$uri]['controller'] . '.php';
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controller = new $controllerName();
                $controller->$action();
            } else {
                echo "Controlador no encontrado: $controllerName";
            }
        } else {
            echo "Ruta no encontrada: $uri ($method).";
            http_response_code(404);
        }
    }
}
