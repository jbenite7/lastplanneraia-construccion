<?php

declare(strict_types=1);

namespace Tests\Unit\Shell;

use App\Contracts\Shell\WeekAdministrationRepository;

/**
 * Doble de prueba puro (sin base de datos) para `WeekAdministrationServiceTest`. Registra cada
 * llamada en `$log` en el orden real en que el servicio las hace, para poder afirmar tanto "no
 * corrió nada después de un bloqueo" como "el orden normalización → arrastre → línea base se
 * respetó".
 */
final class FakeWeekAdministrationRepository implements WeekAdministrationRepository
{
    /** @var list<array{0:string,1:array<mixed>}> */
    public array $log = [];

    public int $conteoSemanasActivas = 0;
    public bool $semanaAnteriorConfirmada = true;
    public string|int $pendientesCicRespuesta = 0;
    public bool $programaMaestroTieneActividadesRespuesta = true;
    public int $maxSemanaConsolidadaRespuesta = 0;
    public int $semanaMaximaRespuesta = 0;

    private function registrar(string $metodo, array $args): void
    {
        $this->log[] = [$metodo, $args];
    }

    /** @return list<string> Solo los nombres de método, en orden. */
    public function metodosLlamados(): array
    {
        return array_column($this->log, 0);
    }

    public function contarSemanasActivas(int $projectId): int
    {
        $this->registrar(__FUNCTION__, [$projectId]);

        return $this->conteoSemanasActivas;
    }

    public function semanaConfirmada(int $projectId, int $semana): bool
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana]);

        return $this->semanaAnteriorConfirmada;
    }

    public function pendientesCic(int $projectId, int $semanaReferencia): string|int
    {
        $this->registrar(__FUNCTION__, [$projectId, $semanaReferencia]);

        return $this->pendientesCicRespuesta;
    }

    public function programaMaestroTieneActividades(int $projectId): bool
    {
        $this->registrar(__FUNCTION__, [$projectId]);

        return $this->programaMaestroTieneActividadesRespuesta;
    }

    public function insertarSemanaActiva(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana, $fechaInicio, $fechaFin]);
    }

    public function eliminarSemanaActivaRecienCreada(int $projectId, int $semana): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana]);
    }

    public function copiarProgramaMaestroASemana(int $projectId, int $semana, bool $preConstruccion): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana, $preConstruccion]);
    }

    public function maxSemanaConsolidada(int $projectId): int
    {
        $this->registrar(__FUNCTION__, [$projectId]);

        return $this->maxSemanaConsolidadaRespuesta;
    }

    public function eliminarSemanasConsolidadasSuperioresA(int $projectId, int $semana): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana]);
    }

    public function fusionarSemanaRecreada(int $projectId, int $semanaAnterior, int $semanaNueva, bool $preConstruccion): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semanaAnterior, $semanaNueva, $preConstruccion]);
    }

    public function copiarSemanaConsolidadaHaciaAdelante(int $projectId, int $semanaOrigen, int $semanaDestino, bool $preConstruccion): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semanaOrigen, $semanaDestino, $preConstruccion]);
    }

    public function normalizarCapitulos(int $projectId, int $semana): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana]);
    }

    public function resetearCapitulosSemana(int $projectId, int $semana, bool $preConstruccion): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana, $preConstruccion]);
    }

    public function resetearFilasOperativasNulas(int $projectId, int $semana, bool $preConstruccion): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana, $preConstruccion]);
    }

    public function sincronizarArrastre(int $projectId, int $semanaOrigen, int $semanaDestino): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semanaOrigen, $semanaDestino]);
    }

    public function sembrarLineaBaseSiFalta(int $projectId): void
    {
        $this->registrar(__FUNCTION__, [$projectId]);
    }

    public function finalizarEstadoSemana(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana, $fechaInicio, $fechaFin]);
    }

    public function semanaMaxima(int $projectId): int
    {
        $this->registrar(__FUNCTION__, [$projectId]);

        return $this->semanaMaximaRespuesta;
    }

    public function eliminarCascada(int $projectId, int $semana): void
    {
        $this->registrar(__FUNCTION__, [$projectId, $semana]);
    }

    public function registrarActividad(string $accion, string $detalle): void
    {
        $this->registrar(__FUNCTION__, [$accion, $detalle]);
    }

    public function semanasActivas(int $projectId): array
    {
        $this->registrar(__FUNCTION__, [$projectId]);

        return [];
    }
}
