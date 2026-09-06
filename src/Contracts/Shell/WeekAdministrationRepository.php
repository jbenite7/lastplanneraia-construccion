<?php

declare(strict_types=1);

namespace App\Contracts\Shell;

/**
 * Cada operación que hoy vive inline en `src/Legacy/nueva_semana.php`,
 * `src/Legacy/eliminar_semana.php` y `src/Legacy/modificar_sem_estado.php`, extraída como un
 * método atómico. `WeekAdministrationService` (Tarea 5, T01) decide QUÉ operación corre y en
 * qué orden — nunca ejecuta SQL — así que puede probarse con un fake que solo registra llamadas,
 * sin base de datos real (restricción del plan: cero DML como evidencia de test).
 *
 * La implementación real (`DatabaseWeekAdministrationRepository`) traslada las mismas consultas
 * del script legado, sin reescribir su lógica: el riesgo de un bug de traducción SQL se evita
 * moviendo el código, no reinterpretándolo.
 */
interface WeekAdministrationRepository
{
    public function contarSemanasActivas(int $projectId): int;

    public function semanaConfirmada(int $projectId, int $semana): bool;

    /** @return string|int 0 si no falta nada; string descriptivo si hay CIC pendiente. */
    public function pendientesCic(int $projectId, int $semanaReferencia): string|int;

    public function programaMaestroTieneActividades(int $projectId): bool;

    public function insertarSemanaActiva(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void;

    /** Rollback del paso anterior cuando el programa maestro está vacío. */
    public function eliminarSemanaActivaRecienCreada(int $projectId, int $semana): void;

    public function copiarProgramaMaestroASemana(int $projectId, int $semana, bool $preConstruccion): void;

    public function maxSemanaConsolidada(int $projectId): int;

    public function eliminarSemanasConsolidadasSuperioresA(int $projectId, int $semana): void;

    public function fusionarSemanaRecreada(int $projectId, int $semanaAnterior, int $semanaNueva, bool $preConstruccion): void;

    public function copiarSemanaConsolidadaHaciaAdelante(int $projectId, int $semanaOrigen, int $semanaDestino, bool $preConstruccion): void;

    public function normalizarCapitulos(int $projectId, int $semana): void;

    public function resetearCapitulosSemana(int $projectId, int $semana, bool $preConstruccion): void;

    public function resetearFilasOperativasNulas(int $projectId, int $semana, bool $preConstruccion): void;

    public function sincronizarArrastre(int $projectId, int $semanaOrigen, int $semanaDestino): void;

    public function sembrarLineaBaseSiFalta(int $projectId): void;

    public function finalizarEstadoSemana(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void;

    public function semanaMaxima(int $projectId): int;

    public function eliminarCascada(int $projectId, int $semana): void;

    public function registrarActividad(string $accion, string $detalle): void;

    /** @return array<int, array{number:int,startsOn:string,endsOn:string}> Ordenadas por número ascendente. */
    public function semanasActivas(int $projectId): array;
}
