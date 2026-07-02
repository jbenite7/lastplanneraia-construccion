<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Support\ModuleRequestContext;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['usuario'] = 'jbenitez';
$_SESSION['db'] = 'prueba';
$_SESSION['semana'] = 7;
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';
unset($_SESSION['project_id']);

$failed = 0;

function tracePass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function traceFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== Activity program sources traceability ===\n";

try {
    $context = ModuleRequestContext::resolve(['allow_zero_week' => false]);
    $projectId = (int) $context['projectId'];
    $week = (int) $context['semana'];
    $activityId = 987654;

    $db = Database::getInstance();
    $service = new SemiAutoService($db);
    $reflection = new ReflectionClass($service);

    $replace = $reflection->getMethod('replaceActivityProgramSources');
    $replace->setAccessible(true);
    $load = $reflection->getMethod('loadActivityProgramSources');
    $load->setAccessible(true);
    $groupCount = $reflection->getMethod('contractSourceGroupCount');
    $groupCount->setAccessible(true);

    $replace->invoke($service, $projectId, $activityId, $week, [
        [
            'unique_id' => 900001,
            'activity' => '[Capitulo: Estructura 5A] Instalacion concreto Eje 48',
            'start_date' => '2030-01-01',
            'context' => 'estructura 5a',
            'location_hint' => 'Eje 48',
            'intervention_hint' => '5A',
            'family_id' => 20,
            'family' => 'Estructura en Concreto',
            'matched_rule' => 'nombre',
            'confidence' => 95,
            'risk_flags' => [],
        ],
        [
            'unique_id' => 900002,
            'activity' => '[Capitulo: Estructura 5B] Instalacion concreto Eje 49',
            'start_date' => '2030-01-02',
            'context' => 'estructura 5b',
            'location_hint' => 'Eje 49',
            'intervention_hint' => '5B',
            'family_id' => 20,
            'family' => 'Estructura en Concreto',
            'matched_rule' => 'nombre',
            'confidence' => 95,
            'risk_flags' => [],
        ],
    ]);

    $sources = $load->invoke($service, $projectId, $activityId, $week);
    count($sources) === 2
        ? tracePass('stores and loads all program sources')
        : traceFail('expected 2 stored sources, got ' . count($sources));

    ((string) ($sources[0]['intervention_hint'] ?? '') !== '' && (string) ($sources[1]['location_hint'] ?? '') !== '')
        ? tracePass('keeps intervention and location hints for contracts')
        : traceFail('missing intervention or location hints');

    ((int) $groupCount->invoke($service, $sources) === 2)
        ? tracePass('detects contractual source groups')
        : traceFail('did not detect two contractual groups');

    $existingProgramSources = $db->query(
        "SELECT unique_id, Actividad AS activity, Fecha_Inicio AS start_date
         FROM programa_consolidado
         WHERE project_id = ? AND Semana = ? AND COALESCE(Titulo, 0) = 0 AND unique_id IS NOT NULL
         ORDER BY Fecha_Inicio ASC, unique_id ASC
         LIMIT 2",
        [$projectId, $week],
    )->fetchAll(PDO::FETCH_ASSOC);
    if (count($existingProgramSources) >= 2) {
        $replace->invoke($service, $projectId, $activityId, $week, $existingProgramSources);
        $preview = $service->preview(SemiAutoService::MODULE_LISTADO, $context);
        $linkedIds = array_map('intval', array_column($existingProgramSources, 'unique_id'));
        $reused = [];
        foreach (($preview['suggestions'] ?? []) as $suggestion) {
            foreach (($suggestion['analysis']['sources'] ?? []) as $source) {
                $sourceId = (int) ($source['unique_id'] ?? 0);
                if (in_array($sourceId, $linkedIds, true)) {
                    $reused[] = $sourceId;
                }
            }
        }
        empty($reused)
            ? tracePass('preview ignores program sources already linked to an activity')
            : traceFail('preview reused linked program sources: ' . implode(', ', array_unique($reused)));
    } else {
        tracePass('skips duplicate preview guard because sample program has fewer than two rows');
    }
} catch (Throwable $e) {
    traceFail($e->getMessage());
} finally {
    if (isset($db, $projectId, $activityId, $week)) {
        $db->query(
            'DELETE FROM actividad_programa_fuentes WHERE project_id = ? AND actividad_id = ? AND semana = ?',
            [$projectId, $activityId, $week],
        );
    }
}

echo "=== Activity program sources traceability: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
