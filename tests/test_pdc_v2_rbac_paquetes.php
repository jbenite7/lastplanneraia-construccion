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

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
