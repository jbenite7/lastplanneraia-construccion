<?php

declare(strict_types=1);
// @requiere: db

/**
 * Test: contrato ejecutable del semáforo de "semanas para iniciar" de Intermedia (D58, CT-8.3
 * punto 4), tras la corrección de regla de color de Felipe (Bitácora del plan, entrada 20): el
 * color NO lo da la cercanía de la franja, lo da si las restricciones duras están liberadas —
 * semana 0 en rojo cuando tiene restricciones duras pendientes (es la más urgente), verde cuando
 * ya las liberó. Eso vuelve a las 4 franjas una FRACCIÓN de listas, no un conteo puro, y por eso
 * ahora SÍ encajan en `MetricExecutor` (`SUM(expr)/COUNT(*)`, el mismo molde ya ejecutable de
 * `pi_hard_restrictions_ready_rate` y `pg_radar_desempeno`) — a diferencia del estado anterior
 * (`descriptiva`, ver `tests/unit/MetricCatalogSemaforoTest.php`), que este test reemplaza para
 * las 4 métricas del semáforo.
 *
 * Ruling ya tomado por el controlador (entrada 20 de la Bitácora, no se re-litiga aquí):
 *  - `formula` => 'SUM(hard_restrictions_ready=1) / COUNT(*)'
 *  - `execution_source` => 'bi_pg_semana'
 *  - `filters` con la franja como comparaciones simples (`Semanas_Inicio=0` para la franja 0,
 *    `Semanas_Inicio>=X` + `Semanas_Inicio<=Y` para las compuestas), más `Titulo=0` y `Ejecutado<1`
 *    (universo lookahead, igual que las metricas hermanas).
 *  - `estado_ejecucion` => 'ejecutable'
 *  - `unit` => 'porcentaje'
 *
 * Este test se ejecuta EN RED contra el catálogo de hoy (2026-08-26), que todavía declara las 4
 * como `descriptiva` con la fórmula vieja de conteo puro — debe fallar hasta que el rol B mueva el
 * catálogo al contrato de arriba.
 *
 * Datos reales usados (verificados contra la base de dev el 2026-08-26, no reinventados):
 *  - Da Porto (project_id 73), semana 2: franja 0 = 2/2, franja 1-2 = 1/4, franja 3-4 = 3/5,
 *    franja 5-6 = 1/5.
 *  - Da Porto (project_id 73), semana 1: franja 0 = 0/0 — denominador cero real, sin inventar
 *    datos sintéticos, usado como caso borde del punto 5 del brief.
 *  - Metrolinea Estación 2 (project_id 65), semanas 26 y 25: franjas con denominador > 0 en las 4,
 *    segunda obra para la paridad cruzada.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricResult;
use App\Services\Bi\MetricScope;

const SEMAFORO_CLAVES = [
    'pi_semaforo_semana_0',
    'pi_semaforo_semana_1_2',
    'pi_semaforo_semana_3_4',
    'pi_semaforo_semana_5_6',
];

/**
 * Filtros esperados por franja bajo el nuevo contrato: `Titulo=0` + `Ejecutado<1` (universo
 * lookahead) más el recorte de `Semanas_Inicio` propio de cada franja, en comparaciones simples
 * (nunca BETWEEN, `MetricExecutor::parseFilter()` no lo reconoce).
 */
const SEMAFORO_FILTROS_ESPERADOS = [
    'pi_semaforo_semana_0' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio=0'],
    'pi_semaforo_semana_1_2' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=1', 'Semanas_Inicio<=2'],
    'pi_semaforo_semana_3_4' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=3', 'Semanas_Inicio<=4'],
    'pi_semaforo_semana_5_6' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=5', 'Semanas_Inicio<=6'],
];

const SEMAFORO_FORMULA_ESPERADA = 'SUM(hard_restrictions_ready=1) / COUNT(*)';

/** Tolerancia declarada para la comparación paridad: hard_restrictions_ready es 0/1 (flag limpio,
 * verificado contra la base de dev: 4.864 filas en 1, 46.319 en 0), así que la suma nunca pierde
 * precisión de punto flotante — paridad exacta, sin margen inventado para tapar una diferencia. */
const SEMAFORO_TOLERANCIA = 0.0;

$passed = 0;
$failed = 0;

function semaforoPass(string $message): void
{
    global $passed;
    echo "  PASS: {$message}\n";
    $passed++;
}

function semaforoFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

/**
 * Oráculo independiente: cuenta listas/pendientes por franja con SQL propio, escrito sin mirar
 * `MetricExecutor::buildSelectExpression()` ni `buildWhereClause()`, para no comparar el ejecutor
 * consigo mismo.
 *
 * @return array{numerador:int, denominador:int}
 */
