<?php

declare(strict_types=1);
// @requiere: puro

/**
 * «Críticas atrasadas» debe estar en OK cuando vale 0 y en «Alto riesgo» cuando vale más de 0, en los tres
 * scorecards que lo muestran (overview, programa-general y curva-s).
 *
 * Por qué existe: `ControlTowerService::kpi()` deriva el estado del TEXTO de la acción, y este indicador
 * llevaba la acción fija «Escalar», así que salía «Alto riesgo» siempre, incluso con 0. Medido el 2026-09-28
 * en 25 proyectos con datos: los 15 con 0 críticas atrasadas y los 10 con más de 0 decían «Alto riesgo».
 * Regla decidida por Felipe el 2026-09-28: 0 = OK; más de 0 = Alto riesgo.
 *
 * No abre la base: los métodos del scorecard son puros, así que se instancia sin constructor.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$bi = (new ReflectionClass(ControlTowerService::class))->newInstanceWithoutConstructor();
$failures = [];

/** @return array{status: string, value: mixed, action: mixed}|null */
function criticasAtrasadas(object $bi, string $metodo, array $data): ?array
{
    $method = new ReflectionMethod($bi, $metodo);
    foreach ($method->invoke($bi, $data) as $kpi) {
        if ($kpi['kpi'] === 'Críticas atrasadas') {
            return ['status' => $kpi['status'], 'value' => $kpi['value'], 'action' => $kpi['action']];
        }
    }

    return null;
}

$casos = [
    // método, datos con 0 críticas atrasadas, datos con 2
    'scorecardOverview' => [[['critical_late_count' => 0]], [['critical_late_count' => 2]]],
    'scorecardPG' => [
        [['is_critical_late' => 0, 'duration_days' => 3]],
        [['is_critical_late' => 1, 'duration_days' => 3], ['is_critical_late' => 1, 'duration_days' => 5]],
    ],
    'scorecardCurvaS' => [[['critical_late' => 0]], [['critical_late' => 2]]],
];

foreach ($casos as $metodo => [$sinCriticas, $conCriticas]) {
    $cero = criticasAtrasadas($bi, $metodo, $sinCriticas);
    $dos = criticasAtrasadas($bi, $metodo, $conCriticas);

    if ($cero === null || $dos === null) {
        $failures[] = "{$metodo}: no devuelve el indicador «Críticas atrasadas»";
        continue;
    }
    if ($cero['value'] !== 0) {
        $failures[] = "{$metodo}: con 0 críticas el valor debe ser 0, salió " . json_encode($cero['value']);
    }
    if ($cero['status'] !== 'OK') {
        $failures[] = "{$metodo}: con 0 críticas atrasadas el estado debe ser OK, salió «{$cero['status']}»";
    }
    if ($cero['action'] !== null) {
        $failures[] = "{$metodo}: con 0 críticas no debe pedir acción, salió " . json_encode($cero['action']);
    }
    if ($dos['value'] !== 2) {
        $failures[] = "{$metodo}: con 2 críticas el valor debe ser 2, salió " . json_encode($dos['value']);
    }
    if ($dos['status'] !== 'Alto riesgo') {
        $failures[] = "{$metodo}: con 2 críticas atrasadas el estado debe ser Alto riesgo, salió «{$dos['status']}»";
    }
    if ($dos['action'] !== 'Escalar') {
        $failures[] = "{$metodo}: con 2 críticas la acción debe ser Escalar, salió " . json_encode($dos['action']);
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: «Críticas atrasadas» sale OK con 0 y Alto riesgo con más de 0 en overview, programa-general y curva-s\n";
