<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Admin\Models\Project;
use Admin\Models\User;
use App\Core\ProgressTracker;
use App\Services\FeatureFlagService;
use App\Services\ReportProcessor;
use App\Security\DataScope\SystemScopeRunner;
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
            'maintenance_active' => file_exists(ADMIN_PROJECT_ROOT . '/.maintenance'),
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
            null,
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
     * Toggle maintenance mode for the platform (creates/deletes .maintenance flag file).
     */
    public function toggleMaintenance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $maintenanceFile = ADMIN_PROJECT_ROOT . '/.maintenance';
        $enabled = $_POST['enabled'] ?? null;

        if (!in_array((string) $enabled, ['0', '1'], true)) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Valor inválido para el switch']);
        }

        $updatedBy = $_SESSION['admin_user']['usuario'] ?? 'admin';

        if ($enabled === '1') {
            if (!file_exists($maintenanceFile)) {
                touch($maintenanceFile);
            }
            Database::getInstance()->logActivity(
                'Configuracion',
                'ACTIVAR',
                "{$updatedBy} activó el modo mantenimiento. La plataforma muestra la pantalla de mantenimiento a los usuarios.",
                null,
            );
            $this->json([
                'success' => true,
                'enabled' => true,
                'message' => 'Modo mantenimiento activado. Los usuarios ven la pantalla de mantenimiento.',
            ]);
        } else {
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
            }
            Database::getInstance()->logActivity(
                'Configuracion',
                'DESACTIVAR',
                "{$updatedBy} desactivó el modo mantenimiento. La plataforma funciona con normalidad.",
                null,
            );
            $this->json([
                'success' => true,
                'enabled' => false,
                'message' => 'Modo mantenimiento desactivado. La plataforma funciona con normalidad.',
            ]);
        }
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
            null,
        );

        $this->json([
            'success' => true,
            'message' => "Se ha activado el cambio de contraseña obligatorio para {$affected} usuarios.",
        ]);
    }

    /**
     * POST endpoint: run consolidation of reports (Curva S, General, Restricciones, PDC, CIC).
     * Sends response immediately via fastcgi_finish_request() then runs inline
     * to avoid dependency on exec() (disabled on some hosting).
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

        $clientToken = $_POST['_token'] ?? '';
        if ($clientToken !== '' && preg_match('/^[a-f0-9]{32}$/i', $clientToken)) {
            $token = $clientToken;
        } else {
            $token = bin2hex(random_bytes(16));
        }

        $existingFiles = glob(sys_get_temp_dir() . '/lps-consolidation-*.json');
        foreach ($existingFiles as $existingFile) {
            $content = @file_get_contents($existingFile);
            if ($content === false) {
                continue;
            }
            $data = json_decode($content, true);
            if (is_array($data) && ($data['status'] ?? '') === 'running' && (time() - ($data['updated_at'] ?? 0)) < 60) {
                $this->json(['success' => false, 'message' => 'Ya hay una consolidación en curso.', 'token' => $data['token'] ?? '']);
                return;
            }
        }

        $tracker = new ProgressTracker($token);
        $totalSteps = $this->countProjectSteps();
        $tracker->init($totalSteps);
        ProgressTracker::cleanup();

        $usuario = $_SESSION['admin_user']['usuario'] ?? 'admin';

        // Send JSON response immediately so client can start polling
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'token' => $token, 'completed' => false]);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ob_flush();
                flush();
            }
        }

        error_log('[CONSOLIDACION] Starting inline consolidation for token: ' . $token);

        $this->executeConsolidation($token, $usuario);
    }

    /**
     * Run consolidation synchronously after sending the HTTP response.
     * Extracted from admin/async/consolidate.php but runs in-web without exec().
     */
    private function executeConsolidation(string $token, string $usuario): void
    {
        try {
            ignore_user_abort(true);
            set_time_limit(0);

            $db = Database::getInstance();
            $tracker = new ProgressTracker($token);

            $totalSteps = $this->countProjectSteps();
            $tracker->init($totalSteps);

            $processor = new ReportProcessor();
            $parts = [];
            $hasErrors = false;

            $processor->setProgressCallback(function (string $reportLabel, string $project, int $index, int $total, string $status, ?string $message = null) use ($tracker) {
                $tracker->update($reportLabel, $project, $index, $total, $status, $message);
            });

            $processor->setSubprocessCallback(function (string $step, string $project, string $subprocess, string $status, ?string $message = null) use ($tracker) {
                $tracker->updateSubprocess($step, $project, $subprocess, $status, $message);
            });

            register_shutdown_function(function () use ($tracker) {
                $error = error_get_last();
                if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                    $msg = sprintf('FATAL: %s in %s:%d', $error['message'], $error['file'], $error['line']);
                    error_log('[CONSOLIDACION] ' . $msg);
                    $tracker->update('', '', 0, 0, 'error', $msg);
                }
            });

            (new SystemScopeRunner($db->dataScope()))->run(
                "admin:consolidation:{$usuario}",
                function () use ($db, $tracker, $processor, $usuario, &$parts, &$hasErrors): void {
                    $steps = [
                        'Curva S'         => 'generateCurvaS',
                        'General'         => 'generateReporteGeneral',
                        'Restricciones'   => 'generateRestriccionesGeneral',
                        'PDC'             => 'generateReportePDC',
                        'Subcontratistas' => 'generateReporteSubcontratistas',
                    ];

                    foreach ($steps as $label => $method) {
                        try {
                            error_log('[CONSOLIDACION] Starting step: ' . $label);
                            $processor->{$method}();
                            $parts[] = "\xE2\x9C\x93 {$label}";
                            error_log('[CONSOLIDACION] Completed step: ' . $label);
                        } catch (\Exception $e) {
                            $hasErrors = true;
                            $parts[] = "\xE2\x9C\x97 {$label} (" . $e->getMessage() . ")";
                            error_log('[CONSOLIDACION] Exception in step ' . $label . ': ' . $e->getMessage());
                        }
                    }

                    try {
                        $processor->updateCICProyectos(null);
                        $parts[] = "\xE2\x9C\x93 CIC";
                    } catch (\Exception $e) {
                        $hasErrors = true;
                        $parts[] = "\xE2\x9C\x97 CIC (" . $e->getMessage() . ")";
                    }

                    foreach (($tracker->toArray()['history'] ?? []) as $entry) {
                        if (($entry['status'] ?? '') === 'error') {
                            $hasErrors = true;
                            break;
                        }
                    }

                    $prefix = $hasErrors ? 'Reportes consolidados con errores' : 'Reportes consolidados';
                    $message = $prefix . ': ' . implode(', ', $parts);
                    $db->logActivity('Admin', 'CONSOLIDAR_REPORTES', "{$usuario} ejecutó consolidación de reportes. {$message}", null);

                    $tracker->complete(!$hasErrors, $message);

                    error_log('[CONSOLIDACION] Finished. ' . $message);
                },
            );
        } catch (\Exception $e) {
            error_log('[CONSOLIDACION] Fatal inline error: ' . $e->getMessage());
            try {
                $tracker = new ProgressTracker($token);
                $tracker->complete(false, 'Error fatal: ' . $e->getMessage());
            } catch (\Exception $inner) {
                error_log('[CONSOLIDACION] Failed to write fatal error to tracker: ' . $inner->getMessage());
            }
        }
    }

    /**
     * GET endpoint: poll progress of consolidation process.
     */
    public function reportProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        $token = $_GET['token'] ?? '';
        if (empty($token) || !preg_match('/^[a-f0-9]{32}$/i', $token)) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Token inválido']);
        }

        $tracker = ProgressTracker::fromToken($token);
        if ($tracker === null) {
            $this->json(['success' => false, 'message' => 'Proceso no encontrado o ya expiró']);
        }

        $this->json(['success' => true, 'progress' => $tracker->toArray()]);
    }

    /**
     * Count total project-level steps across all report types.
     */
    private function countProjectSteps(): int
    {
        $db = Database::getInstance();
        $allCount = (int) $db->query(
            "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1",
        )->fetchColumn();
        $pdcCount = (int) $db->query(
            "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1",
        )->fetchColumn();

        return $allCount * 5 + $pdcCount * 2;
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
                if (strpos($line, $today) !== false
                   && (stripos($line, 'error') !== false || stripos($line, 'exception') !== false || stripos($line, 'fatal') !== false)) {
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
            if (stripos($line, 'error') !== false
                || stripos($line, 'exception') !== false
                || stripos($line, 'fatal') !== false
                || strpos($line, 'Router') !== false
                || strpos($line, 'CSRF') !== false) {

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
