<?php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $paramRoutes = [];

    private function addRoute($route, $controller, $action, $method) {
        // Rutas con {param} van aparte (match por patrón); el resto, match exacto.
        if (strpos($route, '{') !== false) {
            $this->paramRoutes[$method][] = [
                'pattern'    => $route,
                'controller' => $controller,
                'action'     => $action,
            ];
        } else {
            $this->routes[$method][$route] = ['controller' => $controller, 'action' => $action];
        }
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

        // 1. Match exacto (rutas sin parámetros)
        if (array_key_exists($uri, $this->routes[$method] ?? [])) {
            $this->invoke(
                $this->routes[$method][$uri]['controller'],
                $this->routes[$method][$uri]['action'],
                []
            );
            return;
        }

        // 2. Rutas con parámetros {param}
        foreach ($this->paramRoutes[$method] ?? [] as $r) {
            $params = $this->matchPattern($r['pattern'], $uri);
            if ($params !== null) {
                $this->invoke($r['controller'], $r['action'], $params);
                return;
            }
        }

        echo "Ruta no encontrada: $uri ($method).";
        http_response_code(404);
    }

    /**
     * Compara un patrón con {param} contra la URI. Devuelve los params capturados o null.
     */
    private function matchPattern(string $pattern, string $uri): ?array {
        $pSegs = explode('/', trim($pattern, '/'));
        $uSegs = explode('/', trim($uri, '/'));
        if (count($pSegs) !== count($uSegs)) return null;

        $params = [];
        for ($i = 0, $n = count($pSegs); $i < $n; $i++) {
            if (preg_match('/^\{(\w+)\}$/', $pSegs[$i], $m)) {
                $params[$m[1]] = $uSegs[$i];
            } elseif ($pSegs[$i] !== $uSegs[$i]) {
                return null;
            }
        }
        return $params;
    }

    /**
     * Carga el controller, inyecta params en $_GET y los pasa como args
     * (por nombre) al método si su firma los declara.
     */
    private function invoke(string $controllerName, string $action, array $params): void {
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        $full = "App\\Controllers\\" . $controllerName;
        $file = __DIR__ . '/../controllers/' . $controllerName . '.php';
        if (!file_exists($file)) {
            echo "Controlador no encontrado: $full";
            return;
        }
        require_once $file;
        $controller = new $full();

        $args = [];
        try {
            $ref = new \ReflectionMethod($full, $action);
            foreach ($ref->getParameters() as $p) {
                $name = $p->getName();
                if (array_key_exists($name, $params)) {
                    $args[] = $params[$name];
                } elseif ($p->isDefaultValueAvailable()) {
                    $args[] = $p->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }
        } catch (\ReflectionException $e) {
            $args = [];
        }

        $controller->$action(...$args);
    }
}
