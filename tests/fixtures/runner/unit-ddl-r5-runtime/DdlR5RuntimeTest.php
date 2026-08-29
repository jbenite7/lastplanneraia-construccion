<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

abstract class DdlR5RuntimeBaseTest extends TestCase
{
    protected function applyFixture(string $sql): void
    {
        $this->pdo->exec($sql);
    }
}

#[Group('db')]
final class DdlR5RuntimeTest extends DdlR5RuntimeBaseTest
{
    #[DataProvider('rows')]
    public function testProviderConsumer(int $id): void
    {
        self::assertGreaterThan(0, $id);
    }

    public static function rows(): array
    {
        global $pdo;
        $pdo->exec('DROP TABLE fixture_r5_provider');

        return [[1]];
    }

    public function testIndirectWrapper(): void
    {
        call_user_func([$this, 'applyFixture'], 'DROP TABLE fixture_r5_indirect');
    }

    public function testNamedArguments(): void
    {
        $this->executeFixture(
            tag: 'SELECT 1',
            pdo: $this->pdo,
            sql: 'DROP TABLE fixture_r5_named',
        );
    }

    public function testByReferenceClosure(): void
    {
        $sql = 'SELECT 1';
        $mutate = function () use (&$sql): void {
            $sql = 'DROP TABLE fixture_r5_reference';
        };
        $mutate();
        $this->pdo->exec($sql);
    }

    private function executeFixture(string $sql, string $tag, $pdo): void
    {
        $pdo->exec($sql);
    }
}
