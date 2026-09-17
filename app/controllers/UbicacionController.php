<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Ubicacion;

class UbicacionController extends Controller
{
    private $ubicacionModel;

    public function __construct()
    {
        $this->ubicacionModel = new Ubicacion();
    }

    public function obtenerPorReferencia()
    {
        $referencia_id = filter_input(INPUT_GET, 'referencia_id', FILTER_SANITIZE_STRING);
        
        header('Content-Type: application/json');
        
        if (!$referencia_id) {
            echo json_encode([]);
            return;
        }

        $ubicaciones = $this->ubicacionModel->obtenerPorReferencia($referencia_id);
        echo json_encode($ubicaciones);
    }
}
