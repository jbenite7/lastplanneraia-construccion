<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

function table_exists(Database $db, string $table): bool
{
    return (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
        [$table],
    )->fetchColumn() > 0;
}

function json_or_null($value): ?string
{
    if ($value === null) {
        return null;
    }

    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function norm_conf($value): float
{
    $confidence = (float) ($value ?? 0);
    if ($confidence > 0 && $confidence <= 1) {
        $confidence *= 100;
    }

    return max(0.0, min(100.0, round($confidence, 2)));
}

function band(float $confidence): string
{
    if ($confidence >= 80) {
        return 'high';
    }
    if ($confidence >= 50) {
        return 'medium';
    }

    return 'low';
}

function insert_run(Database $db, bool $apply, string $runId, int $projectId, string $module, int $semana, string $status, array $metadata): int
{
    if (!$apply) {
        return 1;
    }

    $db->query(
        "INSERT IGNORE INTO semi_auto_runs
         (run_id, project_id, module, semana, status, requested_by, metadata, total_suggestions, applied_count)
         VALUES (?, ?, ?, ?, ?, 'backfill', ?, 0, 0)",
        [$runId, $projectId, $module, $semana, $status, json_or_null($metadata)],
    );

    return 1;
}

$summary = [
    'runs' => 0,
    'suggestions' => 0,
    'decisions' => 0,
    'configs' => 0,
    'feedback' => 0,
];

if (!table_exists($db, 'semi_auto_runs')) {
    fwrite(STDERR, "Falta ejecutar 20260702_semi_auto_global_tables.sql\n");
    exit(1);
}

if (table_exists($db, 'auto_contrato_log')) {
    $batches = $db->query(
        "SELECT project_id, batch_id, MIN(semana) AS semana, COUNT(*) AS total
         FROM auto_contrato_log
         WHERE project_id IS NOT NULL AND batch_id IS NOT NULL AND batch_id != ''
         GROUP BY project_id, batch_id",
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($batches as $batch) {
        $runId = 'legacy_' . preg_replace('/[^A-Za-z0-9_]+/', '_', (string) $batch['batch_id']);
        $projectId = (int) $batch['project_id'];
        $semana = (int) $batch['semana'];
        $summary['runs'] += insert_run($db, $apply, $runId, $projectId, 'contratos', $semana, 'applied', [
            'source' => 'auto_contrato_log',
            'legacy_batch_id' => $batch['batch_id'],
            'legacy_total' => (int) $batch['total'],
        ]);

        $rows = $db->query(
            "SELECT * FROM auto_contrato_log WHERE project_id = ? AND batch_id = ? ORDER BY id ASC",
            [$projectId, $batch['batch_id']],
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $suggestionId = 'legacy_auto_contrato_' . (int) $row['id'];
            $confidence = norm_conf($row['confianza'] ?? 0);
            if ($apply) {
                $db->query(
                    "INSERT IGNORE INTO semi_auto_suggestions
                     (suggestion_id, run_id, project_id, module, target_table, target_pk, action, status,
                      confidence, confidence_band, title, reason, match_source, preselected,
                      proposed_payload, apply_payload)
                     VALUES (?, ?, ?, 'contratos', 'actividades', ?, 'update_contracts', 'applied',
                      ?, ?, 'Backfill contratos', 'Historial migrado desde auto_contrato_log',
                      'legacy_auto_contrato_log', 1, ?, ?)",
                    [
                        $suggestionId,
                        $runId,
                        $projectId,
                        (string) $row['Id_actividad'],
                        $confidence,
                        band($confidence),
                        json_or_null([
                            'tipoContrato' => $row['tipo_contrato'],
                            'paquetes' => json_decode((string) ($row['paquetes'] ?? '[]'), true) ?: [],
                            'fechaInicioProyectada' => $row['fecha_inicio_proyectada'],
                        ]),
                        json_or_null(['legacy_id' => (int) $row['id']]),
                    ],
                );
                $db->query(
                    "INSERT IGNORE INTO semi_auto_decisions
                     (decision_id, run_id, suggestion_id, project_id, module, decision, after_payload, result_payload, decided_by)
                     VALUES (?, ?, ?, ?, 'contratos', ?, ?, ?, ?)",
                    [
                        'legacy_auto_contrato_decision_' . (int) $row['id'],
                        $runId,
                        $suggestionId,
                        $projectId,
                        $row['accion'] === 'deshacer' ? 'undo' : 'apply',
                        json_or_null([
                            'tipoContrato' => $row['tipo_contrato'],
                            'paquetes' => json_decode((string) ($row['paquetes'] ?? '[]'), true) ?: [],
                        ]),
                        json_or_null(['table' => 'actividades', 'Id' => (int) $row['Id_actividad']]),
                        $row['usuario'] ?: 'backfill',
                    ],
                );
            }
            $summary['suggestions']++;
            $summary['decisions']++;
        }
    }
}

if (table_exists($db, 'general_pdc_project_family_strategy')) {
    $strategies = $db->query(
        "SELECT COALESCE(project_id, p.Id) AS project_id, s.semana, COUNT(*) AS total
         FROM general_pdc_project_family_strategy s
         LEFT JOIN general_proyectos_procesos p ON p.Base_de_Datos = s.db_prefix
         WHERE COALESCE(project_id, p.Id) IS NOT NULL
         GROUP BY COALESCE(project_id, p.Id), s.semana",
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($strategies as $strategy) {
        $projectId = (int) $strategy['project_id'];
        $semana = (int) $strategy['semana'];
        $runId = "legacy_strategy_{$projectId}_{$semana}";
        $summary['runs'] += insert_run($db, $apply, $runId, $projectId, 'pdc', $semana, 'applied', [
            'source' => 'general_pdc_project_family_strategy',
            'strategy_count' => (int) $strategy['total'],
        ]);
    }
}

$projects = $db->query(
    "SELECT Id FROM general_proyectos_procesos WHERE Activo = 1 ORDER BY Id ASC",
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($projects as $projectIdRaw) {
    $projectId = (int) $projectIdRaw;
    $semana = (int) $db->query(
        "SELECT COALESCE(MAX(Semana), 0) FROM semanas_activas WHERE project_id = ?",
        [$projectId],
    )->fetchColumn();
    if ($semana <= 0) {
        continue;
    }

    $contractConfigured = (int) $db->query(
        "SELECT COUNT(*) FROM actividades
         WHERE project_id = ? AND semanaActualizacion = ?
           AND tipoContrato IS NOT NULL AND tipoContrato != ''",
        [$projectId, $semana],
    )->fetchColumn();
    $pdcRows = (int) $db->query(
        "SELECT COUNT(*) FROM pdc WHERE project_id = ? AND semana = ? AND titulo = 0",
        [$projectId, $semana],
    )->fetchColumn();

    $summary['runs'] += insert_run($db, $apply, "baseline_contratos_{$projectId}_{$semana}", $projectId, 'contratos', $semana, 'baseline', [
        'source' => 'current_state',
        'configured_contract_activities' => $contractConfigured,
    ]);
    $summary['runs'] += insert_run($db, $apply, "baseline_pdc_{$projectId}_{$semana}", $projectId, 'pdc', $semana, 'baseline', [
        'source' => 'current_state',
        'pdc_rows' => $pdcRows,
    ]);

    foreach (['listado-actividades', 'contratos', 'pdc'] as $module) {
        if ($apply) {
            $db->query(
                "INSERT IGNORE INTO semi_auto_project_config
                 (project_id, module, high_threshold, medium_threshold, learning_scope, updated_by)
                 VALUES (?, ?, 80, 50, 'project', 'backfill')",
                [$projectId, $module],
            );
        }
        $summary['configs']++;
    }
}

echo ($apply ? 'APPLY' : 'DRY-RUN') . " semi-auto backfill\n";
foreach ($summary as $key => $value) {
    echo "{$key}: {$value}\n";
}
