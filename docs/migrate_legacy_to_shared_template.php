<?php

declare(strict_types=1);

/**
 * Template de migracion legacy -> shared schema.
 *
 * Uso sugerido:
 * php docs/migrate_legacy_to_shared_template.php --dry-run=1
 * php docs/migrate_legacy_to_shared_template.php --project=12
 */

require_once __DIR__ . '/../construccion/conexion.php';

function createPdoFromEnv(): PDO
{
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

final class LegacyToSharedMigrator
{
    private PDO $pdo;
    private bool $dryRun;
    private ?int $onlyProjectId;

    public function __construct(PDO $pdo, bool $dryRun = true, ?int $onlyProjectId = null)
    {
        $this->pdo = $pdo;
        $this->dryRun = $dryRun;
        $this->onlyProjectId = $onlyProjectId;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function run(): void
    {
        $projects = $this->fetchProjects();

        foreach ($projects as $project) {
            $projectId = (int) $project['id'];
            $prefix = (string) $project['code'];

            printf("\n== Proyecto %d (%s) ==\n", $projectId, $prefix);

            try {
                if (!$this->dryRun) {
                    $this->pdo->beginTransaction();
                }

                $this->migrateTable($projectId, $prefix, 'programa', 'project_programa', [
                    'Consecutivo' => 'consecutivo_legacy',
                    'ID' => 'ext_id',
                    'Actividad' => 'actividad',
                    'Titulo' => 'titulo',
                    'Fecha_Inicio' => 'fecha_inicio',
                    'Fecha_Fin' => 'fecha_fin',
                    'Ruta_Critica' => 'ruta_critica',
                    'Ejecutado' => 'ejecutado',
                    'Estado' => 'estado',
                    'Semanas_Inicio' => 'semanas_inicio',
                    'Estado_Restricciones' => 'estado_restricciones',
                    'D_y_E' => 'd_y_e',
                    'Materiales' => 'materiales',
                    'MdeO' => 'mdeo',
                    'Equipos' => 'equipos',
                    'Predecesora' => 'predecesora',
                    'Pdto_Cons' => 'pdto_cons',
                    'Modelo' => 'modelo',
                    'Responsable_AIA' => 'responsable_aia',
                    'Observaciones' => 'observaciones',
                    'Ult_Act_Est' => 'ult_act_est',
                    'Ult_Act_Restr' => 'ult_act_restr',
                ]);

                $this->migrateTable($projectId, $prefix, 'programa_consolidado', 'project_programa_consolidado', [
                    'Semana' => 'semana',
                    'Consecutivo_en_Programa' => 'consecutivo_en_programa',
                    'ID' => 'ext_id',
                    'Actividad' => 'actividad',
                    'Titulo' => 'titulo',
                    'Fecha_Inicio' => 'fecha_inicio',
                    'Fecha_Fin' => 'fecha_fin',
                    'Ruta_Critica' => 'ruta_critica',
                    'Ejecutado' => 'ejecutado',
                    'Estado' => 'estado',
                    'Semanas_Inicio' => 'semanas_inicio',
                    'Estado_Restricciones' => 'estado_restricciones',
                    'D_y_E' => 'd_y_e',
                    'Materiales' => 'materiales',
                    'MdeO' => 'mdeo',
                    'Equipos' => 'equipos',
                    'Predecesora' => 'predecesora',
                    'Pdto_Cons' => 'pdto_cons',
                    'Modelo' => 'modelo',
                    'Sub_Contratista' => 'sub_contratista',
                    'Responsable_AIA' => 'responsable_aia',
                    'Observaciones' => 'observaciones',
                    'Activa' => 'activa',
                    'Ejecutado_Siguiente_Semana' => 'ejecutado_siguiente_semana',
                    'Codigo_Actividad' => 'codigo_actividad',
                    'Medir_Productividad' => 'medir_productividad',
                    'Cantidad_Ppto' => 'cantidad_ppto',
                    'Unidad' => 'unidad',
                    'Programa_Anterior_Asociar' => 'programa_anterior_asociar',
                ]);

                $this->migrateTable($projectId, $prefix, 'programacion_semanal', 'project_programacion_semanal', [
                    'Semana' => 'semana',
                    'Consecutivo_en_Programa' => 'consecutivo_en_programa',
                    'ID' => 'ext_id',
                    'Actividad' => 'actividad',
                    'Descripcion' => 'descripcion',
                    'Ubicacion' => 'ubicacion',
                    'Fecha_Inicio' => 'fecha_inicio',
                    'Fecha_Fin' => 'fecha_fin',
                    'Sub_Contratista' => 'sub_contratista',
                    'Responsable_AIA' => 'responsable_aia',
                    'Empresa' => 'empresa',
                    'Ejecutado' => 'ejecutado',
                    'Medir_Productividad' => 'medir_productividad',
                    'Unidad' => 'unidad',
                    'Cantidad_Ppto' => 'cantidad_ppto',
                    'Cantidad_Sugerida' => 'cantidad_sugerida',
                    'Compromiso' => 'compromiso',
                    'Ejecutado_Real' => 'ejecutado_real',
                    'P_Completado' => 'p_completado',
                    'PAC' => 'pac',
                    'Critica' => 'critica',
                    'Atrasada' => 'atrasada',
                    'Activa' => 'activa',
                    'Prog_Sin_Restricciones_100' => 'prog_sin_restricciones_100',
                    'Categoria_CNP' => 'categoria_cnp',
                    'CNP' => 'cnp',
                    'Observaciones_CNP' => 'observaciones_cnp',
                    'Categoria_CNC' => 'categoria_cnc',
                    'CNC' => 'cnc',
                    'Observaciones_CNC' => 'observaciones_cnc',
                    'Rendimientos' => 'rendimientos',
                    'Codigo_Actividad' => 'codigo_actividad',
                ]);

                $this->migrateTable($projectId, $prefix, 'semanas_activas', 'project_semanas_activas', [
                    'Semana' => 'semana',
                    'Fecha_Inicio_Sem' => 'fecha_inicio_sem',
                    'Fecha_Fin_Sem' => 'fecha_fin_sem',
                    'Semanal_Confirmada' => 'semanal_confirmada',
                    'Fecha_Cierre_Compromisos' => 'fecha_cierre_compromisos',
                    'Fecha_Creacion_Semana' => 'fecha_creacion_semana',
                    'Reprogramacion' => 'reprogramacion',
                    'Diferencia_Estructura_Cron' => 'diferencia_estructura_cron',
                ]);

                $this->migrateTable($projectId, $prefix, 'subcontratistas', 'project_subcontratistas', [
                    'subcontratista' => 'subcontratista',
                    'correo_contacto' => 'correo_contacto',
                    'NIT' => 'nit',
                    'alcance' => 'alcance',
                    'tipo_proveedor' => 'tipo_proveedor',
                ]);

                $this->migrateTable($projectId, $prefix, 'profesionales', 'project_profesionales', [
                    'nombre' => 'nombre',
                    'email' => 'email',
                    'cargo' => 'cargo',
                ]);

                $this->migrateTable($projectId, $prefix, 'cic', 'project_cic', [
                    'Semana' => 'semana',
                    'subcontratista' => 'subcontratista',
                    'correo_contacto' => 'correo_contacto',
                    'NIT' => 'nit',
                    'alcance' => 'alcance',
                    'tipo_proveedor' => 'tipo_proveedor',
                    'PAC' => 'pac',
                    'PAC_Acum' => 'pac_acum',
                    'P_Completado' => 'p_completado',
                    'P_Completado_Acum' => 'p_completado_acum',
                    'Calidad' => 'calidad',
                    'Calidad_Acum' => 'calidad_acum',
                    'GSA' => 'gsa',
                    'GSA_Acum' => 'gsa_acum',
                    'SST' => 'sst',
                    'SST_Acum' => 'sst_acum',
                    'ADM' => 'adm',
                    'ADM_Acum' => 'adm_acum',
                    'Cal_Integral' => 'cal_integral',
                    'Cal_Integral_Acum' => 'cal_integral_acum',
                    'Observaciones' => 'observaciones',
                ]);

                $this->migrateTable($projectId, $prefix, 'cip', 'project_cip', [
                    'Semana' => 'semana',
                    'profesional' => 'profesional',
                    'correo_contacto' => 'correo_contacto',
                    'PAC' => 'pac',
                    'PAC_Acum' => 'pac_acum',
                    'P_Completado' => 'p_completado',
                    'P_Completado_Acum' => 'p_completado_acum',
                    'Act_Criticas_Cumplidas' => 'act_criticas_cumplidas',
                    'Act_No_Criticas_Cumplidas' => 'act_no_criticas_cumplidas',
                    'Act_Atrasadas_Cumplidas' => 'act_atrasadas_cumplidas',
                    'Act_Criticas_Cumplidas_Acum' => 'act_criticas_cumplidas_acum',
                    'Act_No_Criticas_Cumplidas_Acum' => 'act_no_criticas_cumplidas_acum',
                    'Act_Atrasadas_Cumplidas_Acum' => 'act_atrasadas_cumplidas_acum',
                    'PAC_Consolidado' => 'pac_consolidado',
                    'PAC_Consolidado_Acum' => 'pac_consolidado_acum',
                    'Observaciones' => 'observaciones',
                ]);

                $this->migrateTable($projectId, $prefix, 'pdc', 'project_pdc', [
                    'Semana' => 'semana',
                    'Titulo' => 'titulo',
                    'Tipo_Paquete' => 'tipo_paquete',
                    'Paquete_Contratacion' => 'paquete_contratacion',
                    'Contratos' => 'contratos',
                    'Numero_Subcontratos' => 'numero_subcontratos',
                    'Subcontrato_Paquete' => 'subcontrato_paquete',
                    'Estado' => 'estado',
                    'Fecha_Inicio' => 'fecha_inicio',
                    'Fecha_Inicio_Proyectada' => 'fecha_inicio_proyectada',
                    'Fecha_Real_Inicio' => 'fecha_real_inicio',
                    'ID_Proveedor_Adjudicado' => 'id_proveedor_adjudicado',
                    'Numero_Contrato' => 'numero_contrato',
                    'Aplica_Polizas' => 'aplica_polizas',
                    'Fecha_Vencimiento_Polizas' => 'fecha_vencimiento_polizas',
                    'Valor_Presupuesto' => 'valor_presupuesto',
                    'Valor_Primera_Negociacion' => 'valor_primera_negociacion',
                    'Valor_Adjudicado' => 'valor_adjudicado',
                    'Valor_Anticipo' => 'valor_anticipo',
                    'Valor_Reclamado' => 'valor_reclamado',
                    'Valor_Devoluciones' => 'valor_devoluciones',
                    'Observaciones_Contrato' => 'observaciones_contrato',
                ]);

                $this->migrateTable($projectId, $prefix, 'indicadores_generales', 'project_indicadores_generales', [
                    'Semana' => 'semana',
                    'subcontratista_profesional' => 'subcontratista_profesional',
                    'rol' => 'rol',
                    'PAC' => 'pac',
                    'PAC_Acum' => 'pac_acum',
                    'P_Completado' => 'p_completado',
                    'P_Completado_Acum' => 'p_completado_acum',
                    'Comp' => 'comp',
                    'Comp_Acum' => 'comp_acum',
                    'Porcentaje_Cantidades_Comp' => 'porcentaje_cantidades_comp',
                    'Porcentaje_Cantidades_Comp_Acum' => 'porcentaje_cantidades_comp_acum',
                    'Criticas_Comp' => 'criticas_comp',
                    'Criticas_Comp_Acum' => 'criticas_comp_acum',
                    'No_Criticas_Comp' => 'no_criticas_comp',
                    'No_Criticas_Comp_Acum' => 'no_criticas_comp_acum',
                    'Atrasadas_Criticas_Comp' => 'atrasadas_criticas_comp',
                    'Atrasadas_Criticas_Comp_Acum' => 'atrasadas_criticas_comp_acum',
                    'Atrasadas_No_Criticas_Comp' => 'atrasadas_no_criticas_comp',
                    'Atrasadas_No_Criticas_Comp_Acum' => 'atrasadas_no_criticas_comp_acum',
                    'Comp_Sin_Rest_100' => 'comp_sin_rest_100',
                    'Comp_Sin_Rest_100_Acum' => 'comp_sin_rest_100_acum',
                ]);

                if (!$this->dryRun) {
                    $this->pdo->commit();
                }

                echo "OK\n";
            } catch (Throwable $e) {
                if (!$this->dryRun && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                printf("ERROR: %s\n", $e->getMessage());
            }
        }
    }

    private function fetchProjects(): array
    {
        $sql = 'SELECT ID AS id, Base_de_Datos AS code FROM general_proyectos_procesos WHERE Activo = 1';
        $params = [];

        if ($this->onlyProjectId !== null) {
            $sql .= ' AND ID = ?';
            $params[] = $this->onlyProjectId;
        }

        $sql .= ' ORDER BY ID';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function migrateTable(int $projectId, string $prefix, string $legacySuffix, string $targetTable, array $map): void
    {
        $legacyTable = sprintf('%s_%s', $prefix, $legacySuffix);

        if (!$this->tableExists($legacyTable)) {
            printf("  - skip %s (no existe)\n", $legacyTable);
            return;
        }

        $legacyColumns = array_keys($map);
        $targetColumns = array_values($map);
        $targetColumnsWithProject = array_merge(['project_id'], $targetColumns);

        $selectCols = implode(', ', array_map(static fn ($c) => "`$c`", $legacyColumns));
        $insertCols = implode(', ', array_map(static fn ($c) => "`$c`", $targetColumnsWithProject));
        $selectAliases = implode(', ', array_map(
            static fn ($legacy, $target) => sprintf('`%s` AS `%s`', $legacy, $target),
            $legacyColumns,
            $targetColumns
        ));

        $sql = sprintf(
            'INSERT INTO `%s` (%s) SELECT ? AS `project_id`, %s FROM `%s`',
            $targetTable,
            $insertCols,
            $selectAliases,
            $legacyTable
        );

        if ($this->dryRun) {
            printf("  - dry-run %s -> %s\n", $legacyTable, $targetTable);
            return;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId]);
        printf("  - migrated %s -> %s (%d rows)\n", $legacyTable, $targetTable, $stmt->rowCount());
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }
}

$dryRun = (($_GET['dry-run'] ?? null) === '1') || in_array('--dry-run=1', $argv ?? [], true);
$projectArg = null;

foreach (($argv ?? []) as $arg) {
    if (str_starts_with($arg, '--project=')) {
        $projectArg = (int) substr($arg, strlen('--project='));
    }
}

$migrator = new LegacyToSharedMigrator(createPdoFromEnv(), $dryRun, $projectArg);
$migrator->run();
