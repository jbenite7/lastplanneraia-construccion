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
        // Guardarraiz anadido en Task 3 paso 3 (rol B, tras la correccion de alcance semanal de
        // MetricScope, commit def23b0b): `scorecardPS()` calcula
        // `round(count(PAC==1)/$total*100)` con `$r['PAC'] ?? 0` -- si NINGUN compromiso activo de
        // la semana tiene PAC registrado, coalesce a 0 y publica "0%" para no dejar la tarjeta del
        // dashboard vacia. Eso es un default de PRESENTACION aplicado despues del calculo, no lo
        // que "el SQL viejo" (la frase exacta del brief) realmente computa: la consulta cruda con
        // la misma poblacion de `fetchSemanal()` (`Activa IN ('1','NA')`) da `SUM(PAC=1) = NULL`
        // en ese caso (confirmado corriendo la consulta a mano), igual que el `numerador` que
        // calcula `MetricExecutor` -- SQL, no PHP, es quien decide que "sin PAC registrado" es
        // indeterminado, no cero. Se reproduce esa guarda aqui, sin tocar ControlTowerService.php,
        // para comparar la SEMANTICA del SQL viejo, no el default de UI que se le pego encima.
        $db = \Database::getInstance();
        $raw = $db->query(
            "SELECT SUM(PAC=1) AS numerador FROM programacion_semanal WHERE project_id = ? AND Semana = ? AND Activa IN ('1','NA')",
            [$projectId, $semana],
        )->fetch();
        if (($raw['numerador'] ?? null) === null) {
            return null;
        }

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
    'pi_hard_restrictions_ready_rate' => static function (int $projectId, string $semana, ControlTowerService $ct): ?float {
        // Task 3 paso 5, batch 1 (rol B, 2026-08-26). No existe un metodo dedicado en
        // ControlTowerService que publique esta razon como KPI aislado -- `fetchOverview()` trae
        // conteos crudos (`activities_can_do_count`, `hard_restriction_blocked_count`) pero sin el
        // filtro `Ejecutado<1` que si declara el catalogo, asi que no son directamente el mismo
        // universo. El camino viejo mas fiel es reproducir la MISMA expresion que ya usa
        // `bi_pg_semana` (verificada identica letra por letra contra
        // `ControlTowerService::programaGeneralDirectSelect()`) corriendola contra la tabla base
        // `programa_consolidado`, con los filtros exactos del catalogo -- asi la comparacion prueba
        // que la VISTA calza con la tabla, no que dos ejecuciones del mismo SQL calzan consigo
        // mismas.
        $db = \Database::getInstance();
        $row = $db->query(
            "SELECT
                SUM(
                    CASE
                        WHEN CAST(COALESCE(D_y_E, '0') AS DECIMAL(10,2)) >= 1.0
                         AND CAST(COALESCE(Materiales, '0') AS DECIMAL(10,2)) >= 1.0
                         AND CAST(COALESCE(MdeO, '0') AS DECIMAL(10,2)) >= 1.0
                         AND CAST(COALESCE(Equipos, '0') AS DECIMAL(10,2)) >= 1.0
                         AND CAST(COALESCE(Predecesora, '0') AS DECIMAL(10,2)) >= 0.5
                        THEN 1 ELSE 0
                    END
                ) AS numerador,
                COUNT(*) AS denominador
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ?
               AND COALESCE(Titulo, 0) = 0
               AND Semanas_Inicio BETWEEN 0 AND 6
               AND Ejecutado < 1",
            [$projectId, $semana],
        )->fetch();

        $denominador = (int) ($row['denominador'] ?? 0);
        if ($denominador === 0 || ($row['numerador'] ?? null) === null) {
            return null;
        }

        return ((float) $row['numerador']) / $denominador;
    },
    'pg_radar_desempeno' => static function (int $projectId, string $semana, ControlTowerService $ct): ?float {
        // Task 3 paso 5, tanda 2 (rol B, 2026-08-26). Camino viejo = metodo publico REAL de
        // produccion (`getProgramaRadarDetail()`, invocado en vivo por
        // BiControlTowerApiController.php:144), no una reconstruccion propia de SQL: se corre el
        // eje 'desempeno' del radar y se leen `numerator`/`denominator` de
        // `programaRadarAxis()` -- valores crudos SIN el redondeo a 1 decimal de `raw_value` ni
        // el umbral "minimo 3 muestras" que solo gobierna si se MUESTRA el dato (ver
        // known_limitations en el catalogo), no si el ratio es valido.
        $detail = $ct->getProgramaRadarDetail([$projectId], $semana, [], 'desempeno');
        $denominador = (int) ($detail['summary']['denominator'] ?? 0);
        if ($denominador === 0) {
            return null;
        }

        // 'numerator' viene redondeado a 4 decimales (programaRadarAxis()), pero para PAC (que
        // solo toma 0 o 1) la suma siempre es un entero exacto -- ese redondeo no pierde precision
        // aqui, a diferencia de 'productividad' (P_Completado es continuo).
        return ((float) $detail['summary']['numerator']) / $denominador;
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

    // ps_weekly_fulfillment: scorecardPS() publica PAC como entero redondeado
    // (`round(ratio*100)`) antes de mostrarlo en el dashboard; MetricExecutor devuelve el ratio
    // sin redondear. El error maximo posible de un redondeo a entero es medio punto porcentual
    // (0.5/100 = 0.005) -- no es un numero arbitrario para forzar el verde, es la cota matematica
    // del propio `round()` que ya usa la produccion. Confirmado contra datos reales de la obra 65
    // (2026-08-26): deltas observados de 0.00091 a 0.0025, todos por debajo de la cota.
    'ps_weekly_fulfillment' => 0.005,
];

