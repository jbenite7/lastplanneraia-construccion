<?php

declare(strict_types=1);

namespace Tests\Unit\Shell;

use App\Services\Shell\CrearSemanaComando;
use App\Services\Shell\EliminarSemanaComando;
use App\Services\Shell\ResultadoCreacionSemana;
use App\Services\Shell\ResultadoEliminacionSemana;
use App\Services\Shell\WeekAdministrationService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contrato puro de `WeekAdministrationService` (Tarea 5, T01): gating, orden de operaciones y
 * cascada de crear/eliminar semana, contra un `FakeWeekAdministrationRepository` que solo
 * registra llamadas. Nunca toca base de datos — nivel `puro`, cero DML como exige el plan.
 *
 * Invariantes cubiertos, citados de `src/Legacy/nueva_semana.php` y `eliminar_semana.php`
 * (ver docstring de `WeekAdministrationService` para las líneas exactas).
 */
#[Group('puro')]
final class WeekAdministrationServiceTest extends TestCase
{
    private function comandoCrear(array $overrides = []): CrearSemanaComando
    {
        return new CrearSemanaComando(
            projectId: $overrides['projectId'] ?? 73,
            fechaInicio: $overrides['fechaInicio'] ?? '2026-08-24',
            preConstruccion: $overrides['preConstruccion'] ?? false,
            esAdmin: $overrides['esAdmin'] ?? false,
        );
    }

    public function testCicPendienteBloqueaCrearAntesDeInsertarNada(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 3;
        $repo->pendientesCicRespuesta = ' del Subcontratista Acme';

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertFalse($resultado->exito);
        $this->assertSame(ResultadoCreacionSemana::BLOQUEO_CIC_PENDIENTE, $resultado->motivoBloqueo);
        $this->assertStringContainsString('Acme', $resultado->mensaje);
        $this->assertSame(['contarSemanasActivas', 'pendientesCic'], $repo->metodosLlamados());
        $this->assertNotContains('insertarSemanaActiva', $repo->metodosLlamados());
    }

    public function testSemanaAnteriorNoConfirmadaBloqueaParaNoAdmin(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 4;
        $repo->semanaAnteriorConfirmada = false;

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear(['esAdmin' => false]));