function semaforoOraculo(\Database $db, int $projectId, string $semana, string $condicionFranja): array
{
    $row = $db->query(
        "SELECT
            SUM(hard_restrictions_ready = 1) AS numerador,
            COUNT(*) AS denominador
         FROM bi_pg_semana
         WHERE project_id = ?
           AND Semana = ?
           AND Titulo = 0
           AND Ejecutado < 1
           AND ({$condicionFranja})",
        [$projectId, $semana],
    )->fetch();

    return [
        'numerador' => (int) ($row['numerador'] ?? 0),
        'denominador' => (int) ($row['denominador'] ?? 0),
    ];
}

/**
 * Busca EN VIVO, contra la base de dev, una combinación (obra, semana, franja) con denominador 0
 * real. Reemplaza la dependencia de un caso fijo hardcodeado en el código (antes: project_id 73,
 * semana '1'), que se pondría rojo por "falta el caso borde" si la base cambia y una actividad
 * nueva llena ese hueco -- no por una regresión real. Mismo patrón que `semanasRealesDe()` en
 * `tests/test_bi_paridad_metricas.php` (descubre en vivo, nunca hardcodea). Recorre combos
 * (project_id, Semana) reales, acotados a `$limiteCombos` más recientes, cruzados con las 4
 * franjas, y devuelve el primer hueco que encuentre o null si hoy no existe ninguno en toda la
 * base.
 *
 * @param array<string,string> $condicionFranjaPorClave
 * @return array{0:int,1:string,2:string}|null
 */
function buscarComboDenominadorCero(\Database $db, array $condicionFranjaPorClave, int $limiteCombos): ?array
{
    $rows = $db->query(
        'SELECT DISTINCT project_id, Semana FROM programacion_semanal ORDER BY Semana DESC LIMIT ' . (int) $limiteCombos,
    )->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $projectId = (int) $row['project_id'];
        $semana = (string) $row['Semana'];

        foreach ($condicionFranjaPorClave as $clave => $condicion) {
            $oraculo = semaforoOraculo($db, $projectId, $semana, $condicion);
            if ($oraculo['denominador'] === 0) {
                return [$projectId, $semana, $clave];
            }
        }
    }

    return null;
}

echo "=== Test del semáforo por franjas de Intermedia (D58, regla de color corregida) ===\n\n";

$dictionary = new MetricDictionaryService();

// --- 1. Contrato de catálogo: las 4 deben ser ejecutable, con la fórmula/fuente/unidad nuevas ---
foreach (SEMAFORO_CLAVES as $clave) {
    $def = $dictionary->getDefinition($clave);
    if ($def === []) {
        semaforoFail("{$clave}: no existe en el catálogo");
        continue;
    }

    if (($def['estado_ejecucion'] ?? null) === 'ejecutable') {
        semaforoPass("{$clave}: estado_ejecucion es 'ejecutable'");
    } else {
        semaforoFail("{$clave}: estado_ejecucion debería ser 'ejecutable', es '" . ($def['estado_ejecucion'] ?? 'null') . "'");
    }

    if (($def['execution_source'] ?? null) === 'bi_pg_semana') {
        semaforoPass("{$clave}: execution_source es 'bi_pg_semana'");
    } else {
        semaforoFail("{$clave}: execution_source debería ser 'bi_pg_semana', es '" . ($def['execution_source'] ?? 'null') . "'");
    }

    if (($def['formula'] ?? null) === SEMAFORO_FORMULA_ESPERADA) {
        semaforoPass("{$clave}: formula es '" . SEMAFORO_FORMULA_ESPERADA . "'");
    } else {
        semaforoFail("{$clave}: formula debería ser '" . SEMAFORO_FORMULA_ESPERADA . "', es '" . ($def['formula'] ?? 'null') . "'");
    }

    if (($def['unit'] ?? null) === 'porcentaje') {
        semaforoPass("{$clave}: unit es 'porcentaje'");
    } else {
        semaforoFail("{$clave}: unit debería ser 'porcentaje', es '" . ($def['unit'] ?? 'null') . "'");
    }

    $filtrosEsperados = SEMAFORO_FILTROS_ESPERADOS[$clave];
    $filtrosReales = $def['filters'] ?? null;
    if ($filtrosReales === $filtrosEsperados) {
        semaforoPass("{$clave}: filters acota la franja correcta (" . implode(', ', $filtrosEsperados) . ')');
    } else {
        semaforoFail("{$clave}: filters esperado [" . implode(', ', $filtrosEsperados) . '], real [' . implode(', ', (array) $filtrosReales) . ']');
    }
}

