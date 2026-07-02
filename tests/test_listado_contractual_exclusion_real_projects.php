<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Support\OperationalFamilyPolicy;

$failed = 0;

function lcePass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lceFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['usuario'] = 'jbenitez';
    $_SESSION['permiso'] = 'D';
    $_SESSION['permiso_canonico'] = 'D';

    echo "=== Listado contractual exclusion real projects ===\n";

    $db = Database::getInstance();
    $service = new SemiAutoService($db);
    $policy = new OperationalFamilyPolicy();
    $projects = [
        ['name' => 'Optimizacion Aeropuerto JMC', 'projectId' => 68, 'dbPrefix' => 'optimizacionJMC', 'week' => 5],
        ['name' => 'Da Porto', 'projectId' => 73, 'dbPrefix' => 'da_porto', 'week' => 1],
    ];

    foreach ($projects as $project) {
        $context = [
            'projectId' => $project['projectId'],
            'project_id' => $project['projectId'],
            'dbPrefix' => $project['dbPrefix'],
            'db' => $project['dbPrefix'],
            'semana' => $project['week'],
        ];
        $preview = $service->preview(SemiAutoService::MODULE_LISTADO, $context);
        $badReady = [];
        foreach (($preview['suggestions'] ?? []) as $suggestion) {
            $gate = $suggestion['analysis']['quality_gate']['status'] ?? '';
            $proposed = $suggestion['proposed_payload'] ?? [];
            $activityName = (string) ($proposed['actividad'] ?? '');
            if (($suggestion['action'] ?? '') === 'review_contractual_item' && $gate === 'ready') {
                $badReady[] = ($suggestion['suggestion_id'] ?? '(sin id)') . ': elemento contractual marcado listo';
            }
            if ($gate !== 'ready') {
                continue;
            }
            foreach ([$activityName, (string) ($suggestion['title'] ?? ''), (string) ($suggestion['reason'] ?? '')] as $text) {
                foreach ($policy->contractualPackageHintsForText($text) as $hint) {
                    $badReady[] = ($suggestion['suggestion_id'] ?? '(sin id)') . ': ' . ($hint['sourceFamily'] ?? 'contractual');
                }
            }
        }

        empty($badReady)
            ? lcePass($project['name'] . ' no tiene contractuales listos en listado')
            : lceFail($project['name'] . ' tiene contractuales listos: ' . implode('; ', array_slice($badReady, 0, 10)));
    }
} catch (Throwable $e) {
    lceFail($e->getMessage());
}

echo "=== Listado contractual exclusion real projects: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
