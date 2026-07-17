<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Api\ContratosApiController;

$controller = new ContratosApiController();
$failures = [];

try {
    $method = new ReflectionMethod($controller, 'normalizeDurationRows');
    $method->setAccessible(true);
} catch (ReflectionException $exception) {
    fwrite(STDERR, "FAIL: falta el normalizador atómico de las siete duraciones.\n");
    exit(1);
}

$base = [
    'tipoPaquete' => 'Suministro',
    'paqueteContratacion' => 'Paquete de prueba',
    'diasElaboracionPliegos' => '0',
    'diasEntregaPliegos' => '1',
    'diasReciboPropuestas' => '2',
    'diasCuadrosComparativos' => '3',
    'diasLegalizacionContrato' => '4',
    'diasFabricacion' => '5',
    'diasInsumosObra' => '6',
];

$valid = $method->invoke($controller, [$base]);
if (($valid[0]['diasInsumosObra'] ?? null) !== 6 || count($valid[0] ?? []) !== 9) {
    $failures[] = 'No normaliza exactamente identidad y siete duraciones.';
}

foreach (['', '-1', '1.5'] as $invalid) {
    $candidate = $base;
    $candidate['diasFabricacion'] = $invalid;
    $thrown = false;
    try {
        $method->invoke($controller, [$base, $candidate]);
    } catch (Throwable $exception) {
        $thrown = $exception instanceof InvalidArgumentException;
    }
    if (!$thrown) {
        $failures[] = "No rechaza duración inválida '{$invalid}' antes del lote.";
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}

fwrite(STDOUT, "PASS: las siete duraciones se validan como lote antes de persistir.\n");
