<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class MetricCatalogHygieneTest extends TestCase
{
    public function testNingunaMetricaReferenciaCamposRetirados(): void
    {
        // N8: reprogramaciones_semanales salió por retiro funcional; los demás
        // «muertos» (Categoria_CP, CP, alerta_crisis) están VIVOS y se quedan.
        $texto = json_encode((new MetricDictionaryService())->exportDictionary());
        self::assertStringNotContainsString('reprogramaciones_semanales', $texto);
    }

    public function testCicAprobacionHeredaLaInhabilitacionDelIntegral(): void
    {
        // CT-20.3: clasificar un número que no se publica sería publicarlo por
        // la puerta de atrás. El estado de aprobación declara su dependencia.
        $def = (new MetricDictionaryService())->getDefinition('cic_aprobacion_status');
        self::assertSame('cic_cal_integral', $def['completeness_inherits_from'] ?? null);
    }
}
