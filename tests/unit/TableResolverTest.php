<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TableResolver;

/**
 * Cubre `TableResolver`, el punto único de resolución de nombres de tabla entre el modo
 * `{prefix}_{tableType}` (legado) y el de tablas globales (`docs/global-tables-architecture.md`).
 *
 * Convertido desde `tests/TableResolverTest.php` (script autoejecutable sin framework, fuera
 * del alcance de `scripts/run-php-tests.php`) a esta clase PHPUnit para que quede enganchado al
 * runner del proyecto, siguiendo la convención de sus vecinos en `tests/unit/`.
 *
 * Nivel `datos-proyecto`: `resolve()` con el flag OFF y `getProjectIdByPrefix()` consultan
 * `general_proyectos_procesos` y dependen del proyecto fijo "Prueba" (Id=27,
 * Base_de_Datos='prueba'), el mismo fixture que ya usa `tests/test_pg_pasado_servidor.php` con
 * ese mismo nivel — no basta con que haya una base de datos alcanzable (nivel `db`), hace falta
 * ese proyecto sembrado.
 */
#[Group('datos-proyecto')]
final class TableResolverTest extends TestCase
{
    protected function setUp(): void
    {
        TableResolver::clearCache();
        TableResolver::setUseGlobalTablesForTest(false);
    }

    protected function tearDown(): void
    {
        TableResolver::setUseGlobalTablesForTest(false);
        TableResolver::clearCache();
    }

    public function testResolveConFlagOffDevuelvePruebaPrograma(): void
    {
        self::assertSame('prueba_programa', TableResolver::resolve(27, 'programa'));
    }

    public function testResolveConFlagOnDevuelveTablaSinPrefijo(): void
    {
        TableResolver::setUseGlobalTablesForTest(true);

        self::assertSame('programa', TableResolver::resolve(27, 'programa'));
        self::assertSame(
            'programacion_semanal',
            TableResolver::resolve(75, 'programacion_semanal'),
        );
    }

    public function testResolveConProyectoInexistenteLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TableResolver::resolve(999, 'programa');
    }

    public function testResolveConTipoDeTablaInvalidoLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TableResolver::resolve(27, 'tabla_inexistente');
    }

    public function testGetProjectIdByPrefixDevuelveElIdDelProyecto(): void
    {
        self::assertSame(27, TableResolver::getProjectIdByPrefix('prueba'));
    }

    public function testGetProjectIdByPrefixConPrefijoInexistenteDevuelveNull(): void
    {
        self::assertNull(TableResolver::getProjectIdByPrefix('no_existe'));
    }

    public function testGetValidTablesDevuelveLasTablasGlobalesConocidas(): void
    {
        self::assertCount(16, TableResolver::getValidTables());
    }

    public function testResolveByPrefixConFlagOffDevuelvePruebaPrograma(): void
    {
        self::assertSame(
            'prueba_programa',
            TableResolver::resolveByPrefix('prueba', 'programa'),
        );
    }

    public function testResolveByPrefixConFlagOnDevuelveTablaSinPrefijo(): void
    {
        TableResolver::setUseGlobalTablesForTest(true);

        self::assertSame(
            'programa',
            TableResolver::resolveByPrefix('prueba', 'programa'),
        );
    }

    public function testCachePermiteCienBusquedasEnMenosDeCincuentaMilisegundos(): void
    {
        $inicio = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            TableResolver::resolve(27, 'programa');
        }
        $transcurrido = (microtime(true) - $inicio) * 1000;

        self::assertLessThan(50, $transcurrido, "100 lookups tardaron {$transcurrido}ms (se esperaba <50ms)");
    }

    public function testResolveByPrefixConTipoDeTablaInvalidoLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TableResolver::resolveByPrefix('prueba', 'tabla_inexistente');
    }
}
