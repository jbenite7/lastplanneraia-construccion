<?php

namespace Admin\Controllers;

use Admin\Models\Project;
use Admin\Models\User;
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
        ];

        $this->render('dashboard', [
            'title' => 'Dashboard de Administración',
            'stats' => $stats,
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
