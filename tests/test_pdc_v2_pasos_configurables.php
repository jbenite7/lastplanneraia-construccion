<?php
// tests/test_pdc_v2_pasos_configurables.php — A4.1: pasos configurables, sobre MySQL real.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PasosContratacionService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999905; // proyecto de pruebas propio de A4.1
$svc = new PasosContratacionService($db);

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$P]);
};
$limpiar();

// ── El catálogo y la constante de código no pueden divergir ──────────────────
echo "=== catálogo global ===\n";
$cat = $svc->catalogo();
$porClave = [];
foreach ($cat as $p) { $porClave[$p['clave']] = $p; }
$assert(count($cat) >= 9, 'El catálogo tiene al menos los 9 pasos sembrados. Dio ' . count($cat));
foreach (PlanFechasService::PASOS as $i => $p) {
    $c = $porClave[$p['clave']] ?? null;
    $assert($c !== null && $c['colLegacy'] === $p['col'],
        "El catálogo y PASOS coinciden en la columna legacy de «{$p['paso']}».");
    $assert($c !== null && abs((float) $c['peso'] - PlanFechasService::PESOS_REPARTO[$i]) < 0.000001,
        "El catálogo y PESOS_REPARTO coinciden en el peso de «{$p['paso']}».");
}
// `?? 'x'` NO sirve para comprobar un null: el operador trata «existe y vale null» igual que
// «no existe», así que la comprobación se caía sola justo en el caso que quería verificar.
$assert(isset($porClave['aprobacion_cliente']) && $porClave['aprobacion_cliente']['colLegacy'] === null,
    'Aprobación del cliente no tiene columna legacy: usa días fijos.');
$assert(($porClave['licify']['diasSugeridos'] ?? 0) === 1, 'Licify sugiere 1 día, como dice el histórico.');

// ── Sin configuración: los siete de siempre ─────────────────────────────────
echo "=== proceso por defecto ===\n";
$def = $svc->deProyecto($P);
$assert(!$svc->configurado($P), 'Un proyecto sin filas no está configurado.');
$assert(count($def) === 7, 'Sin configuración devuelve los siete pasos. Dio ' . count($def));
$assert(array_column($def, 'clave') === array_column(PlanFechasService::PASOS, 'clave'),
    'Y en el mismo orden que la constante de código.');
$assert($def[0]['pasoId'] !== null, 'Cada paso por defecto resuelve su id del catálogo.');

// ── Guardar una configuración ───────────────────────────────────────────────
echo "=== guardar ===\n";
$r = $svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'],
    ['clave' => 'entrega_pliegos', 'alias' => 'Envío de pliegos'],
    ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'],
    ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'legalizacion'],
    ['clave' => 'fabricacion'],
    ['clave' => 'insumos_obra'],
], 'test-a41');
$assert(($r['ok'] ?? false) === true, 'Guardar una lista de ocho pasos.');
$cfg = $svc->deProyecto($P);
$assert(count($cfg) === 8, 'La obra ahora tiene ocho pasos. Dio ' . count($cfg));
$assert($cfg[4]['clave'] === 'aprobacion_cliente' && $cfg[4]['diasFijos'] === 15,
    'Aprobación del cliente quedó en la quinta posición con sus 15 días.');
$assert($cfg[1]['nombre'] === 'Envío de pliegos', 'El alias de la obra manda en el nombre.');
$assert($svc->configurado($P), 'Ahora sí está configurado.');

// ── Validaciones ────────────────────────────────────────────────────────────
echo "=== validaciones ===\n";
$sinDias = $svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente']], 'test-a41');
$assert(($sinDias['ok'] ?? true) === false && ($sinDias['code'] ?? '') === 'DIAS_FIJOS_REQUERIDOS',
    'Un paso sin columna legacy exige días fijos.');
$vacia = $svc->guardar($P, [], 'test-a41');
$assert(($vacia['ok'] ?? true) === false && ($vacia['code'] ?? '') === 'SIN_PASOS',
    'Una obra no puede quedarse sin ningún paso.');
$repetida = $svc->guardar($P, [['clave' => 'legalizacion'], ['clave' => 'legalizacion']], 'test-a41');
$assert(($repetida['ok'] ?? true) === false && ($repetida['code'] ?? '') === 'PASO_REPETIDO',
    'Un paso no puede aparecer dos veces.');
$inventada = $svc->guardar($P, [['clave' => 'no_existe_este_paso']], 'test-a41');
$assert(($inventada['ok'] ?? true) === false && ($inventada['code'] ?? '') === 'PASO_DESCONOCIDO',
    'Solo se aceptan claves del catálogo activo.');
$assert(count($svc->deProyecto($P)) === 8, 'Ninguna validación fallida dejó la configuración a medias.');

// ── Restablecer ─────────────────────────────────────────────────────────────
echo "=== restablecer ===\n";
$svc->restablecer($P);
$assert(!$svc->configurado($P) && count($svc->deProyecto($P)) === 7,
    'Restablecer devuelve la obra al proceso por defecto.');

$limpiar();
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
