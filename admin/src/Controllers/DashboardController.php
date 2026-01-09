<?php

namespace Admin\Controllers;

use Admin\Models\Project;
use Admin\Models\User;
use Database;

class DashboardController extends AdminController
{
    /**
     * Show dashboard home with advanced system health metrics.
     */
    public function index()
    {
        $db = Database::getInstance();
        $projectModel = new Project($db);
        $userModel = new User($db);

        // 1. Entity Counts & Active Projects List
        $totalProjects = $projectModel->count();
        $activeProjects = $projectModel->getActiveNames();
        $totalUsers = $userModel->count();

        // 2. System Health - Log Errors
        $logErrors = $this->getLogErrorCount();
        $recentErrors = $this->getRecentErrors(10);

        // 3. Database Health & Integrity
        $dbStats = $this->getDatabaseStats();
        $integrityIssues = $projectModel->getIntegrityReport();
        $orphanTables = $projectModel->getOrphanTables();

        // 4. PHP Server Environment
        $phpLimits = $this->getPhpLimits();

        // 5. Backup Status
        $backupStatus = $this->getBackupStatus();

        $this->render('dashboard', [
            'title' => 'Salud del Sistema - Panel Admin',
            'user' => $_SESSION['admin_user'],
            'stats' => [
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
                'backup_status' => $backupStatus
            ]
        ]);
    }

    /**
     * Get PHP critical configuration limits.
     */
    private function getPhpLimits()
    {
        return [
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution' => ini_get('max_execution_time') . 's'
        ];
    }

    /**
     * Check for recent backup files.
     */
    private function getBackupStatus()
    {
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) return ['last_backup' => 'No configurado', 'count' => 0];

        $files = glob($backupDir . '/*.sql');
        if (empty($files)) return ['last_backup' => 'Ninguno encontrado', 'count' => 0];

        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return [
            'last_backup' => date('d-M-Y H:i', filemtime($files[0])),
            'count' => count($files)
        ];
    }

    /**
     * Count errors in the PHP log for today.
     */
    private function getLogErrorCount()
    {
        $logFile = __DIR__ . '/../../logs/php_error.log';
        if (!file_exists($logFile)) return 0;

        $today = date('d-M-Y');
        $count = 0;
        
        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, $today) !== false && (strpos($line, 'Error') !== false || strpos($line, 'Exception') !== false || strpos($line, 'Fatal') !== false)) {
                    $count++;
                }
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Get the most recent events from the log.
     */
    private function getRecentErrors($limit = 10)
    {
        $logFile = __DIR__ . '/../../logs/php_error.log';
        if (!file_exists($logFile)) return [];

        $lines = [];
        $fp = fopen($logFile, 'r');
        if (!$fp) return [];

        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $buffer = 4096;
        $chunk = '';

        while ($pos > 0 && count($lines) < $limit * 3) {
            $readSize = min($pos, $buffer);
            $pos -= $readSize;
            fseek($fp, $pos);
            $chunk = fread($fp, $readSize) . $chunk;
            $lines = explode("\n", $chunk);
        }
        fclose($fp);

        $results = [];
        foreach (array_reverse($lines) as $line) {
            if (empty(trim($line))) continue;
            if (strpos($line, 'Error') !== false || strpos($line, 'Exception') !== false || strpos($line, 'Router') !== false || strpos($line, 'CSRF') !== false) {
                $results[] = trim($line);
                if (count($results) >= $limit) break;
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
                'total_tables' => $row['table_count'] ?? 0
            ];
        } catch (\Exception $e) {
            return ['size_mb' => 0, 'total_tables' => 0];
        }
    }
}
