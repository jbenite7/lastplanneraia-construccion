<?php

$failed = 0;

function lcrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lcrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lcrAssert(bool $condition, string $message): void
{
    $condition ? lcrPass($message) : lcrFail($message);
}

echo "=== LACP legacy cleanup readiness ===\n";

try {
    $root = dirname(__DIR__);
    $manifestPath = $root . '/docs/qa/evidence/catalog-goal-audit-20260702/legacy-cleanup-readiness.md';
    $statusPath = $root . '/docs/qa/evidence/catalog-goal-audit-20260702/STATUS.md';
    $completionPath = $root . '/docs/qa/evidence/catalog-goal-audit-20260702/completion-audit.md';
    $routes = file_get_contents($root . '/public/index.php') ?: '';
    $manifest = file_get_contents($manifestPath) ?: '';
    $status = file_get_contents($statusPath) ?: '';
    $completion = file_get_contents($completionPath) ?: '';

    lcrAssert($manifest !== '', 'existe manifiesto de limpieza legacy LACP');

    $removedRoutes = [
        '/legacy/pdc/actualizar_pdc.php' => '/api/pdc/auto/apply-from-contratos',
    ];

    foreach ($removedRoutes as $legacy => $replacement) {
        lcrAssert(!str_contains($routes, $legacy), "ruta legacy retirada {$legacy}");
        lcrAssert(str_contains($routes, $replacement), "reemplazo moderno declarado {$replacement}");
        lcrAssert(str_contains($manifest, $legacy), "manifiesto cubre {$legacy}");
        lcrAssert(str_contains($manifest, $replacement), "manifiesto cubre reemplazo {$replacement}");
    }

    lcrAssert(!is_file($root . '/src/Legacy/actualizar_pdc.php'), 'archivo legacy actualizar_pdc retirado');
    lcrAssert(is_file($root . '/src/Legacy/_pdc_functions.php'), '_pdc_functions se conserva por dependencia fuera de alcance');

    foreach ([
        'tests/test_legacy_absence_for_lacp_runtime.php',
        'tests/test_lacp_modern_navigation.php',
        'tests/test_pdc_modern_replaces_legacy_update.php',
        'tests/test_lacp_backup_restore_before_cleanup.php',
    ] as $guard) {
        lcrAssert(str_contains($manifest, $guard), "manifiesto lista guarda {$guard}");
    }

    $manifestLower = mb_strtolower($manifest, 'UTF-8');
    foreach ([
        'aprobacion explicita',
        'backup externo',
        'restauracion local',
        'comparar conteos',
        'fuera del alcance',
        'retirado',
    ] as $requiredText) {
        lcrAssert(str_contains($manifestLower, $requiredText), "manifiesto incluye {$requiredText}");
    }

    lcrAssert(str_contains($status, 'legacy-cleanup-readiness.md'), 'STATUS enlaza el manifiesto legacy');
    lcrAssert(str_contains($completion, 'legacy-cleanup-readiness.md'), 'completion-audit enlaza el manifiesto legacy');
} catch (Throwable $e) {
    lcrFail($e->getMessage());
}

echo "=== LACP legacy cleanup readiness: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
