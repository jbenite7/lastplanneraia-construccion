<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['usuario'] = 'qa-listado-existing-family-skip';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';
$_SESSION['project_id'] = 74;
$_SESSION['db'] = 'milan_campestre_torre';

$service = new SemiAutoService(Database::getInstance());
$preview = $service->preview(SemiAutoService::MODULE_LISTADO, [
    'projectId' => 74,
    'project_id' => 74,
    'dbPrefix' => 'milan_campestre_torre',
    'db' => 'milan_campestre_torre',
    'semana' => 6,
]);

$proposedNames = [];
foreach (($preview['suggestions'] ?? []) as $suggestion) {
    $name = trim((string) ($suggestion['proposed']['actividad'] ?? $suggestion['payload']['fields']['actividad'] ?? ''));
    if ($name !== '') {
        $proposedNames[] = $name;
    }
}

$existingFamilies = [
    'Morteros de Nivelacion de Losas',
    'Mamposteria en Ladrillo/Bloque Interior',
    'Revoques y Panetes',
    'Carpinteria en Madera',
    'Carpinteria Metalica',
    'Puertas y Accesorios',
    'Pisos y Enchapes',
];

foreach ($existingFamilies as $family) {
    if (in_array($family, $proposedNames, true)) {
        echo "FAIL: existing family was proposed again: {$family}\n";
        exit(1);
    }
}

echo "PASS: listado skips existing Milan families with level/zone sources\n";
