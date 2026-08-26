<?php

declare(strict_types=1);
// @requiere: db

/**
 * Test: el trinquete de paridad metrica por metrica (Task 3, Ola 1 — Torre de Control piloto).
 *
 * Cada metrica del catalogo (`MetricDictionaryService`) declara un `estado_ejecucion`:
 * `descriptiva` (nace asi, no se toca aqui), `en_paridad` (los dos caminos deben calzar) o
 * `ejecutable` (el SQL viejo ya se borro de `ControlTowerService`). Este test:
 *
 *  1. Para cada metrica `en_paridad`, corre el camino viejo (via `ControlTowerService::getBrief()`)
 *     y el camino nuevo (`MetricExecutor::execute()`) sobre semanas reales de al menos dos obras,
 *     y falla imprimiendo ambos valores, la obra y la semana ante cualquier discrepancia por
 *     encima de la tolerancia declarada para esa metrica.
 *  2. Para cada metrica `ejecutable`, verifica por reflexion que su metodo viejo en
 *     `ControlTowerService` ya no existe.
 *
 * Diseñado para pasar EN VACIO: hoy las 19 metricas del catalogo nacen `descriptiva`
 * (MetricDictionaryService Paso 1), asi que ningun bucle de comparacion corre todavia — el test
 * solo confirma que el mecanismo esta listo. Paso 3 (rol B) mueve `ps_weekly_fulfillment` a
 * `en_paridad` y es ahi cuando el primer trinquete empieza a comparar de verdad.
 *
 * --- Extensibilidad: donde se agrega cada metrica nueva ---
 *
 *  - `$oldPathResolvers[$metricKey]`: callable que calcula el valor del camino viejo. Se agrega
 *    la entrada EN EL MISMO commit que mueve la metrica a `en_paridad` (Paso 3-5) — si falta,
 *    el test falla fuerte en vez de saltarse la metrica en silencio.
 *  - `$parityToleranceByMetric[$metricKey]`: tolerancia declarada (misma unidad que
 *    `MetricResult::value()`), default 0.0. Hook pedido por el brief para Paso 5
 *    (`pg_finish_variance_days_p50`, Monte Carlo con semilla aleatoria): dos corridas legitimas
 *    no calzan bit a bit, y la tolerancia se documenta aqui, no se oculta subiendo el default global.
 *  - `$oldMethodByMetric[$metricKey]`: nombre del metodo de `ControlTowerService` que debe haber
 *    desaparecido cuando la metrica pasa a `ejecutable`. Se agrega junto con el borrado real del
 *    metodo (Paso 4-6) — nunca antes.
 *
 * --- Obras y semanas reales usadas (verificadas contra la base de dev el 2026-08-26) ---
 *
 * Da Porto (project_id 73) es la obra piloto de Ola 1, pero hoy solo trae 2 semanas reales en
 * `programacion_semanal` (proyecto recien sembrado: Semana 1 y 2, ver `semanas_activas`). Para
 * cubrir "cuatro semanas reales" sin inventar datos, la segunda obra es Metrolinea Estacion 2
 * (project_id 65): 24 semanas distintas, 1197 filas, 99.3% de captura de PAC en compromisos
 * activos — la mas densa de las candidatas revisadas (68, 65, 74, 62, 63, 70). El test no
 * hardcodea los numeros de semana: los descubre en vivo con `semanasRealesDe()`, tope 4 por obra,
 * asi que si Da Porto acumula mas semanas despues, el test las toma solas sin tocar este archivo.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricScope;
use App\Services\ControlTowerService;

const PROJECT_DA_PORTO = 73;
const PROJECT_SEGUNDA_OBRA = 65; // Metrolinea Estacion 2 — 24 semanas, 99.3% de captura de PAC.
const OBRAS_PARIDAD = [PROJECT_DA_PORTO, PROJECT_SEGUNDA_OBRA];
const SEMANAS_POR_OBRA = 4; // "cuatro semanas reales" del brief; se usan las que existan hasta este tope.

/**
 * Camino viejo por metrica: callable(int $projectId, string $semana, ControlTowerService $ct):
 * float|int|null. Debe devolver el valor en la MISMA escala que `MetricResult::value()` (p.ej.
 * ratio 0-1 para porcentajes, no 0-100), para que la resta con el camino nuevo sea comparable.
 *
 * @var array<string, callable(int, string, ControlTowerService): (float|int|null)>
 */
