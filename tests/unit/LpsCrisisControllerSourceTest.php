<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * T02-AC-113 y "safe errors" (Tarea 6, brief §Paso 1): dos propiedades del código fuente que no se
 * pueden ejercitar por HTTP sin DML (la fixture no tiene actor elegible por escritura hoy — ver
 * task-6-report.md), así que se verifican por inspección del código, igual que hace
 * `tests/test_t02_lps_caller_census.mjs` para otras invariantes de este mismo frente.
 */
#[Group('puro')]
final class LpsCrisisControllerSourceTest extends TestCase
{
    private const CONTROLLER = __DIR__ . '/../../src/Controllers/Api/LpsApiController.php';
    private const SERVICE = __DIR__ . '/../../src/Services/Lps/LpsCrisisService.php';
    private const LEGACY_REPO = __DIR__ . '/../../src/Services/Lps/LpsLegacyCrisisRepository.php';

    public function testElControladorNiElServicioDeCrisisLlamaElAutoEscalamientoNocturno(): void
    {
        // El nombre del método SÍ puede aparecer en un comentario explicando que no se llama
        // (como en LpsCrisisService y en este propio archivo); lo que no puede aparecer es una
        // invocación real: `->escalarAlertasActivas(`.
        foreach ([self::CONTROLLER, self::SERVICE, self::LEGACY_REPO] as $archivo) {
            $fuente = (string) file_get_contents($archivo);
            self::assertStringNotContainsString(
                '->escalarAlertasActivas(',
                $fuente,
                basename($archivo) . ' no debe invocar el auto-escalamiento nocturno (T02-AC-113).',
            );
            self::assertStringNotContainsString(
                'nivel_actual = nivel_actual + 1',
                $fuente,
                basename($archivo) . ' no debe incrementar nivel_actual: eso es exclusivo del cron.',
            );
        }
    }

    public function testRegistrarYCerrarCrisisNuncaEcoanElMensajeCrudoDeUnaExcepcion(): void
    {
        $fuente = (string) file_get_contents(self::CONTROLLER);
        $inicioRegistrar = strpos($fuente, 'function registerCrisis');
        $inicioCerrar = strpos($fuente, 'function closeCrisis');
        self::assertIsInt($inicioRegistrar);
        self::assertIsInt($inicioCerrar);

        $finRegistrar = strpos($fuente, 'function closeCrisis', $inicioRegistrar);
        $finCerrar = strlen($fuente);

        $cuerpoRegistrar = substr($fuente, $inicioRegistrar, $finRegistrar - $inicioRegistrar);
        $cuerpoCerrar = substr($fuente, $inicioCerrar, $finCerrar - $inicioCerrar);

        foreach (['registerCrisis' => $cuerpoRegistrar, 'closeCrisis' => $cuerpoCerrar] as $nombre => $cuerpo) {
            // $t->getMessage() sólo puede aparecer dentro de una llamada a error_log() (servidor);
            // nunca dentro de un json_encode/'mensaje' que vaya al cliente.
            self::assertMatchesRegularExpression(
                '/error_log\([^;]*\$t->getMessage\(\)/',
                $cuerpo,
                "$nombre debe loguear la excepción server-side.",
            );
            self::assertDoesNotMatchRegularExpression(
                '/json_encode\([^;]*\$t->getMessage\(\)/s',
                $cuerpo,
                "$nombre no debe filtrar \$t->getMessage() en el JSON de respuesta al cliente.",
            );
        }
    }
}
