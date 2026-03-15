<?php

namespace App\Controllers\Core;

use Admin\Core\RoleManager;
use Database;

class ProjectSelectorController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit();
        }

        $usuario = (string)$_SESSION['usuario'];

        $sql = "SELECT p.ID,
                       p.Proyecto_Proceso,
                       p.Area,
                       p.Activo,
                       p.Base_de_Datos,
                       pm.role AS permiso
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND p.Area = 'Construccion'
                  AND p.Activo = 1
                  AND (p.Acceso = 1 OR pm.role IN ('A', 'D'))
                ORDER BY p.Proyecto_Proceso ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario]);
        $proyectos = $stmt->fetchAll();

        foreach ($proyectos as &$project) {
            $project['progreso'] = rand(0, 100);
            $normalizedRole = $this->normalizeRoleCode((string)($project['permiso'] ?? 'V'));
            $project['permiso'] = $normalizedRole;
            $project['rol_nombre'] = RoleManager::getRoleName($normalizedRole);
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

        $usuario = (string)$_SESSION['usuario'];
        $proyectoSeleccionado = trim((string)($_POST['proyecto'] ?? ''));

        if ($proyectoSeleccionado === '') {
            $_SESSION['error'] = 'Debes seleccionar un proyecto.';
            header('Location: /proyectos');
            exit();
        }

        $sql = "SELECT p.ID,
                       p.Proyecto_Proceso,
                       p.Base_de_Datos,
                       p.Acceso,
                       p.pdcActivo,
                       pm.role
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND p.Proyecto_Proceso = ?
                  AND p.Area = 'Construccion'
                  AND p.Activo = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario, $proyectoSeleccionado]);
        $accessData = $stmt->fetch();

        if (!$accessData) {
            $_SESSION['error'] = 'No tienes permiso para acceder a este proyecto.';
            header('Location: /proyectos');
            exit();
        }

        $dbName = (string)($accessData['Base_de_Datos'] ?? '');
        $permiso = $this->normalizeRoleCode((string)($accessData['role'] ?? 'V'));

        if ((int)($accessData['Acceso'] ?? 1) === 0 && !in_array($permiso, ['A', 'D'], true)) {
            $_SESSION['error'] = 'El proyecto seleccionado se encuentra inactivo para tu perfil.';
            header('Location: /proyectos');
            exit();
        }

        $_SESSION['proyecto'] = $proyectoSeleccionado;
        $_SESSION['db'] = $dbName;
        $_SESSION['permiso'] = $permiso;
        $_SESSION['permiso_canonico'] = $permiso;
        $_SESSION['pdcActivo'] = $accessData['pdcActivo'] ?? 0;

        $_SESSION['semana'] = 0;
        if ($dbName !== '' && preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            try {
                // Lógica de Contexto Inteligente:
                // Priorizamos la última CONFIRMADA si existe (para proyectos en marcha).
                // Si no hay ninguna confirmada, usamos la última EXISTENTE (para proyectos recién iniciados).
                $query = "SELECT 
                            MAX(Semana) as max_overall,
                            MAX(CASE WHEN Semanal_Confirmada = 1 THEN Semana ELSE NULL END) as max_confirmed
                          FROM {$dbName}_semanas_activas";
                $stmt = $this->db->query($query);
                $res = $stmt->fetch();
                
                $maxOverall = (int)($res['max_overall'] ?? 0);
                $maxConfirmed = ($res['max_confirmed'] !== null) ? (int)$res['max_confirmed'] : null;
                
                // Si hay confirmadas, la base es la confirmada. Si no, la base es la unificada.
                $_SESSION['semana'] = ($maxConfirmed !== null) ? $maxConfirmed : $maxOverall;
            } catch (\Exception $e) {
                $_SESSION['semana'] = 0;
            }
        }

        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity(
                'Login',
                'ACCESO_PROYECTO',
                "Usuario $usuario ingresó a proyecto $proyectoSeleccionado",
                $dbName
            );
        }

        switch ($permiso) {
            case 'V':
            case 'A':
            case 'D':
            case 'R':
            case 'OT':
            case 'DCV':
                header('Location: /programa-general');
                break;

            case 'G':
            case 'S':
            case 'SG':
                header('Location: /programacion-semanal/cic');
                break;

            case 'C':
                header('Location: /programacion-semanal');
                break;

            default:
                header('Location: /dashboard');
                break;
        }

        exit();
    }

    private function normalizeRoleCode(string $role): string
    {
        $role = strtoupper(trim($role));

        if ($role === 'P') {
            return 'D';
        }

        if ($role === 'U' || $role === '') {
            return 'V';
        }

        $knownRoles = array_keys(RoleManager::getAll());
        if (!in_array($role, $knownRoles, true)) {
            return 'V';
        }

        return $role;
    }
}
