<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Dos reglas del semáforo de BI decididas por Felipe el 2026-09-28:
 *
 * 1. Indicadores de CONTEO de cosas malas (compromisos en riesgo, PDC en riesgo, restricciones no listas,
 *    contratistas en alerta; «Bloqueadas» sigue el porcentaje: test_bi_bloqueadas_porcentaje.php): 0 = verde (estado OK, sin acción); 1 o más = amarillo (estado
 *    «Medio», con su acción). Solo «Críticas atrasadas» sube a rojo (ya cubierto por
 *    test_bi_criticas_atrasadas_estado.php).
 * 2. Un «Medio» CALCULADO se ve amarillo: la desviación del plan entre 0 y −5 pp, el PAC entre 60% y 80%
 *    y la desviación de la curva S entre 0 y −5%.
 *
 * Por qué existe: `ControlTowerService::kpi()` deriva el estado del TEXTO de la acción. Las acciones fijas
 * «Liberar», «Intervenir» y «Revisar compras/Revisar» daban estado fijo sin mirar el valor, y la acción
 * «Medio» caía en «OK», así que el amarillo calculado nunca se veía. Verificado ejecutando kpi() el
 * 2026-09-28.
 *
 * No abre la base: los métodos del scorecard son puros, así que se instancia sin constructor.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$bi = (new ReflectionClass(ControlTowerService::class))->newInstanceWithoutConstructor();
$failures = [];

function kpiDe(object $bi, string $metodo, array $data, string $nombre): ?array
{
    foreach ((new ReflectionMethod($bi, $metodo))->invoke($bi, $data) as $kpi) {
        if ($kpi['kpi'] === $nombre) {
            return $kpi;
        }
    }

    return null;
}

function esperar(array &$failures, string $etiqueta, ?array $kpi, string $estado, ?bool $conAccion): void
{
    if ($kpi === null) {
        $failures[] = "{$etiqueta}: el indicador no existe en el scorecard";

        return;
    }
    if ($kpi['status'] !== $estado) {
        $failures[] = "{$etiqueta}: el estado debe ser «{$estado}», salió «{$kpi['status']}»";
    }
    if ($conAccion === true && ($kpi['action'] === null || $kpi['action'] === '')) {
        $failures[] = "{$etiqueta}: con valor mayor que 0 debe llevar su acción, salió vacía";
    }
    if ($conAccion === false && $kpi['action'] !== null) {
        $failures[] = "{$etiqueta}: con valor 0 no debe pedir acción, salió " . json_encode($kpi['action']);
    }
}

// --- Regla 1: indicadores de conteo -------------------------------------------------------------
$conteoOverview = [
    'Compromisos en riesgo' => 'weekly_commitments_at_risk_count',
    'PDC en riesgo' => 'pdc_at_risk_count',
    'Contratistas en alerta' => 'contractors_at_risk_count',
];
foreach ($conteoOverview as $nombre => $clave) {
    esperar($failures, "overview · {$nombre} = 0", kpiDe($bi, 'scorecardOverview', [[$clave => 0]], $nombre), 'OK', false);
    esperar($failures, "overview · {$nombre} = 3", kpiDe($bi, 'scorecardOverview', [[$clave => 3]], $nombre), 'Medio', true);
}

$sinListas = fn(int $n): array => array_map(fn() => ['is_hard' => 1, 'is_ready' => 0], range(1, max($n, 1)));
$ninguna = [['is_hard' => 1, 'is_ready' => 1]];
esperar($failures, 'PI · Restricciones no listas = 0', kpiDe($bi, 'scorecardPI', $ninguna, 'Restricciones no listas'), 'OK', false);
esperar($failures, 'PI · Restricciones no listas = 3', kpiDe($bi, 'scorecardPI', $sinListas(3), 'Restricciones no listas'), 'Medio', true);

// --- Regla 2: el Medio calculado se ve amarillo -------------------------------------------------
$filaPG = fn(float $real, float $teorico): array => [['is_critical_late' => 0, 'duration_days' => 10, 'Ejecutado' => $real, 'theoretical_progress_by_duration' => $teorico]];
esperar($failures, 'PG · desviación 0 pp', kpiDe($bi, 'scorecardPG', $filaPG(0.50, 0.50), 'Desviación vs plan'), 'OK', null);
esperar($failures, 'PG · desviación −3 pp (Medio calculado)', kpiDe($bi, 'scorecardPG', $filaPG(0.47, 0.50), 'Desviación vs plan'), 'Medio', null);
esperar($failures, 'PG · desviación −8 pp', kpiDe($bi, 'scorecardPG', $filaPG(0.42, 0.50), 'Desviación vs plan'), 'Alto riesgo', null);

$filasPS = fn(int $conPac, int $total): array => array_map(fn($i) => ['PAC' => $i < $conPac ? 1 : 0], range(0, $total - 1));
esperar($failures, 'PS · PAC 90%', kpiDe($bi, 'scorecardPS', $filasPS(9, 10), 'PAC'), 'OK', null);
esperar($failures, 'PS · PAC 70% (Medio calculado)', kpiDe($bi, 'scorecardPS', $filasPS(7, 10), 'PAC'), 'Medio', null);
esperar($failures, 'PS · PAC 50%', kpiDe($bi, 'scorecardPS', $filasPS(5, 10), 'PAC'), 'Alto riesgo', null);

esperar($failures, 'Curva S · desviación 0', kpiDe($bi, 'scorecardCurvaS', [['pct_desviacion' => 0.0]], '% Desviación'), 'OK', null);
esperar($failures, 'Curva S · desviación −3% (Medio calculado)', kpiDe($bi, 'scorecardCurvaS', [['pct_desviacion' => -0.03]], '% Desviación'), 'Medio', null);
esperar($failures, 'Curva S · desviación −8%', kpiDe($bi, 'scorecardCurvaS', [['pct_desviacion' => -0.08]], '% Desviación'), 'Alto riesgo', null);

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: los indicadores de conteo salen OK con 0 y Medio con 1 o más, y el Medio calculado se ve amarillo\n";
