<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\MultiProjectScope;
use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricScope;
use Database;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contrato de Task 2 (Ola 1, Torre de Control piloto): `MetricExecutor::execute()` convierte
 * una definicion del catalogo (`MetricDictionaryService::getDefinition()`) en una consulta real,
 * aislada por `project_id`, con sentencias preparadas — nunca SQL libre.
 *
 * CONCERN dejado para el controlador (no bloquea esta prueba, ver task-2-report.md): el catalogo
 * real (`MetricDictionaryService::catalog()`) SI trae `execution_source`, `filters` y
 * `aggregation_policy` como pide el brief, pero:
 *  - `filters` son fragmentos de prosa SQL-like ('Titulo=0', 'Semanas_Inicio BETWEEN 0 AND 6'),
 *    NO pares columna/operador/valor estructurados como asume el paso 3 del brief.
 *  - `aggregation_policy` es texto descriptivo para humanos ('Ratio de sumas, no promedio de
 *    porcentajes.'), no una directiva que un builder de SQL pueda interpretar mecanicamente.
 *  - No existe ningun campo `select` en el catalogo real, y el brief lo asume implicito en
 *    `SELECT {select} FROM {source} WHERE ...`.
 * Esta prueba NO usa el catalogo real de produccion (evitaria arrastrar vistas BI complejas,
 * ajenas al alcance de Task 2) sino un `MetricDictionaryService` de prueba (subclase) con
 * definiciones minimas que SI usan los nombres de campo reales (`execution_source`, `filters`,
 * `aggregation_policy`) pero con valores deliberadamente simples y ejecutables, para no bloquear
 * el paso 1/2 mientras el controlador decide como cerrar la brecha de forma real.
 *
 * Fix acotado (2026-08-26, entrada 4 de la bitacora del piloto): Task 3 (rol B) encontro que
 * `MetricExecutor::execute()` nunca acota por semana -- `MetricScope` solo carga `project_id`(s)
 * y un `startDate`/`endDate` opcional que `buildWhereClause()` nunca lee, asi que cualquier
 * metrica con grain semanal (la mayoria de las 19 del catalogo real, ej. `ps_weekly_fulfillment`)
 * agrega TODA la historia del proyecto en vez de una semana puntual. Ver
 * `.superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-3-report.md` para el repro completo
 * y `task-2b-fix-report.md` para el razonamiento de la decision de API tomada aqui.
 *
 * Decision de API para este fix: `MetricScope` gana un cuarto parametro `?string $week`, campo
 * separado de `startDate`/`endDate` porque son dominios distintos -- `Semana` es un identificador
 * de negocio (ej. `25`, columna `Semana` en `bi_ps_compromisos`/`programacion_semanal`), no una
 * fecha de calendario. `MetricExecutor::buildWhereClause()` agrega `Semana = ?` al WHERE siempre
 * que `$scope->week()` no sea null -- sin inspeccionar el texto libre de `cutoff_policy` del
 * catalogo, que varia de redaccion metrica por metrica y seria fragil de parsear. Quien construye
 * el `MetricScope` (el futuro llamador BI) es quien ya sabe, por el `cutoff_policy`/`grain` de la
 * metrica que va a pedir, si debe pasar `week` o no; el executor solo obedece lo que el scope trae,
 * igual que ya hace con `project_id`.
 *
 * Nivel `puro`: la frontera Database/PDOStatement se dobla en memoria. El guard SQL tiene sus
 * propios tests focales; este contrato verifica cómo el executor interpreta sus resultados sin
 * pedir DDL ni ampliar privilegios del usuario runtime.
 */
#[Group('puro')]
final class MetricExecutorTest extends TestCase
{
    private const TABLA = 'bi_metric_executor_test_rows';
    private const PROJECT_A = 990101;
    private const PROJECT_B = 990102;

    /** @var list<array{project_id: int, cumplido: int, activo: int, Semana: int}> */
    private array $rows = [];

    protected function setUp(): void
    {
        $this->rows = [];
    }

    private function sembrar(int $projectId, int $cumplido, int $activo = 1, int $semana = 0): void
    {
        $this->rows[] = [
            'project_id' => $projectId,
            'cumplido' => $cumplido,
            'activo' => $activo,
            'Semana' => $semana,
        ];
    }

    private function ejecutor(): MetricExecutor
    {
        // La tabla se pasa por constructor, no se lee como `MetricExecutorTest::TABLA` desde
        // dentro de la clase anonima: una `private const` de la clase de test no es visible ahi
        // aunque este anidada lexicamente, porque la clase anonima extiende
        // `MetricDictionaryService`, no `MetricExecutorTest` — no hay relacion de herencia que
        // habilite el acceso, ni con `protected`. Bug real encontrado por rol B al implementar.
        $database = $this->createStub(Database::class);
        $database->method('queryForProjects')->willReturnCallback(
            function (MultiProjectScope $authority, string $sql, array $params = []): PDOStatement {
                $rows = array_values(array_filter(
                    $this->rows,
                    static fn(array $row): bool => $authority->allows($row['project_id']),
                ));
                $parameter = 0;
                foreach (['Semana', 'activo', 'cumplido'] as $column) {
                    if (!str_contains($sql, "{$column} = ?")) {
                        continue;
                    }
                    $expected = (int) ($params[$parameter++] ?? PHP_INT_MIN);
                    $rows = array_values(array_filter(
                        $rows,
                        static fn(array $row): bool => $row[$column] === $expected,
                    ));
                }

                $statement = $this->createStub(PDOStatement::class);
                if (str_starts_with($sql, 'SELECT DISTINCT project_id')) {
                    $projectIds = array_values(array_unique(array_column($rows, 'project_id')));
                    $statement->method('fetchAll')->willReturn(array_map(
                        static fn(int $projectId): array => ['project_id' => $projectId],
                        $projectIds,
                    ));
                } else {
                    $statement->method('fetch')->willReturn([
                        'numerador' => array_sum(array_column($rows, 'cumplido')),
                        'denominador' => count($rows),
                    ]);
                }

                return $statement;
            },
        );

        return new MetricExecutor($database, new class(self::TABLA) extends MetricDictionaryService {
            public function __construct(private readonly string $tabla)
            {
            }

            public function getDefinition(string $metricKey): array
            {
                return match ($metricKey) {
                    'test_ratio_ok' => [
                        'metric_key' => 'test_ratio_ok',
                        'execution_source' => $this->tabla,
                        'filters' => ['activo = 1'],
                        'aggregation_policy' => 'ratio:cumplido',
                    ],
                    'test_ratio_sin_filas' => [
                        'metric_key' => 'test_ratio_sin_filas',
                        'execution_source' => $this->tabla,
                        'filters' => ['activo = 1', 'cumplido = 9'], // condicion que nunca matchea
                        'aggregation_policy' => 'ratio:cumplido',
                    ],
                    // Grain semanal real (ver `ps_weekly_fulfillment` en el catalogo real): los
                    // `filters` estaticos del catalogo no pueden expresar "y ademas Semana = ?",
                    // por eso el acotamiento por semana tiene que venir del `MetricScope`, no de
                    // aqui -- exactamente el vacio que este fix cierra.
                    'test_ratio_semanal' => [
                        'metric_key' => 'test_ratio_semanal',
                        'execution_source' => $this->tabla,
                        'filters' => ['activo = 1'],
                        'aggregation_policy' => 'ratio:cumplido',
                        'grain' => 'project_id + Semana',
                        'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
                    ],
                    default => [],
                };
            }
        });
    }

    public function testCalculaRatioYPueblaBasisSiempre(): void
    {
        $this->sembrar(self::PROJECT_A, cumplido: 1);
        $this->sembrar(self::PROJECT_A, cumplido: 1);
        $this->sembrar(self::PROJECT_A, cumplido: 0);
        // Fila de otro proyecto que NO debe entrar al calculo (aislamiento por project_id).
        $this->sembrar(self::PROJECT_B, cumplido: 0);

        $scope = new MetricScope(
            new MultiProjectScope([self::PROJECT_A], 'metric.fixture', 'V', 'test:metric-executor'),
            '2026-08-01',
            '2026-08-31',
        );
        $resultado = $this->ejecutor()->execute('test_ratio_ok', $scope);

        $this->assertEqualsWithDelta(
            2 / 3,
            $resultado->value(),
            0.0001,
            'debe promediar solo las filas del proyecto en scope, sin mezclar el otro proyecto',
        );

        $basis = $resultado->basis();
        $this->assertArrayHasKey('obras_incluidas', $basis);
        $this->assertArrayHasKey('obras_esperadas', $basis);
        $this->assertArrayHasKey('corte', $basis);
        $this->assertArrayHasKey('filas_usadas', $basis);
        $this->assertSame(1, $basis['obras_incluidas'], 'solo el proyecto A aporto filas');
        $this->assertSame(1, $basis['obras_esperadas'], 'el scope pidio un solo proyecto');
        $this->assertSame(3, $basis['filas_usadas'], 'las 3 filas del proyecto A, ninguna del B');
        $this->assertNotSame('', $basis['corte']);

        $this->assertSame('completa', $resultado->completeness());
        $this->assertSame([], $resultado->missing());
    }

    /**
     * Caso obligatorio del brief: denominador cero nunca es division por cero ni null mudo.
     */
    public function testDenominadorCeroDevuelveInsuficienteSinDividirPorCero(): void
    {
        $this->sembrar(self::PROJECT_A, cumplido: 1);

        $scope = new MetricScope(
            new MultiProjectScope([self::PROJECT_A], 'metric.fixture', 'V', 'test:metric-executor'),
            '2026-08-01',
            '2026-08-31',
        );
        $resultado = $this->ejecutor()->execute('test_ratio_sin_filas', $scope);

        $this->assertSame('insuficiente', $resultado->completeness());

        $basis = $resultado->basis();
        $this->assertSame(0, $basis['filas_usadas'], 'ninguna fila matcheo el filtro, el denominador es 0');
        $this->assertArrayHasKey('obras_incluidas', $basis);
        $this->assertArrayHasKey('obras_esperadas', $basis);
        $this->assertArrayHasKey('corte', $basis);

        $this->assertNotEmpty($resultado->missing(), 'debe declarar explicitamente que falto data, no callar');
    }

    public function testAislaEstrictoPorProjectIdCuandoElScopeExcluyeElProyectoConDatos(): void
    {
        // Todas las filas son del proyecto B; el scope solo pide el A.
        $this->sembrar(self::PROJECT_B, cumplido: 1);
        $this->sembrar(self::PROJECT_B, cumplido: 1);

        $scope = new MetricScope(
            new MultiProjectScope([self::PROJECT_A], 'metric.fixture', 'V', 'test:metric-executor'),
            '2026-08-01',
            '2026-08-31',
        );
        $resultado = $this->ejecutor()->execute('test_ratio_ok', $scope);

        $this->assertSame('insuficiente', $resultado->completeness());
        $this->assertSame(0, $resultado->basis()['filas_usadas'], 'el project_id del scope no debe ver filas ajenas');
    }

    /**
     * Reproduce el hallazgo real de Task 3 (rol B): sin acotar por semana, una metrica de grain
     * semanal (`ps_weekly_fulfillment` en produccion) agrega TODA la historia del proyecto en vez
     * de la semana pedida, dando el mismo valor sin importar que semana se compare. Sembramos dos
     * semanas con ratios opuestos (semana 1 = 100% cumplido, semana 2 = 0% cumplido) para que
     * cualquier fuga entre semanas se note de inmediato: el agregado sin acotar daria 50% para
     * ambas, un valor que no coincide con ninguna de las dos semanas reales.
     */
    public function testAislaEstrictoPorSemanaCuandoElScopeEspecificaUnaSemanaConcreta(): void
    {
        // Semana 1: 2 de 2 cumplidas -> ratio 1.0 si se acota bien.
        $this->sembrar(self::PROJECT_A, cumplido: 1, semana: 1);
        $this->sembrar(self::PROJECT_A, cumplido: 1, semana: 1);
        // Semana 2: 0 de 2 cumplidas -> ratio 0.0 si se acota bien.
        $this->sembrar(self::PROJECT_A, cumplido: 0, semana: 2);
        $this->sembrar(self::PROJECT_A, cumplido: 0, semana: 2);

        $scopeSemana1 = new MetricScope(
            new MultiProjectScope([self::PROJECT_A], 'metric.fixture', 'V', 'test:metric-executor:week-1'),
            week: '1',
        );
        $resultadoSemana1 = $this->ejecutor()->execute('test_ratio_semanal', $scopeSemana1);

        $this->assertSame(
            2,
            $resultadoSemana1->basis()['filas_usadas'],
            'la semana 1 solo tiene 2 filas propias; si ve 4 esta agregando toda la historia',
        );
        $this->assertEqualsWithDelta(
            1.0,
            $resultadoSemana1->value(),
            0.0001,
            'la semana 1 es 100% cumplida; 0.5 significaria que se mezclo con la semana 2',
        );

        $scopeSemana2 = new MetricScope(
            new MultiProjectScope([self::PROJECT_A], 'metric.fixture', 'V', 'test:metric-executor:week-2'),
            week: '2',
        );
        $resultadoSemana2 = $this->ejecutor()->execute('test_ratio_semanal', $scopeSemana2);

        $this->assertSame(
            2,
            $resultadoSemana2->basis()['filas_usadas'],
            'la semana 2 solo tiene 2 filas propias; si ve 4 esta agregando toda la historia',
        );
        $this->assertEqualsWithDelta(
            0.0,
            $resultadoSemana2->value(),
            0.0001,
            'la semana 2 es 0% cumplida; 0.5 significaria que se mezclo con la semana 1',
        );
    }
}