/**
 * Metodo de ControlTowerService que debe haber desaparecido cuando la metrica pasa a `ejecutable`.
 *
 * @var array<string, string>
 */
$oldMethodByMetric = [];

/**
 * Motivo documentado para las metricas `ejecutable` cuyo metodo viejo NO se borra a proposito,
 * porque `ControlTowerService` lo sigue usando para OTRAS metricas sin entrada en el catalogo
 * todavia. Ruling del controlador (Task 3, cierre del paso 4, 2026-08-26): una metrica puede
 * quedar `ejecutable` -- MetricExecutor es su fuente de verdad -- sin que su metodo viejo
 * desaparezca, siempre que el motivo quede anotado aqui y verificable, no oculto. El trinquete de
 * borrado salta la metrica con un PASS informativo en vez de exigir la ausencia del metodo.
 *
 * @var array<string, string>
 */
$oldMethodRetainedByMetric = [
    'ps_weekly_fulfillment' => "ControlTowerService::scorecardPS() calcula 4 KPIs de una sola "
        . 'pasada (Compromisos activos, PAC, En riesgo, CNC esta semana); solo PAC mapea a '
        . 'ps_weekly_fulfillment. Los otros 3 no tienen entrada en el catalogo y scorecardPS() es '
        . "su UNICA fuente para el reporte 'semanal' en vivo (BiControlTowerApiController.php:196, "
        . "detras de 'Programacion Semanal'). Borrar el metodo entero habria roto esos 3 KPIs en "
        . 'produccion. MetricExecutor es la fuente de verdad para PAC desde ahora; scorecardPS() '
        . 'se conserva para los otros 3 hasta que tengan su propia metrica catalogada.',
    'pi_hard_restrictions_ready_rate' => 'No existe un metodo dedicado que publicara esta razon '
        . "como KPI aislado -- nunca se mostro como % en produccion, solo como dos conteos crudos "
        . "(activities_can_do_count, hard_restriction_blocked_count) dentro de fetchOverview(), sin "
        . 'el filtro Ejecutado<1 que si exige el catalogo, asi que ni siquiera es el mismo universo. '
        . 'ControlTowerService::programaGeneralDirectSelect() (la fuente real de hard_restrictions_'
        . "ready) se conserva porque alimenta TODO el reporte 'programa-general' -- lista de "
        . 'actividades, radares, conteos CNP/CNC -- no solo esta metrica. MetricExecutor es la '
        . 'fuente de verdad para esta razon desde ahora; no hay SQL dedicado que borrar.',
    'pg_radar_desempeno' => "ControlTowerService::programaRadar() calcula los 3 ejes del radar "
        . "('productividad', 'eficiencia', 'desempeno') en una sola pasada sobre la poblacion; solo "
        . "'desempeno' mapea a pg_radar_desempeno. 'productividad' necesita capar cada fila a maximo "
        . '1.0 antes de sumar (MIN(P_Completado,1)) y "eficiencia" necesita promediar un ratio POR '
        . 'FILA (Ejecutado_Real/Compromiso) -- ninguna de las dos operaciones es expresable en la '
        . 'gramatica de MetricExecutor (solo sabe SUM(columna_simple)/COUNT(*)), asi que quedan sin '
        . 'entrada ejecutable todavia (hallazgo estructural, Task 3 tanda 2, documentado en '
        . "known_limitations del catalogo). getProgramaRadarDetail()/programaRadar() son ademas la "
        . "UNICA fuente del endpoint en vivo 'programa-general-radar-detail' "
        . '(BiControlTowerApiController.php:144). Borrar el metodo habria roto esos 2 ejes en '
        . 'produccion. MetricExecutor es la fuente de verdad para "desempeno" desde ahora; '
        . 'programaRadar() se conserva para los otros 2 ejes hasta que tengan su propia metrica '
        . 'catalogada y ejecutable.',
];

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
                // MetricScope::week() (fix de Task 2, commit def23b0b) acota MetricExecutor a
                // "Semana = ?". Se pasa la semana del propio bucle: cada metrica en_paridad de
                // grain semanal se compara semana a semana, y su cutoff_policy de catalogo dice
                // "Semana seleccionada; no se infiere con fecha del servidor" -- no hay rango de
                // fechas que resolver aqui, la semana pedida ES el corte.
                $scope = new MetricScope([$projectId], week: $semana);
                $newValue = $executor->execute($metricKey, $scope)->value();
            } catch (\Throwable $e) {
                paridadFail("{$metricKey} [obra {$projectId}, semana {$semana}]: camino nuevo lanzo excepcion: {$e->getMessage()}");
                continue;
            }

            if ($oldValue === null && $newValue === null) {
                // Ruling del controlador (Bitacora del plan, entrada 5): los dos caminos
                // coincidiendo en "sin dato" (p.ej. una semana que arranco ayer y no tiene ningun
                // PAC registrado todavia) es acuerdo, no discrepancia. Se cuenta como paridad y se
                // anota aparte -- mensaje distinto al de la comparacion numerica de abajo -- para
                // no mezclar "concordamos en que no hay dato" con "los valores calzan".
                paridadPass(sprintf(
                    '%s [obra %d, semana %s]: sin datos, ambos caminos concuerdan (viejo=null, nuevo=null)',
                    $metricKey,
                    $projectId,
                    $semana,
                ));
                continue;
            }

            if ($oldValue === null || $newValue === null) {
                // Caso asimetrico: un lado tiene valor y el otro no. Aqui SI hay riesgo real de
                // discrepancia oculta (uno de los dos caminos esta viendo datos que el otro no ve),
                // asi que sigue siendo fallo fuerte.
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
    $retentionReason = $oldMethodRetainedByMetric[$metricKey] ?? null;

    if ($oldMethod === null && $retentionReason === null) {
        paridadFail("{$metricKey}: esta ejecutable pero no tiene entrada en \$oldMethodByMetric ni en \$oldMethodRetainedByMetric — no se puede verificar que el SQL viejo se haya borrado (o que su conservacion este documentada)");
        continue;
    }

    if ($retentionReason !== null) {
        paridadPass("{$metricKey}: SQL viejo se conserva a proposito (no bloquea 'ejecutable') — {$retentionReason}");
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
