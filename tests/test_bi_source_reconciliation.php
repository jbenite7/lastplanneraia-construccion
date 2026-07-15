<?php

/**
 * Reconciles the historical BI view contract against its global sources.
 * All database assertions are read-only SELECT statements.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$db = \Database::getInstance();
$passed = 0;
$failed = 0;

function biReconciliationPass(string $message): void
{
    global $passed;
    echo "  PASS: {$message}\n";
    $passed++;
}

function biReconciliationFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function biAssertZero(\Database $db, string $sql, string $message): void
{
    $count = (int) $db->query($sql)->fetchColumn();
    $count === 0
        ? biReconciliationPass($message)
        : biReconciliationFail("{$message} ({$count} discrepancies)");
}

echo "=== BI Historical Source Reconciliation ===\n\n";

$requiredViewFiles = [
    'database/bi/002_bi_pi_restricciones.sql' => 'bi_pi_restricciones',
    'database/bi/004_bi_pdc_general.sql' => 'bi_pdc_general',
    'database/bi/005_bi_cic_contratistas.sql' => 'bi_cic_contratistas',
    'database/bi/006_bi_cip_responsables.sql' => 'bi_cip_responsables',
];

foreach ($requiredViewFiles as $relativePath => $viewName) {
    $sql = file_get_contents(dirname(__DIR__) . '/' . $relativePath);
    if ($sql === false || !preg_match('/CREATE\s+OR\s+REPLACE\s+VIEW\s+`' . preg_quote($viewName, '/') . '`/i', $sql)) {
        biReconciliationFail("{$relativePath} installs {$viewName}");
        continue;
    }

    biReconciliationPass("{$relativePath} installs {$viewName}");
}

$cipSql = file_get_contents(dirname(__DIR__) . '/database/bi/006_bi_cip_responsables.sql');
$cipPopulationContract = $cipSql !== false
    && !preg_match('/Activa\s*=\s*[\'\"]Si[\'\"]/i', $cipSql)
    && preg_match('/Activa\s+IN\s*\(\s*[\'\"]1[\'\"]\s*,\s*[\'\"]NA[\'\"]\s*\)/i', $cipSql);
if (!$cipPopulationContract) {
    biReconciliationFail("bi_cip_responsables counts the real Activa IN ('1','NA') commitment population");
} else {
    biReconciliationPass("bi_cip_responsables counts the real Activa IN ('1','NA') commitment population");
}

$weeklySql = file_get_contents(dirname(__DIR__) . '/database/bi/003_bi_ps_compromisos.sql');
if ($weeklySql === false || !preg_match('/NULL\s+AS\s+pac_expected_baseline/i', $weeklySql)) {
    biReconciliationFail('PAC baseline remains unknown instead of fabricating a probability');
} else {
    biReconciliationPass('PAC baseline remains unknown instead of fabricating a probability');
}

$weeklyPopulationContract = $weeklySql !== false
    && !preg_match('/WHERE\s+ps\.Activa\s*=\s*[\'\"]Si[\'\"]/i', $weeklySql)
    && preg_match('/AS\s+is_cnp_population/i', $weeklySql)
    && preg_match('/AS\s+is_cnc_population/i', $weeklySql)
    && preg_match('/AS\s+is_commitment_population/i', $weeklySql);
if (
    !$weeklyPopulationContract
) {
    biReconciliationFail('bi_ps_compromisos declares the real CNP, CNC and commitment populations without Activa=Si');
} else {
    biReconciliationPass('bi_ps_compromisos declares the real CNP, CNC and commitment populations without Activa=Si');
}

$curveSql = file_get_contents(dirname(__DIR__) . '/database/bi/007_bi_curva_s_duracion.sql');
if ($curveSql === false || stripos($curveSql, 'avg_recent_progress_4w') !== false) {
    biReconciliationFail('Curva S omits an unreliable global recent-progress average');
} else {
    biReconciliationPass('Curva S omits an unreliable global recent-progress average');
}

$clampedProgress = 'LEAST(1.0, GREATEST(0.0, COALESCE(pg.Ejecutado, 0)))';
if ($curveSql === false || substr_count($curveSql, $clampedProgress) !== 3) {
    biReconciliationFail('Curva S clamps every weighted real-progress calculation to [0,1]');
} else {
    biReconciliationPass('Curva S clamps every weighted real-progress calculation to [0,1]');
}

biAssertZero(
    $db,
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, unique_id
        FROM bi_pg_semana
        GROUP BY project_id, Semana, unique_id
        HAVING COUNT(*) > 1
    ) AS duplicate_grains",
    'bi_pg_semana is unique at project_id + Semana + unique_id',
);

biAssertZero(
    $db,
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, Consecutivo_en_Programa AS unique_id,
               1 AS source_row, 0 AS view_row
        FROM programa_consolidado
        WHERE COALESCE(Titulo, 0) = 0
        UNION ALL
        SELECT project_id, Semana, unique_id, 0, 1
        FROM bi_pg_semana
    ) AS grains
    GROUP BY project_id, Semana, unique_id
    HAVING SUM(source_row) <> 1 OR SUM(view_row) <> 1",
    'bi_pg_semana matches programa_consolidado activity grain',
);

biAssertZero(
    $db,
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, Consecutivo AS row_id, 1 AS source_row, 0 AS view_row
        FROM programacion_semanal WHERE Activa IN ('0', '1', 'NA')
        UNION ALL
        SELECT project_id, Semana, row_id, 0, 1 FROM bi_ps_compromisos
    ) AS grains
    GROUP BY project_id, Semana, row_id
    HAVING SUM(source_row) <> 1 OR SUM(view_row) <> 1",
    'bi_ps_compromisos matches the CNP, CNC and commitment source grain',
);

if ($weeklyPopulationContract) {
    biAssertZero(
        $db,
        "SELECT COUNT(*)
         FROM programacion_semanal ps
         LEFT JOIN bi_ps_compromisos bi
           ON bi.project_id = ps.project_id
          AND bi.Semana = ps.Semana
          AND bi.row_id = ps.Consecutivo
         WHERE ps.Activa IN ('0', '1', 'NA')
           AND (
               COALESCE(bi.is_cnp_population, -1) <> CASE WHEN ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> '' THEN 1 ELSE 0 END
            OR COALESCE(bi.is_cnc_population, -1) <> CASE WHEN ps.Activa IN ('1', 'NA') AND COALESCE(TRIM(ps.CNC), '') <> '' THEN 1 ELSE 0 END
            OR COALESCE(bi.is_commitment_population, -1) <> CASE WHEN ps.Activa IN ('1', 'NA') THEN 1 ELSE 0 END
           )",
        'bi_ps_compromisos flags each real CNP, CNC and commitment population explicitly',
    );

    $jmcCnp = (int) $db->query(
        "SELECT COUNT(*) FROM bi_ps_compromisos
         WHERE project_id = 68 AND Semana = 6 AND is_cnp_population = 1",
    )->fetchColumn();
    $jmcCnc = (int) $db->query(
        "SELECT COUNT(*) FROM bi_ps_compromisos
         WHERE project_id = 68 AND Semana = 6 AND is_cnc_population = 1",
    )->fetchColumn();
    if ($jmcCnp === 33 && $jmcCnc === 0) {
        biReconciliationPass('JMC week 6 exposes 33 real CNP rows and no invented CNC rows');
    } else {
        biReconciliationFail("JMC week 6 population contract mismatch (CNP {$jmcCnp}, CNC {$jmcCnc})");
    }
}

biAssertZero(
    $db,
    "SELECT COUNT(*)
     FROM programa_consolidado pc
     INNER JOIN bi_pg_semana pg
        ON pg.project_id = pc.project_id
       AND pg.Semana = pc.Semana
       AND pg.unique_id = pc.Consecutivo_en_Programa
     WHERE COALESCE(pc.Titulo, 0) = 0
       AND pc.Fecha_Inicio IS NOT NULL
       AND pc.Fecha_Inicio = pc.Fecha_Fin
       AND pg.duration_days <> 1",
    'one-day activities have inclusive duration_days = 1',
);

$historicalSqlFiles = [
    'database/bi/001_bi_pg_semana.sql',
    'database/bi/003_bi_ps_compromisos.sql',
    'database/bi/007_bi_curva_s_duracion.sql',
    'database/bi/008_bi_riesgos.sql',
    'database/bi/009_bi_control_tower_summary.sql',
    'database/bi/010_bi_lineage.sql',
];

foreach ($historicalSqlFiles as $relativePath) {
    $sql = file_get_contents(dirname(__DIR__) . '/' . $relativePath);
    if ($sql === false || stripos($sql, 'CURDATE(') !== false) {
        biReconciliationFail("{$relativePath} has a dynamic CURDATE historical cutoff");
        continue;
    }
    biReconciliationPass("{$relativePath} has no CURDATE historical cutoff");
}

$lineageSql = file_get_contents(dirname(__DIR__) . '/database/bi/010_bi_lineage.sql');
if ($lineageSql === false || stripos($lineageSql, 'NOW(') !== false) {
    biReconciliationFail('bi_lineage keeps static metadata instead of dynamic NOW() timestamps');
} else {
    biReconciliationPass('bi_lineage keeps static metadata instead of dynamic NOW() timestamps');
}

echo "\n---\nResult: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