// --- 2. Las 4 franjas son mutuamente excluyentes y cubren exactamente 0-6, sin hueco ---
// Se derivan los límites de cada franja de los propios `filters` del catálogo (no de una tabla
// hardcodeada aparte), para que este trinquete detecte también un cambio de rango accidental.
function semaforoRangoDesdeFiltros(array $filtros): array
{
    $min = null;
    $max = null;
    foreach ($filtros as $filtro) {
        if (preg_match('/^Semanas_Inicio=(\d+)$/', (string) $filtro, $m) === 1) {
            $min = (int) $m[1];
            $max = (int) $m[1];
        } elseif (preg_match('/^Semanas_Inicio>=(\d+)$/', (string) $filtro, $m) === 1) {
            $min = (int) $m[1];
        } elseif (preg_match('/^Semanas_Inicio<=(\d+)$/', (string) $filtro, $m) === 1) {
            $max = (int) $m[1];
        }
    }

    return [$min, $max];
}

$rangos = [];
foreach (SEMAFORO_CLAVES as $clave) {
    $def = $dictionary->getDefinition($clave);
    [$min, $max] = semaforoRangoDesdeFiltros($def['filters'] ?? []);
    if ($min === null || $max === null) {
        semaforoFail("{$clave}: no se pudo derivar un rango Semanas_Inicio de sus filters");
        continue;
    }
    $rangos[$clave] = [$min, $max];
}

if (count($rangos) === count(SEMAFORO_CLAVES)) {
    $anterior = null;
    $huecoOsolape = false;
    foreach (SEMAFORO_CLAVES as $clave) {
        [$min, $max] = $rangos[$clave];
        if ($min > $max) {
            semaforoFail("{$clave}: rango invertido ({$min} > {$max})");
            $huecoOsolape = true;
        }
        if ($anterior !== null && $min !== $anterior + 1) {
            semaforoFail("{$clave}: debe empezar en " . ($anterior + 1) . " (justo tras la franja previa), empieza en {$min}");
            $huecoOsolape = true;
        }
        $anterior = $max;
    }

    if (!$huecoOsolape) {
        semaforoPass('las 4 franjas son mutuamente excluyentes y cubren 0-6 sin hueco ni solape');
    }

    if ($rangos['pi_semaforo_semana_0'][0] === 0) {
        semaforoPass('la franja 0 arranca en Semanas_Inicio=0');
    } else {
        semaforoFail("la franja 0 debería arrancar en 0, arranca en {$rangos['pi_semaforo_semana_0'][0]}");
    }

    if ($rangos['pi_semaforo_semana_5_6'][1] === 6) {
        semaforoPass('la última franja termina en Semanas_Inicio=6 (límite del lookahead)');
    } else {
        semaforoFail("la última franja debería terminar en 6, termina en {$rangos['pi_semaforo_semana_5_6'][1]}");
    }
}

// --- 3, 4 y 5: paridad real contra oráculo crudo, basis().filas_usadas poblado, y denominador cero ---
$db = \Database::getInstance();
$executor = new MetricExecutor($db, $dictionary);

const PROJECT_DA_PORTO = 73;
const PROJECT_METROLINEA = 65;

/**
 * Casos (obra, semana): 2 obras × 2 semanas con datos reales, como pide el brief, para la paridad
 * cruzada del punto 3. El caso borde de denominador cero (punto 5) NO se hardcodea aquí -- se
 * descubre en vivo más abajo con `buscarComboDenominadorCero()`, porque un combo fijo (antes:
 * project_id 73, semana '1') se pondría rojo por "falta el caso borde" si la base de dev cambia y
 * una actividad nueva llena ese hueco, en vez de por una regresión real.
 *
 * @var list<array{0:int,1:string}>
 */
$casos = [
    [PROJECT_DA_PORTO, '2'],
    [PROJECT_METROLINEA, '26'],
    [PROJECT_METROLINEA, '25'],
];

$condicionFranjaPorClave = [
    'pi_semaforo_semana_0' => 'Semanas_Inicio = 0',
    'pi_semaforo_semana_1_2' => 'Semanas_Inicio >= 1 AND Semanas_Inicio <= 2',
    'pi_semaforo_semana_3_4' => 'Semanas_Inicio >= 3 AND Semanas_Inicio <= 4',
    'pi_semaforo_semana_5_6' => 'Semanas_Inicio >= 5 AND Semanas_Inicio <= 6',
];

