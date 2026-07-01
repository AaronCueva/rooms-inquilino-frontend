<?php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Verificar autenticación
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $this->render('inquilino/dashboard', [
            'nombre_usuario' => $_SESSION['nombres'] . ' ' . $_SESSION['apellidos'],
            'titulo' => 'Explorar',
        ]);
    }
    
    // Aquí puedes añadir más métodos para las otras vistas (pagos, contrato, etc.)
}
