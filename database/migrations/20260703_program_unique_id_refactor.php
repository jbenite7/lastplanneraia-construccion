<?php

require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();
$ref = new ReflectionClass($db);
$prop = $ref->getProperty('pdo');
$prop->setAccessible(true);
$pdo = $prop->getValue($db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rollback = in_array('--rollback', $argv ?? [], true);
$apply = in_array('--apply', $argv ?? [], true) || $rollback;

function scalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    return scalar($pdo, 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]) > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    return scalar($pdo, 'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $index]) > 0;
}

function constraintExists(PDO $pdo, string $name): bool
{
    return scalar($pdo, 'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?', [$name]) > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    return scalar($pdo, 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]) > 0;
}

function execSql(PDO $pdo, string $sql, bool $apply): void
{
    echo ($apply ? 'APPLY ' : 'DRY   ') . $sql . PHP_EOL;
    if ($apply) {
        $pdo->exec($sql);
    }
}

function addColumn(PDO $pdo, string $table, string $column, string $definition, bool $apply): void
{
    if (!columnExists($pdo, $table, $column)) {
        execSql($pdo, "ALTER TABLE `{$table}` ADD COLUMN {$definition}", $apply);
    }
}

function dropColumn(PDO $pdo, string $table, string $column, bool $apply): void
{
    if (columnExists($pdo, $table, $column)) {
        execSql($pdo, "ALTER TABLE `{$table}` DROP COLUMN `{$column}`", $apply);
    }
}

function addIndex(PDO $pdo, string $table, string $index, string $definition, bool $apply): void
{
    if (!indexExists($pdo, $table, $index)) {
        execSql($pdo, "ALTER TABLE `{$table}` ADD {$definition}", $apply);
    }
}

function addForeignKey(PDO $pdo, string $table, string $name, string $definition, bool $apply): void
{
    if (!constraintExists($pdo, $name)) {
        execSql($pdo, "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` {$definition}", $apply);
    }
}

function dropForeignKey(PDO $pdo, string $table, string $name, bool $apply): void
{
    if (constraintExists($pdo, $name)) {
        execSql($pdo, "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`", $apply);
    }
}

function dropIndex(PDO $pdo, string $table, string $index, bool $apply): void
{
    if (indexExists($pdo, $table, $index)) {
        execSql($pdo, "ALTER TABLE `{$table}` DROP INDEX `{$index}`", $apply);
    }
}

function syncTrigger(PDO $pdo, string $table, string $name, string $timing, array $pairs, bool $apply): void
{
    $trigger = "trg_{$table}_unique_id_{$timing}";
    execSql($pdo, "DROP TRIGGER IF EXISTS `{$trigger}`", $apply);
    $body = [];
    foreach ($pairs as [$newColumn, $legacyColumn]) {
        $body[] = "IF NEW.`{$newColumn}` IS NULL AND NEW.`{$legacyColumn}` IS NOT NULL THEN SET NEW.`{$newColumn}` = NEW.`{$legacyColumn}`; END IF";
        $body[] = "IF NEW.`{$legacyColumn}` IS NULL AND NEW.`{$newColumn}` IS NOT NULL THEN SET NEW.`{$legacyColumn}` = NEW.`{$newColumn}`; END IF";
    }
    execSql($pdo, "CREATE TRIGGER `{$trigger}` BEFORE {$timing} ON `{$table}` FOR EACH ROW BEGIN " . implode('; ', $body) . '; END', $apply);
}

function syncAutoProgramLogTrigger(PDO $pdo, string $timing, bool $apply): void
{
    $trigger = "trg_auto_program_log_unique_id_{$timing}";
    execSql($pdo, "DROP TRIGGER IF EXISTS `{$trigger}`", $apply);
    execSql(
        $pdo,
        "CREATE TRIGGER `{$trigger}` BEFORE {$timing} ON `auto_program_log` FOR EACH ROW BEGIN IF NEW.`consecutivo` > 0 AND EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`consecutivo`) THEN SET NEW.`unique_id` = NEW.`consecutivo`; ELSEIF NEW.`unique_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`unique_id`) THEN SET NEW.`unique_id` = NULL; ELSEIF NEW.`consecutivo` <= 0 THEN SET NEW.`unique_id` = NULL; END IF; END",
        $apply
    );
}

