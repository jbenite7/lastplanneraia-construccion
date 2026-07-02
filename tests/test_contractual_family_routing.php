<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Support\ActivityMatcher;
use App\Support\OperationalFamilyPolicy;

$failed = 0;

function cfrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function cfrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function cfrAssert(bool $condition, string $message): void
{
    $condition ? cfrPass($message) : cfrFail($message);
}

echo "=== Contractual family routing ===\n";

try {
    $db = Database::getInstance();
    $matcher = new ActivityMatcher();
    $rules = $matcher->loadRules();

    $acero = $matcher->matchActivity(['Actividad' => 'Acero de refuerzo para estructura'], $rules);
    cfrAssert($acero === null || !empty($acero['contractual_only']), 'acero no queda como familia operativa lista');
    $policy = new OperationalFamilyPolicy($db);
    $aceroHints = $policy->contractualPackageHintsForText('Acero de refuerzo para estructura');
    cfrAssert(!empty($aceroHints), 'acero se detecta como elemento contractual para Contratos');

    $enchapes = $matcher->matchActivity(['Actividad' => 'Enchapes ceramicos en muros de baños'], $rules);
    cfrAssert($enchapes !== null, 'matcher detecta enchapes');
    cfrAssert(($enchapes['familia_nombre'] ?? '') === 'Pisos y Enchapes', 'enchapes se normaliza a Pisos y Enchapes');

    $rci = $matcher->matchActivity(['Actividad' => 'Instalacion red contra incendio piping'], $rules);
    cfrAssert($rci !== null, 'matcher detecta RCI');
    cfrAssert(($rci['familia_nombre'] ?? '') === OperationalFamilyPolicy::RCI_FAMILY, 'RCI se normaliza a Red de Extinción');

    $service = new SemiAutoService($db);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('policyContractPackagesForActivity');
    $method->setAccessible(true);
    $packages = $method->invoke($service, [
        'actividad' => 'Estructura en Concreto',
        'fechaInicio' => '2030-01-01',
    ], [
        'Actividad' => 'Estructura en Concreto',
        'Fecha_Inicio' => '2030-01-01',
    ], [
        ['activity' => 'Acero de refuerzo para estructura', 'family' => 'Acero de Refuerzo y Estructural'],
    ]);
    $packageNames = array_map(static fn(array $item): string => (string) ($item['paqueteNombre'] ?? ''), $packages);
    cfrAssert(!empty($packageNames), 'contratos deriva paquetes desde fuentes contractuales');
    cfrAssert(count(array_filter($packageNames, static fn(string $name): bool => stripos($name, 'ACERO') !== false)) > 0, 'contratos deriva paquete de acero');
} catch (Throwable $e) {
    cfrFail($e->getMessage());
}

echo "=== Contractual family routing: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
