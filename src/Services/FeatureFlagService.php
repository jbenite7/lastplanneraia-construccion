<?php

namespace App\Services;

use Database;
use Throwable;

class FeatureFlagService
{
    private const TABLE = 'general_feature_flags';
    private const CONSOLE_LOGS_KEY = 'console_logs_enabled';

    private $db;
    private $tableEnsured = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isConsoleLogsEnabled(): bool
    {
        return $this->getBoolFlag(self::CONSOLE_LOGS_KEY, false);
    }

    public function setConsoleLogsEnabled(bool $enabled, ?string $updatedBy = null): bool
    {
        return $this->setBoolFlag(
            self::CONSOLE_LOGS_KEY,
            $enabled,
            'Controla la visualizacion global de console.log en el frontend.',
            $updatedBy,
        );
    }

    public function getPublicFrontendFlags(): array
    {
        return [
            'consoleLogsEnabled' => $this->isConsoleLogsEnabled(),
        ];
    }

    private function getBoolFlag(string $key, bool $default = false): bool
    {
        try {
            $stmt = $this->db->query('SELECT flag_value FROM ' . self::TABLE . ' WHERE flag_key = ? LIMIT 1', [$key]);
            $row = $stmt->fetch();

            if (!$row) {
                return $default;
            }

            return (bool) ($row['flag_value'] ?? 0);
        } catch (Throwable $e) {
            if ($this->recoverMissingTable($e)) {
                return $default;
            }

            error_log('FeatureFlagService read error: ' . $e->getMessage());

            return $default;
        }
    }

    private function setBoolFlag(string $key, bool $value, string $description = '', ?string $updatedBy = null): bool
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (flag_key, flag_value, description, updated_by) VALUES (?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE flag_value = VALUES(flag_value), description = VALUES(description), updated_by = VALUES(updated_by)';

        try {
            $this->db->query($sql, [
                $key,
                $value ? 1 : 0,
                $description,
                $updatedBy,
            ]);

            return true;
        } catch (Throwable $e) {
            if ($this->recoverMissingTable($e)) {
                try {
                    $this->db->query($sql, [
                        $key,
                        $value ? 1 : 0,
                        $description,
                        $updatedBy,
                    ]);

                    return true;
                } catch (Throwable $retryError) {
                    error_log('FeatureFlagService write retry error: ' . $retryError->getMessage());

                    return false;
                }
            }

            error_log('FeatureFlagService write error: ' . $e->getMessage());

            return false;
        }
    }

    private function recoverMissingTable(Throwable $e): bool
    {
        if (!$this->isMissingTableException($e)) {
            return false;
        }

        try {
            $this->createTable();

            return true;
        } catch (Throwable $createError) {
            error_log('FeatureFlagService table recovery error: ' . $createError->getMessage());

            return false;
        }
    }

    private function createTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' ('
            . 'id INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'flag_key VARCHAR(100) NOT NULL,'
            . 'flag_value TINYINT(1) NOT NULL DEFAULT 0,'
            . 'description VARCHAR(255) DEFAULT NULL,'
            . 'updated_by VARCHAR(100) DEFAULT NULL,'
            . 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY unique_flag_key (flag_key)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->db->query($sql);
        $this->tableEnsured = true;
    }

    private function isMissingTableException(Throwable $e): bool
    {
        return $e->getCode() === '42S02'
            || strpos($e->getMessage(), 'Base table or view not found') !== false;
    }
}
