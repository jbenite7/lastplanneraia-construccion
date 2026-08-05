<?php
/**
 * BI Views Deploy Script
 *
 * Executes all BI SQL views in order, idempotently (CREATE OR REPLACE VIEW).
 * Run: docker compose exec app php database/bi/deploy.php
 * Or with --apply flag for dry-run safety.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$isDryRun = !in_array('--apply', $argv ?? [], true);

$views = [
    '001_bi_pg_semana.sql',
    '002_bi_pi_restricciones.sql',
    '003_bi_ps_compromisos.sql',
    '005_bi_cic_contratistas.sql',
    '006_bi_cip_responsables.sql',
    '007_bi_curva_s_duracion.sql',
    '008_bi_riesgos.sql',
    '009_bi_control_tower_summary.sql',
    '010_bi_lineage.sql',
];

echo "=== BI Views Deploy ===\n";
echo "Mode: " . ($isDryRun ? "DRY RUN (use --apply to execute)" : "APPLY") . "\n";
echo "Directory: " . __DIR__ . "\n";
echo "Views to deploy: " . count($views) . "\n";
echo str_repeat('-', 60) . "\n";

$success = 0;
$failed = 0;
$db = Database::getInstance();

foreach ($views as $index => $file) {
    $path = __DIR__ . '/' . $file;
    $num = $index + 1;

    if (!file_exists($path)) {
        echo "[{$num}/" . count($views) . "] SKIP {$file} — file not found\n";
        $failed++;
        continue;
    }

    $sql = file_get_contents($path);
    if (empty(trim($sql))) {
        echo "[{$num}/" . count($views) . "] SKIP {$file} — empty file\n";
        $failed++;
        continue;
    }

    echo "[{$num}/" . count($views) . "] " . ($isDryRun ? "WOULD deploy" : "Deploying") . " {$file}... ";

    if ($isDryRun) {
        $viewName = '';
        if (preg_match('/CREATE\s+OR\s+REPLACE\s+VIEW\s+`(\w+)`/i', $sql, $m)) {
            $viewName = $m[1];
        }
        echo "OK (view: {$viewName}, " . strlen($sql) . " bytes)\n";
        $success++;
        continue;
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $viewName = '';
        if (preg_match('/CREATE\s+OR\s+REPLACE\s+VIEW\s+`(\w+)`/i', $sql, $m)) {
            $viewName = $m[1];
        }
        echo "OK ({$viewName})\n";
        $success++;
    } catch (\PDOException $e) {
        echo "FAILED — " . $e->getMessage() . "\n";
        $failed++;
    } catch (\Exception $e) {
        echo "FAILED — " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo str_repeat('-', 60) . "\n";
echo "Result: {$success} succeeded, {$failed} failed, " . count($views) . " total\n";

if ($isDryRun) {
    echo "\nDRY RUN complete. Run with --apply to execute.\n";
}

exit($failed > 0 ? 1 : 0);
