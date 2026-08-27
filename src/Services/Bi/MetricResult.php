<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * Resultado inmutable de ejecutar una metrica del catalogo.
 *
 * `basis()` esta siempre poblado -- incluso cuando `value()` es `null` porque no
 * hubo datos suficientes -- para que quien consuma el resultado nunca reciba un
 * `null` mudo sin saber por que. Ver `MetricExecutor::execute()`.
 */
final class MetricResult
{
    public const COMPLETA = 'completa';
    public const PARCIAL = 'parcial';
    public const INSUFICIENTE = 'insuficiente';

    /**
     * @param array{obras_incluidas:int, obras_esperadas:int, corte:string, filas_usadas:int} $basis
     * @param array<mixed> $missing
     */
    public function __construct(
        private readonly float|int|null $value,
        private readonly array $basis,
        private readonly string $completeness,
        private readonly array $missing,
    ) {
    }

    public function value(): float|int|null
    {
        return $this->value;
    }

    /**
     * @return array{obras_incluidas:int, obras_esperadas:int, corte:string, filas_usadas:int}
     */
    public function basis(): array
    {
        return $this->basis;
    }

    /**
     * `completa` | `parcial` | `insuficiente`.
     */
    public function completeness(): string
    {
        return $this->completeness;
    }

    /**
     * Vacio cuando `completeness()` es `completa`; poblado y explicito en cualquier
     * otro caso -- nunca se calla por que falta data.
     *
     * @return array<mixed>
     */
    public function missing(): array
    {
        return $this->missing;
    }
}
