<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

class LegacyToGlobalMigrationException extends RuntimeException
{
}

class LegacyToGlobalMigrator
{
    private const TABLE_ORDER = [
        'semanas_activas',
        'subcontratistas',
        'profesionales',
        'programa',
        'programa_consolidado',
        'programacion_semanal',
        'pdc',
        'papelera_pdc',
        'cic',
        'cip',
        'indicadores_generales',
        'actividades',
        'cambios',
        'lps_escalamientos',
        'lps_drawer_comentarios',
        'pg_tracking',
        'pi_shared_constraints',
        'pi_shared_constraint_links',
        'auto_contrato_log',
        'auto_program_log',
    ];

    private const PROJECT_SCOPED_KEYS = [
        'actividades' => ['Id'],
        'auto_contrato_log' => ['semana', 'Id_actividad', 'accion', 'batch_id'],
        'auto_program_log' => ['semana', 'consecutivo', 'accion'],
        'cambios' => ['id'],
        'cic' => ['Id'],
        'cip' => ['Semana', 'profesional'],
        'indicadores_generales' => ['Semana', 'subcontratista_profesional', 'rol'],
        'lps_drawer_comentarios' => ['id'],
        'lps_escalamientos' => ['id'],
        'papelera_pdc' => ['consecutivo'],
        'pdc' => ['consecutivo'],
        'pg_tracking' => ['consecutivo_en_programa', 'semana'],
        'pi_shared_constraint_links' => ['Id'],
        'pi_shared_constraints' => ['Id'],
        'profesionales' => ['id'],
        'programa' => ['Consecutivo'],
        'programa_consolidado' => ['Consecutivo'],
        'programacion_semanal' => ['Consecutivo'],
        'semanas_activas' => ['Id'],
        'subcontratistas' => ['Id'],
    ];

    private const SYNTHETIC_PROGRAMA_COLUMNS = [
        'Id',
        'Actividad',
        'Titulo',
        'Fecha_Inicio',
        'Fecha_Fin',
        'Ruta_Critica',
        'Ejecutado',
        'Estado',
        'Semanas_Inicio',
        'Responsable_AIA',
        'Observaciones',
        'Ult_Act_Est',
        'Ult_Act_Restr',
        'restriccion_pc_1',
        'restriccion_pc_2',
        'restriccion_pc_3',
        'restriccion_pc_4',
    ];

    private PDO $pdo;
    /** @var callable */
    private $logger;
    /** @var array<string, array<int, string>> */
    private array $columnsCache = [];
    /** @var array<string, array<int, string>> */
    private array $primaryKeyCache = [];
    /** @var array<string, array<string, bool>> */
    private array $autoIncrementCache = [];

    public function __construct(PDO $pdo, ?callable $logger = null)
    {
        $this->pdo = $pdo;
        $this->logger = $logger ?? static function (string $line): void {
            echo $line . PHP_EOL;
        };
    }

    /**
     * @param array{apply?: bool, strict?: bool, projectId?: int|null} $options
     * @return array{projects:int,tables:int,sourceRows:int,pendingRows:int,writtenRows:int,conflicts:int,skipped:int}
     */
    public function run(array $options = []): array
    {
        $apply = (bool) ($options['apply'] ?? false);
        $strict = (bool) ($options['strict'] ?? false);
        $projectId = isset($options['projectId']) ? (int) $options['projectId'] : null;

        $summary = [
            'projects' => 0,
            'tables' => 0,
            'sourceRows' => 0,
            'pendingRows' => 0,
            'writtenRows' => 0,
            'conflicts' => 0,
            'skipped' => 0,
        ];

        $this->log('=== ' . ($apply ? 'APPLY' : 'DRY-RUN') . ' legacy to global migration ===');
        foreach ($this->projects($projectId) as $project) {
            $summary['projects']++;
            $projectSummary = $this->migrateProject($project, $apply, $strict);
            foreach ($projectSummary as $key => $value) {
                $summary[$key] += $value;
            }
        }

        $this->log(sprintf(
            'Summary: projects=%d tables=%d sourceRows=%d pendingRows=%d writtenRows=%d skipped=%d conflicts=%d',
            $summary['projects'],
            $summary['tables'],
            $summary['sourceRows'],
            $summary['pendingRows'],
            $summary['writtenRows'],
            $summary['skipped'],
            $summary['conflicts'],
        ));

        return $summary;
    }