if ($rollback) {
    foreach ([
        ['programa_consolidado', 'fk_pc__programa__unique_id'],
        ['programacion_semanal', 'fk_ps__programa__unique_id'],
        ['lps_drawer_comentarios', 'fk_ldc__programa__unique_id'],
        ['lps_escalamientos', 'fk_le__programa__unique_id'],
        ['pg_tracking', 'fk_pgt__programa__unique_id'],
        ['pi_shared_constraint_links', 'fk_pscl__programa__unique_id'],
        ['auto_program_log', 'fk_apl__programa__unique_id'],
    ] as [$table, $fk]) {
        dropForeignKey($pdo, $table, $fk, $apply);
    }

    foreach ([
        'programa', 'programa_consolidado', 'programacion_semanal', 'lps_drawer_comentarios',
        'lps_escalamientos', 'pg_tracking', 'pi_shared_constraint_links', 'auto_program_log',
    ] as $table) {
        execSql($pdo, "DROP TRIGGER IF EXISTS `trg_{$table}_unique_id_INSERT`", $apply);
        execSql($pdo, "DROP TRIGGER IF EXISTS `trg_{$table}_unique_id_UPDATE`", $apply);
    }

    foreach ([
        ['programa', 'uq_programa_project_unique_id'],
        ['programa_consolidado', 'idx_pc_project_semana_unique_id'],
        ['programa_consolidado', 'idx_pc_project_row_id'],
        ['programacion_semanal', 'idx_ps_project_semana_unique_id'],
        ['programacion_semanal', 'idx_ps_project_row_id'],
        ['lps_drawer_comentarios', 'idx_ldc_project_unique_week'],
        ['lps_escalamientos', 'idx_le_project_unique_week'],
        ['pg_tracking', 'idx_pgt_project_unique_week'],
        ['pi_shared_constraint_links', 'idx_pscl_project_unique_week'],
        ['auto_program_log', 'idx_apl_project_unique_week'],
    ] as [$table, $index]) {
        dropIndex($pdo, $table, $index, $apply);
    }

    foreach ([
        ['programa', 'unique_id'],
        ['programa_consolidado', 'row_id'],
        ['programa_consolidado', 'unique_id'],
        ['programacion_semanal', 'row_id'],
        ['programacion_semanal', 'unique_id'],
        ['lps_drawer_comentarios', 'unique_id'],
        ['lps_escalamientos', 'unique_id'],
        ['pg_tracking', 'unique_id'],
        ['pi_shared_constraint_links', 'unique_id'],
        ['auto_program_log', 'unique_id'],
    ] as [$table, $column]) {
        dropColumn($pdo, $table, $column, $apply);
    }

    execSql($pdo, 'DROP TABLE IF EXISTS `program_unique_id_sequences`', $apply);
    echo "Rollback unique_id terminado.\n";
    exit(0);
}

execSql($pdo, 'CREATE TABLE IF NOT EXISTS `program_unique_id_sequences` (`project_id` int NOT NULL PRIMARY KEY, `next_unique_id` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', $apply);

