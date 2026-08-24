<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class RadarAxisNamingTest extends TestCase
{
    public function testElEjeNoSeLlamaProductividadPorqueMideAvance(): void
    {
        $definicion = (new MetricDictionaryService())->getDefinition('pg_radar_productividad');

        $this->assertNotSame(
            'Radar: Productividad',
            $definicion['metric_name'],
            'El eje mide P_Completado (avance), no productividad. El nombre debe decir lo que mide.'
        );
        $this->assertStringContainsString('P_Completado', $definicion['formula']);
    }
}
