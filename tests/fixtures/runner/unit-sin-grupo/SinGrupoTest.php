<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// A propósito sin #[Group]: el runner tiene que cazarlo.
final class SinGrupoTest extends TestCase
{
    public function testNada(): void
    {
        self::assertTrue(true);
    }
}
