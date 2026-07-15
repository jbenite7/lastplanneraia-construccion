<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;
use App\Security\RbacService;

$db = Database::getInstance();
$viewerPermissions = RbacCatalog::fallbackPermissionsByRole()['V'] ?? [];
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, "PASS: {$message}\n");
        return;
    }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$assert(in_array('lps.contratos.ver', $viewerPermissions, true), 'V conserva consulta de Contratos.');
$assert(!in_array('lps.contratos.editar', $viewerPermissions, true), 'V no recibe edicion de Contratos.');
$assert(!in_array('lps.contratos.auto_definir', $viewerPermissions, true), 'V no recibe auto-definicion de Contratos.');
$assert(!(new RbacService($db))->can('lps.contratos.editar', 'V'), 'RBAC rechaza edicion de Contratos para V.');
$assert(!(new RbacService($db))->can('lps.contratos.auto_definir', 'V'), 'RBAC rechaza auto-definicion de Contratos para V.');

exit($failures === [] ? 0 : 1);
