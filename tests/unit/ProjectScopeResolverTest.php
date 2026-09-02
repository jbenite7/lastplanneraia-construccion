<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\ProjectScopeResolver;
use App\Security\RbacService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class ProjectScopeResolverTest extends TestCase
{
    private function resolver(): ProjectScopeResolver
    {
        $lookup = static fn (string $user, int $projectId): ?array => match ([$user, $projectId]) {
            ['test.A', 73] => [
                'project_id' => 73,
                'role' => 'P',
                'Activo' => 1,
                'Area' => 'Construccion',
            ],
            ['test.A', 27] => null,
            ['test.A', 74] => [
                'project_id' => 74,
                'role' => 'R',
                'Activo' => 0,
                'Area' => 'Construccion',
            ],
            ['test.A', 75] => [
                'project_id' => 75,
                'role' => 'V',
                'Activo' => 1,
                'Area' => 'Administracion',
            ],
            default => null,
        };

        // `new RbacService()` sin argumento cae a `Database::getInstance()` en su constructor
        // (`src/Security/RbacService.php:15`), así que esta clase —declarada `#[Group('puro')]`—
        // abría una conexión real aunque su `$lookup` sea un doble y ninguna aserción toque datos.
        // En una máquina con la base levantada eso no se nota; el lane `puro` del CI corre con
        // `--no-deps`, sin servicio de base, y ahí el proceso muere entero:
        //
        //   Error: No se pudo conectar a la base de datos.
        //   Fatal error: Premature end of PHP process when running
        //   Tests\Unit\ProjectScopeResolverTest::testResuelveExactamenteLaMembresiaDeclarada
        //
        // El doble se pasa por el parámetro que el propio servicio ya ofrece. No es un `null`
        // silencioso a propósito: si algún día esta ruta empieza a consultar la base, el test
        // debe romperse con un mensaje que lo diga, no volver a conectarse por su cuenta.
        return new ProjectScopeResolver($lookup, new RbacService(new class {
            public function __call(string $metodo, array $argumentos): never
            {
                throw new \LogicException(
                    "ProjectScopeResolverTest es de nivel `puro` y no debe consultar la base de "
                    . "datos: se llamó a Database::{$metodo}(). Si esta ruta ahora la necesita, "
                    . 'mueve la clase al grupo que corresponda en vez de restaurar la conexión.'
                );
            }
        }));
    }

    public function testResuelveExactamenteLaMembresiaDeclarada(): void
    {
        $scope = $this->resolver()->resolve([
            'usuario' => 'test.A',
            'project_id' => 73,
            'permiso' => 'V',
        ]);

        self::assertSame(73, $scope?->projectId());
        self::assertSame('test.A', $scope?->user());
        self::assertSame('D', $scope?->role());
    }

    public function testNoEligeOtroProyectoCuandoFaltaLaMembresiaDeclarada(): void
    {
        self::assertNull($this->resolver()->resolve([
            'usuario' => 'test.A',
            'project_id' => 27,
            'permiso' => 'A',
        ]));
    }

    public function testRechazaUsuarioVacio(): void
    {
        self::assertNull($this->resolver()->resolve([
            'usuario' => '   ',
            'project_id' => 73,
            'permiso' => 'A',
        ]));
    }

    public function testRechazaProyectoInactivo(): void
    {
        self::assertNull($this->resolver()->resolve([
            'usuario' => 'test.A',
            'project_id' => 74,
            'permiso' => 'R',
        ]));
    }

    public function testRechazaAreaFueraDelContrato(): void
    {
        self::assertNull($this->resolver()->resolve([
            'usuario' => 'test.A',
            'project_id' => 75,
            'permiso' => 'V',
        ]));
    }
}
