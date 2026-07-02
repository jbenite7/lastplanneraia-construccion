<?php

require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function mesPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function mesFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function mesAssert(bool $condition, string $message): void
{
    $condition ? mesPass($message) : mesFail($message);
}

echo "=== Metrolinea evidence scope ===\n";

try {
    $db = Database::getInstance();
    $projects = $db->query(
        "SELECT Id, Proyecto_Proceso
         FROM general_proyectos_procesos
         WHERE Proyecto_Proceso LIKE '%Metrolinea%'
         ORDER BY Id",
    )->fetchAll(PDO::FETCH_ASSOC);

    mesAssert(count($projects) > 0, 'existen proyectos Metrolinea en la BD');

    $metrolineaIds = array_map(static fn(array $row): int => (int) $row['Id'], $projects);
    $placeholders = implode(',', array_fill(0, count($metrolineaIds), '?'));

    $programRows = (int) $db->query(
        "SELECT COUNT(*) FROM programa_consolidado WHERE project_id IN ({$placeholders})",
        $metrolineaIds,
    )->fetchColumn();
    mesAssert($programRows > 0, 'Metrolinea tiene cronogramas para preview');

    $activityRows = (int) $db->query(
        "SELECT COUNT(*) FROM actividades WHERE project_id IN ({$placeholders})",
        $metrolineaIds,
    )->fetchColumn();
    $pdcRows = (int) $db->query(
        "SELECT COUNT(*) FROM pdc WHERE project_id IN ({$placeholders})",
        $metrolineaIds,
    )->fetchColumn();
    mesAssert($activityRows === 0, 'Metrolinea no tiene actividades globales aplicables en el corte actual');
    mesAssert($pdcRows === 0, 'Metrolinea no tiene PDC global aplicable en el corte actual');

    $evidenceDir = __DIR__ . '/../docs/qa/evidence/previews-lacp-four-projects-20260702130343';
    foreach (['EVIDENCE.md', 'summary.json', 'summary.png', 'recording.webm', 'trace.zip'] as $file) {
        $path = $evidenceDir . '/' . $file;
        mesAssert(is_file($path) && filesize($path) > 0, "existe evidencia Metrolinea {$file}");
    }

    $summary = json_decode(file_get_contents($evidenceDir . '/summary.json') ?: '[]', true);
    $rows = is_array($summary['summaries'] ?? null) ? $summary['summaries'] : [];
    $metrolineaRows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => str_contains((string) ($row['project'] ?? ''), 'Metrolinea'),
    ));
    mesAssert(count($metrolineaRows) === 3, 'evidencia E2E cubre Listado, Contratos y PDC para Metrolinea');

    $modules = array_map(static fn(array $row): string => (string) ($row['module'] ?? ''), $metrolineaRows);
    sort($modules);
    mesAssert($modules === ['contratos', 'listado-actividades', 'pdc'], 'modulos Metrolinea esperados en evidencia E2E');

    $completionAudit = file_get_contents(__DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/completion-audit.md') ?: '';
    mesAssert(str_contains($completionAudit, 'Metrolinea se omite'), 'auditoria de cierre explica omision de CRUD completo de Metrolinea');
} catch (Throwable $e) {
    mesFail($e->getMessage());
}

echo "=== Metrolinea evidence scope: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
