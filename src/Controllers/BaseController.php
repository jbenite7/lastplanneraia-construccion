<?php

namespace App\Controllers;

use App\Core\SessionMiddleware;
use Database;

/**
 * Clase base abstracta para todos los controladores.
 *
 * Proporciona funcionalidad común:
 * - Instancia automática de Database (Singleton)
 * - Método requireAuth() para validación de sesión
 * - Método getSessionVars() para extraer variables de sesión
 * - Método render() para cargar vistas
 */
abstract class BaseController
{
    /**
     * Instancia de Database (Singleton)
     * @var Database
     */
    protected $db;

    /**
     * Constructor: inicializa la conexión a la base de datos
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Valida que el usuario esté autenticado mediante SessionMiddleware.
     * Redirige a /login si no hay sesión activa o si el timeout expiró.
     *
     * @return void
     */
    protected function requireAuth()
    {
        SessionMiddleware::check();
    }

    /**
     * Valida permisos RBAC usando RbacService.
     * Retorna 403 y termina la ejecución si se deniega.
     *
     * @param string $permissionKey Clave del permiso
     * @param string $errorMessage Mensaje a mostrar en el abort
     * @return void
     */
    protected function authorizePermission(string $permissionKey, string $errorMessage = 'Acceso denegado.')
    {
        $rbac = new \App\Security\RbacService($this->db);
        if (!$rbac->can($permissionKey)) {
            http_response_code(403);
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                echo json_encode(['error' => $errorMessage]);
            } else {
                echo "<h1>Error 403</h1><p>$errorMessage</p>";
            }
            exit;
        }
    }

    /**
     * Extrae las variables de sesión más comunes utilizadas en las vistas.
     *
     * @return array Asociativo con las variables de sesión
     */
    protected function getSessionVars()
    {
        return [
            'dbName' => $_SESSION['db'] ?? '',
            'semana' => (int) ($_SESSION['semana'] ?? 0),
            'proyecto' => $_SESSION['proyecto'] ?? '',
            'permiso' => $_SESSION['permiso'] ?? '',
            'pdcActivo' => $_SESSION['pdcActivo'] ?? '',
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? '',
            'area' => $_SESSION['area'] ?? 'Construccion',
        ];
    }

    /**
     * Sincroniza la semana desde rutas modernas como /pdc?semana=5.
     */
    protected function syncRequestedWeekContext(): bool
    {
        $rawWeek = $_GET['semana'] ?? null;
        if ($rawWeek === null || $rawWeek === '') {
            return false;
        }

        $week = filter_var($rawWeek, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($week === false) {
            error_log('Semana solicitada inválida en ruta moderna: ' . (string) $rawWeek);
            return false;
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $_SESSION['semana'] = (int) $week;
            return true;
        }

        $exists = (int) $this->db->query(
            'SELECT COUNT(*) FROM semanas_activas WHERE project_id = ? AND Semana = ?',
            [$projectId, (int) $week],
        )->fetchColumn();
        if ($exists <= 0) {
            error_log("Semana {$week} no existe para el proyecto activo {$projectId}.");
            return false;
        }

        $_SESSION['semana'] = (int) $week;
        return true;
    }

    /**
     * Renderiza una vista con datos opcionales.
     *
     * @param string $viewPath Ruta relativa a PROJECT_ROOT (ej: '/views/modulo/...')
     * @param array $data Datos a pasar a la vista (se extraen como variables)
     * @return void
     */
    protected function render($viewPath, $data = [])
    {
        // Extraer datos como variables individuales
        extract($data);

        // Cargar la vista
        require PROJECT_ROOT . $viewPath;
    }
}
