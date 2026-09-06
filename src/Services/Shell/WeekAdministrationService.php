<?php

declare(strict_types=1);

namespace App\Services\Shell;

use App\Contracts\Shell\WeekAdministrationRepository;

/**
 * Reglas de crear/eliminar semana extraídas de `src/Legacy/nueva_semana.php` y
 * `src/Legacy/eliminar_semana.php` (Tarea 5, T01). Este servicio decide — nunca ejecuta SQL — así
 * que es 100% probable con un fake de `WeekAdministrationRepository` que solo registra llamadas
 * (`tests/unit/Shell/WeekAdministrationServiceTest.php`), sin tocar una base de datos real.
 *
 * Invariantes documentados a partir del código legado (ver `task-5-report.md` para las citas
 * línea a línea):
 *
 * 1. CIC pendiente bloquea crear, sin importar el conteo actual de semanas
 *    (`verificarCICActualizada.php`, hoy orquestado por el cliente antes de llamar
 *    `nueva_semana.php`; aquí se mueve al servidor para que React no repita la regla).
 * 2. Con semanas existentes, la semana anterior debe estar `Semanal_Confirmada` antes de crear
 *    la siguiente — excepto para el rol Admin (`nueva_semana.php` líneas 54-63).
 * 3. La primera semana (`conteo == 0`) exige que el Programa Maestro tenga actividades; si no,
 *    la semana recién insertada se revierte (líneas 75-83).
 *   - Primera semana: copia Programa → Programa Consolidado, normaliza capítulos, resetea filas
 *     de capítulo (líneas 85-103).
 *   - Semanas siguientes: limpia huérfanos por encima de la semana a crear, decide entre
 *     "fusionar semana recreada" (ya hay filas para esa semana) o "copiar hacia adelante" (no
 *     las hay), normaliza, resetea capítulo y filas operativas nulas, sincroniza arrastre de
 *     avance real y siembra la línea base contractual una sola vez (líneas 105-201).
 * 4. El rango de la semana es siempre 7 días: fin = inicio + 6.
 * 5. Eliminar solo admite la semana máxima activa del proyecto; la cascada borra
 *    `semanas_activas`, `programa_consolidado`, `programacion_semanal` y `cic` con
 *    `Semana >= $semana` (`eliminar_semana.php` líneas 32-56). Este servicio exige igualdad
 *    exacta con la máxima — más estricto que el `>=` legado, que nunca tenía una ruta real para
 *    pedir una semana mayor a la máxima desde la UI.
 */
final class WeekAdministrationService
{
    public function __construct(private readonly WeekAdministrationRepository $repositorio)
    {
    }

    public function crear(CrearSemanaComando $comando): ResultadoCreacionSemana
    {
        $conteo = $this->repositorio->contarSemanasActivas($comando->projectId);

        $pendientesCic = $this->repositorio->pendientesCic($comando->projectId, $conteo);
        if ($pendientesCic !== 0 && $pendientesCic !== '0') {
            return ResultadoCreacionSemana::bloqueada(
                ResultadoCreacionSemana::BLOQUEO_CIC_PENDIENTE,
                "No se pueden crear nuevas semanas hasta realizar las Calificaciones Integrales{$pendientesCic}.",
            );
        }

        if ($conteo > 0 && !$this->repositorio->semanaConfirmada($comando->projectId, $conteo) && !$comando->esAdmin) {
            return ResultadoCreacionSemana::bloqueada(
                ResultadoCreacionSemana::BLOQUEO_SEMANA_NO_CONFIRMADA,
                "No se puede crear la Semana " . ($conteo + 1) . " hasta confirmar los compromisos de la Semana {$conteo}.",
            );
        }

        $semanaCrear = $conteo + 1;
        $fechaFin = self::finDeSemana($comando->fechaInicio);

        $this->repositorio->insertarSemanaActiva($comando->projectId, $semanaCrear, $comando->fechaInicio, $fechaFin);

        if ($conteo === 0) {
            if (!$this->repositorio->programaMaestroTieneActividades($comando->projectId)) {
                $this->repositorio->eliminarSemanaActivaRecienCreada($comando->projectId, $semanaCrear);

                return ResultadoCreacionSemana::bloqueada(
                    ResultadoCreacionSemana::BLOQUEO_PROGRAMA_MAESTRO_VACIO,
                    'No hay actividades en el Programa Maestro. Cargue el programa antes de crear la primera semana.',
                );
            }

            $this->repositorio->copiarProgramaMaestroASemana($comando->projectId, $semanaCrear, $comando->preConstruccion);
            $this->repositorio->normalizarCapitulos($comando->projectId, $semanaCrear);
            $this->repositorio->resetearCapitulosSemana($comando->projectId, $semanaCrear, $comando->preConstruccion);
        } else {
            $maxConsolidada = $this->repositorio->maxSemanaConsolidada($comando->projectId);

            if ($maxConsolidada > $semanaCrear) {
                $this->repositorio->eliminarSemanasConsolidadasSuperioresA($comando->projectId, $semanaCrear);
            }

            if ($maxConsolidada === $semanaCrear) {
                $this->repositorio->fusionarSemanaRecreada($comando->projectId, $conteo, $semanaCrear, $comando->preConstruccion);
            } else {
                $this->repositorio->copiarSemanaConsolidadaHaciaAdelante($comando->projectId, $conteo, $semanaCrear, $comando->preConstruccion);
            }

            $this->repositorio->normalizarCapitulos($comando->projectId, $semanaCrear);
            $this->repositorio->resetearCapitulosSemana($comando->projectId, $semanaCrear, $comando->preConstruccion);
            $this->repositorio->resetearFilasOperativasNulas($comando->projectId, $semanaCrear, $comando->preConstruccion);
            $this->repositorio->sincronizarArrastre($comando->projectId, $conteo, $semanaCrear);
            $this->repositorio->sembrarLineaBaseSiFalta($comando->projectId);
        }

        $this->repositorio->finalizarEstadoSemana($comando->projectId, $semanaCrear, $comando->fechaInicio, $fechaFin);

        return ResultadoCreacionSemana::exitosa($semanaCrear, $comando->fechaInicio, $fechaFin);
    }

    public function eliminarUltima(EliminarSemanaComando $comando): ResultadoEliminacionSemana
    {
        $maxSemana = $this->repositorio->semanaMaxima($comando->projectId);

        if ($maxSemana === 0) {
            return ResultadoEliminacionSemana::bloqueada(
                ResultadoEliminacionSemana::BLOQUEO_SIN_SEMANAS,
                'El proyecto no tiene semanas activas para eliminar.',
            );
        }

        if ($comando->semanaSolicitada !== $maxSemana) {
            return ResultadoEliminacionSemana::bloqueada(
                ResultadoEliminacionSemana::BLOQUEO_NO_ES_LA_ULTIMA,
                "Solo se puede eliminar la última semana activa (Semana {$maxSemana}) para mantener la integridad de los datos.",
            );
        }

        $this->repositorio->eliminarCascada($comando->projectId, $maxSemana);
        $this->repositorio->registrarActividad(
            'ELIMINAR_SEMANA',
            "Eliminación de semana {$maxSemana} y superiores en proyecto {$comando->projectId}",
        );

        return ResultadoEliminacionSemana::exitosa($maxSemana, $maxSemana - 1);
    }

    private static function finDeSemana(string $fechaInicio): string
    {
        return date('Y-m-d', strtotime($fechaInicio . ' + 6 days'));
    }
}
