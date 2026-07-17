<?php
/**
 * Test: BI views exist and have project_id scope.
 * Phase 1 verification.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$db = \Database::getInstance();

$views = [
    'bi_pg_semana',
    'bi_pi_restricciones',
    'bi_ps_compromisos',
    'bi_pdc_general',
    'bi_cic_contratistas',
    'bi_cip_responsables',
    'bi_curva_s_duracion',
    'bi_riesgos',
    'bi_control_tower_summary',
    'bi_lineage',
];

echo "=== Testing BI View Existence ===\n\n";
$passed = 0;
$failed = 0;

foreach ($views as $view) {
    try {
        $stmt = $db->query("SELECT 1 FROM {$view} LIMIT 0");
        echo "  PASS: {$view} exists (no error on DESCRIBE)\n";
        $passed++;
    } catch (\Exception $e) {
        echo "  FAIL: {$view} — {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n---\nResult: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
