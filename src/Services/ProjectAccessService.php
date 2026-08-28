<?php

declare(strict_types=1);

namespace App\Services;

use Admin\Core\RoleManager;
use App\Security\RbacCatalog;
use App\Security\RbacService;
use Database;
use TableResolver;

/**
 * Único camino para listar y seleccionar un proyecto en el shell.
 *
 * Mantiene juntas la consulta de membresía, los controles de proyecto activo
 * y Acceso, la normalización RBAC y los efectos que dejan listo el contexto de
 * sesión. Los adaptadores HTTP/legados deciden solamente cómo responder.
 */
class ProjectAccessService
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

    /** @return list<array<string, mixed>> */
    public function listForUser(string $usuario): array
    {
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

        $rows = $this->db->queryWithProject($sql, [$usuario])->fetchAll();
        $managementRoles = RbacCatalog::managementRoles();
        $proyectos = [];

        foreach ($rows as $project) {
            $normalizedRole = $this->rbac->normalizeRole((string) ($project['permiso'] ?? ''));
            $accesoAbierto = (int) ($project['Acceso'] ?? 1) === 1;

            if (!$accesoAbierto && !in_array($normalizedRole, $managementRoles, true)) {
                continue;
            }

            $project['permiso'] = $normalizedRole;
            $project['rol_nombre'] = RoleManager::getRoleName($normalizedRole);
            $proyectos[] = $project;
        }

        return $proyectos;
    }

    /**
     * Valida y establece el proyecto, sin enviar encabezados ni terminar el proceso.
     *
     * @return array{success:bool,message:string|null,route:string|null}
     */
    public function select(string $usuario, string $proyectoSeleccionado): array
    {
        $usuario = trim($usuario);
        $proyectoSeleccionado = trim($proyectoSeleccionado);

        if ($usuario === '') {
            return $this->failure('No tienes permiso para acceder a este proyecto.');
        }

        if ($proyectoSeleccionado === '') {
            return $this->failure('Debes seleccionar un proyecto.');
        }

        $sql = "SELECT p.ID,
                       p.Proyecto_Proceso,
                       p.Base_de_Datos,
                       p.Area,
                       p.Acceso,
                       p.pdcActivo,
                       pm.role
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND p.Proyecto_Proceso = ?
                  AND p.Area IN ('Construccion', 'Pre-Construccion')
                  AND p.Activo = 1
                LIMIT 1";

        $accessData = $this->db->queryWithProject($sql, [$usuario, $proyectoSeleccionado])->fetch();

        if (!$accessData) {
            return $this->failure('No tienes permiso para acceder a este proyecto.');
        }

        $dbName = (string) ($accessData['Base_de_Datos'] ?? '');
        $permiso = $this->rbac->normalizeRole((string) ($accessData['role'] ?? ''));

        if ((int) ($accessData['Acceso'] ?? 1) === 0 && !in_array($permiso, RbacCatalog::managementRoles(), true)) {
            return $this->failure('El proyecto seleccionado se encuentra inactivo para tu perfil.');
        }

        $_SESSION['proyecto'] = $proyectoSeleccionado;
        $_SESSION['project_id'] = (int) ($accessData['ID'] ?? 0);
        $_SESSION['area'] = (string) ($accessData['Area'] ?? 'Construccion');
        $_SESSION['db'] = $dbName;
        $_SESSION['permiso'] = $permiso;

        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId) {
            $this->db->setProjectContext($projectId);
        }
        $_SESSION['permiso_canonico'] = $permiso;
        $_SESSION['pdcActivo'] = $accessData['pdcActivo'] ?? 0;

        $landing = $this->projectLandingService->resolve($dbName, $permiso, $_SESSION['area']);
        $_SESSION['semana'] = (int) ($landing['week'] ?? 0);

        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity(
                'Login',
                'ACCESO_PROYECTO',
                "Usuario {$usuario} ingresó a proyecto {$proyectoSeleccionado}",
                $dbName,
            );
        }

        return [
            'success' => true,
            'message' => null,
            'route' => (string) ($landing['route'] ?? '/dashboard'),
        ];
    }

    /** @return array{success:false,message:string,route:null} */
    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'route' => null,
        ];
    }
}
