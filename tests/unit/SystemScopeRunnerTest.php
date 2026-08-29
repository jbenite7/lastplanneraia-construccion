<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\DataScopeContext;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScope;
use App\Security\DataScope\SystemScopeRunner;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('puro')]
final class SystemScopeRunnerTest extends TestCase
{
    public function testBindsSystemScopeForSuccessfulOperationAndClearsIt(): void
    {
        $context = new DataScopeContext();
        $runner = new SystemScopeRunner($context);

        $result = $runner->run(' report:general:test.A ', static function () use ($context): string {
            $scope = $context->current();
            self::assertInstanceOf(SystemScope::class, $scope);
            self::assertSame('report:general:test.A', $scope->reason());

            return 'resultado';
        });

        self::assertSame('resultado', $result);
        self::assertNull($context->current());
    }

    public function testClearsSystemScopeWhenOperationThrows(): void
    {
        $context = new DataScopeContext();
        $runner = new SystemScopeRunner($context);

        try {
            $runner->run('maintenance:test', static function () use ($context): never {
                self::assertInstanceOf(SystemScope::class, $context->current());
                throw new RuntimeException('fallo controlado');
            });
            self::fail('La excepción de la operación no se propagó.');
        } catch (RuntimeException $error) {
            self::assertSame('fallo controlado', $error->getMessage());
        }

        self::assertNull($context->current());
    }

    public function testRejectsExistingProjectScopeWithoutReplacingOrClearingIt(): void
    {
        $context = new DataScopeContext();
        $projectScope = new ProjectScope(73, 'test.A', 'A');
        $context->bind($projectScope);
        $runner = new SystemScopeRunner($context);
        $called = false;

        try {
            $runner->run('maintenance:test', static function () use (&$called): void {
                $called = true;
            });
            self::fail('El runner aceptó un contexto ocupado.');
        } catch (LogicException $error) {
            self::assertSame('SystemScopeRunner exige un contexto de datos vacío.', $error->getMessage());
        }

        self::assertFalse($called);
        self::assertSame($projectScope, $context->current());
    }
}
