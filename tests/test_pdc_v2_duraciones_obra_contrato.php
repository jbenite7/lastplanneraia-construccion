<?php
// tests/test_pdc_v2_duraciones_obra_contrato.php — contrato de las duraciones por obra.
declare(strict_types=1);
// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$rutas = file_get_contents(__DIR__ . '/../public/index.php');
$assert(str_contains($rutas, '/plan-compras/api/plan/duraciones/obra'),
    'Las rutas de duraciones por obra están registradas.');
$assert(substr_count($rutas, '/plan-compras/api/plan/duraciones/obra') >= 2,
    'Están registrados los dos verbos: guardar y restablecer.');

$ctrl = file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasPlanController.php');
$assert(str_contains($ctrl, "can('lps.paquetes_contratacion.editar')"),
    'El guard de la excepción de obra exige .editar, no .reglas.');
$assert(str_contains($ctrl, 'guardEditarObra'),
    'Existe un guard propio para la excepción de obra.');
$assert(substr_count($ctrl, "'plan_compras_v2'") >= 2,
    'Las mutaciones nuevas validan CSRF del ámbito plan_compras_v2.');
$assert(str_contains($ctrl, 'DURACION_NO_DISPONIBLE'),
    'Se conserva el 403 cuando la fila no la usa esta obra.');
$assert(!str_contains($ctrl, "\$_POST['project_id']") && !str_contains($ctrl, "\$body['projectId']"),
    'El proyecto sale de la sesión y nunca del cliente.');

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
