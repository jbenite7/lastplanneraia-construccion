<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class RojoTest extends TestCase
{
    public function testFalla(): void
    {
        self::assertSame(1, 2);
    }
}
