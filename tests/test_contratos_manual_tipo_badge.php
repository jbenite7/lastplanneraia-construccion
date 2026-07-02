<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Controllers\Api\ContratosApiController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['db'] = 'prueba';
$_SESSION['project_id'] = 27;

$controller = new ContratosApiController();
$method = new ReflectionMethod(ContratosApiController::class, 'inferTipoContratoFromPackages');
$method->setAccessible(true);

$cases = [
    ['', ['paqueteSI1' => 'RED DE EXTINCIÓN'], 'SI'],
    ['', ['paqueteMO1' => 'MO PISOS Y ENCHAPES', 'paqueteS1' => 'PISOS Y ENCHAPES'], 'MO,S'],
    ['S', ['paqueteMO1' => 'MO PISOS Y ENCHAPES'], 'MO,S'],
    ['SI', [], 'SI'],
    ['', ['paqueteSI1' => '   ', 'paqueteMO1' => null], ''],
];

foreach ($cases as [$tipoContrato, $packages, $expected]) {
    $actual = $method->invoke($controller, $tipoContrato, $packages);
    if ($actual !== $expected) {
        echo "FAIL: expected '$expected', got '$actual'\n";
        exit(1);
    }
}

echo "PASS: contratos manual packages infer visible badges\n";
