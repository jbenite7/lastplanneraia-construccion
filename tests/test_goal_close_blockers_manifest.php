<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/familias_revision_obligatoria.php';

$failed = 0;

function gcbPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function gcbFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function gcbAssert(bool $condition, string $message): void
{
    $condition ? gcbPass($message) : gcbFail($message);
}

function gcbScalar(Database $db, string $sql, array $params = []): mixed
{
    return $db->query($sql, $params)->fetchColumn();
}

echo "=== Goal close blockers manifest ===\n";

try {
    $db = Database::getInstance();
    $root = dirname(__DIR__);
    $base = $root . '/docs/qa/evidence/catalog-goal-audit-20260702';
    $manifestPath = $base . '/goal-close-blockers.json';
    $statusPath = $base . '/STATUS.md';
    $completionPath = $base . '/completion-audit.md';

    $manifest = json_decode(file_get_contents($manifestPath) ?: '', true);
    $status = file_get_contents($statusPath) ?: '';
    $completion = file_get_contents($completionPath) ?: '';

    gcbAssert(is_array($manifest), 'manifiesto JSON es valido');
    gcbAssert(($manifest['status'] ?? '') === 'ready_to_close', 'manifiesto permite cierre del goal');
    gcbAssert(($manifest['operational_target']['status'] ?? '') === 'verified', 'objetivo operativo de 3 proyectos queda verificado');

    $projects = $manifest['operational_target']['projects'] ?? [];
    gcbAssert(count($projects) === 3, 'manifiesto cubre los 3 proyectos obligatorios');

    foreach ($projects as $project) {
        gcbAssert((int) ($project['contracts'] ?? -1) === (int) ($project['pdc'] ?? -2), "{$project['name']} contratos y PDC coinciden");
        gcbAssert((int) ($project['missing'] ?? -1) === 0, "{$project['name']} sin faltantes");
        gcbAssert((int) ($project['duplicates'] ?? -1) === 0, "{$project['name']} sin duplicados");
    }

    $blockers = $manifest['blockers'] ?? [];
    $byId = [];
    foreach ($blockers as $blocker) {
        $byId[(string) ($blocker['id'] ?? '')] = $blocker;
    }

    gcbAssert($byId === [], 'manifiesto no conserva bloqueos abiertos');
    gcbAssert(!isset($byId['human_family_decisions']), 'bloqueo de decisiones humanas ya no sigue abierto');

    $exclusions = $manifest['accepted_exclusions'] ?? [];
    $exclusionIds = array_column($exclusions, 'id');
    gcbAssert(in_array('metrolinea_apply_crud_gap', $exclusionIds, true), 'Metrolinea queda como exclusion aceptada');

    $pendingHuman = familiasConRevisionObligatoria($db);
    gcbAssert($pendingHuman === FAMILIAS_REVISION_OBLIGATORIA, 'el catalogo mantiene exactamente las familias con revision obligatoria vigentes');

    $metroProjects = $db->query(
        "SELECT Id FROM general_proyectos_procesos
         WHERE Proyecto_Proceso LIKE '%Metrolinea%'",
    )->fetchAll(PDO::FETCH_COLUMN);
    gcbAssert(count($metroProjects) > 0, 'existen proyectos Metrolinea para validar brecha');

    if ($metroProjects !== []) {
        $placeholders = implode(',', array_fill(0, count($metroProjects), '?'));
        $metroActivities = (int) gcbScalar(
            $db,
            "SELECT COUNT(*) FROM actividades WHERE project_id IN ({$placeholders})",
            $metroProjects,
        );
        $metroPdc = (int) gcbScalar(
            $db,
            "SELECT COUNT(*) FROM pdc WHERE project_id IN ({$placeholders})",
            $metroProjects,
        );
        gcbAssert($metroActivities === 0, 'Metrolinea omitido sigue sin actividades globales aplicables');
        gcbAssert($metroPdc === 0, 'Metrolinea omitido sigue sin PDC global aplicable');
    }

    foreach ($manifest['minimum_close_checks'] ?? [] as $check) {
        gcbAssert(is_string($check) && $check !== '', "check minimo declarado: {$check}");
    }

    gcbAssert(str_contains($status, 'goal-close-blockers.json'), 'STATUS enlaza manifiesto de bloqueos');
    gcbAssert(str_contains($completion, 'goal-close-blockers.json'), 'completion-audit enlaza manifiesto de bloqueos');
} catch (Throwable $e) {
    gcbFail($e->getMessage());
}

echo "=== Goal close blockers manifest: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
