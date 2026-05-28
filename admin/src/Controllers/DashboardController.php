<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Admin\Models\Project;
use Admin\Models\User;
use App\Services\FeatureFlagService;
use App\Services\ReportProcessor;
use Database;

class DashboardController extends AdminController
{
    private $projectModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminRole('Solo administradores pueden acceder al dashboard.');
    }

    /**
     * Show dashboard home with advanced system health metrics.
     */
    public function index()
    {
        $db = Database::getInstance();
        $this->projectModel = new Project($db);
        $this->userModel = new User($db);
        $featureFlags = new FeatureFlagService();

        // 1. Entity Counts & Active Projects List
        $totalProjects = $this->projectModel->count();
        $activeProjects = $this->projectModel->getActiveNames();
        $totalUsers = $this->userModel->count();

        // 2. System Health - Log Errors
        $logErrors = $this->getLogErrorCount();
        $recentErrors = $this->getRecentErrors(30);

        // 3. Database Health & Integrity
        $dbStats = $this->getDatabaseStats();
        $integrityIssues = $this->projectModel->getIntegrityReport();
        $orphanTables = $this->projectModel->getOrphanTables();

        // 4. PHP Server Environment
        $phpLimits = $this->getPhpLimits();

        // 5. Backup Status
        $backupStatus = $this->getBackupStatus();

        // 6. Password Change Status
        $passwordStats = $this->userModel->getPasswordChangeStats();

        $stats = [
            'total_projects' => $totalProjects,
            'active_projects_list' => $activeProjects,
            'active_projects_count' => count($activeProjects),
            'total_users' => $totalUsers,
            'log_errors' => $logErrors,
            'db_size' => $dbStats['size_mb'],
            'total_tables' => $dbStats['total_tables'],
            'recent_errors' => $recentErrors,
            'integrity_issues' => $integrityIssues,
            'orphan_tables' => $orphanTables,
            'php_limits' => $phpLimits,
            'backup_status' => $backupStatus,
            'audit_logs' => $this->getAuditLogs(10),
            'console_logs_enabled' => $featureFlags->isConsoleLogsEnabled(),
            'password_stats' => $passwordStats,
        ];

        $this->render('dashboard', [
            'title' => 'Dashboard de Administración',
            'stats' => $stats,
            'csrf_token' => Security::generateCsrfToken(),
        ]);
    }

    public function toggleConsoleLogs()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $enabled = $_POST['enabled'] ?? null;
        if (!in_array((string) $enabled, ['0', '1'], true)) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Valor inválido para el switch']);
        }

        $enabled = (bool) ((int) $enabled);
        $featureFlags = new FeatureFlagService();
        $updatedBy = $_SESSION['admin_user']['usuario'] ?? 'admin';

        if (!$featureFlags->setConsoleLogsEnabled($enabled, $updatedBy)) {
            http_response_code(500);
            $this->json(['success' => false, 'message' => 'No se pudo guardar la configuración global']);
        }

        $actionText = $enabled ? 'habilitó' : 'deshabilitó';
        Database::getInstance()->logActivity(
            'Configuracion',
            'ACTUALIZAR',
            "{$updatedBy} {$actionText} la visualizacion global de console.log en el frontend.",
            null
        );

        $this->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Console logs activados globalmente. Recarga cualquier vista para verlos.'
                : 'Console logs ocultos globalmente. Recarga cualquier vista para aplicar el cambio.',
        ]);
    }

    /**
     * Force password change for all users.
     */
    public function forcePasswordChange()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $db = \Database::getInstance();
        $userModel = new User($db);
        $affected = $userModel->forcePasswordChangeForAll();

        $updatedBy = $_SESSION['admin_user']['usuario'] ?? 'admin';
        $db->logActivity(
            'Seguridad',
            'RESETEAR_CLAVES',
            "{$updatedBy} activó el cambio de contraseña obligatorio para {$affected} usuarios activos.",
            null
        );

        $this->json([
            'success' => true,
            'message' => "Se ha activado el cambio de contraseña obligatorio para {$affected} usuarios.",
        ]);
    }

    /**
     * Run all report consolidation processes.
     */
    public function runReportes()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $processor = new ReportProcessor();
        $parts = [];
        $hasErrors = false;

        $steps = [
            'Curva S'         => 'generateCurvaS',
            'General'         => 'generateReporteGeneral',
            'Restricciones'   => 'generateRestriccionesGeneral',
            'PDC'             => 'generateReportePDC',
            'Subcontratistas' => 'generateReporteSubcontratistas',
        ];

        foreach ($steps as $label => $method) {
            try {
                $processor->{$method}();
                $parts[] = "\xE2\x9C\x93 {$label}";
            } catch (\Exception $e) {
                $hasErrors = true;
                $parts[] = "\xE2\x9C\x97 {$label} (" . $e->getMessage() . ")";
            }
        }

        try {
            $processor->updateCICProyectos(null);
            $parts[] = "\xE2\x9C\x93 CIC";
        } catch (\Exception $e) {
            $hasErrors = true;
            $parts[] = "\xE2\x9C\x97 CIC (" . $e->getMessage() . ")";
        }

        $prefix = $hasErrors ? 'Reportes consolidados con errores' : 'Reportes consolidados';
        $message = $prefix . ': ' . implode(', ', $parts);

        $updatedBy = $_SESSION['admin_user']['usuario'] ?? 'admin';
        Database::getInstance()->logActivity(
            'Admin',
            'CONSOLIDAR_REPORTES',
            "{$updatedBy} ejecutó consolidación de reportes. {$message}",
            null
        );

        $this->json([
            'success' => !$hasErrors,
            'notification' => $message,
        ]);
    }

    /**
     * Get recent audit logs from database.
     */
    private function getAuditLogs($limit = 10)
    {
        $db = \Database::getInstance();
        $sql = "SELECT * FROM general_auditoria_acciones ORDER BY fecha DESC LIMIT ?";
        try {
            $stmt = $db->query($sql, [$limit]);

            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Error fetching audit logs: " . $e->getMessage());

            return [];
        }
    }

    /**
     * Count errors in the PHP log for today.
     */
    private function getPhpLimits()
    {
        return [
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution' => ini_get('max_execution_time') . 's',
        ];
    }

    /**
     * Check for recent backup files.
     */
    private function getBackupStatus()
    {
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            return ['last_backup' => 'No configurado', 'count' => 0];
        }

        $files = glob($backupDir . '/*.sql');
        if (empty($files)) {
            return ['last_backup' => 'Ninguno encontrado', 'count' => 0];
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return [
            'last_backup' => date('d-M-Y H:i', filemtime($files[0])),
            'count' => count($files),
        ];
    }

    /**
     * Count errors in the PHP log for today.
     */
    private function getLogErrorCount()
    {
        $logFile = __DIR__ . '/../../logs/php_error.log';
        if (!file_exists($logFile)) {
            return 0;
        }

        $today = date('d-M-Y');
        $count = 0;

        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                // Buscamos la fecha de hoy y palabras clave de error (insensible a mayúsculas)
                if (strpos($line, $today) !== false &&
                   (stripos($line, 'error') !== false || stripos($line, 'exception') !== false || stripos($line, 'fatal') !== false)) {
                    $count++;
                }
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Get all relevant events from the log for today.
     */
    private function getRecentErrors($limit = 100) // Increased internal limit
    {
        $logFile = __DIR__ . '/../../logs/php_error.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $today = date('d-M-Y');
        $results = [];

        $fp = fopen($logFile, 'r');
        if (!$fp) {
            return [];
        }

        // Simple tail to avoid reading GBs, but enough to cover a full day of activity
        $buffer = 4096;
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $lines = [];
        $chunk = '';

        // Read up to 100KB backwards - usually more than enough for daily logs
        $maxRead = 102400;
        $bytesRead = 0;

        while ($pos > 0 && $bytesRead < $maxRead) {
            $readSize = min($pos, $buffer);
            $pos -= $readSize;
            $bytesRead += $readSize;
            fseek($fp, $pos);
            $chunk = fread($fp, $readSize) . $chunk;
            $lines = explode("\n", $chunk);
        }
        fclose($fp);

        foreach (array_reverse($lines) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // FILTRO CRÍTICO: Solo registros de hoy
            if (strpos($line, $today) === false) {
                continue;
            }

            // Capturar errores, actividad de Router o CSRF
            if (stripos($line, 'error') !== false ||
                stripos($line, 'exception') !== false ||
                stripos($line, 'fatal') !== false ||
                strpos($line, 'Router') !== false ||
                strpos($line, 'CSRF') !== false) {

                $results[] = $line;
                // Safety break to not overload the browser if logs are massive
                if (count($results) >= 100) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Get database size and table count.
     */
    private function getDatabaseStats()
    {
        $dbName = $_ENV['DB_NAME'];
        $db = Database::getInstance();

        $sql = "SELECT 
                    SUM(data_length + index_length) / 1024 / 1024 AS size_mb,
                    COUNT(*) as table_count
                FROM information_schema.TABLES 
                WHERE table_schema = ?";

        try {
            $stmt = $db->query($sql, [$dbName]);
            $row = $stmt->fetch();

            return [
                'size_mb' => round($row['size_mb'] ?? 0, 2),
                'total_tables' => $row['table_count'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['size_mb' => 0, 'total_tables' => 0];
        }
    }
}
