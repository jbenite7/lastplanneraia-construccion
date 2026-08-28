<?php
// tests/unit/ReportPaletteTest.php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class ReportPaletteTest extends TestCase
{
    /** D17: lo que se ve en pantalla clara es lo que se imprime. */
    public function testLaPaletaDelExcelEsLaCalibradaDePantalla(): void
    {
        $esperada = [
            'red' => 'FFF6C3C3',
            'orange' => 'FFF8C9A5',
            'amber' => 'FFFFECB2',
            'violet' => 'FFDAD4F5',
            'green' => 'FFC2E2D3',
            'blue' => 'FFC1D5EC',
            'teal' => 'FFC8EFEC',
            'neutral' => 'FFE4E4E7',
        ];
        $this->assertSame($esperada, \App\Controllers\Gestion\ReportController::STATE_FILLS);
    }
}