$oldPathResolvers = [
    'ps_weekly_fulfillment' => static function (int $projectId, string $semana, ControlTowerService $ct): ?float {
        $brief = $ct->getBrief('semanal', [$projectId], $semana, 'A');
        foreach ($brief['scorecard'] ?? [] as $kpiRow) {
            if (($kpiRow['kpi'] ?? null) === 'PAC') {
                // scorecardPS() publica un entero ya redondeado (`round($pac/$total*100)`); se
                // divide por 100 para comparar contra el ratio sin redondear de MetricExecutor.
                // Ese redondeo previo es, el mismo, una fuente esperada de discrepancia que
                // Paso 3 debe documentar al mover esta metrica a en_paridad, no ocultar subiendo
                // la tolerancia sin anotar por que.
                return ((float) $kpiRow['value']) / 100;
            }
        }

        return null;
    },
];

/**
 * Tolerancia de paridad declarada por metrica, misma unidad que `MetricResult::value()`.
 * Default 0.0 (paridad exacta) para cualquier metrica sin entrada aqui.
 *
 * @var array<string, float>
 */
$parityToleranceByMetric = [
    // 'pg_finish_variance_days_p50' => 1.5, // dias — a declarar cuando Paso 5 la mueva a en_paridad
    // (Monte Carlo con semilla aleatoria: dos corridas legitimas no calzan bit a bit).
];

/**
 * Metodo de ControlTowerService que debe haber desaparecido cuando la metrica pasa a `ejecutable`.
 * Vacio hoy: cero metricas son `ejecutable` todavia (Paso 4 no ha corrido).
 *
 * @var array<string, string>
 */
$oldMethodByMetric = [];

$passed = 0;
$failed = 0;

function paridadPass(string $message): void
{
    global $passed;
    echo "  PASS: {$message}\n";
    $passed++;
}

function paridadFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

/**
 * Hasta `$limit` semanas reales (columna `Semana`) mas recientes con filas en
 * `programacion_semanal` para el proyecto dado. Descubierto en vivo contra la base de dev, nunca
 * hardcodeado: si una obra solo tiene 2 semanas reales (como Da Porto hoy), devuelve 2.
 *
 * @return list<string>
 */
function semanasRealesDe(\Database $db, int $projectId, int $limit): array
{
    $rows = $db->query(
        'SELECT DISTINCT Semana FROM programacion_semanal WHERE project_id = ? ORDER BY Semana DESC LIMIT ' . (int) $limit,
        [$projectId],
    )->fetchAll(\PDO::FETCH_COLUMN);

    return array_map('strval', $rows);
}

echo "=== Test de paridad metrica por metrica (Task 3, Ola 1 — Torre de Control) ===\n\n";

$db = \Database::getInstance();
$dictionary = new MetricDictionaryService();
$executor = new MetricExecutor($db, $dictionary);
$controlTower = new ControlTowerService();

// Guardarraíl: las obras deben existir con datos reales de programacion. Si esto falla, el
// resto del test no puede probar nada de verdad — mejor fallar fuerte aqui que correr en
// silencio sobre cero semanas y reportar un vacuo "0 discrepancias".
$semanasPorObra = [];
foreach (OBRAS_PARIDAD as $projectId) {
    $semanas = semanasRealesDe($db, $projectId, SEMANAS_POR_OBRA);
    $semanasPorObra[$projectId] = $semanas;
    if ($semanas === []) {
        paridadFail("obra {$projectId}: no tiene semanas reales en programacion_semanal — no se puede correr paridad");
    } else {
        paridadPass("obra {$projectId}: " . count($semanas) . ' semana(s) real(es) disponibles (' . implode(', ', $semanas) . ')');
    }
}

$definitions = $dictionary->exportDictionary();
$enParidad = array_values(array_filter(
    $definitions,
    static fn (array $d): bool => ($d['estado_ejecucion'] ?? null) === 'en_paridad',
));
$ejecutables = array_values(array_filter(
    $definitions,
    static fn (array $d): bool => ($d['estado_ejecucion'] ?? null) === 'ejecutable',
));
$descriptivas = count($definitions) - count($enParidad) - count($ejecutables);

