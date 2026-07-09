<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Alojamiento;
use App\Models\UniversidadModel;
use App\Models\Catalogo;

/**
 * Home pública (landing). No requiere sesión.
 * Ref. DF-NidoUniversitario §3.1.
 */
class HomeController extends Controller
{
    /**
     * Landing page: hero buscador + destacados + secciones.
     */
    public function index()
    {
        $alojamientoModel = new Alojamiento();
        $universidadModel = new UniversidadModel();

        $destacados = $alojamientoModel->getDestacados(8);
        $universidades = $universidadModel->obtenerTodas();
        $tipos = $alojamientoModel->getTipos();

        $this->render('home/index', [
            'destacados' => $destacados,
            'universidades' => $universidades,
            'tipos' => $tipos,
        ], 'public');
    }

    /**
     * Autocomplete de universidades para el buscador del hero.
     * GET /api/universidades?q=...
     */
    public function buscarUniversidades()
    {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');

        if ($q === '') {
            echo json_encode([]);
            exit;
        }

        $universidadModel = new UniversidadModel();
        $resultados = $universidadModel->buscarPorNombre($q);

        echo json_encode($resultados);
        exit;
    }
}
