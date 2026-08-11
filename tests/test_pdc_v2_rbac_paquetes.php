<?php

declare(strict_types=1);
// @requiere: db


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

// B2 · reprogramación. Simular NO escribe, pero va con el guard de escritura a propósito: enseñar
// el delta a quien no puede aplicarlo produce una pantalla que promete un botón que dará 403.
$assert(str_contains($rutas, '/plan-compras/api/plan/reprogramacion/simular'),
    'La ruta GET de simular la reprogramación está registrada.');
$assert(str_contains($rutas, '/plan-compras/api/plan/reprogramacion/aplicar'),
    'La ruta POST de aplicar la reprogramación está registrada.');
$simularFn = substr($controlador, (int) strpos($controlador, 'public function simularReprogramacion'));
$simularFn = substr($simularFn, 0, (int) strpos($simularFn, "\n    /**", 10));
$assert(str_contains($simularFn, "can('lps.paquetes_contratacion.editar')"),
    'simularReprogramacion() exige el permiso de editar, aunque solo lea.');
// CSRF protege mutaciones; simular no lo es, y el cliente solo adjunta el token en POST. Pedirlo en
// este GET dejaba la pestaña «Desfases» sin poder enseñar el delta (visto en el e2e).
$assert(!str_contains($simularFn, 'CsrfTokenManager'),
    'simularReprogramacion() NO exige CSRF: es un GET que no escribe.');

$aplicarFn = substr($controlador, (int) strpos($controlador, 'public function aplicarReprogramacion'));
$aplicarFn = substr($aplicarFn, 0, (int) strpos($aplicarFn, "\n    /**", 10));
$assert(str_contains($aplicarFn, 'guardEscritura()'),
    'aplicarReprogramacion() sí pasa por el guard de escritura (permiso + CSRF): escribe.');
// Rol permitido y rol denegado, que es lo que exige AGENTS.md para toda ruta protegida nueva.
$assert($rbac->can('lps.paquetes_contratacion.editar', 'D'),
    'Rol permitido: D puede aplicar una reprogramación.');
$assert(!$rbac->can('lps.paquetes_contratacion.editar', 'V'),
    'Rol denegado: V (Visualizador) no puede aplicar una reprogramación.');
$assert(!$rbac->can('lps.paquetes_contratacion.editar', 'R'),
    'Rol denegado: R tampoco, aunque vea el plan.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