        $this->assertFalse($resultado->exito);
        $this->assertSame(ResultadoCreacionSemana::BLOQUEO_SEMANA_NO_CONFIRMADA, $resultado->motivoBloqueo);
        $this->assertStringContainsString('Semana 5', $resultado->mensaje);
        $this->assertStringContainsString('Semana 4', $resultado->mensaje);
        $this->assertNotContains('insertarSemanaActiva', $repo->metodosLlamados());
    }

    public function testExcepcionDeAdminPermiteCrearConSemanaAnteriorSinConfirmar(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 4;
        $repo->semanaAnteriorConfirmada = false;
        $repo->maxSemanaConsolidadaRespuesta = 4;

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear(['esAdmin' => true]));

        $this->assertTrue($resultado->exito);
        $this->assertSame(5, $resultado->semana);
        $this->assertContains('insertarSemanaActiva', $repo->metodosLlamados());
    }

    public function testPrimeraSemanaSinActividadesEnProgramaMaestroRevierteLaInsercion(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 0;
        $repo->programaMaestroTieneActividadesRespuesta = false;

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertFalse($resultado->exito);
        $this->assertSame(ResultadoCreacionSemana::BLOQUEO_PROGRAMA_MAESTRO_VACIO, $resultado->motivoBloqueo);
        $this->assertSame(
            ['contarSemanasActivas', 'pendientesCic', 'insertarSemanaActiva', 'programaMaestroTieneActividades', 'eliminarSemanaActivaRecienCreada'],
            $repo->metodosLlamados(),
        );
        $this->assertNotContains('copiarProgramaMaestroASemana', $repo->metodosLlamados());
    }

    public function testPrimeraSemanaConProgramaMaestroCopiaYNormalizaSinArrastreNiLineaBase(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 0;
        $repo->programaMaestroTieneActividadesRespuesta = true;

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertTrue($resultado->exito);
        $this->assertSame(1, $resultado->semana);
        $this->assertSame(
            [
                'contarSemanasActivas', 'pendientesCic', 'insertarSemanaActiva',
                'programaMaestroTieneActividades', 'copiarProgramaMaestroASemana',
                'normalizarCapitulos', 'resetearCapitulosSemana', 'finalizarEstadoSemana',
            ],
            $repo->metodosLlamados(),
        );
        // La línea base contractual y el arrastre son exclusivos de semanas >= 2 (no hay
        // "semana anterior" que arrastrar cuando conteo == 0).
        $this->assertNotContains('sincronizarArrastre', $repo->metodosLlamados());
        $this->assertNotContains('sembrarLineaBaseSiFalta', $repo->metodosLlamados());
    }

    public function testSemanaSiguienteLimpiaHuerfanosYCopiaHaciaAdelanteCuandoQuedanPorEncima(): void
    {
        // Fiel al legado (nueva_semana.php líneas 111-114): la comparación de "fusionar vs
        // copiar" reutiliza el MISMO valor de maxSemanaConsolidada leído antes del DELETE de
        // huérfanos, sin re-consultar — así que "hay huérfanos por encima" (max > semanaCrear)
        // y "fusionar" (max == semanaCrear) son ramas mutuamente excluyentes.
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 2;
        $repo->maxSemanaConsolidadaRespuesta = 5; // huérfanas por encima de la semana 3 a crear

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertTrue($resultado->exito);
        $this->assertSame(3, $resultado->semana);
        $this->assertSame(
            [
                'contarSemanasActivas', 'pendientesCic', 'semanaConfirmada', 'insertarSemanaActiva',
                'maxSemanaConsolidada', 'eliminarSemanasConsolidadasSuperioresA', 'copiarSemanaConsolidadaHaciaAdelante',
                'normalizarCapitulos', 'resetearCapitulosSemana', 'resetearFilasOperativasNulas',
                'sincronizarArrastre', 'sembrarLineaBaseSiFalta', 'finalizarEstadoSemana',
            ],
            $repo->metodosLlamados(),
        );
    }

    public function testSemanaSiguienteFusionaCuandoYaHayFilasParaEsaSemanaSinHuerfanos(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 2;
        $repo->maxSemanaConsolidadaRespuesta = 3; // ya existen filas de la semana 3 a crear

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertTrue($resultado->exito);
        $this->assertNotContains('eliminarSemanasConsolidadasSuperioresA', $repo->metodosLlamados());
        $this->assertContains('fusionarSemanaRecreada', $repo->metodosLlamados());
        $this->assertNotContains('copiarSemanaConsolidadaHaciaAdelante', $repo->metodosLlamados());
    }

    public function testSemanaSiguienteCopiaHaciaAdelanteCuandoNoHayFilasPrevias(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 2;
        $repo->maxSemanaConsolidadaRespuesta = 2; // nada por encima de la semana 3 a crear, ni fusión

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear());

        $this->assertTrue($resultado->exito);
        $this->assertNotContains('eliminarSemanasConsolidadasSuperioresA', $repo->metodosLlamados());
        $this->assertContains('copiarSemanaConsolidadaHaciaAdelante', $repo->metodosLlamados());
        $this->assertNotContains('fusionarSemanaRecreada', $repo->metodosLlamados());
    }

    public function testRangoDeSieteDiasSeCalculaDesdeLaFechaDeInicio(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 0;

        $resultado = (new WeekAdministrationService($repo))->crear($this->comandoCrear(['fechaInicio' => '2026-08-24']));

        $this->assertSame('2026-08-24', $resultado->fechaInicio);
        $this->assertSame('2026-08-30', $resultado->fechaFin);
    }

    public function testDiferenciaConstruccionVsPreConstruccionViajaAlRepositorio(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->conteoSemanasActivas = 0;

        (new WeekAdministrationService($repo))->crear($this->comandoCrear(['preConstruccion' => true]));

        [, $args] = $repo->log[array_search('copiarProgramaMaestroASemana', $repo->metodosLlamados(), true)];
        $this->assertTrue($args[2], 'el flag Pre-Construcción debe llegar intacto al repositorio');
    }

    public function testEliminarUltimaSemanaEjecutaCascadaYRegistraActividad(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->semanaMaximaRespuesta = 6;

        $resultado = (new WeekAdministrationService($repo))->eliminarUltima(new EliminarSemanaComando(73, 6));

        $this->assertTrue($resultado->exito);
        $this->assertSame(6, $resultado->semanaEliminada);
        $this->assertSame(5, $resultado->nuevaSemanaMaxima);
        $this->assertSame(['semanaMaxima', 'eliminarCascada', 'registrarActividad'], $repo->metodosLlamados());
    }

    public function testEliminarUnaSemanaQueNoEsLaMaximaSeBloqueaSinTocarNada(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->semanaMaximaRespuesta = 6;

        $resultado = (new WeekAdministrationService($repo))->eliminarUltima(new EliminarSemanaComando(73, 4));

        $this->assertFalse($resultado->exito);
        $this->assertSame(ResultadoEliminacionSemana::BLOQUEO_NO_ES_LA_ULTIMA, $resultado->motivoBloqueo);
        $this->assertSame(['semanaMaxima'], $repo->metodosLlamados());
    }

    public function testEliminarSinSemanasActivasSeBloqueaSinTocarNada(): void
    {
        $repo = new FakeWeekAdministrationRepository();
        $repo->semanaMaximaRespuesta = 0;

        $resultado = (new WeekAdministrationService($repo))->eliminarUltima(new EliminarSemanaComando(73, 1));

        $this->assertFalse($resultado->exito);
        $this->assertSame(ResultadoEliminacionSemana::BLOQUEO_SIN_SEMANAS, $resultado->motivoBloqueo);
        $this->assertSame(['semanaMaxima'], $repo->metodosLlamados());
    }
}
