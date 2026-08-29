<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\DataScopeContext;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScope;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class DataScopeContextTest extends TestCase
{
    public function testProyectoUnicoRechazaIdNoPositivo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProjectScope(0, 'test.A', 'A');
    }

    public function testMultiproyectoNormalizaYNoAceptaConjuntoVacio(): void
    {
        $scope = new MultiProjectScope([73, 27, 73], 'test.A', 'A', 'bi:control-tower');
        self::assertSame([27, 73], $scope->projectIds());
        self::assertTrue($scope->allows(73));

        $this->expectException(InvalidArgumentException::class);
        new MultiProjectScope([], 'test.A', 'A', 'bi:control-tower');
    }

    public function testContextoSeLimpiaEntreRequests(): void
    {
        $context = new DataScopeContext();
        $context->bind(new ProjectScope(73, 'test.A', 'A'));
        self::assertInstanceOf(ProjectScope::class, $context->current());
        $context->clear();
        self::assertNull($context->current());
    }

    public function testProyectoNormalizaIdentidadYRechazaUsuarioVacio(): void
    {
        $scope = new ProjectScope(73, ' test.A ', ' A ');
        self::assertSame('test.A', $scope->user());
        self::assertSame('A', $scope->role());

        $this->expectException(InvalidArgumentException::class);
        new ProjectScope(73, '   ', 'A');
    }

    public function testMultiproyectoRechazaIdsNoPositivosYRazonVacia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MultiProjectScope([27, 0], 'test.A', 'A', 'bi:control-tower');
    }

    public function testSystemScopeExigeRazonAuditable(): void
    {
        self::assertSame('mantenimiento de índices', SystemScope::forMaintenance(' mantenimiento de índices ')->reason());

        $this->expectException(InvalidArgumentException::class);
        SystemScope::forMaintenance('   ');
    }

    public function testContextoNoReemplazaAutoridadSinLimpiezaExplicita(): void
    {
        $context = new DataScopeContext();
        $context->bind(SystemScope::forMaintenance('reconciliación'));

        $this->expectException(LogicException::class);
        $context->bind(new ProjectScope(73, 'test.A', 'A'));
    }
}
