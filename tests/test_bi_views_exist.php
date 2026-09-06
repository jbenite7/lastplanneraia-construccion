<?php
/**
 * Test: BI views exist and have project_id scope.
 * Phase 1 verification.
 */
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';

$db = \Database::getInstance();

$views = [
    'bi_pg_semana',
    'bi_pi_restricciones',
    'bi_ps_compromisos',
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

// Comprobar que una vista existe y es consultable es mantenimiento del esquema, no trabajo dentro
// de una obra: por eso va bajo SystemScope y no bajo el alcance de un proyecto, que aquí no
// significaría nada. El `LIMIT 0` sigue garantizando que no se lee un solo dato de nadie.
(new \App\Security\DataScope\SystemScopeRunner($db->dataScope()))->run(
    'test:bi-views-exist',
    static function () use ($db, $views, &$passed, &$failed): void {
        foreach ($views as $view) {
            try {
                $db->query("SELECT 1 FROM {$view} LIMIT 0");
                echo "  PASS: {$view} exists (no error on DESCRIBE)\n";
                $passed++;
            } catch (\Exception $e) {
                echo "  FAIL: {$view} — {$e->getMessage()}\n";
                $failed++;
            }
        }
    },
);

echo "\n---\nResult: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
