<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\RbacCatalog;
use App\Security\RbacService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

echo "=== PDC v2: permiso lps.pdc.importar ===\n";

$assert(in_array('lps.pdc.importar', RbacCatalog::permissionKeys(), true), 'La clave existe en el catálogo.');

$rbac = new RbacService(Database::getInstance());
$assert($rbac->can('lps.pdc.importar', 'A'), 'A puede importar (wildcard).');
$assert($rbac->can('lps.pdc.importar', 'D'), 'D puede importar.');
foreach (['R', 'OT', 'DCV', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    $assert(!$rbac->can('lps.pdc.importar', $rol), "{$rol} NO puede importar.");
}

// f18 de la revisión de UX: fijar la versión oficial exige el permiso de IMPORTAR, no el de ver.
// Deja fuera a Planeación, que sí puede amarrar y calcular: cambiar cuál presupuesto rige mueve la
// base de todo lo demás.
$controlador = file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasImportController.php') ?: '';
$rutas = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
$assert(str_contains($rutas, '/plan-compras/api/presupuesto/activar'), 'La ruta POST de activar versión está registrada.');
$activarFn = substr($controlador, (int) strpos($controlador, 'public function activar'));
$activarFn = substr($activarFn, 0, (int) strpos($activarFn, 'public function impactoVersion'));
$assert(str_contains($activarFn, 'guardEscritura()'),
    'activar() pasa por el guard de importar (lps.pdc.importar + CSRF), no por el de lectura.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
