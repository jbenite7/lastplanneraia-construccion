<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/20260701_migrate_legacy_to_global.php';

final class ProdProjectGlobalSync
{
    private const PROJECTS = [
        27 => 'prueba',
        68 => 'optimizacionJMC',
        69 => 'metrolineaConfinamientoDos',
        70 => 'metrolineaDieciseisDescendente',
        71 => 'metrolineaDieciseisAscendente',
        72 => 'metrolineaMampDos',
        73 => 'da_porto',
        74 => 'milan_campestre_torre',
    ];

    private const LEGACY_TABLES = [
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

    private const DELETE_TABLES = [
        'auto_program_log',
        'auto_contrato_log',
        'pi_shared_constraint_links',
        'pi_shared_constraints',
        'pg_tracking',
        'lps_drawer_comentarios',
        'lps_escalamientos',
        'cambios',
        'actividad_programa_fuentes',
        'contratos_trazabilidad',
        'programacion_semanal',
        'programa_consolidado',
        'papelera_pdc',
        'pdc',
        'actividades',
        'cic',
        'cip',
        'indicadores_generales',
        'subcontratistas',
        'profesionales',
        'semanas_activas',
        'program_unique_id_sequences',
        'programa',
    ];

    private PDO $pdo;
    private string $sourceSchema;

    public function __construct(PDO $pdo, string $sourceSchema)
    {
        $this->pdo = $pdo;
        $this->sourceSchema = $sourceSchema;
    }

    public function run(bool $apply, bool $dropLegacy, bool $dropSourceSchema): void
    {
        $this->assertSourceSchema();
        $this->syncProjectMetadata($apply);
        $this->dropAutoProgramLogUniqueKey($apply);
        $this->deleteGlobalRows($apply);
        $this->copyLegacySources($apply);
        $this->migrateLegacyToGlobal($apply);
        $this->remapAllProjects($apply);
        $this->reloadAutoProgramLogs($apply);
        $this->refreshProgramSequences($apply);
        $this->validateCounts();
        $this->validateReferences();

        if ($dropLegacy) {
            $this->archiveCopiedLegacyTables($apply);
        }

        if ($dropSourceSchema) {
            $this->dropSourceSchema($apply);
        }

        $this->seedDevTestUsers($apply);
    }

    private function assertSourceSchema(): void
    {
        if (!$this->validIdentifier($this->sourceSchema)) {
            throw new RuntimeException("Esquema fuente inválido: {$this->sourceSchema}");
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $stmt->execute([$this->sourceSchema]);
        if ((int) $stmt->fetchColumn() !== 1) {
            throw new RuntimeException("No existe el esquema fuente {$this->sourceSchema}.");
        }
    }

    private function seedDevTestUsers(bool $apply): void
    {
        if (!$apply) {
            echo "PLAN seed usuarios de prueba dev\n";
            return;
        }

        require __DIR__ . '/../seeds/dev_test_users.php';
    }

    private function syncProjectMetadata(bool $apply): void
    {
        if (!$this->sourceTableExists('general_proyectos_procesos') || !$this->targetTableExists('general_proyectos_procesos')) {
            return;
        }

        $sourceCols = $this->columns('general_proyectos_procesos', $this->sourceSchema);
        $targetCols = $this->columns('general_proyectos_procesos');
        $columns = array_values(array_intersect($targetCols, $sourceCols));
        if (!in_array('ID', $columns, true)) {
            return;
        }

        $ids = implode(',', array_keys(self::PROJECTS));
        $quoted = array_map([$this, 'quote'], $columns);
        $updates = array_filter(
            array_map(static fn(string $col): ?string => $col === 'ID' ? null : "`{$col}` = VALUES(`{$col}`)", $columns)
        );

        $sql = sprintf(
            'INSERT INTO `general_proyectos_procesos` (%s)
             SELECT %s FROM %s WHERE `ID` IN (%s)
             ON DUPLICATE KEY UPDATE %s',
            implode(', ', $quoted),
            implode(', ', $quoted),
            $this->qtable('general_proyectos_procesos', $this->sourceSchema),
            $ids,
            implode(', ', $updates),
        );
        $this->exec($sql, $apply, 'sincronizar metadata de proyectos');
    }

    private function dropAutoProgramLogUniqueKey(bool $apply): void
    {
        if (!$this->indexExists('auto_program_log', 'uq_project_semana_consecutivo_accion')) {
            return;
        }

        $this->exec(
            'ALTER TABLE `auto_program_log` DROP INDEX `uq_project_semana_consecutivo_accion`',
            $apply,
            'quitar unicidad de auto_program_log',
        );
    }

    private function deleteGlobalRows(bool $apply): void
    {
        $ids = implode(',', array_keys(self::PROJECTS));
        $this->exec('SET FOREIGN_KEY_CHECKS=0', $apply, 'desactivar FKs para limpieza');
        foreach (self::DELETE_TABLES as $table) {
            if (!$this->targetTableExists($table) || !$this->columnExists($table, 'project_id')) {
                continue;
            }
            $this->exec("DELETE FROM `{$table}` WHERE `project_id` IN ({$ids})", $apply, "limpiar {$table}");
        }
        $this->exec('SET FOREIGN_KEY_CHECKS=1', $apply, 'reactivar FKs');
    }

    private function copyLegacySources(bool $apply): void
    {
        $this->exec('SET FOREIGN_KEY_CHECKS=0', $apply, 'desactivar FKs para copiar legacy');
        foreach (self::PROJECTS as $prefix) {
            foreach (self::LEGACY_TABLES as $table) {
                $legacy = "{$prefix}_{$table}";
                if (!$this->sourceTableExists($legacy)) {
                    continue;
                }

                $this->exec("DROP TABLE IF EXISTS `{$legacy}`", $apply, "recrear {$legacy}");
                $this->exec("CREATE TABLE `{$legacy}` LIKE {$this->qtable($legacy, $this->sourceSchema)}", $apply, "estructura {$legacy}");
                $this->exec("INSERT INTO `{$legacy}` SELECT * FROM {$this->qtable($legacy, $this->sourceSchema)}", $apply, "datos {$legacy}");
            }
        }
        $this->exec('SET FOREIGN_KEY_CHECKS=1', $apply, 'reactivar FKs tras copiar legacy');
    }

    private function migrateLegacyToGlobal(bool $apply): void
    {
        if (!$apply) {
            echo "DRY   migración legacy->global omitida en seco\n";
            return;
        }

        $migrator = new LegacyToGlobalMigrator($this->pdo, static function (string $line): void {
            echo "[legacy->global] {$line}\n";
        });

        foreach (self::PROJECTS as $projectId => $_prefix) {
            $migrator->run([
                'apply' => true,
                'strict' => true,
                'projectId' => $projectId,
            ]);
        }
    }

    private function remapAllProjects(bool $apply): void
    {
        foreach (self::PROJECTS as $projectId => $prefix) {
            if (!$this->targetTableExists("{$prefix}_programa") || $this->countRows("{$prefix}_programa") === 0) {
                echo "SKIP  {$prefix}: sin programa legacy; se conservan padres sintéticos.\n";
                continue;
            }

            $this->createProjectMaps($prefix);
            $pcMapped = $this->countRows('tmp_pc_row_map');
            $psMapped = $this->countRows('tmp_ps_row_map');
            $weekMapped = $this->countRows('tmp_week_ref_map');
            echo "INFO  {$prefix}: mapa pc={$pcMapped} ps={$psMapped} semana_ref={$weekMapped}\n";

            $this->exec(
                "UPDATE `programa_consolidado` dst
                 JOIN `tmp_pc_row_map` m ON m.row_id = dst.`Consecutivo`
                 SET dst.`Consecutivo_en_Programa` = m.new_ref, dst.`unique_id` = m.new_ref
                 WHERE dst.`project_id` = {$projectId}",
                $apply,
                "remap programa_consolidado {$prefix}",
            );
            $this->exec(
                "UPDATE `programacion_semanal` dst
                 JOIN `tmp_ps_row_map` m ON m.row_id = dst.`Consecutivo`
                 SET dst.`Consecutivo_En_Programa` = m.new_ref, dst.`unique_id` = m.new_ref
                 WHERE dst.`project_id` = {$projectId}",
                $apply,
                "remap programacion_semanal {$prefix}",
            );
            $this->remapAuxTable($projectId, 'pi_shared_constraint_links', 'ConsecutivoEnPrograma', 'Semana', $apply);
            $this->remapAuxTable($projectId, 'lps_drawer_comentarios', 'consecutivo_en_programa', 'semana', $apply);
            $this->remapAuxTable($projectId, 'lps_escalamientos', 'consecutivo_en_programa', 'semana', $apply);
            $this->remapAuxTable($projectId, 'pg_tracking', 'consecutivo_en_programa', 'semana', $apply);

            $this->exec(
                "DELETE dst FROM `programa` dst
                 LEFT JOIN `{$prefix}_programa` src ON src.`Consecutivo` = dst.`Consecutivo`
                 LEFT JOIN (
                    SELECT `unique_id` FROM `programa_consolidado` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    UNION
                    SELECT `unique_id` FROM `programacion_semanal` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    UNION
                    SELECT `unique_id` FROM `pi_shared_constraint_links` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    UNION
                    SELECT `unique_id` FROM `lps_drawer_comentarios` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    UNION
                    SELECT `unique_id` FROM `lps_escalamientos` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    UNION
                    SELECT `unique_id` FROM `pg_tracking` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                 ) refs ON refs.`unique_id` = dst.`unique_id`
                 WHERE dst.`project_id` = {$projectId}
                   AND src.`Consecutivo` IS NULL
                   AND refs.`unique_id` IS NULL",
                $apply,
                "eliminar padres sintéticos no referenciados {$prefix}",
            );
        }
    }

    private function createProjectMaps(string $prefix): void
    {
        $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_program_ref_candidates');
        $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_pc_row_map');
        $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_ps_row_map');
        $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_week_ref_map');
        $this->pdo->exec('CREATE TEMPORARY TABLE tmp_program_ref_candidates (source_kind varchar(2) NOT NULL, row_id int NOT NULL, semana int NOT NULL, old_ref int NOT NULL, new_ref int NOT NULL) ENGINE=Memory');
        $this->pdo->exec('CREATE TEMPORARY TABLE tmp_pc_row_map (row_id int NOT NULL PRIMARY KEY, new_ref int NOT NULL) ENGINE=Memory');
        $this->pdo->exec('CREATE TEMPORARY TABLE tmp_ps_row_map (row_id int NOT NULL PRIMARY KEY, new_ref int NOT NULL) ENGINE=Memory');
        $this->pdo->exec('CREATE TEMPORARY TABLE tmp_week_ref_map (semana int NOT NULL, old_ref int NOT NULL, new_ref int NOT NULL, PRIMARY KEY (semana, old_ref)) ENGINE=Memory');

        if ($this->targetTableExists("{$prefix}_programa_consolidado")) {
            $this->pdo->exec(
                "INSERT INTO tmp_program_ref_candidates (source_kind, row_id, semana, old_ref, new_ref)
                 SELECT 'pc', pc.`Consecutivo`, pc.`Semana`, pc.`Consecutivo_en_Programa`, p.`Consecutivo`
                 FROM `{$prefix}_programa_consolidado` pc
                 JOIN `{$prefix}_programa` p
                   ON p.`Id` <=> pc.`Id`
                  AND p.`Actividad` <=> pc.`Actividad`
                  AND (
                    (p.`Fecha_Inicio` <=> pc.`Fecha_Inicio` AND p.`Fecha_Fin` <=> pc.`Fecha_Fin`)
                    OR NOT EXISTS (
                        SELECT 1
                        FROM `{$prefix}_programa` p2
                        WHERE p2.`Id` <=> pc.`Id`
                          AND p2.`Actividad` <=> pc.`Actividad`
                          AND p2.`Fecha_Inicio` <=> pc.`Fecha_Inicio`
                          AND p2.`Fecha_Fin` <=> pc.`Fecha_Fin`
                    )
                  )
                 WHERE pc.`Consecutivo_en_Programa` IS NOT NULL"
            );
        }

        if ($this->targetTableExists("{$prefix}_programacion_semanal")) {
            $this->pdo->exec(
                "INSERT INTO tmp_program_ref_candidates (source_kind, row_id, semana, old_ref, new_ref)
                 SELECT 'ps', ps.`Consecutivo`, ps.`Semana`, ps.`Consecutivo_En_Programa`, p.`Consecutivo`
                 FROM `{$prefix}_programacion_semanal` ps
                 JOIN `{$prefix}_programa` p
                   ON p.`Id` <=> ps.`Id`
                  AND p.`Actividad` <=> ps.`Actividad`
                  AND (
                    (p.`Fecha_Inicio` <=> ps.`Fecha_Inicio` AND p.`Fecha_Fin` <=> ps.`Fecha_Fin`)
                    OR NOT EXISTS (
                        SELECT 1
                        FROM `{$prefix}_programa` p2
                        WHERE p2.`Id` <=> ps.`Id`
                          AND p2.`Actividad` <=> ps.`Actividad`
                          AND p2.`Fecha_Inicio` <=> ps.`Fecha_Inicio`
                          AND p2.`Fecha_Fin` <=> ps.`Fecha_Fin`
                    )
                  )
                 WHERE ps.`Consecutivo_En_Programa` IS NOT NULL"
            );
        }

        $conflicts = $this->pdo->query(
            'SELECT source_kind, row_id, COUNT(DISTINCT new_ref) AS variants
             FROM tmp_program_ref_candidates
             GROUP BY source_kind, row_id
             HAVING variants > 1
             LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);
        if ($conflicts !== []) {
            throw new RuntimeException('Mapa ambiguo de programa: ' . json_encode($conflicts, JSON_UNESCAPED_UNICODE));
        }

        $this->pdo->exec(
            "INSERT INTO tmp_pc_row_map (row_id, new_ref)
             SELECT row_id, MIN(new_ref)
             FROM tmp_program_ref_candidates
             WHERE source_kind = 'pc'
             GROUP BY row_id"
        );
        $this->pdo->exec(
            "INSERT INTO tmp_ps_row_map (row_id, new_ref)
             SELECT row_id, MIN(new_ref)
             FROM tmp_program_ref_candidates
             WHERE source_kind = 'ps'
             GROUP BY row_id"
        );

        $weekConflicts = $this->pdo->query(
            'SELECT semana, old_ref, COUNT(DISTINCT new_ref) AS variants
             FROM tmp_program_ref_candidates
             GROUP BY semana, old_ref
             HAVING variants > 1
             LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);
        if ($weekConflicts !== []) {
            throw new RuntimeException('Mapa ambiguo por semana: ' . json_encode($weekConflicts, JSON_UNESCAPED_UNICODE));
        }

        $this->pdo->exec(
            'INSERT INTO tmp_week_ref_map (semana, old_ref, new_ref)
             SELECT semana, old_ref, MIN(new_ref)
             FROM tmp_program_ref_candidates
             GROUP BY semana, old_ref'
        );
    }

    private function remapAuxTable(int $projectId, string $table, string $refColumn, string $weekColumn, bool $apply): void
    {
        if (
            !$this->targetTableExists($table)
            || !$this->columnExists($table, $refColumn)
            || !$this->columnExists($table, $weekColumn)
            || !$this->columnExists($table, 'unique_id')
        ) {
            return;
        }

        $this->exec(
            "UPDATE `{$table}` dst
             JOIN `tmp_week_ref_map` m
               ON m.old_ref = dst.`{$refColumn}`
              AND m.semana = dst.`{$weekColumn}`
             SET dst.`{$refColumn}` = m.new_ref, dst.`unique_id` = m.new_ref
             WHERE dst.`project_id` = {$projectId}",
            $apply,
            "remap {$table}",
        );
    }

    private function reloadAutoProgramLogs(bool $apply): void
    {
        foreach (self::PROJECTS as $projectId => $prefix) {
            if (!$this->targetTableExists("{$prefix}_auto_program_log")) {
                continue;
            }

            if ($this->targetTableExists("{$prefix}_programa") && $this->countRows("{$prefix}_programa") > 0) {
                $this->createProjectMaps($prefix);
            } else {
                $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_week_ref_map');
                $this->pdo->exec('CREATE TEMPORARY TABLE tmp_week_ref_map (semana int NOT NULL, old_ref int NOT NULL, new_ref int NOT NULL, PRIMARY KEY (semana, old_ref)) ENGINE=Memory');
            }

            $this->exec("DELETE FROM `auto_program_log` WHERE `project_id` = {$projectId}", $apply, "limpiar auto logs {$prefix}");
            $createdAt = $this->sourceColumnExpr("{$prefix}_auto_program_log", 'creado_en', 'CURRENT_TIMESTAMP');
            $category = $this->sourceColumnExpr("{$prefix}_auto_program_log", 'categoria_cnp', 'NULL');
            $cnp = $this->sourceColumnExpr("{$prefix}_auto_program_log", 'cnp', 'NULL');
            $order = $this->columnExists("{$prefix}_auto_program_log", 'id') ? ' ORDER BY src.`id`' : '';

            $sql = "INSERT INTO `auto_program_log`
                    (`project_id`, `semana`, `consecutivo`, `unique_id`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
                    SELECT {$projectId}, src.`semana`, src.`consecutivo`,
                           CASE
                             WHEN src.`consecutivo` <= 0 THEN NULL
                             WHEN m.new_ref IS NOT NULL THEN m.new_ref
                             WHEN p.`unique_id` IS NOT NULL THEN src.`consecutivo`
                             ELSE NULL
                           END,
                           src.`accion`, src.`detalle`, {$category}, {$cnp}, {$createdAt}
                    FROM `{$prefix}_auto_program_log` src
                    LEFT JOIN `tmp_week_ref_map` m ON m.old_ref = src.`consecutivo` AND m.semana = src.`semana`
                    LEFT JOIN `programa` p ON p.`project_id` = {$projectId} AND p.`unique_id` = src.`consecutivo`
                    {$order}";
            $this->exec($sql, $apply, "recargar auto logs {$prefix}");
        }
    }

    private function refreshProgramSequences(bool $apply): void
    {
        if (!$this->targetTableExists('program_unique_id_sequences')) {
            return;
        }

        $ids = implode(',', array_keys(self::PROJECTS));
        $this->exec("DELETE FROM `program_unique_id_sequences` WHERE `project_id` IN ({$ids})", $apply, 'limpiar secuencias');
        $this->exec(
            "INSERT INTO `program_unique_id_sequences` (`project_id`, `next_unique_id`)
             SELECT `project_id`, COALESCE(MAX(`unique_id`), 0) + 1
             FROM `programa`
             WHERE `project_id` IN ({$ids})
             GROUP BY `project_id`
             ON DUPLICATE KEY UPDATE `next_unique_id` = VALUES(`next_unique_id`)",
            $apply,
            'recrear secuencias',
        );
    }

    private function validateCounts(): void
    {
        $checked = 0;
        foreach (self::PROJECTS as $projectId => $prefix) {
            foreach (self::LEGACY_TABLES as $table) {
                $legacy = "{$prefix}_{$table}";
                if (!$this->targetTableExists($table) || !$this->targetTableExists($legacy)) {
                    continue;
                }

                $source = $this->countRows($legacy);
                $target = $this->countRowsByProject($table, $projectId);
                $expected = $this->expectedTargetCount($projectId, $prefix, $table, $source);
                if ($expected !== $target) {
                    throw new RuntimeException("Conteo distinto {$prefix}.{$table}: source={$source} expected={$expected} global={$target}");
                }
                $checked++;
            }
        }
        echo "OK    conteos validados={$checked}\n";
    }

    private function expectedTargetCount(int $projectId, string $prefix, string $table, int $source): int
    {
        if ($table === 'programa') {
            return $source + $this->countReferencedSyntheticProgramParents($projectId, $prefix);
        }

        if ($table !== 'subcontratistas' || !$this->targetTableExists("{$prefix}_cic")) {
            return $source;
        }

        $legacySub = "{$prefix}_subcontratistas";
        if (!$this->targetTableExists($legacySub)) {
            return $source;
        }

        $sql = "SELECT COUNT(*)
                FROM (
                    SELECT c.`subcontratista`
                    FROM `{$prefix}_cic` c
                    WHERE c.`subcontratista` IS NOT NULL
                      AND TRIM(c.`subcontratista`) <> ''
                      AND NOT EXISTS (
                          SELECT 1
                          FROM `{$legacySub}` s
                          WHERE s.`subcontratista` = c.`subcontratista`
                      )
                    GROUP BY c.`subcontratista`
                ) missing";

        return $source + (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function countReferencedSyntheticProgramParents(int $projectId, string $prefix): int
    {
        $legacyProgram = "{$prefix}_programa";
        if (!$this->targetTableExists($legacyProgram)) {
            return 0;
        }

        $sql = "SELECT COUNT(*)
                FROM (
                    SELECT refs.`unique_id`
                    FROM (
                        SELECT `unique_id` FROM `programa_consolidado` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                        UNION
                        SELECT `unique_id` FROM `programacion_semanal` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                        UNION
                        SELECT `unique_id` FROM `pi_shared_constraint_links` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                        UNION
                        SELECT `unique_id` FROM `lps_drawer_comentarios` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                        UNION
                        SELECT `unique_id` FROM `lps_escalamientos` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                        UNION
                        SELECT `unique_id` FROM `pg_tracking` WHERE `project_id` = {$projectId} AND `unique_id` IS NOT NULL
                    ) refs
                    LEFT JOIN `{$legacyProgram}` src ON src.`Consecutivo` = refs.`unique_id`
                    WHERE src.`Consecutivo` IS NULL
                    GROUP BY refs.`unique_id`
                ) missing";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function validateReferences(): void
    {
        $checks = [
            'programa_consolidado' => 'unique_id',
            'programacion_semanal' => 'unique_id',
            'pi_shared_constraint_links' => 'unique_id',
            'auto_program_log' => 'unique_id',
            'lps_drawer_comentarios' => 'unique_id',
            'lps_escalamientos' => 'unique_id',
            'pg_tracking' => 'unique_id',
        ];
        $ids = implode(',', array_keys(self::PROJECTS));
        foreach ($checks as $table => $column) {
            if (!$this->targetTableExists($table) || !$this->columnExists($table, $column)) {
                continue;
            }

            $sql = "SELECT COUNT(*)
                    FROM `{$table}` t
                    LEFT JOIN `programa` p
                      ON p.`project_id` = t.`project_id`
                     AND p.`unique_id` = t.`{$column}`
                    WHERE t.`project_id` IN ({$ids})
                      AND t.`{$column}` IS NOT NULL
                      AND p.`unique_id` IS NULL";
            $orphans = (int) $this->pdo->query($sql)->fetchColumn();
            if ($orphans > 0) {
                throw new RuntimeException("Referencias huérfanas en {$table}.{$column}: {$orphans}");
            }
        }
        echo "OK    referencias unique_id sin huérfanos\n";
    }

    private function archiveCopiedLegacyTables(bool $apply): void
    {
        $this->exec('SET FOREIGN_KEY_CHECKS=0', $apply, 'desactivar FKs para archivar legacy');
        foreach (self::PROJECTS as $prefix) {
            foreach (self::LEGACY_TABLES as $table) {
                $legacy = "{$prefix}_{$table}";
                if ($this->targetTableExists($legacy)) {
                    $archive = "zleg_{$legacy}";
                    if ($this->targetTableExists($archive)) {
                        $this->exec("DROP TABLE `{$archive}`", $apply, "reemplazar archivo {$archive}");
                    }
                    $this->exec("RENAME TABLE `{$legacy}` TO `{$archive}`", $apply, "archivar legacy {$legacy}");
                }
            }
        }
        $this->exec('SET FOREIGN_KEY_CHECKS=1', $apply, 'reactivar FKs tras archivar legacy');
    }

    private function dropSourceSchema(bool $apply): void
    {
        $this->exec("DROP DATABASE `{$this->sourceSchema}`", $apply, "borrar esquema fuente {$this->sourceSchema}");
    }

    private function sourceColumnExpr(string $table, string $column, string $fallback): string
    {
        return $this->columnExists($table, $column) ? "src.`{$column}`" : $fallback;
    }

    private function countRows(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    private function countRowsByProject(string $table, int $projectId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `project_id` = ?");
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    private function sourceTableExists(string $table): bool
    {
        return $this->tableExists($table, $this->sourceSchema);
    }

    private function targetTableExists(string $table): bool
    {
        return $this->tableExists($table, null);
    }

    private function tableExists(string $table, ?string $schema): bool
    {
        if (!$this->validIdentifier($table)) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$schema ?? $this->currentDatabase(), $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table, ?string $schema = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION'
        );
        $stmt->execute([$schema ?? $this->currentDatabase(), $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function qtable(string $table, ?string $schema = null): string
    {
        if (!$this->validIdentifier($table) || ($schema !== null && !$this->validIdentifier($schema))) {
            throw new RuntimeException('Identificador inválido.');
        }

        return $schema === null ? "`{$table}`" : "`{$schema}`.`{$table}`";
    }

    private function quote(string $column): string
    {
        if (!$this->validIdentifier($column)) {
            throw new RuntimeException("Columna inválida: {$column}");
        }
        return "`{$column}`";
    }

    private function currentDatabase(): string
    {
        return (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn();
    }

    private function validIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $identifier) === 1;
    }

    private function exec(string $sql, bool $apply, string $label): void
    {
        echo ($apply ? 'APPLY ' : 'DRY   ') . $label . "\n";
        if ($apply) {
            $this->pdo->exec($sql);
        }
    }
}

$options = getopt('', ['apply', 'source-schema:', 'drop-legacy', 'drop-source-schema']);
$apply = array_key_exists('apply', $options);
$sourceSchema = (string) ($options['source-schema'] ?? 'lps_repair_source');

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

(new ProdProjectGlobalSync($pdo, $sourceSchema))->run(
    $apply,
    array_key_exists('drop-legacy', $options),
    array_key_exists('drop-source-schema', $options),
);

echo ($apply ? 'Sincronización aplicada.' : 'Dry-run completado. Use --apply para ejecutar.') . PHP_EOL;
