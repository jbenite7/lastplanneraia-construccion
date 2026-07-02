<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function hdacPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function hdacFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function hdacAssert(bool $condition, string $message): void
{
    $condition ? hdacPass($message) : hdacFail($message);
}

echo "=== Human decision approval checklist ===\n";

try {
    $db = Database::getInstance();
    $base = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702';
    $checklistPath = $base . '/human-decision-approval-checklist.md';
    $packagePath = $base . '/human-decision-proposed-actions.json';
    $statusPath = $base . '/STATUS.md';

    $checklist = file_get_contents($checklistPath) ?: '';
    $package = json_decode(file_get_contents($packagePath) ?: '', true);
    $status = file_get_contents($statusPath) ?: '';

    hdacAssert($checklist !== '', 'existe checklist de aprobacion humana');
    hdacAssert(str_contains($checklist, 'decisiones aplicadas en BD'), 'checklist declara decisiones aplicadas');
    hdacAssert(str_contains($checklist, 'usuario aprobo los 6 lotes'), 'checklist registra aprobacion explicita');
    hdacAssert(str_contains($checklist, 'human-decision-proposed-actions.json'), 'checklist enlaza paquete JSON');

    $decisions = $package['decisions'] ?? [];
    hdacAssert(count($decisions) === 13, 'paquete mantiene 13 decisiones');

    foreach ($decisions as $decision) {
        $code = (string) ($decision['family_code'] ?? '');
        $name = (string) ($decision['family_name'] ?? '');
        hdacAssert($code !== '' && str_contains($checklist, "`{$code}`"), "checklist cubre codigo {$code}");
        hdacAssert($name !== '' && str_contains($checklist, $name), "checklist cubre nombre {$name}");
    }

    foreach ([
        'Lote 1: equipos y recursos para Contratos',
        'Lote 2: Aseo',
        'Lote 3: Red de Telecomunicaciones',
        'Lote 4: Campamento de Obra',
        'Lote 5: Botada de Escombros',
        'Lote 6: Amenidades Especiales de Cubierta',
        'Verificacion minima despues de aprobar',
        'Criterio de cierre',
    ] as $section) {
        hdacAssert(str_contains($checklist, $section), "checklist incluye {$section}");
    }

    foreach ([
        'tests/test_human_decision_actions_package.php',
        'tests/test_human_decision_matrix_coverage.php',
        'tests/test_review_required_families_block_auto_apply.php',
        'tests/test_learning_persistence_catalog_db.php',
        'tests/test_listado_reclassified_real_projects.php',
        'tests/test_pdc_three_projects_perfect_20260702.php',
        'git diff --check',
    ] as $commandOrTest) {
        hdacAssert(str_contains($checklist, $commandOrTest), "checklist exige {$commandOrTest}");
    }

    $decisionRows = $db->query(
        "SELECT codigo
         FROM general_pdc_familias
         WHERE codigo IN (
             'AMENIDADES_CUBIERTA', 'ASEO', 'BOMBA_CONCRETO', 'EXCAVADORA',
             'MALACATE', 'MONTACARGAS', 'MOTORGRUA', 'PLANTA_CONCRETO',
             'TORREGRUA', 'VOLQUETA', 'RED_TELECOMUNICACIONES', 'CAMPAMENTO',
             'BOTADA_ESCOMBROS'
         )
         ORDER BY codigo",
    )->fetchAll(PDO::FETCH_COLUMN);
    hdacAssert(count($decisionRows) === 13, 'BD conserva trazabilidad de las 13 decisiones');

    foreach ($decisionRows as $code) {
        hdacAssert(str_contains($checklist, "`{$code}`"), "checklist cubre decision de BD {$code}");
    }

    $pendingRows = $db->query(
        "SELECT codigo
         FROM general_pdc_familias
         WHERE COALESCE(activa, 1) = 1
           AND COALESCE(siempre_revision, 0) = 1
         ORDER BY codigo",
    )->fetchAll(PDO::FETCH_COLUMN);
    hdacAssert($pendingRows === [], 'BD no conserva decisiones humanas pendientes');

    hdacAssert(str_contains($status, 'human-decision-approval-checklist.md'), 'STATUS enlaza checklist');
} catch (Throwable $e) {
    hdacFail($e->getMessage());
}

echo "=== Human decision approval checklist: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
