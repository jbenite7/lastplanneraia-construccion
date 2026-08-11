<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class VerdeTest extends TestCase
{
    public function testPasa(): void
    {
        self::assertSame(2, 1 + 1);
    }
}
