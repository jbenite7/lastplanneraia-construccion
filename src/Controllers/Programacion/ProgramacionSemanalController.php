<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;

class ProgramacionSemanalController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int)($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';
        $permiso = $_SESSION['permiso'] ?? ''; // Requerido para lógica de permisos en vistas
        $pdcActivo = $_SESSION['pdcActivo'] ?? '';

        $subcontratistas = [];
        $profesionales = [];
        $categoriasCnc = [];

        if (!empty($dbName) && preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            try {
                $stmtSub = $this->db->query("SELECT subcontratista FROM {$dbName}_subcontratistas WHERE activo = 1 ORDER BY subcontratista ASC");
                $subcontratistas = $stmtSub->fetchAll();

                $stmtProf = $this->db->query("SELECT nombre FROM {$dbName}_profesionales WHERE Activo = 1 ORDER BY nombre ASC");
                $profesionales = $stmtProf->fetchAll();

                $stmtCnc = $this->db->query("SELECT DISTINCT Categoria_CNC FROM general_cnc ORDER BY Categoria_CNC ASC");
                $categoriasCnc = $stmtCnc->fetchAll();
            } catch (\Throwable $e) {
                error_log('Error cargando listas de programación semanal: ' . $e->getMessage());
                $subcontratistas = [];
                $profesionales = [];
                $categoriasCnc = [];
            }
        }

        require PROJECT_ROOT . '/views/programacion-semanal/programacion_semanal.view.php';
    }

    public function cnp()
    {
        $this->requireAuth();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int)($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';

        require PROJECT_ROOT . '/views/programacion-semanal/CNP.view.php';
    }

    public function cnc()
    {
        $this->requireAuth();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int)($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';

        require PROJECT_ROOT . '/views/programacion-semanal/CNC.view.php';
    }

    public function cic()
    {
        $this->requireAuth();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int)($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';

        require PROJECT_ROOT . '/views/programacion-semanal/CIC.view.php';
    }
}
