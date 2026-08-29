<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('db')]
final class DdlWrapperRuntimeTest extends TestCase
{
    public function testFixtureSchema(): void
    {
        $this->runSql('CREATE TABLE fixture_phpunit_method (id INT)');
    }

    private function runSql(string $sql): void
    {
        $this->pdo->exec($sql);
    }
}