    /**
     * @param array{ID:int|string,Base_de_Datos:string} $project
     * @return array{tables:int,sourceRows:int,pendingRows:int,writtenRows:int,conflicts:int,skipped:int}
     */
    private function migrateProject(array $project, bool $apply, bool $strict): array
    {
        $projectId = (int) $project['ID'];
        $prefix = (string) $project['Base_de_Datos'];
        if (!$this->validIdentifier($prefix)) {
            throw new LegacyToGlobalMigrationException("Invalid project prefix for project {$projectId}: {$prefix}");
        }

        $summary = [
            'tables' => 0,
            'sourceRows' => 0,
            'pendingRows' => 0,
            'writtenRows' => 0,
            'conflicts' => 0,
            'skipped' => 0,
        ];

        foreach (self::TABLE_ORDER as $table) {
            $source = $this->resolveSourceTable($prefix, $table, $strict);
            if ($source === null) {
                $summary['skipped']++;
                continue;
            }

            $synthetic = $this->synthesizeProgramParents($projectId, $source, $table, $apply);
            if ($synthetic['pendingRows'] > 0 || $synthetic['writtenRows'] > 0) {
                $summary['pendingRows'] += $synthetic['pendingRows'];
                $summary['writtenRows'] += $synthetic['writtenRows'];
                $this->log(sprintf(
                    '%s.%s -> programa parents: pending=%d written=%d',
                    $prefix,
                    $table,
                    $synthetic['pendingRows'],
                    $synthetic['writtenRows'],
                ));
            }

            $syntheticSubcontractors = $this->synthesizeSubcontractors($projectId, $source, $table, $apply);
            if ($syntheticSubcontractors['pendingRows'] > 0 || $syntheticSubcontractors['writtenRows'] > 0) {
                $summary['pendingRows'] += $syntheticSubcontractors['pendingRows'];
                $summary['writtenRows'] += $syntheticSubcontractors['writtenRows'];
                $this->log(sprintf(
                    '%s.%s -> subcontratistas parents: pending=%d written=%d',
                    $prefix,
                    $table,
                    $syntheticSubcontractors['pendingRows'],
                    $syntheticSubcontractors['writtenRows'],
                ));
            }

            $result = $this->migrateTable($projectId, $source, $table, $apply);
            $summary['tables']++;
            foreach (['sourceRows', 'pendingRows', 'writtenRows'] as $key) {
                $summary[$key] += $result[$key];
            }

            $this->log(sprintf(
                '%s.%s -> %s: source=%d pending=%d written=%d',
                $prefix,
                $table,
                $table,
                $result['sourceRows'],
                $result['pendingRows'],
                $result['writtenRows'],
            ));
        }

        return $summary;
    }

    /**
     * @return array{sourceRows:int,pendingRows:int,writtenRows:int}
     */
    private function migrateTable(int $projectId, string $sourceTable, string $targetTable, bool $apply): array
    {
        $sourceRows = $this->countRows($sourceTable);
        $columns = $this->insertColumns($sourceTable, $targetTable);
        if ($columns === []) {
            throw new LegacyToGlobalMigrationException("No common migratable columns for {$sourceTable} -> {$targetTable}");
        }

        $keys = $this->keyColumns($sourceTable, $targetTable, $columns);
        if ($keys === []) {
            throw new LegacyToGlobalMigrationException("No idempotency key for {$sourceTable} -> {$targetTable}");
        }

        $pending = $this->pendingRows($projectId, $sourceTable, $targetTable, $keys);
        $written = 0;
        if ($apply && $pending > 0) {
            $written = $this->insertPendingRows($projectId, $sourceTable, $targetTable, $columns, $keys);
        }

        return [
            'sourceRows' => $sourceRows,
            'pendingRows' => $pending,
            'writtenRows' => $written,
        ];
    }

    /**
     * @return array{pendingRows:int,writtenRows:int}
     */
    private function synthesizeProgramParents(int $projectId, string $sourceTable, string $sourceKind, bool $apply): array
    {
        $referenceColumn = match ($sourceKind) {
            'programa_consolidado' => 'Consecutivo_en_Programa',
            'programacion_semanal' => 'Consecutivo_En_Programa',
            default => null,
        };

        if ($referenceColumn === null || !$this->hasColumns([$referenceColumn], array_flip($this->columns($sourceTable)))) {
            return ['pendingRows' => 0, 'writtenRows' => 0];
        }

        $pending = $this->pendingProgramParents($projectId, $sourceTable, $referenceColumn);
        $written = $apply && $pending > 0
            ? $this->insertProgramParents($projectId, $sourceTable, $referenceColumn)
            : 0;

        return ['pendingRows' => $pending, 'writtenRows' => $written];
    }

