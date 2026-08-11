<?php

namespace App\Controllers\Core;

use Admin\Core\RoleManager;
use App\Security\RbacCatalog;
use App\Security\RbacService;
use App\Services\ProjectLandingService;
use Database;
use TableResolver;

class ProjectSelectorController
{
    private $db;
    private RbacService $rbac;
    private ProjectLandingService $projectLandingService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rbac = new RbacService($this->db);
        $this->projectLandingService = new ProjectLandingService();
    }

    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit();
        }

        $usuario = (string) $_SESSION['usuario'];

        $sql = "SELECT p.ID,
                       p.Proyecto_Proceso,
                       p.Area,
                       p.Activo,
                       p.Acceso,
                       p.Base_de_Datos,
                       pm.role AS permiso
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND p.Area IN ('Construccion', 'Pre-Construccion')
                  AND p.Activo = 1
                ORDER BY p.Proyecto_Proceso ASC";

        $stmt = $this->db->queryWithProject($sql, [$usuario]);
        $rows = $stmt->fetchAll();

        $managementRoles = RbacCatalog::managementRoles();
        $proyectos = [];

        foreach ($rows as $project) {
            $normalizedRole = $this->rbac->normalizeRole((string) ($project['permiso'] ?? ''));
            $accesoAbierto = (int) ($project['Acceso'] ?? 1) === 1;

            // Misma invariante que enterProject(): un proyecto con Acceso=0 solo es visible
            // para quien normaliza a rol de jefatura (A/D). Antes index() filtraba en SQL con
            // el rol crudo (perdía alias de texto como 'Director de Obra') y enterProject()
            // comprobaba con el rol ya normalizado — divergían. Ahora ambos filtran/comprueban
            // con el mismo rol normalizado por RbacService::normalizeRole().
            if (!$accesoAbierto && !in_array($normalizedRole, $managementRoles, true)) {
                continue;
            }

            $project['permiso'] = $normalizedRole;
            $project['rol_nombre'] = RoleManager::getRoleName($normalizedRole);
            $proyectos[] = $project;
        }

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
     * Extraído de select() para que la puerta de servicio de desarrollo (App\Core\DevDoor)
     * reutilice esta misma lógica en vez de duplicarla: la verificación de membresía contra
     * project_members, la normalización del rol y el respeto a Acceso=0 deben ser idénticos
     * por los dos caminos, o el rol obtenido por la puerta dejaría de ser el rol real.
     *
     * Nunca retorna: siempre redirige.
     */
    public function enterProject(string $usuario, string $proyectoSeleccionado): void
    {
        if ($proyectoSeleccionado === '') {
            $_SESSION['error'] = 'Debes seleccionar un proyecto.';
            header('Location: /proyectos');
            exit();
        }

        $sql = "SELECT p.ID,
                       p.Proyecto_Proceso,
                       p.Base_de_Datos,
                       p.Area,
                       p.Acceso,
                       p.pdcActivo,
                       p.Area,
                       pm.role
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND p.Proyecto_Proceso = ?
                  AND p.Area IN ('Construccion', 'Pre-Construccion')
                  AND p.Activo = 1
                LIMIT 1";

        $stmt = $this->db->queryWithProject($sql, [$usuario, $proyectoSeleccionado]);
        $accessData = $stmt->fetch();

        if (!$accessData) {
            $_SESSION['error'] = 'No tienes permiso para acceder a este proyecto.';
            header('Location: /proyectos');
            exit();
        }

        $dbName = (string) ($accessData['Base_de_Datos'] ?? '');
        $permiso = $this->rbac->normalizeRole((string) ($accessData['role'] ?? ''));

        if ((int) ($accessData['Acceso'] ?? 1) === 0 && !in_array($permiso, RbacCatalog::managementRoles(), true)) {
            $_SESSION['error'] = 'El proyecto seleccionado se encuentra inactivo para tu perfil.';
            header('Location: /proyectos');
            exit();
        }

        $_SESSION['proyecto'] = $proyectoSeleccionado;
        $_SESSION['project_id'] = (int) ($accessData['ID'] ?? 0);
        $_SESSION['area'] = (string) ($accessData['Area'] ?? 'Construccion');
        $_SESSION['db'] = $dbName;
        $_SESSION['permiso'] = $permiso;

        // Centralizar contexto de proyecto en Database
        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId) {
            $this->db->setProjectContext($projectId);
        }
        $_SESSION['permiso_canonico'] = $permiso;
        $_SESSION['pdcActivo'] = $accessData['pdcActivo'] ?? 0;
        $_SESSION['area'] = $accessData['Area'] ?? 'Construccion';

        $landing = $this->projectLandingService->resolve($dbName, $permiso, $_SESSION['area']);
        $_SESSION['semana'] = (int) ($landing['week'] ?? 0);

        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity(
                'Login',
                'ACCESO_PROYECTO',
                "Usuario $usuario ingresó a proyecto $proyectoSeleccionado",
                $dbName,
            );
        }

        header('Location: ' . ($landing['route'] ?? '/dashboard'));

        exit();
    }
}
