<?php

$failed = 0;

function gcrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function gcrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function gcrAssert(bool $condition, string $message): void
{
    $condition ? gcrPass($message) : gcrFail($message);
}

echo "=== Goal close readiness script ===\n";

try {
    $root = dirname(__DIR__);
    $script = $root . '/docs/qa/scripts/goal_close_readiness.php';

    $output = [];
    $exitCode = 0;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script), $output, $exitCode);

    $json = implode("\n", $output);
    $payload = json_decode($json, true);

    gcrAssert($exitCode === 0, 'script permite cierre con exit code 0');
    gcrAssert(is_array($payload), 'script devuelve JSON valido');
    gcrAssert(($payload['status'] ?? '') === 'ready_to_close', 'estado actual permite cerrar');
    gcrAssert(($payload['operational_target']['status'] ?? '') === 'verified', 'objetivo operativo sigue verificado');

    $openBlockers = $payload['open_blockers'] ?? [];
    $dynamicBlockers = $payload['dynamic_blockers'] ?? [];
    gcrAssert(count($openBlockers) === 0, 'manifiesto no mantiene bloqueos abiertos');
    gcrAssert(count($dynamicBlockers) === 0, 'BD no confirma bloqueos dinamicos');

    $blockerIds = array_column($openBlockers, 'id');
    gcrAssert(!in_array('metrolinea_apply_crud_gap', $blockerIds, true), 'bloqueo Metrolinea ya no visible');
    gcrAssert(!in_array('destructive_legacy_cleanup', $blockerIds, true), 'bloqueo legacy ya no visible');
    gcrAssert(!in_array('human_family_decisions', $blockerIds, true), 'bloqueo humano ya no visible');

    $exclusions = $payload['accepted_exclusions'] ?? [];
    $exclusionIds = array_column(is_array($exclusions) ? $exclusions : [], 'id');
    gcrAssert(in_array('metrolinea_apply_crud_gap', $exclusionIds, true), 'script reporta exclusion aceptada Metrolinea');

    $signals = $payload['current_database_signals'] ?? [];
    gcrAssert((int) ($signals['pending_human_family_decisions'] ?? -1) === 0, 'BD reporta 0 decisiones humanas pendientes');
    gcrAssert((int) ($signals['metrolinea_projects'] ?? 0) > 0, 'BD reporta proyectos Metrolinea');
    gcrAssert((int) ($signals['metrolinea_activities'] ?? -1) === 0, 'BD reporta 0 actividades Metrolinea');
    gcrAssert((int) ($signals['metrolinea_pdc'] ?? -1) === 0, 'BD reporta 0 PDC Metrolinea');

    foreach ([
        'tests/test_pdc_three_projects_perfect_20260702.php',
        'tests/test_goal_close_blockers_manifest.php',
        'git diff --check',
    ] as $minimumCheck) {
        gcrAssert(
            in_array('docker compose exec app php ' . $minimumCheck, $payload['minimum_close_checks'] ?? [], true)
            || in_array($minimumCheck, $payload['minimum_close_checks'] ?? [], true),
            "check minimo incluido {$minimumCheck}",
        );
    }
} catch (Throwable $e) {
    gcrFail($e->getMessage());
}

echo "=== Goal close readiness script: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
