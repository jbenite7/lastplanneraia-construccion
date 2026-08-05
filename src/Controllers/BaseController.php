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
     * Estado semanal del proyecto resuelto en servidor: la última semana creada
     * (`Max_Semana`) y si la semana en curso está confirmada (`Semanal_Confirmada`).
     *
     * Reproduce a propósito la misma consulta que `src/Legacy/datosGeneralesPagina.php`,
     * para que el valor que emite el PHP en el bloque `.encabezado` y el que llega
     * después por AJAX sean el mismo dato y no puedan divergir (C-46).
     *
     * @return array{maxSemana: int, semanalConfirmada: int}
     */
    protected function getWeekStatusVars(string $dbName, int $semana): array
    {
        $estado = ['maxSemana' => 0, 'semanalConfirmada' => 0];

        if ($dbName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            return $estado;
        }

        try {
            $tSa = \TableResolver::resolveByPrefix($dbName, 'semanas_activas');
            $projectId = \TableResolver::getProjectIdByPrefix($dbName);

            $stmtMax = $this->db->queryWithProject(
                "SELECT Semana FROM {$tSa} ORDER BY Semana DESC LIMIT 1"
            );
            $estado['maxSemana'] = (int) ($stmtMax->fetchColumn() ?: 0);

            $stmtSc = $this->db->queryWithProject(
                "SELECT Semanal_Confirmada FROM {$tSa} WHERE project_id = ? AND Semana = ? LIMIT 1",
                [$projectId, $semana]
            );
            $estado['semanalConfirmada'] = (int) ($stmtSc->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            error_log('Error resolviendo el estado semanal en servidor (C-46): ' . $e->getMessage());
        }

        return $estado;
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
