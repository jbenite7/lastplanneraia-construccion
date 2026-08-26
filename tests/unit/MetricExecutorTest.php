<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricScope;
use Database;
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
 * Nivel `db`: no hay PDO en memoria en este repo; corre contra el MySQL de desarrollo (Docker).
 */
#[Group('db')]
final class MetricExecutorTest extends TestCase
{
    private const TABLA = 'bi_metric_executor_test_rows';
    private const PROJECT_A = 990101;
    private const PROJECT_B = 990102;

    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->query('DROP TEMPORARY TABLE IF EXISTS ' . self::TABLA);
        $this->db->query(
            'CREATE TEMPORARY TABLE ' . self::TABLA . ' (
                project_id INT NOT NULL,
                cumplido TINYINT NOT NULL,
                activo TINYINT NOT NULL DEFAULT 1
            )'
        );
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TEMPORARY TABLE IF EXISTS ' . self::TABLA);
    }

    private function sembrar(int $projectId, int $cumplido, int $activo = 1): void
    {
        $this->db->query(
            'INSERT INTO ' . self::TABLA . ' (project_id, cumplido, activo) VALUES (?, ?, ?)',
            [$projectId, $cumplido, $activo],
        );
    }

    private function ejecutor(): MetricExecutor
    {
        // La tabla se pasa por constructor, no se lee como `MetricExecutorTest::TABLA` desde
        // dentro de la clase anonima: una `private const` de la clase de test no es visible ahi
        // aunque este anidada lexicamente, porque la clase anonima extiende
        // `MetricDictionaryService`, no `MetricExecutorTest` — no hay relacion de herencia que
        // habilite el acceso, ni con `protected`. Bug real encontrado por rol B al implementar.
        return new MetricExecutor($this->db, new class(self::TABLA) extends MetricDictionaryService {
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

        $scope = new MetricScope([self::PROJECT_A], '2026-08-01', '2026-08-31');
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
        $this->assertIsString($basis['corte']);

        $this->assertSame('completa', $resultado->completeness());
        $this->assertIsArray($resultado->missing());
    }

    /**
     * Caso obligatorio del brief: denominador cero nunca es division por cero ni null mudo.
     */
    public function testDenominadorCeroDevuelveInsuficienteSinDividirPorCero(): void
    {
        $this->sembrar(self::PROJECT_A, cumplido: 1);

        $scope = new MetricScope([self::PROJECT_A], '2026-08-01', '2026-08-31');
        $resultado = $this->ejecutor()->execute('test_ratio_sin_filas', $scope);

        $this->assertSame('insuficiente', $resultado->completeness());

        $basis = $resultado->basis();
        $this->assertSame(0, $basis['filas_usadas'], 'ninguna fila matcheo el filtro, el denominador es 0');
        $this->assertArrayHasKey('obras_incluidas', $basis);
        $this->assertArrayHasKey('obras_esperadas', $basis);
        $this->assertArrayHasKey('corte', $basis);

        $this->assertIsArray($resultado->missing());
        $this->assertNotEmpty($resultado->missing(), 'debe declarar explicitamente que falto data, no callar');
    }

    public function testAislaEstrictoPorProjectIdCuandoElScopeExcluyeElProyectoConDatos(): void
    {
        // Todas las filas son del proyecto B; el scope solo pide el A.
        $this->sembrar(self::PROJECT_B, cumplido: 1);
        $this->sembrar(self::PROJECT_B, cumplido: 1);

        $scope = new MetricScope([self::PROJECT_A], '2026-08-01', '2026-08-31');
        $resultado = $this->ejecutor()->execute('test_ratio_ok', $scope);

        $this->assertSame('insuficiente', $resultado->completeness());
        $this->assertSame(0, $resultado->basis()['filas_usadas'], 'el project_id del scope no debe ver filas ajenas');
    }
}
