<?php
// tests/test_pdc_v2_rbac_pasos.php — A4.1: rutas y permisos de la configuración de pasos.
declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

echo "=== rutas ===\n";
$rutas = (string) file_get_contents(__DIR__ . '/../public/index.php');
$assert(str_contains($rutas, "\$router->get('/plan-compras/api/plan/pasos'"), 'La ruta GET de pasos está registrada.');
$assert(str_contains($rutas, "\$router->post('/plan-compras/api/plan/pasos'"), 'La ruta POST de pasos está registrada.');
$assert(str_contains($rutas, "\$router->post('/plan-compras/api/plan/pasos/restablecer'"), 'La ruta POST de restablecer está registrada.');
$assert(strpos($rutas, '/plan-compras/api/plan/pasos/restablecer') < strpos($rutas, "\$router->post('/plan-compras/api/plan/pasos',"),
    'La ruta sufijada va antes que la desnuda, como el resto del bloque.');

echo "=== permisos ===\n";
$ctrl = (string) file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasPlanController.php');
$assert(str_contains($ctrl, 'lps.paquetes_contratacion.reglas'),
    'Escribir pasos exige el permiso de reglas, no el de editar.');
$assert(substr_count($ctrl, 'guardReglas()') >= 3,
    'Los dos POST de pasos y la definición usan guardReglas(). Encontrados: ' . substr_count($ctrl, 'guardReglas()'));
// El GET tiene que seguir siendo de lectura: si pidiera el permiso de reglas, quien solo consulta el
// plan no podría ni ver qué pasos tiene su obra.
$assert(preg_match('/function pasos\(\): void\s*\{\s*\$projectId = \$this->guardLectura\(\)/', $ctrl) === 1,
    'El GET de pasos usa el guard de lectura, no el de reglas.');

$db = Database::getInstance();
$permiso = (int) $db->query(
    'SELECT COUNT(*) FROM rbac_permissions WHERE permission_key = ?',
    ['lps.paquetes_contratacion.reglas'],
)->fetchColumn();
$assert($permiso === 1, 'El permiso lps.paquetes_contratacion.reglas existe en la BD. Dio ' . $permiso);

$roles = $db->query(
    'SELECT role_code FROM rbac_role_permissions WHERE permission_key = ? AND allowed = 1 ORDER BY role_code',
    ['lps.paquetes_contratacion.reglas'],
)->fetchAll(PDO::FETCH_COLUMN);
$assert(count($roles) > 0, 'Y hay al menos un rol que lo tiene: ' . implode(', ', $roles));

// ── A4.1 · diferido nº 2 — copiar la configuración entre obras ───────────────
$rutas = (string) file_get_contents(__DIR__ . '/../public/index.php');
foreach (['pasos/origenes', 'pasos/copia-preview', 'pasos/copiar'] as $ruta) {
    $assert(str_contains($rutas, "/plan-compras/api/plan/{$ruta}"), "La ruta {$ruta} está registrada.");
}
// Copiar reemplaza el proceso de la obra entera: mismo permiso que configurarlo a mano.
foreach (['origenesPasos', 'previewCopiaPasos', 'copiarPasos'] as $fn) {
    $cuerpo = substr($ctrl, (int) strpos($ctrl, "public function {$fn}"));
    $cuerpo = substr($cuerpo, 0, (int) strpos($cuerpo, "\n    /**", 10));
    $assert(str_contains($cuerpo, 'guardReglas'), "{$fn}() exige el permiso de reglas.");
}
// Los dos GET no pueden exigir CSRF: el cliente solo adjunta el token en POST, y pedirlo dejaría la
// pantalla sin poder leer de qué obras se puede copiar (mismo tropiezo ya visto en B2).
$copiarFn = substr($ctrl, (int) strpos($ctrl, 'public function copiarPasos'));
$copiarFn = substr($copiarFn, 0, (int) strpos($copiarFn, "\n    /**", 10));
$assert(str_contains($copiarFn, '$this->guardReglas()'),
    'copiarPasos() sí exige CSRF: escribe la configuración de la obra.');

// ── A4.1 · diferido nº 4 — duraciones del catálogo editables ─────────────────
// Son de la EMPRESA: cambiar un número mueve las fechas de todas las obras que usen esa fila, así
// que van con el permiso de reglas, no con el de editar el plan.
$assert(str_contains($rutas, '/plan-compras/api/plan/duraciones'), 'La ruta de duraciones está registrada.');
$durFn = substr($ctrl, (int) strpos($ctrl, 'public function guardarDuracion'));
$durFn = substr($durFn, 0, (int) strpos($durFn, "\n    /**", 10));
$assert(str_contains($durFn, '$this->guardReglas()'),
    'guardarDuracion() exige el permiso de reglas y CSRF.');
$assert(str_contains($durFn, 'DURACION_NO_DISPONIBLE'),
    'Y revalida que la fila sea una de las que esta obra usa: el id llega del cliente.');

// Rol permitido y rol denegado, como exige AGENTS.md para toda ruta protegida nueva.
$rbac = new App\Security\RbacService($db);
$assert($rbac->can('lps.paquetes_contratacion.reglas', 'D'), 'Rol permitido: D puede copiar la configuración.');
$assert(!$rbac->can('lps.paquetes_contratacion.reglas', 'V'), 'Rol denegado: V no puede copiar la configuración.');
$assert(!$rbac->can('lps.paquetes_contratacion.reglas', 'R'), 'Rol denegado: R tampoco, aunque vea el plan.');

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
