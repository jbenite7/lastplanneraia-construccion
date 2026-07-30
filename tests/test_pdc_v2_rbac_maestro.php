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

echo "=== PDC v2: permiso lps.pdc.maestro ===\n";
$assert(in_array('lps.pdc.maestro', RbacCatalog::permissionKeys(), true), 'La clave existe en el catálogo.');
$rbac = new RbacService(Database::getInstance());
$assert($rbac->can('lps.pdc.maestro', 'A'), 'A administra el maestro (wildcard).');
$assert($rbac->can('lps.pdc.maestro', 'D'), 'D administra el maestro.');
// OT entró el 2026-07-30 por decisión de Felipe. Este test afirmaba lo contrario, y lo afirmaba con
// razón hasta ese día: la pregunta la abrió la Ola 2 —quién decide si un equipo se alquila o se
// compra— y la respuesta es que esa es una decisión de compra, y Compras vive en «Oficina Técnica /
// Compras». La capacidad es única y abre todo el maestro; se asumió porque OT ya tenía
// `lps.paquetes_contratacion.reglas`, que redirige insumos en todos los proyectos.
$assert($rbac->can('lps.pdc.maestro', 'OT'), 'OT administra el maestro (Compras clasifica equipos).');
foreach (['R', 'DCV', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    $assert(!$rbac->can('lps.pdc.maestro', $rol), "{$rol} NO administra el maestro.");
}
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
