<?php
// tests/test_pdc_v2_pasos_historial.php — A4.1 · diferido nº 3: quién cambió la configuración de
// pasos, cuándo y a qué. Tabla de solo anexar.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PasosContratacionService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999980;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_pasos_historial WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$P]);
};
$limpiar();

$svc = new PasosContratacionService($db);

echo "=== A4.1 · historial de la configuración de pasos ===\n";

$svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion']], 'ana');
$svc->guardar($P, [['clave' => 'elaboracion_pliegos']], 'beto');

$h = $svc->historial($P);
$assert(count($h) === 2, 'Dos guardados dejan dos entradas: ' . count($h));
$assert($h[0]['usuario'] === 'beto', 'La más reciente va primero: ' . $h[0]['usuario']);
$assert($h[1]['usuario'] === 'ana', 'Y la vieja detrás: ' . $h[1]['usuario']);
$assert(count($h[1]['pasos']) === 2, 'La entrada vieja conserva los dos pasos que tenía entonces: ' . count($h[1]['pasos']));
$assert(count($h[0]['pasos']) === 1, 'La nueva conserva el único que quedó: ' . count($h[0]['pasos']));
$assert(($h[1]['pasos'][1]['clave'] ?? '') === 'legalizacion',
    'El historial guarda QUÉ pasos había, no solo cuántos: ' . json_encode($h[1]['pasos'][1] ?? null));
$assert(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $h[0]['cuando']) === 1,
    'Y cuándo se hizo: ' . $h[0]['cuando']);

// ── Restablecer también deja rastro: volver al proceso por defecto es un cambio ──
$svc->restablecer($P, 'carla');
$h2 = $svc->historial($P);
$assert(count($h2) === 3, 'Restablecer también se registra: ' . count($h2));
$assert($h2[0]['pasos'] === [], 'Y se guarda como «sin configuración propia».');
$assert($h2[0]['usuario'] === 'carla', 'Con quién lo hizo: ' . $h2[0]['usuario']);

// ── Solo anexa: nada de lo viejo se reescribe ni se borra ───────────────────
$assert($h2[2]['usuario'] === 'ana' && count($h2[2]['pasos']) === 2,
    'La entrada más vieja sigue intacta después de dos cambios más.');

// ── Aislamiento entre obras ─────────────────────────────────────────────────
$assert($svc->historial(999981) === [], 'El historial de otra obra no se ve desde aquí.');

// ── Cero regresión: el historial no cambia lo que la obra usa ───────────────
$assert(count($svc->deProyecto($P)) === 7, 'Tras restablecer, los siete por defecto: ' . count($svc->deProyecto($P)));
$assert($svc->configurado($P) === false, 'Y la obra no figura como configurada.');

$limpiar();

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
