<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario'] = 'qa-pdc-boundary';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';

$failed = 0;

function pdcBoundaryPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function pdcBoundaryFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== PDC does not create families ===\n";

try {
    $serviceSource = file_get_contents(__DIR__ . '/../src/Services/SemiAutoService.php') ?: '';
    $pdcSection = substr(
        $serviceSource,
        strpos($serviceSource, 'private function buildPdcSuggestions') ?: 0,
        3800,
    );
    !str_contains($pdcSection, 'general_pdc_familias')
        ? pdcBoundaryPass('PDC suggestion builder does not read/write family catalog')
        : pdcBoundaryFail('PDC suggestion builder still touches family catalog');
    !str_contains($pdcSection, 'create_activity')
        ? pdcBoundaryPass('PDC suggestion builder does not create activities')
        : pdcBoundaryFail('PDC suggestion builder references create_activity');

    $db = Database::getInstance();
    $service = new SemiAutoService($db);
    $projects = [
        ['id' => 68, 'name' => 'Optimización Aeropuerto JMC', 'db' => 'optimizacionJMC', 'week' => 7],
        ['id' => 73, 'name' => 'Da Porto', 'db' => 'da_porto', 'week' => 8],
        ['id' => 74, 'name' => 'Milán Campestre Torre 19', 'db' => 'milan_campestre_torre', 'week' => 6],
    ];

    foreach ($projects as $project) {
        $preview = $service->preview(SemiAutoService::MODULE_PDC, [
            'projectId' => $project['id'],
            'project_id' => $project['id'],
            'dbPrefix' => $project['db'],
            'db' => $project['db'],
            'semana' => $project['week'],
        ]);
        $badTargets = [];
        foreach (($preview['suggestions'] ?? []) as $suggestion) {
            $targetTable = (string) ($suggestion['target_table'] ?? '');
            $action = (string) ($suggestion['action'] ?? '');
            if ($targetTable !== 'pdc' || !in_array($action, ['create_pdc_package', 'update_pdc_package'], true)) {
                $badTargets[] = $targetTable . ':' . $action;
            }
        }
        empty($badTargets)
            ? pdcBoundaryPass($project['name'] . ' PDC suggestions target only PDC packages')
            : pdcBoundaryFail($project['name'] . ' has invalid PDC targets: ' . implode(', ', $badTargets));
    }
} catch (Throwable $e) {
    pdcBoundaryFail($e->getMessage());
}

echo "=== PDC does not create families: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
