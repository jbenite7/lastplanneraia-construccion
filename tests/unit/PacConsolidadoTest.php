<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReportProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * `calculatePACConsolidado()` recibe valores que vienen directo de columnas texto de `cip`:
 * ahi conviven numeros ('0.000'), 'NA', 'NR', NULL y cadenas vacias. En PHP 8 multiplicar dos
 * cadenas no numericas es un TypeError, y el 2026-08-19 la combinacion real
 * ('NA', 'NA', 'NA', '0.000') tumbo `updateCICProyectos()` completo en
 * `ReportProcessor.php:1189`. Este test fija el contrato: todo lo no numerico cuenta como NA
 * y un PAC no numerico consolida en 0, sin excepciones.
 *
 * Nivel `puro`: solo ejercita aritmetica via reflexion, sin base de datos.
 */
#[Group('puro')]
final class PacConsolidadoTest extends TestCase
{
    private function calcular(mixed $pac, mixed $crit, mixed $nocrit, mixed $atr): mixed
    {
        $method = new ReflectionMethod(ReportProcessor::class, 'calculatePACConsolidado');

        return $method->invoke(
            (new \ReflectionClass(ReportProcessor::class))->newInstanceWithoutConstructor(),
            $pac,
            $crit,
            $nocrit,
            $atr,
        );
    }

    public static function combinacionesNoNumericas(): array
    {
        return [
            'PAC NA con atrasadas numericas (caso real del 2026-08-19)' => ['NA', 'NA', 'NA', '0.000', 0.0],
            'PAC NR con criticas numericas' => ['NR', '0.500', 'NA', 'NA', 0.0],
            'PAC vacio con todo numerico' => ['', '1.000', '0.500', '0.250', 0.0],
            'componente NR se trata como NA' => ['0.800', 'NR', 'NA', '0.500', 0.4],
            'todo NA consolida en cero' => ['NA', 'NA', 'NA', 'NA', 0.0],
        ];
    }

    #[DataProvider('combinacionesNoNumericas')]
    public function testValoresNoNumericosNoRevientan(mixed $pac, mixed $crit, mixed $nocrit, mixed $atr, float $esperado): void
    {
        $this->assertEqualsWithDelta($esperado, (float) $this->calcular($pac, $crit, $nocrit, $atr), 0.0005);
    }

    public function testCombinacionNumericaCompletaConservaLosPesos(): void
    {
        // 0.9 * (1.0*0.4 + 0.5*0.2 + 0.25*0.4) = 0.54
        $this->assertEqualsWithDelta(0.54, (float) $this->calcular('0.9', '1', '0.5', '0.25'), 0.0005);
    }
}
