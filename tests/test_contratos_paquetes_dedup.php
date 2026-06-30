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
$method = new ReflectionMethod(ContratosApiController::class, 'formatUniquePackageNames');
$method->setAccessible(true);

$cases = [
    [['ACERO; ACERO', 'CONCRETO; CONCRETO'], 'ACERO, CONCRETO'],
    [['Paquete A ; paquete a', 'Paquete B', 'Paquete B'], 'Paquete A, Paquete B'],
    [['Aparatos sanitarios, griferias, incrustaciones', 'Uno; Dos'], 'Aparatos sanitarios, griferias, incrustaciones, Uno, Dos'],
];

foreach ($cases as [$input, $expected]) {
    $actual = $method->invoke($controller, $input);
    if ($actual !== $expected) {
        echo "FAIL: expected '$expected', got '$actual'\n";
        exit(1);
    }
}

echo "PASS: contratos package names are deduplicated\n";
