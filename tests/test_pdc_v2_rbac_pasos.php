<?php
// tests/test_pdc_v2_rbac_pasos.php — A4.1: rutas y permisos de la configuración de pasos.
declare(strict_types=1);

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

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
