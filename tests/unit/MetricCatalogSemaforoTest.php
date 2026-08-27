<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Task 7 paso 3-bis (Ola 1, Torre de Control piloto), rol A: contrato de catálogo para las
 * "cuatro métricas nuevas" que D58 pide para el semáforo por semanas del lienzo de Intermedia
 * (CT-8.3, punto 4: "Semáforo por semanas para iniciar (0 a 6), reconstruido desde Power BI").
 *
 * La spec nunca detalló qué mide cada franja del semáforo — entrada 13 de la Bitácora del piloto
 * (`docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md`) documenta la laguna y la
 * decisión explícita de Felipe que la resuelve: **cuatro franjas por urgencia según
 * `Semanas_Inicio`** — semana 0 (listas para iniciar ya), 1-2, 3-4, 5-6 — cada una contando
 * actividades del lookahead (`programa_consolidado`/`bi_pg_semana`, `Titulo=0`) que caen en esa
 * ventana, coloreadas verde (franja 0) a rojo (franja 5-6) por cercanía.
 *
 * `estado_ejecucion`: las 4 nacen `descriptiva`, no `ejecutable`. Investigado, no asumido:
 * `MetricExecutor::buildSelectExpression()` (`src/Services/Bi/MetricExecutor.php`) solo reconoce
 * dos formas — `ratio:<columna>` o `SUM(expr)/COUNT(*)` — y en ambos casos el resultado SIEMPRE es
 * un cociente `numerador/denominador` (`MetricExecutor::execute()` línea 100:
 * `$value = ... ((float) $numerador) / $filasUsadas`). No existe un tercer modo de conteo puro sin
 * denominador — el mismo vacío que la entrada 6 de la Bitácora ya documentó para
 * `pg_activities_to_do` y hermanas ("nunca conteo puro (sin denominador)"), y que Task 3 dejó
 * explícitamente fuera de alcance de esta etapa ("extender MetricExecutor... no lo pide la
 * condición de hecho de esta etapa"). Un "cuenta cuántas actividades caen en la franja" es, por su
 * propia definición, un conteo puro — no una razón. Forzarlo como `SUM(condición)/COUNT(*)`
 * (fracción del lookahead total) sería técnicamente ejecutable para la franja 0 en solitario
 * (numerador `Semanas_Inicio=0`, una sola comparación, matchea
 * `MetricExecutor::NUMERATOR_EXPRESSION_PATTERN`), pero NO para las franjas 1-2/3-4/5-6: su
 * numerador necesita una condición compuesta (`Semanas_Inicio>=1 AND Semanas_Inicio<=2`), y el
 * patrón de numerador del ejecutor solo admite un identificador con UNA comparación simple, nunca
 * un rango — igual que `MetricExecutor::parseFilter()` tampoco reconoce `BETWEEN`. Promover solo
 * la franja 0 a `ejecutable` dejaría 3 de 4 hermanas inconsistentes por una limitación del motor,
 * no por una diferencia real de definición — se prefiere declarar las 4 `descriptiva` por igual,
 * documentando el motivo, en vez de una migración parcial cosmética. No se extendió
 * `MetricExecutor` (fuera de alcance, declarado explícitamente en la entrada 6).
 *
 * Grupo `puro`: solo lee el array del catálogo (`MetricDictionaryService::catalog()`), sin tocar
 * la base de datos.
 */
#[Group('puro')]
final class MetricCatalogSemaforoTest extends TestCase
{
    /**
     * Nombres de clave elegidos (rol A): prefijo `pi_` (report_key `intermedia`, igual que
     * `pi_hard_restrictions_ready_rate` y `pi_restriction_pareto`) + `semaforo_semana_<franja>`,
     * describiendo la franja de urgencia, no una fórmula interna.
     */
    private const CLAVES_ESPERADAS = [
        'pi_semaforo_semana_0',
        'pi_semaforo_semana_1_2',
        'pi_semaforo_semana_3_4',
        'pi_semaforo_semana_5_6',
    ];

    /**
     * Filtros esperados por franja: `Titulo=0` (no titulares) y `Ejecutado<1` (aún no ejecutada)
     * son el universo "lookahead" ya usado por `pg_activities_to_do`, la métrica hermana con la
     * misma definición de población. El límite de `Semanas_Inicio` usa comparaciones simples
     * (`>=`/`<=`/`=`), nunca `BETWEEN` — mismo criterio que la corrección CT-16 documentada junto a
     * `pi_hard_restrictions_ready_rate`: `MetricExecutor::parseFilter()` no reconoce `BETWEEN`, así
     * que declarar el filtro ya en la forma parseable dejar la métrica lista para el día que el
     * motor soporte conteo puro, sin tener que re-redactar el catálogo entonces.
     */
    private const FILTROS_ESPERADOS = [
        'pi_semaforo_semana_0' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio=0'],
        'pi_semaforo_semana_1_2' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=1', 'Semanas_Inicio<=2'],
        'pi_semaforo_semana_3_4' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=3', 'Semanas_Inicio<=4'],
        'pi_semaforo_semana_5_6' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=5', 'Semanas_Inicio<=6'],
    ];

    public function testLasCuatroMetricasDelSemaforoExistenEnElCatalogo(): void
    {
        $dictionary = new MetricDictionaryService();
        $clavesReales = array_column($dictionary->exportDictionary(), 'metric_key');

        foreach (self::CLAVES_ESPERADAS as $clave) {
            self::assertContains(
                $clave,
                $clavesReales,
                "falta la clave '{$clave}' del semáforo por semanas (D58) en el catálogo",
            );
        }
    }

    /**
     * CT-6.2 exige el contrato completo antes de pintarse: fuente, filtro, grano — y, para una
     * métrica `descriptiva` (sin `MetricExecutor` de por medio), `known_limitations` es donde vive
     * la declaración de completitud en prosa, igual que en `pg_activities_to_do` y
     * `pg_observed_activity_delay_days`.
     */
    public function testCadaFranjaDeclaraFuenteFiltroGranoYMotivoDeCompletitud(): void
    {
        $dictionary = new MetricDictionaryService();

        foreach (self::CLAVES_ESPERADAS as $clave) {
            $def = $dictionary->getDefinition($clave);
            self::assertNotSame([], $def, "getDefinition('{$clave}') debe devolver el contrato completo");

            self::assertSame('intermedia', $def['report_key'] ?? null, "{$clave}: report_key debe ser 'intermedia' (CT-8.3)");

            // bi_pg_semana, no bi_pi_restricciones: grano de ACTIVIDAD (project_id+Semana+unique_id),
            // igual que pi_hard_restrictions_ready_rate y pg_activities_to_do -- no el grano de
            // RESTRICCION de bi_pi_restricciones, que multiplicaria el conteo (una actividad puede
            // tener hasta 5 filas, una por tipo de restriccion dura).
            self::assertSame('bi_pg_semana', $def['execution_source'] ?? null, "{$clave}: execution_source debe ser 'bi_pg_semana'");
            self::assertContains('programa_consolidado', $def['source_relations'] ?? [], "{$clave}: debe declarar programa_consolidado como fuente real");

            self::assertSame('project_id + Semana', $def['grain'] ?? null, "{$clave}: grain debe ser 'project_id + Semana'");
            self::assertNotSame('', trim((string) ($def['cutoff_policy'] ?? '')), "{$clave}: cutoff_policy no puede estar vacío");

            self::assertSame(
                self::FILTROS_ESPERADOS[$clave],
                $def['filters'] ?? null,
                "{$clave}: filtros de Semanas_Inicio deben ser comparaciones simples, no BETWEEN (CT-16)",
            );

            self::assertSame('actividades', $def['unit'] ?? null, "{$clave}: unit debe ser 'actividades' (es un conteo, no un porcentaje)");
            self::assertIsBool($def['supports_multi_project'] ?? null, "{$clave}: supports_multi_project debe estar declarado");
            self::assertIsBool($def['synthetic_defaults_allowed'] ?? null, "{$clave}: synthetic_defaults_allowed debe estar declarado");

            $limitaciones = (string) ($def['known_limitations'] ?? '');
            self::assertNotSame('', trim($limitaciones), "{$clave}: known_limitations debe documentar la completitud (CT-6.2)");
        }
    }

    /**
     * `estado_ejecucion` debe ser `descriptiva` en las 4, con el motivo (el vacío de conteo puro
     * de `MetricExecutor`) documentado en el catálogo mismo -- igual que el resto de entradas
     * `descriptiva` con causa (`pg_radar_productividad`, `pi_restriction_pareto`), no un silencio.
     */
    public function testLasCuatroSonDescriptivaConElVacioDeMetricExecutorDocumentado(): void
    {
        $dictionary = new MetricDictionaryService();

        foreach (self::CLAVES_ESPERADAS as $clave) {
            $def = $dictionary->getDefinition($clave);

            self::assertSame(
                'descriptiva',
                $def['estado_ejecucion'] ?? null,
                "{$clave}: debe ser 'descriptiva' -- MetricExecutor no soporta conteo puro sin denominador (entrada 6 de la Bitácora)",
            );

            $limitaciones = (string) ($def['known_limitations'] ?? '');
            self::assertStringContainsString(
                'MetricExecutor',
                $limitaciones,
                "{$clave}: known_limitations debe nombrar por qué MetricExecutor no puede ejecutar esta métrica hoy",
            );
        }
    }

    /**
     * Las 4 franjas deben cubrir 0-6 sin solaparse: verifica que las claves de Semanas_Inicio de
     * cada filtro son mutuamente excluyentes (el límite superior de una franja es menor que el
     * inferior de la siguiente) y que, en conjunto, describen el mismo intervalo [0,6] que
     * `is_lookahead_window` en `bi_pg_semana` (`Semanas_Inicio BETWEEN 0 AND 6`) -- ni negativos
     * (ya vencidas) ni mayores a 6 (fuera de ventana) entran en ninguna franja, por diseño.
     */
    public function testLasFranjasSonMutuamenteExcluyentesYCubrenSolo0a6(): void
    {
        $rangos = [
            'pi_semaforo_semana_0' => [0, 0],
            'pi_semaforo_semana_1_2' => [1, 2],
            'pi_semaforo_semana_3_4' => [3, 4],
            'pi_semaforo_semana_5_6' => [5, 6],
        ];

        $anterior = null;
        foreach ($rangos as $clave => [$min, $max]) {
            self::assertLessThanOrEqual($max, $min, "{$clave}: rango invertido");
            if ($anterior !== null) {
                self::assertGreaterThan($anterior, $min, "{$clave}: debe empezar justo después de la franja previa, sin solapar ni dejar hueco");
            }
            $anterior = $max;
        }

        self::assertSame(0, $rangos['pi_semaforo_semana_0'][0], 'la primera franja debe arrancar en semana 0 (listas para iniciar ya)');
        self::assertSame(6, $rangos['pi_semaforo_semana_5_6'][1], 'la última franja debe terminar en semana 6, el límite del lookahead (is_lookahead_window)');
    }
}
