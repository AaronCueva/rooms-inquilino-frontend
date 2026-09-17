<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class MenuMaestro {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene los menús asociados a un rol estructurados en secciones y submenús.
     */
    public function obtenerMenuPorRol($rol_id) {
        if (!$rol_id) return [];

        $query = "
            SELECT 
                m.menu_maestro_id,
                m.nombre,
                m.descripcion,
                m.url,
                m.icono,
                m.orden,
                m.referencia_id
            FROM menu_maestro m
            INNER JOIN menu_rol mr ON m.menu_maestro_id = mr.menu_maestro_id
            WHERE mr.rol_id = :rol_id 
              AND m.habilitado = true 
              AND mr.habilitado = true
            ORDER BY m.orden ASC, m.nombre ASC
        ";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute(['rol_id' => $rol_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->construirArbolMenu($items);
        } catch (Exception $e) {
            error_log("Error obteniendo menú por rol: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Construye un árbol agrupando los hijos dentro de sus padres (secciones)
     */
    private function construirArbolMenu($items) {
        $arbol = [];
        $hijos = [];

        foreach ($items as $item) {
            if (empty($item['referencia_id'])) {
                $item['hijos'] = [];
                $arbol[$item['menu_maestro_id']] = $item;
            } else {
                $hijos[] = $item;
            }
        }

        foreach ($hijos as $hijo) {
            $padre_id = $hijo['referencia_id'];
            if (isset($arbol[$padre_id])) {
                $arbol[$padre_id]['hijos'][] = $hijo;
            }
        }

        return array_values($arbol);
    }
}
