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

        return new ProjectScopeResolver($lookup, new RbacService());
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
