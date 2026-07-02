<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$failed = 0;

function pdcDurPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function pdcDurFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function pdcDurAssert(bool $condition, string $message): void
{
    $condition ? pdcDurPass($message) : pdcDurFail($message);
}

echo "=== PDC duration catalog ===\n";

try {
    $db = Database::getInstance();
    $service = new SemiAutoService($db);
    $reflection = new ReflectionClass($service);

    $durationMethod = $reflection->getMethod('pdcDurationsForPackage');
    $durationMethod->setAccessible(true);
    $durations = $durationMethod->invoke($service, [
        'tipoPaquete' => 'Suministro e Instalación',
        'paqueteNombre' => 'ASCENSORES',
    ]);

    pdcDurAssert((int) ($durations['dias_fabricacion'] ?? 0) === 300, 'lee fabricación de ASCENSORES desde catálogo');
    pdcDurAssert((int) ($durations['dias_insumos'] ?? 0) === 20, 'lee insumos de ASCENSORES desde catálogo');

    $editableMethod = $reflection->getMethod('pdcEditableFields');
    $editableMethod->setAccessible(true);
    $editable = $editableMethod->invoke($service);

    foreach ([
        'diasElaboracionPliegos',
        'diasEntregaPliegos',
        'diasReciboPropuestas',
        'diasCuadrosComparativos',
        'diasLegalizacionContrato',
        'diasFabricacion',
        'diasInsumosObra',
    ] as $field) {
        pdcDurAssert(in_array($field, $editable, true), "{$field} es actualizable en PDC moderno");
    }
} catch (Throwable $e) {
    pdcDurFail($e->getMessage());
}

echo "=== PDC duration catalog: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