echo "\nCatalogo: " . count($definitions) . " metricas totales — "
    . "{$descriptivas} descriptiva, " . count($enParidad) . " en_paridad, " . count($ejecutables) . " ejecutable.\n\n";

// --- Trinquete 1: metricas en_paridad corren los dos caminos y no pueden discrepar (salvo tolerancia declarada) ---
foreach ($enParidad as $definition) {
    $metricKey = (string) $definition['metric_key'];
    $tolerance = (float) ($parityToleranceByMetric[$metricKey] ?? 0.0);
    $resolver = $oldPathResolvers[$metricKey] ?? null;

    if ($resolver === null) {
        paridadFail("{$metricKey}: esta en_paridad pero no tiene resolver del camino viejo en \$oldPathResolvers — no se puede verificar paridad");
        continue;
    }

    foreach (OBRAS_PARIDAD as $projectId) {
        foreach ($semanasPorObra[$projectId] as $semana) {
            try {
                $oldValue = $resolver($projectId, $semana, $controlTower);
            } catch (\Throwable $e) {
                paridadFail("{$metricKey} [obra {$projectId}, semana {$semana}]: camino viejo lanzo excepcion: {$e->getMessage()}");
                continue;
            }

            try {
                $scope = new MetricScope([$projectId]);
                $newValue = $executor->execute($metricKey, $scope)->value();
            } catch (\Throwable $e) {
                paridadFail("{$metricKey} [obra {$projectId}, semana {$semana}]: camino nuevo lanzo excepcion: {$e->getMessage()}");
                continue;
            }

            if ($oldValue === null || $newValue === null) {
                paridadFail(sprintf(
                    '%s [obra %d, semana %s]: no se puede comparar — viejo=%s, nuevo=%s',
                    $metricKey,
                    $projectId,
                    $semana,
                    $oldValue === null ? 'null' : (string) $oldValue,
                    $newValue === null ? 'null' : (string) $newValue,
                ));
                continue;
            }

            $delta = abs($oldValue - $newValue);
            if ($delta > $tolerance) {
                paridadFail(sprintf(
                    '%s [obra %d, semana %s]: DISCREPANCIA — viejo=%s, nuevo=%s, delta=%s, tolerancia=%s',
                    $metricKey,
                    $projectId,
                    $semana,
                    $oldValue,
                    $newValue,
                    $delta,
                    $tolerance,
                ));
            } else {
                paridadPass(sprintf(
                    '%s [obra %d, semana %s]: paridad OK — viejo=%s, nuevo=%s (delta=%s <= tolerancia=%s)',
                    $metricKey,
                    $projectId,
                    $semana,
                    $oldValue,
                    $newValue,
                    $delta,
                    $tolerance,
                ));
            }
        }
    }
}

if ($enParidad === []) {
    paridadPass('ninguna metrica esta en_paridad todavia — trinquete de paridad vacio por diseno (Paso 3 aun no corre)');
}

// --- Trinquete 2: metricas ejecutable ya no deben tener su SQL viejo en ControlTowerService ---
$reflection = new \ReflectionClass(ControlTowerService::class);
foreach ($ejecutables as $definition) {
    $metricKey = (string) $definition['metric_key'];
    $oldMethod = $oldMethodByMetric[$metricKey] ?? null;

    if ($oldMethod === null) {
        paridadFail("{$metricKey}: esta ejecutable pero no tiene entrada en \$oldMethodByMetric — no se puede verificar que el SQL viejo se haya borrado");
        continue;
    }

    if ($reflection->hasMethod($oldMethod)) {
        paridadFail("{$metricKey}: ControlTowerService::{$oldMethod}() todavia existe — deberia haberse borrado al pasar a ejecutable");
    } else {
        paridadPass("{$metricKey}: ControlTowerService::{$oldMethod}() ya no existe, como corresponde a ejecutable");
    }
}

if ($ejecutables === []) {
    paridadPass('ninguna metrica es ejecutable todavia — trinquete de borrado vacio por diseno (Paso 4 aun no corre)');
}

echo "\n---\nResultado: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
