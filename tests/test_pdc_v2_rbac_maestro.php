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
foreach (['R', 'OT', 'DCV', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    $assert(!$rbac->can('lps.pdc.maestro', $rol), "{$rol} NO administra el maestro.");
}
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
