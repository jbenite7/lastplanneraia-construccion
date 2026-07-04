<?php

require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();
$ref = new ReflectionClass($db);
$prop = $ref->getProperty('pdo');
$prop->setAccessible(true);
$pdo = $prop->getValue($db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$apply = in_array('--apply', $argv ?? [], true);

function cambiosScalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function cambiosExec(PDO $pdo, string $sql, bool $apply): void
{
    echo ($apply ? 'APPLY ' : 'DRY   ') . $sql . PHP_EOL;
    if ($apply) {
        $pdo->exec($sql);
    }
}

function cambiosTableExists(PDO $pdo, string $table): bool
{
    return cambiosScalar($pdo, 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]) > 0;
}

function cambiosColumnExists(PDO $pdo, string $table, string $column): bool
{
    return cambiosScalar($pdo, 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]) > 0;
}

function cambiosIndexExists(PDO $pdo, string $table, string $index): bool
{
    return cambiosScalar($pdo, 'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $index]) > 0;
}

function cambiosColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function cambiosPrimaryKey(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function cambiosColumnIsAutoIncrement(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return str_contains(strtolower((string) $stmt->fetchColumn()), 'auto_increment');
}

function cambiosQuoted(array $columns): string
{
    return implode(', ', array_map(static fn(string $column): string => "`{$column}`", $columns));
}

function cambiosEnsureTable(PDO $pdo, bool $apply): void
{
    if (cambiosTableExists($pdo, 'cambios')) {
        return;
    }

    cambiosExec($pdo, "CREATE TABLE `cambios` (
        `project_id` int NOT NULL,
        `id` int NOT NULL,
        `solicitanteCambio` int DEFAULT NULL,
        `detalleSolicitanteOtro` longtext,
        `fechaSolicitud` date DEFAULT NULL,
        `prioridad` int DEFAULT NULL,
        `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
        `responsableSolucion` int DEFAULT NULL,
        `detalleResponsableSolucion` longtext,
        `justificacion` longtext,
        `descripcion` longtext,
        `incidenciaAlcance` longtext,
        `tiempoCronograma` float DEFAULT NULL,
        `tiempoCronogramaAfectado` float DEFAULT NULL,
        `incidenciaCronograma` longtext,
        `valorPresupuesto` float DEFAULT NULL,
        `costoDirecto` float DEFAULT NULL,
        `costoDirectoAIU` float DEFAULT NULL,
        `costoDirectoAIUIVA` float DEFAULT NULL,
        `valorAprobado` float DEFAULT NULL,
        `incidenciaPresupuesto` longtext,
        `incidenciaCalidad` longtext,
        `incidenciaRiesgo` longtext,
        `incidenciaRecurso` longtext,
        `fechaTentativaDefinicion` date DEFAULT NULL,
        `fechaEntregaInterventoria` date DEFAULT NULL,
        `Observaciones` longtext,
        `fechaDefinicion` date DEFAULT NULL,
        `aprobacion` int DEFAULT NULL,
        `soportes` longtext,
        PRIMARY KEY (`project_id`, `id`),
        KEY `idx_project_cambios` (`project_id`, `id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci", $apply);
}

function cambiosNormalizeTable(PDO $pdo, bool $apply): void
{
    if (!cambiosColumnExists($pdo, 'cambios', 'project_id')) {
        $rowCount = cambiosScalar($pdo, 'SELECT COUNT(*) FROM `cambios`');
        cambiosExec($pdo, 'ALTER TABLE `cambios` ADD COLUMN `project_id` int NOT NULL DEFAULT 0 FIRST', $apply);

        if ($rowCount > 0) {
            $projectCount = cambiosScalar($pdo, "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Activo = 1");
            if ($projectCount !== 1) {
                throw new RuntimeException('cambios existe con datos sin project_id y hay multiples proyectos activos; requiere mapeo manual.');
            }

            $projectId = cambiosScalar($pdo, "SELECT Id FROM general_proyectos_procesos WHERE Activo = 1 LIMIT 1");
            cambiosExec($pdo, "UPDATE `cambios` SET `project_id` = {$projectId} WHERE `project_id` = 0", $apply);
        }

        cambiosExec($pdo, 'ALTER TABLE `cambios` MODIFY `project_id` int NOT NULL', $apply);
    }

    if (cambiosColumnIsAutoIncrement($pdo, 'cambios', 'id')) {
        cambiosExec($pdo, 'ALTER TABLE `cambios` MODIFY `id` int NOT NULL', $apply);
    }

    if (cambiosPrimaryKey($pdo, 'cambios') !== ['project_id', 'id']) {
        if (cambiosPrimaryKey($pdo, 'cambios') !== []) {
            cambiosExec($pdo, 'ALTER TABLE `cambios` DROP PRIMARY KEY', $apply);
        }
        cambiosExec($pdo, 'ALTER TABLE `cambios` ADD PRIMARY KEY (`project_id`, `id`)', $apply);
    }

    if (!cambiosIndexExists($pdo, 'cambios', 'idx_project_cambios')) {
        cambiosExec($pdo, 'ALTER TABLE `cambios` ADD KEY `idx_project_cambios` (`project_id`, `id`)', $apply);
    }
}

function cambiosMigrateLegacy(PDO $pdo, bool $apply): void
{
    $targetColumns = cambiosColumns($pdo, 'cambios');
    $stmt = $pdo->query("SELECT ID, Base_de_Datos FROM general_proyectos_procesos WHERE Base_de_Datos IS NOT NULL AND Base_de_Datos != ''");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $project) {
        $projectId = (int) $project['ID'];
        $prefix = (string) $project['Base_de_Datos'];
        if (preg_match('/^[A-Za-z0-9_]+$/', $prefix) !== 1) {
            continue;
        }

        $source = "{$prefix}_cambios";
        if (!cambiosTableExists($pdo, $source)) {
            continue;
        }

        $copyColumns = array_values(array_intersect($targetColumns, cambiosColumns($pdo, $source)));
        $copyColumns = array_values(array_filter($copyColumns, static fn(string $column): bool => $column !== 'project_id'));
        if (!in_array('id', $copyColumns, true)) {
            continue;
        }

        $insertColumns = array_merge(['project_id'], $copyColumns);
        $selectColumns = cambiosQuoted($copyColumns);
        $sql = "INSERT IGNORE INTO `cambios` (" . cambiosQuoted($insertColumns) . ")
                SELECT {$projectId}, {$selectColumns} FROM `{$source}`";
        cambiosExec($pdo, $sql, $apply);
    }
}

cambiosEnsureTable($pdo, $apply);
cambiosNormalizeTable($pdo, $apply);
cambiosMigrateLegacy($pdo, $apply);
