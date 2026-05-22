<?php

namespace App\Controllers\Core;

use App\Services\ProjectLandingService;

class DashboardController
{
    private ProjectLandingService $projectLandingService;

    public function __construct()
    {
        $this->projectLandingService = new ProjectLandingService();
    }

    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit;
        }

        $dbName = (string) ($_SESSION['db'] ?? '');
        $permiso = (string) ($_SESSION['permiso'] ?? '');

        if ($dbName === '' || preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
            header('Location: /proyectos');
            exit;
        }

        $landing = $this->projectLandingService->resolve($dbName, $permiso);

        $_SESSION['semana'] = (int) ($landing['week'] ?? 0);
        header('Location: ' . ($landing['route'] ?? '/programa-general'));
        exit;
    }

    public function escalamientos()
    {
        if (!isset($_SESSION['usuario'])) { header('Location: /login'); exit; }
        $dbName = (string) ($_SESSION['db'] ?? '');
        if ($dbName === '' || preg_match('/^[a-zA-Z0-9_]+$/', $dbName) !== 1) {
            header('Location: /proyectos'); exit;
        }
        $db = \Database::getInstance();
        $pStmt = $db->prepare("SELECT ID FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? AND Area = 'Construccion' LIMIT 1");
        $pStmt->execute([$_SESSION['proyecto'] ?? '']);
        $p = $pStmt->fetch(\PDO::FETCH_ASSOC);
        $projId = $p ? (int)$p['ID'] : 0;

        $lps = new \App\Services\LpsService();
        $crisis = $lps->getActiveCrisisByProject($dbName, $projId);
        
        require_once PROJECT_ROOT . '/views/dashboard/escalamientos.php';
    }
}

