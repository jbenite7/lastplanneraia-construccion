<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Lps\LpsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Caracteriza los DOS calculadores de estado de Programa General, que hasta hoy no tenian
 * ninguna prueba: `pg_calculate_status()` (legacy, con constantes PG_STATUS_*) y
 * `LpsService::calculateGeneralStatus()` (con literales). Son la misma clasificacion escrita
 * dos veces, y de ellas depende el `Estado` de 65.549 filas.
 *
 * Se escribio ANTES de tocar nada, a proposito: es caracterizacion, no TDD. Primero se fija lo
 * que el codigo hace hoy, y solo despues se cambia — porque cambiar la clasificacion de todo un
 * cronograma sin red seria adivinar si algo se rompio.
 *
 * Nivel `puro`: ninguna de las dos consulta la base.
 */
#[Group('puro')]
final class EstadoProgramaGeneralTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Legacy/estado_programa_general.php';
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function casosDeEstado(): array
    {
        // Todos los casos usan la semana del 2026-08-17 al 23 como semana activa.
        return [
            'un capitulo es capitulo antes de mirar fechas' => [
                ['titulo' => 1, 'ej' => 0.0, 'fi' => '2026-09-01', 'ff' => '2026-09-30',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Capítulo'],
            'ejecutado completo es terminada' => [
                ['titulo' => 0, 'ej' => 1.0, 'fi' => '2026-08-01', 'ff' => '2026-08-10',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Terminada'],
            'sin fechas y sin avance es sin datos' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => null, 'ff' => null,
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Sin Datos'],
            'empieza antes de la semana y no ha arrancado: atrasada' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-08-03', 'ff' => '2026-08-14',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Atrasada'],
            'empieza dentro de la semana: debe iniciar' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-08-19', 'ff' => '2026-08-28',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Debe Iniciar'],
            // Este caso NO describe un acuerdo: describe la divergencia medida el 2026-08-19.
            // El legacy usa PG_STATUS_EPS = 0.001 y responde `En Curso`; LpsService tiene un 0.1
            // suelto en esta rama y responde `Sin Datos`. Se anade aqui para que la prueba de
            // paridad la nombre antes de arreglarla.
            'sin fechas con avance del 5%: aqui los dos discrepan hoy' => [
                ['titulo' => 0, 'ej' => 0.05, 'fi' => null, 'ff' => null,
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'En Curso'],
            // El borde exacto de la regla de 7+ semanas, probado por los dos lados. Sobre la
            // semana del 17-ago: 2026-09-28 son 42 dias = offset 6, y 2026-10-05 son 49 = offset 7.
            'empieza en 6 semanas: sigue siendo actividad futura' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-09-28', 'ff' => '2026-10-09',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Actividad Futura'],
            'empieza en 7 semanas justas: fuera de ventana' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-10-05', 'ff' => '2026-10-16',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Fuera de Ventana'],
            'empieza en tres semanas: actividad futura' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-09-07', 'ff' => '2026-09-18',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Actividad Futura'],
        ];
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('casosDeEstado')]
    public function testElCalculadorLegacyClasifica(array $c, string $esperado): void
    {
        $this->assertSame($esperado, pg_calculate_status(
            $c['titulo'],
            $c['ej'],
            $c['fi'],
            $c['ff'],
            $c['fs'],
            $c['fe'],
        ));
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('casosDeEstado')]
    public function testElCalculadorDeLpsServiceClasificaIgual(array $c, string $esperado): void
    {
        $this->assertSame($esperado, (new LpsService())->calculateGeneralStatus(
            $c['titulo'],
            $c['ej'],
            $c['fi'],
            $c['ff'],
            $c['fs'],
            $c['fe'],
        ));
    }

    /**
     * La paridad es el invariante: dos implementaciones de la misma clasificacion tienen que
     * responder lo mismo. Esta es la prueba que se pondra roja cuando alguien toque una y olvide
     * la otra, que es exactamente como llegaron a divergir en el umbral de la rama sin fechas.
     *
     * @param array<string, mixed> $c
     */
    #[DataProvider('casosDeEstado')]
    public function testLosDosCalculadoresCoinciden(array $c, string $esperadoIgnorado): void
    {
        $this->assertSame(
            pg_calculate_status($c['titulo'], $c['ej'], $c['fi'], $c['ff'], $c['fs'], $c['fe']),
            (new LpsService())->calculateGeneralStatus(
                $c['titulo'],
                $c['ej'],
                $c['fi'],
                $c['ff'],
                $c['fs'],
                $c['fe'],
            ),
        );
    }
}