addColumn($pdo, 'programa', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `project_id`', $apply);
addColumn($pdo, 'programa_consolidado', 'row_id', '`row_id` int DEFAULT NULL AFTER `project_id`', $apply);
addColumn($pdo, 'programa_consolidado', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `Semana`', $apply);
addColumn($pdo, 'programacion_semanal', 'row_id', '`row_id` int DEFAULT NULL AFTER `project_id`', $apply);
addColumn($pdo, 'programacion_semanal', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `Semana`', $apply);
addColumn($pdo, 'lps_drawer_comentarios', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `semana`', $apply);
addColumn($pdo, 'lps_escalamientos', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `semana`', $apply);
addColumn($pdo, 'pg_tracking', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `project_id`', $apply);
addColumn($pdo, 'pi_shared_constraint_links', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `Semana`', $apply);
addColumn($pdo, 'auto_program_log', 'unique_id', '`unique_id` int DEFAULT NULL AFTER `consecutivo`', $apply);

foreach ([
    'UPDATE `programa` SET `unique_id` = `Consecutivo` WHERE `unique_id` IS NULL OR `unique_id` <> `Consecutivo`',
    'UPDATE `programa_consolidado` SET `row_id` = `Consecutivo` WHERE `row_id` IS NULL OR `row_id` <> `Consecutivo`',
    'UPDATE `programa_consolidado` SET `unique_id` = `Consecutivo_en_Programa` WHERE `unique_id` IS NULL OR `unique_id` <> `Consecutivo_en_Programa`',
    'UPDATE `programacion_semanal` SET `row_id` = `Consecutivo` WHERE `row_id` IS NULL OR `row_id` <> `Consecutivo`',
    'UPDATE `programacion_semanal` SET `unique_id` = `Consecutivo_En_Programa` WHERE `unique_id` IS NULL OR `unique_id` <> `Consecutivo_En_Programa`',
    'UPDATE `lps_drawer_comentarios` SET `unique_id` = `consecutivo_en_programa` WHERE `unique_id` IS NULL OR `unique_id` <> `consecutivo_en_programa`',
    'UPDATE `lps_escalamientos` SET `unique_id` = `consecutivo_en_programa` WHERE `unique_id` IS NULL OR `unique_id` <> `consecutivo_en_programa`',
    'UPDATE `pg_tracking` SET `unique_id` = `consecutivo_en_programa` WHERE `unique_id` IS NULL OR `unique_id` <> `consecutivo_en_programa`',
    'UPDATE `pi_shared_constraint_links` SET `unique_id` = `ConsecutivoEnPrograma` WHERE `unique_id` IS NULL OR `unique_id` <> `ConsecutivoEnPrograma`',
    'UPDATE `auto_program_log` l SET `unique_id` = CASE WHEN l.`consecutivo` > 0 AND EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> l.`project_id` AND p.`unique_id` = l.`consecutivo`) THEN l.`consecutivo` ELSE NULL END WHERE (l.`unique_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> l.`project_id` AND p.`unique_id` = l.`unique_id`)) OR (l.`consecutivo` > 0 AND EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> l.`project_id` AND p.`unique_id` = l.`consecutivo`) AND (l.`unique_id` IS NULL OR l.`unique_id` <> l.`consecutivo`)) OR (l.`consecutivo` <= 0 AND l.`unique_id` IS NOT NULL)',
] as $sql) {
    execSql($pdo, $sql, $apply);
}

execSql($pdo, 'INSERT INTO `program_unique_id_sequences` (`project_id`, `next_unique_id`) SELECT `project_id`, COALESCE(MAX(`unique_id`), 0) + 1 FROM `programa` GROUP BY `project_id` ON DUPLICATE KEY UPDATE `next_unique_id` = GREATEST(`next_unique_id`, VALUES(`next_unique_id`))', $apply);

addIndex($pdo, 'programa', 'uq_programa_project_unique_id', 'UNIQUE KEY `uq_programa_project_unique_id` (`project_id`, `unique_id`)', $apply);
addIndex($pdo, 'programa_consolidado', 'idx_pc_project_semana_unique_id', 'KEY `idx_pc_project_semana_unique_id` (`project_id`, `Semana`, `unique_id`)', $apply);
addIndex($pdo, 'programa_consolidado', 'idx_pc_project_row_id', 'KEY `idx_pc_project_row_id` (`project_id`, `row_id`)', $apply);
addIndex($pdo, 'programacion_semanal', 'idx_ps_project_semana_unique_id', 'KEY `idx_ps_project_semana_unique_id` (`project_id`, `Semana`, `unique_id`)', $apply);
addIndex($pdo, 'programacion_semanal', 'idx_ps_project_row_id', 'KEY `idx_ps_project_row_id` (`project_id`, `row_id`)', $apply);
addIndex($pdo, 'lps_drawer_comentarios', 'idx_ldc_project_unique_week', 'KEY `idx_ldc_project_unique_week` (`project_id`, `unique_id`, `semana`)', $apply);
addIndex($pdo, 'lps_escalamientos', 'idx_le_project_unique_week', 'KEY `idx_le_project_unique_week` (`project_id`, `unique_id`, `semana`)', $apply);
addIndex($pdo, 'pg_tracking', 'idx_pgt_project_unique_week', 'KEY `idx_pgt_project_unique_week` (`project_id`, `unique_id`, `semana`)', $apply);
addIndex($pdo, 'pi_shared_constraint_links', 'idx_pscl_project_unique_week', 'KEY `idx_pscl_project_unique_week` (`project_id`, `unique_id`, `Semana`)', $apply);
addIndex($pdo, 'auto_program_log', 'idx_apl_project_unique_week', 'KEY `idx_apl_project_unique_week` (`project_id`, `unique_id`, `semana`)', $apply);

addForeignKey($pdo, 'programa_consolidado', 'fk_pc__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'programacion_semanal', 'fk_ps__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'lps_drawer_comentarios', 'fk_ldc__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'lps_escalamientos', 'fk_le__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'pg_tracking', 'fk_pgt__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'pi_shared_constraint_links', 'fk_pscl__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);
addForeignKey($pdo, 'auto_program_log', 'fk_apl__programa__unique_id', 'FOREIGN KEY (`project_id`, `unique_id`) REFERENCES `programa` (`project_id`, `unique_id`) ON DELETE CASCADE', $apply);

syncTrigger($pdo, 'programa', 'programa', 'INSERT', [['unique_id', 'Consecutivo']], $apply);
syncTrigger($pdo, 'programa', 'programa', 'UPDATE', [['unique_id', 'Consecutivo']], $apply);
syncTrigger($pdo, 'programa_consolidado', 'pc', 'INSERT', [['row_id', 'Consecutivo'], ['unique_id', 'Consecutivo_en_Programa']], $apply);
syncTrigger($pdo, 'programa_consolidado', 'pc', 'UPDATE', [['row_id', 'Consecutivo'], ['unique_id', 'Consecutivo_en_Programa']], $apply);
syncTrigger($pdo, 'programacion_semanal', 'ps', 'INSERT', [['row_id', 'Consecutivo'], ['unique_id', 'Consecutivo_En_Programa']], $apply);
syncTrigger($pdo, 'programacion_semanal', 'ps', 'UPDATE', [['row_id', 'Consecutivo'], ['unique_id', 'Consecutivo_En_Programa']], $apply);
syncTrigger($pdo, 'lps_drawer_comentarios', 'ldc', 'INSERT', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'lps_drawer_comentarios', 'ldc', 'UPDATE', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'lps_escalamientos', 'le', 'INSERT', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'lps_escalamientos', 'le', 'UPDATE', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'pg_tracking', 'pgt', 'INSERT', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'pg_tracking', 'pgt', 'UPDATE', [['unique_id', 'consecutivo_en_programa']], $apply);
syncTrigger($pdo, 'pi_shared_constraint_links', 'pscl', 'INSERT', [['unique_id', 'ConsecutivoEnPrograma']], $apply);
syncTrigger($pdo, 'pi_shared_constraint_links', 'pscl', 'UPDATE', [['unique_id', 'ConsecutivoEnPrograma']], $apply);
syncAutoProgramLogTrigger($pdo, 'INSERT', $apply);
syncAutoProgramLogTrigger($pdo, 'UPDATE', $apply);

echo ($apply ? 'Migración unique_id aplicada.' : 'Dry-run unique_id completado. Use --apply para ejecutar.') . PHP_EOL;
