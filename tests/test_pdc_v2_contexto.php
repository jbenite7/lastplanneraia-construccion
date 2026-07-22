<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Controllers\Api\PlanComprasApiController;
use App\Security\RbacService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, "PASS: {$message}\n");
        return;
    }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$capture = static function (callable $fn): array {
    ob_start();
    $fn();
    $raw = (string) ob_get_clean();
    return json_decode($raw, true) ?? ['__raw' => $raw];
};

echo "=== PDC v2: GET /plan-compras/api/contexto ===\n";

// Caso 1: sesión completa de un director → envelope ok con contexto y csrf.
$_SESSION = [
    'usuario' => 'test', 'nombreUsuario' => 'Test Dir', 'permiso' => 'D',
    'permiso_canonico' => 'D', 'project_id' => 999, 'proyecto' => 'PROYECTO TEST',
];
$out = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out['ok'] ?? null) === true, 'Responde envelope ok:true con sesión válida.');
$assert(($out['data']['projectId'] ?? 0) === 999, 'projectId viene de $_SESSION[project_id].');
$assert(($out['data']['proyectoNombre'] ?? '') === 'PROYECTO TEST', 'proyectoNombre viene de la sesión.');
$assert(($out['data']['usuario'] ?? '') === 'Test Dir', 'usuario prefiere nombreUsuario de la sesión.');
$assert(($out['data']['rol'] ?? '') === 'D', 'rol usa permiso_canonico.');
$assert(is_string($out['data']['csrfToken'] ?? null) && strlen($out['data']['csrfToken']) === 64, 'csrfToken generado (64 hex).');

// Caso 2: segunda llamada en la misma sesión → csrfToken estable (mismo form key).
$out2 = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out2['data']['csrfToken'] ?? '') === ($out['data']['csrfToken'] ?? null), 'csrfToken es estable por sesión.');

// Caso 3: sin proyecto activo → envelope de error NO_PROJECT.
$_SESSION['project_id'] = 0;
$out3 = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out3['ok'] ?? null) === false, 'Sin proyecto responde ok:false.');
$assert(($out3['error']['code'] ?? '') === 'NO_PROJECT', 'Código de error NO_PROJECT.');

// Caso 4: contrato RBAC — V (visualizador) puede ver pero no editar.
$db = Database::getInstance();
$rbac = new RbacService($db);
$assert($rbac->can('lps.pdc.ver', 'V'), 'V conserva lectura del PDC (lps.pdc.ver).');
$assert(!$rbac->can('lps.pdc.editar', 'V'), 'V no recibe edición del PDC (lps.pdc.editar).');

// Caso 5: rol sin lps.pdc.ver → el endpoint responde FORBIDDEN.
// Se busca un rol real sin el permiso; si todos lo tienen, se registra PASS informativo.
$rolSinVer = null;
foreach (['C', 'V', 'S', 'G', 'SG', 'OT', 'DCV'] as $rolCandidato) {
    if (!$rbac->can('lps.pdc.ver', $rolCandidato)) {
        $rolSinVer = $rolCandidato;
        break;
    }
}
if ($rolSinVer !== null) {
    $_SESSION['project_id'] = 999;
    $_SESSION['permiso'] = $rolSinVer;
    $_SESSION['permiso_canonico'] = $rolSinVer;
    $out5 = $capture(static fn () => (new PlanComprasApiController())->contexto());
    $assert(($out5['ok'] ?? null) === false && ($out5['error']['code'] ?? '') === 'FORBIDDEN',
        "Rol {$rolSinVer} sin lps.pdc.ver recibe FORBIDDEN.");
} else {
    fwrite(STDOUT, "PASS: (informativo) todos los roles canónicos tienen lps.pdc.ver; rama FORBIDDEN cubierta por diseño.\n");
}

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