// El caso borde de denominador 0 (punto 5) se descubre en vivo y se agrega a $casos si no está ya
// cubierto por los combos fijos de arriba -- así el bucle de abajo lo ejerce igual que los demás.
$comboDenominadorCeroLive = buscarComboDenominadorCero($db, $condicionFranjaPorClave, 200);
if ($comboDenominadorCeroLive !== null) {
    [$projectIdLive, $semanaLive] = $comboDenominadorCeroLive;
    $yaCubierto = false;
    foreach ($casos as [$p, $s]) {
        if ($p === $projectIdLive && $s === $semanaLive) {
            $yaCubierto = true;
            break;
        }
    }

    if (!$yaCubierto) {
        $casos[] = [$projectIdLive, $semanaLive];
    }
}

$denominadorCeroVisto = false;

foreach ($casos as [$projectId, $semana]) {
    foreach (SEMAFORO_CLAVES as $clave) {
        $condicion = $condicionFranjaPorClave[$clave];
        $oraculo = semaforoOraculo($db, $projectId, $semana, $condicion);

        try {
            $scope = new MetricScope([$projectId], week: $semana);
            $result = $executor->execute($clave, $scope);
        } catch (\Throwable $e) {
            semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: MetricExecutor::execute() lanzó excepción: {$e->getMessage()}");
            continue;
        }

        $basis = $result->basis();
        $filasUsadas = $basis['filas_usadas'] ?? null;

        // --- punto 4: basis().filas_usadas poblado y coincide con el denominador del oráculo ---
        if ($filasUsadas === null) {
            semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: basis()['filas_usadas'] no vino poblado -- la UI no puede derivar listas/pendientes");
        } elseif ((int) $filasUsadas === $oraculo['denominador']) {
            semaforoPass("{$clave} [obra {$projectId}, semana {$semana}]: basis()['filas_usadas']={$filasUsadas} coincide con el denominador del oráculo");
        } else {
            semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: basis()['filas_usadas']={$filasUsadas}, oráculo denominador={$oraculo['denominador']}");
        }

        // --- punto 5: denominador cero -> insuficiente, nunca null mudo ni división por cero ---
        if ($oraculo['denominador'] === 0) {
            $denominadorCeroVisto = true;

            if ($result->completeness() === MetricResult::INSUFICIENTE) {
                semaforoPass("{$clave} [obra {$projectId}, semana {$semana}]: denominador 0 real -> completeness()='insuficiente'");
            } else {
                semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: denominador 0 real pero completeness()='" . $result->completeness() . "' (esperado 'insuficiente')");
            }

            if ($result->value() === null) {
                semaforoPass("{$clave} [obra {$projectId}, semana {$semana}]: value()=null explícito con denominador 0, no un 0% engañoso");
            } else {
                semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: value()=" . $result->value() . ' con denominador 0 -- debería ser null, no un porcentaje inventado');
            }

            continue;
        }

        // --- puntos 3: paridad de valor contra el oráculo, para denominador > 0 ---
        $valorOraculo = $oraculo['numerador'] / $oraculo['denominador'];
        $valorEjecutor = $result->value();

        if ($valorEjecutor === null) {
            semaforoFail("{$clave} [obra {$projectId}, semana {$semana}]: oráculo tiene denominador {$oraculo['denominador']} > 0 pero MetricExecutor devolvió value()=null");
            continue;
        }

        $delta = abs($valorOraculo - (float) $valorEjecutor);
        if ($delta <= SEMAFORO_TOLERANCIA) {
            semaforoPass(sprintf(
                "%s [obra %d, semana %s]: paridad OK -- oráculo=%s (%d/%d), ejecutor=%s",
                $clave,
                $projectId,
                $semana,
                $valorOraculo,
                $oraculo['numerador'],
                $oraculo['denominador'],
                $valorEjecutor,
            ));
        } else {
            semaforoFail(sprintf(
                "%s [obra %d, semana %s]: DISCREPANCIA -- oráculo=%s (%d/%d), ejecutor=%s, delta=%s > tolerancia=%s",
                $clave,
                $projectId,
                $semana,
                $valorOraculo,
                $oraculo['numerador'],
                $oraculo['denominador'],
                $valorEjecutor,
                $delta,
                SEMAFORO_TOLERANCIA,
            ));
        }
    }
}

if (!$denominadorCeroVisto) {
    semaforoFail('ningún combo (obra, semana, franja) con denominador 0 existe hoy en toda la base -- el caso borde del punto 5 no se pudo ejercer');
}

echo "\n---\nResultado: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
