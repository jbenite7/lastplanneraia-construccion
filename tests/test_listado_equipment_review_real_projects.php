<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$failed = 0;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function lerPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lerFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== Listado equipment contracts real projects ===\n";

try {
    $_SESSION['usuario'] = 'qa-equipment-contracts';
    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';

    $db = Database::getInstance();
    $service = new SemiAutoService($db);

    $equipmentIds = array_flip(array_map(
        'intval',
        $db->query(
            "SELECT id
             FROM general_pdc_familias
             WHERE categoria = 'EQUIPOS'",
        )->fetchAll(PDO::FETCH_COLUMN),
    ));

    $projects = [
        ['name' => 'Optimizacion Aeropuerto JMC', 'projectId' => 68, 'dbPrefix' => 'optimizacionJMC', 'week' => 7],
        ['name' => 'Da Porto', 'projectId' => 73, 'dbPrefix' => 'da_porto', 'week' => 8],
        ['name' => 'Milan Campestre Torre 19', 'projectId' => 74, 'dbPrefix' => 'milan_campestre_torre', 'week' => 6],
        ['name' => 'Metrolinea Estacion 16 Ascendente', 'projectId' => 71, 'dbPrefix' => 'metrolineaAscendente', 'week' => 1],
    ];

    foreach ($projects as $project) {
        $preview = $service->preview(SemiAutoService::MODULE_LISTADO, [
            'projectId' => $project['projectId'],
            'project_id' => $project['projectId'],
            'dbPrefix' => $project['dbPrefix'],
            'db' => $project['dbPrefix'],
            'semana' => $project['week'],
        ]);

        $bad = [];
        foreach (($preview['suggestions'] ?? []) as $suggestion) {
            if (($suggestion['action'] ?? '') !== 'create_activity') {
                continue;
            }
            $familyId = (int) ($suggestion['analysis']['technical']['familia_id'] ?? 0);
            if ($familyId > 0 && isset($equipmentIds[$familyId])) {
                $bad[] = (string) ($suggestion['title'] ?? $suggestion['suggestion_id'] ?? '(sin titulo)');
            }
        }

        $bad === []
            ? lerPass($project['name'] . ' no propone EQUIPOS como familias de Listado')
            : lerFail($project['name'] . ' propone EQUIPOS como familias: ' . implode('; ', array_slice($bad, 0, 10)));
    }
} catch (Throwable $e) {
    lerFail($e->getMessage());
}

echo "=== Listado equipment contracts real projects: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
