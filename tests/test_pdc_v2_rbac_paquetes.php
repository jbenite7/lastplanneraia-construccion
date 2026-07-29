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

echo "=== PDC v2: permisos lps.paquetes_contratacion.* ===\n";
$assert(in_array('lps.paquetes_contratacion.ver', RbacCatalog::permissionKeys(), true), 'Clave .ver existe en el catálogo.');
$assert(in_array('lps.paquetes_contratacion.editar', RbacCatalog::permissionKeys(), true), 'Clave .editar existe en el catálogo.');

$rbac = new RbacService(Database::getInstance());
// A (wildcard) y D administran; ambos ven.
foreach (['A', 'D'] as $rol) {
    $assert($rbac->can('lps.paquetes_contratacion.ver', $rol), "{$rol} ve paquetes.");
    $assert($rbac->can('lps.paquetes_contratacion.editar', $rol), "{$rol} edita paquetes.");
}
// P edita (perfil de planeación).
$assert($rbac->can('lps.paquetes_contratacion.editar', 'P'), 'P edita paquetes.');
// R ve pero NO edita (solo lectura).
$assert($rbac->can('lps.paquetes_contratacion.ver', 'R'), 'R ve paquetes.');
$assert(!$rbac->can('lps.paquetes_contratacion.editar', 'R'), 'R NO edita paquetes.');

// f13 de la revisión de UX: desamarrar lo puede hacer exactamente quien puede amarrar. Un permiso
// aparte dejaría a gente capaz de crear un amarre equivocado sin poder corregirlo.
$assert(!in_array('lps.paquetes_contratacion.desamarrar', RbacCatalog::permissionKeys(), true),
    'Desamarrar NO estrena permiso propio: usa el mismo .editar que amarrar.');

$controlador = file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasPlanController.php') ?: '';
$rutas = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
$assert(str_contains($rutas, "/plan-compras/api/plan/desamarrar"),
    'La ruta POST de desamarrar está registrada.');
$desamarrarFn = substr($controlador, (int) strpos($controlador, 'public function desamarrar'));
$desamarrarFn = substr($desamarrarFn, 0, (int) strpos($desamarrarFn, 'public function calcular'));
$assert(str_contains($desamarrarFn, 'guardEscritura()'),
    'desamarrar() pasa por el mismo guard de escritura que amarrar (permiso + CSRF).');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
