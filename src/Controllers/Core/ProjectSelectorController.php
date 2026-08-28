<?php

namespace App\Controllers\Core;

use App\Services\ProjectAccessService;

class ProjectSelectorController
{
    private ProjectAccessService $projectAccess;

    public function __construct()
    {
        $this->projectAccess = new ProjectAccessService();
    }

    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit();
        }

        $usuario = (string) $_SESSION['usuario'];

        $proyectos = $this->projectAccess->listForUser($usuario);

        require PROJECT_ROOT . '/views/core/project_selector.view.php';
    }

    public function select()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyectos');
            exit();
        }

        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit();
        }

        $usuario = (string) $_SESSION['usuario'];
        $proyectoSeleccionado = trim((string) ($_POST['proyecto'] ?? ''));

        $this->enterProject($usuario, $proyectoSeleccionado);
    }

    /**
     * Establece el contexto de proyecto en la sesión y redirige a la pantalla de aterrizaje.
     *
     * Adaptador de redirección para el flujo legado. La lógica de autorización y
     * contexto vive en ProjectAccessService para que la API y DevDoor no puedan
     * divergir ni reutilizar esta salida terminal.
     *
     * Nunca retorna: siempre redirige.
     */
    public function enterProject(string $usuario, string $proyectoSeleccionado): void
    {
        $result = $this->projectAccess->select($usuario, $proyectoSeleccionado);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            header('Location: /proyectos');
            exit();
        }

        header('Location: ' . ($result['route'] ?? '/dashboard'));

        exit();
    }
}
