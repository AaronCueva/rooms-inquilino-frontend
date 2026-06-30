<?php
namespace App\Core;

class Controller {

    /**
     * Renderiza una vista dentro de un layout.
     * @param string $view Ruta de la vista (ej. 'auth/login')
     * @param array $data Datos a pasar a la vista
     * @param string $layout Plantilla base a usar (ej. 'auth', 'admin')
     */
    public function render($view, $data = [], $layout = 'main') {
        // Extraer variables para que estén disponibles en la vista
        extract($data);

        // Guardar la vista en un buffer
        ob_start();
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("Vista no encontrada: " . $viewFile);
        }
        $content = ob_get_clean();

        // Requerir el layout e inyectar el contenido de la vista
        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require_once $layoutFile;
        } else {
            echo $content; // Si no hay layout, se muestra solo la vista
        }
    }

    /**
     * Redirige a una ruta especificada
     */
    public function redirect($url) {
        header("Location: " . $url);
        exit;
    }

    /**
     * Guarda un mensaje flash en sesion para mostrar en la siguiente pantalla
     */
    public function setFlash($tipo, $mensaje) {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /**
     * Obtiene y limpia el mensaje flash de la sesion
     */
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
