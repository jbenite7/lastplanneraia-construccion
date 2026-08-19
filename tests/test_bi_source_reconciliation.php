<?php

/**
 * Reconciles the historical BI view contract against deterministic fixture data.
 *
 * Database assertions run only against the sacrificial fixture projects: the contract under
 * test is the VIEW LOGIC (grain, populations, duration math), not the health of the shared
 * dev database. Reconciling against every live project made the test fail whenever another
 * session restored dirty data (e.g. rows with a NULL Consecutivo_en_Programa in projects
 * 62/65/68, measured 2026-08-19) — failures unrelated to the SQL contracts this test guards.
 * The fixture writes happen inside a transaction that rolls back on shutdown.
 */
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

$db = \Database::getInstance();
BiContractFixture::seedCausalRows($db);
BiContractFixture::seedProgramSnapshots($db);
$fixtureProjectsSql = BiContractFixture::PROYECTO_A . ', ' . BiContractFixture::PROYECTO_B;
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

// Guardia anti-vacuidad: si el fixture dejara de sembrar, las reconciliaciones de abajo
// pasarían en verde sobre cero filas sin medir nada.
$fixtureRowCount = (int) $db->query(
    "SELECT COUNT(*) FROM bi_pg_semana WHERE project_id IN ({$fixtureProjectsSql})",
)->fetchColumn();
$fixtureRowCount > 0
    ? biReconciliationPass('fixture projects expose rows through bi_pg_semana')
    : biReconciliationFail('fixture projects expose rows through bi_pg_semana');

biAssertZero(
    $db,
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, unique_id
        FROM bi_pg_semana
        WHERE project_id IN ({$fixtureProjectsSql})
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
          AND project_id IN ({$fixtureProjectsSql})
        UNION ALL
        SELECT project_id, Semana, unique_id, 0, 1
        FROM bi_pg_semana
        WHERE project_id IN ({$fixtureProjectsSql})
    ) AS grains
    GROUP BY project_id, Semana, unique_id
    HAVING SUM(source_row) <> 1 OR SUM(view_row) <> 1",
    'bi_pg_semana matches programa_consolidado activity grain',
);

biAssertZero(
    $db,
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, Consecutivo AS row_id, 1 AS source_row, 0 AS view_row
        FROM programacion_semanal
        WHERE Activa IN ('0', '1', 'NA') AND project_id IN ({$fixtureProjectsSql})
        UNION ALL
        SELECT project_id, Semana, row_id, 0, 1 FROM bi_ps_compromisos
        WHERE project_id IN ({$fixtureProjectsSql})
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
           AND ps.project_id IN ({$fixtureProjectsSql})
           AND (
               COALESCE(bi.is_cnp_population, -1) <> CASE WHEN ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> '' THEN 1 ELSE 0 END
            OR COALESCE(bi.is_cnc_population, -1) <> CASE WHEN ps.Activa IN ('1', 'NA') AND COALESCE(TRIM(ps.CNC), '') <> '' THEN 1 ELSE 0 END
            OR COALESCE(bi.is_commitment_population, -1) <> CASE WHEN ps.Activa IN ('1', 'NA') THEN 1 ELSE 0 END
           )",
        'bi_ps_compromisos flags each real CNP, CNC and commitment population explicitly',
    );

    biAssertZero(
        $db,
        "SELECT COUNT(*) FROM (
            SELECT project_id, Semana,
                SUM(Activa = '0' AND COALESCE(TRIM(CNP), '') <> '') AS source_cnp,
                SUM(Activa IN ('1', 'NA') AND COALESCE(TRIM(CNC), '') <> '') AS source_cnc
            FROM programacion_semanal
            WHERE project_id IN ({$fixtureProjectsSql})
            GROUP BY project_id, Semana
        ) source
        LEFT JOIN (
            SELECT project_id, Semana, SUM(is_cnp_population) AS view_cnp, SUM(is_cnc_population) AS view_cnc
            FROM bi_ps_compromisos
            WHERE project_id IN ({$fixtureProjectsSql})
            GROUP BY project_id, Semana
        ) view USING (project_id, Semana)
        WHERE source.source_cnp <> COALESCE(view.view_cnp, 0)
           OR source.source_cnc <> COALESCE(view.view_cnc, 0)",
        'BI cause populations reconcile with every sanitized fixture project and week',
    );
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
       AND pc.project_id IN ({$fixtureProjectsSql})
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