    private function pendingProgramParents(int $projectId, string $sourceTable, string $referenceColumn): int
    {
        $sql = "SELECT COUNT(DISTINCT src.`{$referenceColumn}`)
                FROM `{$sourceTable}` src
                WHERE src.`{$referenceColumn}` IS NOT NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM `programa` dst
                    WHERE dst.project_id = ? AND dst.Consecutivo = src.`{$referenceColumn}`
                  )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    private function insertProgramParents(int $projectId, string $sourceTable, string $referenceColumn): int
    {
        $sourceColumns = array_flip($this->columns($sourceTable));
        $copyColumns = [];
        $targetColumns = array_flip($this->columns('programa'));
        foreach (self::SYNTHETIC_PROGRAMA_COLUMNS as $column) {
            if (!isset($sourceColumns[$column], $targetColumns[$column])) {
                continue;
            }
            $copyColumns[] = $column;
        }

        $targetColumns = array_merge(['project_id', 'Consecutivo'], $copyColumns);
        $selectColumns = array_merge(
            ['?', "src.`{$referenceColumn}`"],
            array_map(static fn(string $column): string => "MIN(src.`{$column}`)", $copyColumns),
        );
        $sql = sprintf(
            'INSERT INTO `programa` (%s)
             SELECT %s
             FROM `%s` src
             WHERE src.`%s` IS NOT NULL
               AND NOT EXISTS (
                 SELECT 1 FROM `programa` dst
                 WHERE dst.project_id = ? AND dst.Consecutivo = src.`%s`
               )
             GROUP BY src.`%s`
             ORDER BY src.`%s`',
            implode(', ', array_map(static fn(string $column): string => "`{$column}`", $targetColumns)),
            implode(', ', $selectColumns),
            $sourceTable,
            $referenceColumn,
            $referenceColumn,
            $referenceColumn,
            $referenceColumn,
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId, $projectId]);
        return $stmt->rowCount();
    }

    /**
     * @return array{pendingRows:int,writtenRows:int}
     */
    private function synthesizeSubcontractors(int $projectId, string $sourceTable, string $sourceKind, bool $apply): array
    {
        if ($sourceKind !== 'cic' || !$this->hasColumns(['subcontratista'], array_flip($this->columns($sourceTable)))) {
            return ['pendingRows' => 0, 'writtenRows' => 0];
        }

        $pending = $this->pendingSubcontractors($projectId, $sourceTable);
        $written = $apply && $pending > 0
            ? $this->insertSubcontractors($projectId, $sourceTable)
            : 0;

        return ['pendingRows' => $pending, 'writtenRows' => $written];
    }

    private function pendingSubcontractors(int $projectId, string $sourceTable): int
    {
        $sql = "SELECT COUNT(DISTINCT src.`subcontratista`)
                FROM `{$sourceTable}` src
                WHERE src.`subcontratista` IS NOT NULL
                  AND TRIM(src.`subcontratista`) <> ''
                  AND NOT EXISTS (
                    SELECT 1 FROM `subcontratistas` dst
                    WHERE dst.project_id = ? AND dst.subcontratista = src.`subcontratista`
                  )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    private function insertSubcontractors(int $projectId, string $sourceTable): int
    {
        $sql = "INSERT INTO `subcontratistas`
                    (`project_id`, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
                SELECT
                    ?,
                    (SELECT COALESCE(MAX(existing.`Id`), 0)
                     FROM `subcontratistas` existing
                     WHERE existing.project_id = ?) + ROW_NUMBER() OVER (ORDER BY src.`subcontratista`),
                    src.`subcontratista`,
                    '',
                    0,
                    '',
                    'CIC',
                    1
                FROM `{$sourceTable}` src
                WHERE src.`subcontratista` IS NOT NULL
                  AND TRIM(src.`subcontratista`) <> ''
                  AND NOT EXISTS (
                    SELECT 1 FROM `subcontratistas` dst
                    WHERE dst.project_id = ? AND dst.subcontratista = src.`subcontratista`
                  )
                GROUP BY src.`subcontratista`
                ORDER BY src.`subcontratista`";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId, $projectId, $projectId]);
        return $stmt->rowCount();
    }

    /**
     * @return array<int, array{ID:int|string,Base_de_Datos:string}>
     */
    private function projects(?int $projectId): array
    {
        $sql = "SELECT ID, Base_de_Datos
                FROM general_proyectos_procesos
                WHERE Base_de_Datos IS NOT NULL
                  AND CHAR_LENGTH(Base_de_Datos) > 0";
        $params = [];
        if ($projectId !== null) {
            $sql .= ' AND ID = ?';
            $params[] = $projectId;
        }
        $sql .= ' ORDER BY ID';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function resolveSourceTable(string $prefix, string $table, bool $strict): ?string
    {
        $legacy = "{$prefix}_{$table}";
        $archive = "zleg_{$prefix}_{$table}";
        $hasLegacy = $this->tableExists($legacy);
        $hasArchive = $this->tableExists($archive);

        if ($hasLegacy && $hasArchive && $strict && $this->tableSignature($legacy, $archive) !== 0) {
            throw new LegacyToGlobalMigrationException("divergent sources for {$legacy} and {$archive}");
        }

        if ($hasLegacy) {
            return $legacy;
        }

        return $hasArchive ? $archive : null;
    }

    /**
     * @return array<int, string>
     */
    private function insertColumns(string $sourceTable, string $targetTable): array
    {
        $sourceColumns = array_flip($this->columns($sourceTable));
        $autoIncrement = $this->autoIncrementColumns($targetTable);
        $columns = [];

        foreach ($this->columns($targetTable) as $column) {
            if ($column === 'project_id' || !isset($sourceColumns[$column])) {
                continue;
            }

            if (($autoIncrement[$column] ?? false) && !in_array($column, self::PROJECT_SCOPED_KEYS[$targetTable] ?? [], true)) {
                continue;
            }

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @param array<int, string> $insertColumns
     * @return array<int, string>
     */
    private function keyColumns(string $sourceTable, string $targetTable, array $insertColumns): array
    {
        $sourceColumns = array_flip($this->columns($sourceTable));
        $configured = self::PROJECT_SCOPED_KEYS[$targetTable] ?? [];
        if ($configured !== [] && $this->hasColumns($configured, $sourceColumns)) {
            return $configured;
        }

        $primary = array_values(array_filter(
            $this->primaryKeyColumns($targetTable),
            static fn(string $column): bool => $column !== 'project_id'
        ));
        if ($primary !== [] && $this->hasColumns($primary, $sourceColumns)) {
            return $primary;
        }

        return $insertColumns;
    }

    /**
     * @param array<int, string> $columns
     */
    private function hasColumns(array $columns, array $sourceColumns): bool
    {
        foreach ($columns as $column) {
            if (!isset($sourceColumns[$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $keys
     */
    private function pendingRows(int $projectId, string $sourceTable, string $targetTable, array $keys): int
    {
        $conditions = $this->keyConditions($keys);
        $sql = "SELECT COUNT(*)
                FROM (
                    SELECT 1
                    FROM `{$sourceTable}` src
                    WHERE NOT EXISTS (
                        SELECT 1 FROM `{$targetTable}` dst
                        WHERE dst.`project_id` = ? AND {$conditions}
                    )
                    GROUP BY {$this->sourceGroupBy($keys)}
                ) pending";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $keys
     */
    private function insertPendingRows(int $projectId, string $sourceTable, string $targetTable, array $columns, array $keys): int
    {
        if ($this->sourceHasDuplicateKeys($sourceTable, $keys)) {
            return $this->insertGroupedPendingRows($projectId, $sourceTable, $targetTable, $columns, $keys);
        }

        $targetColumns = array_merge(['project_id'], $columns);
        $selectColumns = array_merge(['?'], array_map(static fn(string $column): string => "src.`{$column}`", $columns));
        $conditions = $this->keyConditions($keys);
        $orderBy = $this->orderByClause($keys);
        $sql = sprintf(
            'INSERT INTO `%s` (%s)
             SELECT %s
             FROM `%s` src
             WHERE NOT EXISTS (
                 SELECT 1 FROM `%s` dst
                 WHERE dst.`project_id` = ? AND %s
             )%s',
            $targetTable,
            implode(', ', array_map(static fn(string $column): string => "`{$column}`", $targetColumns)),
            implode(', ', $selectColumns),
            $sourceTable,
            $targetTable,
            $conditions,
            $orderBy,
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId, $projectId]);
        return $stmt->rowCount();
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $keys
     */
    private function insertGroupedPendingRows(int $projectId, string $sourceTable, string $targetTable, array $columns, array $keys): int
    {
        $targetColumns = array_merge(['project_id'], $columns);
        $selectColumns = array_merge(['?'], $this->groupedSelectColumns($columns, $keys));
        $conditions = $this->keyConditions($keys);
        $sql = sprintf(
            'INSERT INTO `%s` (%s)
             SELECT %s
             FROM `%s` src
             WHERE NOT EXISTS (
                 SELECT 1 FROM `%s` dst
                 WHERE dst.`project_id` = ? AND %s
             )
             GROUP BY %s
             ORDER BY %s',
            $targetTable,
            implode(', ', array_map(static fn(string $column): string => "`{$column}`", $targetColumns)),
            implode(', ', $selectColumns),
            $sourceTable,
            $targetTable,
            $conditions,
            $this->sourceGroupBy($keys),
            $this->sourceGroupBy($keys),
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId, $projectId]);
        return $stmt->rowCount();
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function groupedSelectColumns(array $columns, array $keys): array
    {
        return array_map(static function (string $column) use ($keys): string {
            if (in_array($column, $keys, true)) {
                return "src.`{$column}`";
            }

            return "MIN(src.`{$column}`)";
        }, $columns);
    }

    /**
     * @param array<int, string> $keys
     */
    private function sourceHasDuplicateKeys(string $sourceTable, array $keys): bool
    {
        $sql = "SELECT 1 FROM `{$sourceTable}` src GROUP BY {$this->sourceGroupBy($keys)} HAVING COUNT(*) > 1 LIMIT 1";
        return (bool) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * @param array<int, string> $keys
     */
    private function keyConditions(array $keys): string
    {
        return implode(' AND ', array_map(
            static fn(string $column): string => "CAST(dst.`{$column}` AS CHAR) <=> CAST(src.`{$column}` AS CHAR)",
            $keys,
        ));
    }

    /**
     * @param array<int, string> $keys
     */
    private function sourceGroupBy(array $keys): string
    {
        return implode(', ', array_map(static fn(string $column): string => "src.`{$column}`", $keys));
    }

    /**
     * @param array<int, string> $keys
     */
    private function orderByClause(array $keys): string
    {
        if ($keys === []) {
            return '';
        }

        return ' ORDER BY ' . implode(', ', array_map(static fn(string $column): string => "src.`{$column}`", $keys));
    }

    private function tableSignature(string $leftTable, string $rightTable): int
    {
        $leftColumns = $this->columns($leftTable);
        $rightColumns = $this->columns($rightTable);
        $columns = array_values(array_intersect($leftColumns, $rightColumns));
        if ($columns === []) {
            return $this->countRows($leftTable) <=> $this->countRows($rightTable);
        }

        $left = $this->signature($leftTable, $columns);
        $right = $this->signature($rightTable, $columns);
        return $left <=> $right;
    }

    /**
     * @param array<int, string> $columns
     */
    private function signature(string $table, array $columns): string
    {
        $concat = implode(
            ", '#', ",
            array_map(static fn(string $column): string => "COALESCE(CAST(`{$column}` AS CHAR), '')", $columns),
        );
        $sql = "SELECT CONCAT(COUNT(*), ':', COALESCE(SUM(CRC32(CONCAT_WS('#', {$concat}))), 0)) FROM `{$table}`";
        return (string) $this->pdo->query($sql)->fetchColumn();
    }

    private function countRows(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    private function tableExists(string $table): bool
    {
        if (!$this->validIdentifier($table)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table): array
    {
        if (isset($this->columnsCache[$table])) {
            return $this->columnsCache[$table];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION'
        );
        $stmt->execute([$table]);
        $this->columnsCache[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $this->columnsCache[$table];
    }

    /**
     * @return array<int, string>
     */
    private function primaryKeyColumns(string $table): array
    {
        if (isset($this->primaryKeyCache[$table])) {
            return $this->primaryKeyCache[$table];
        }

        $stmt = $this->pdo->prepare(
            "SELECT COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute([$table]);
        $this->primaryKeyCache[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $this->primaryKeyCache[$table];
    }

    /**
     * @return array<string, bool>
     */
    private function autoIncrementColumns(string $table): array
    {
        if (isset($this->autoIncrementCache[$table])) {
            return $this->autoIncrementCache[$table];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COLUMN_NAME, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);

        $columns = [];
        foreach ($stmt->fetchAll() as $row) {
            $columns[$row['COLUMN_NAME']] = str_contains(strtolower((string) $row['EXTRA']), 'auto_increment');
        }

        $this->autoIncrementCache[$table] = $columns;
        return $columns;
    }

    private function validIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $identifier) === 1;
    }

    private function log(string $line): void
    {
        ($this->logger)($line);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $options = getopt('', ['apply', 'dry-run', 'strict', 'project-id:']);
    $apply = array_key_exists('apply', $options);
    $projectId = isset($options['project-id']) ? (int) $options['project-id'] : null;
    $strict = array_key_exists('strict', $options);

    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
    $pdo->exec("SET time_zone = '-05:00'");

    try {
        $migrator = new LegacyToGlobalMigrator($pdo);
        $migrator->run([
            'apply' => $apply,
            'strict' => $strict,
            'projectId' => $projectId,
        ]);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
