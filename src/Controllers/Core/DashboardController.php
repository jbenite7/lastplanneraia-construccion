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
}
