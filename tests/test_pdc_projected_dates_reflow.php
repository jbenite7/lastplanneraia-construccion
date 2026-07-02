<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Api\PdcApiController;

$failed = 0;

function pdcReflowPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function pdcReflowFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function pdcReflowAssert(bool $condition, string $message): void
{
    $condition ? pdcReflowPass($message) : pdcReflowFail($message);
}

echo "=== PDC projected dates reflow ===\n";

try {
    $reflection = new ReflectionClass(PdcApiController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('calcularFechasProcesoContratacion');
    $method->setAccessible(true);

    $dates = $method->invoke($controller, '2026-04-07', [
        'ElaboracionPliegos' => 10,
        'EntregaPliegos' => 15,
        'ReciboPropuestas' => 15,
        'CuadrosComparativos' => 30,
        'LegalizacionContrato' => 21,
        'Fabricacion' => 55,
        'InsumosObra' => 10,
    ], [
        'ElaboracionPliegos' => '2025-12-31',
    ]);

    pdcReflowAssert($dates['ElaboracionPliegos'] === '2025-12-31', 'fecha real tardía reemplaza la proyectada de la etapa');
    pdcReflowAssert($dates['EntregaPliegos'] === '2026-01-10', 'etapa siguiente se reproyecta desde la fecha real');
    pdcReflowAssert($dates['ReciboPropuestas'] === '2026-01-25', 'tercera etapa conserva el desplazamiento');
    pdcReflowAssert($dates['CuadrosComparativos'] === '2026-02-09', 'cuadros comparativos conserva el desplazamiento');
    pdcReflowAssert($dates['InicioProyectadaContrato'] === '2026-06-05', 'inicio de contrato se desplaza por la demora real');
} catch (Throwable $e) {
    pdcReflowFail($e->getMessage());
}

echo "=== PDC projected dates reflow: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
