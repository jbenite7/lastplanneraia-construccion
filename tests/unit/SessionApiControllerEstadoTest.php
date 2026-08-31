<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Api\SessionApiController;
use App\Core\SessionMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre `SessionApiController::estadoParaRazon()`, el mapeo puro razón → estado
 * de arranque (spec T01 §8.2: `state` solo admite `anonymous`,
 * `password_change_required` o `authenticated`).
 *
 * Existe como test `puro` (sin sesión ni base de datos) específicamente porque
 * dos de los ocho escenarios que pide la Tarea 2 no son practicables por HTTP
 * sin romper reglas del repo: `inactive` necesitaría un usuario sembrado con
 * `activo=0` que no existe hoy, y `session_unverified` ("fallo recuperable de
 * servidor") solo ocurre cuando `Database::getInstance()` lanza — forzarlo de
 * verdad en un test HTTP requeriría tumbar la conexión real. El contrato de
 * *forma* de esos dos casos (qué `state`/`reason` produce el controlador) se
 * verifica aquí; `tests/test_api_session_contract.php` cubre por HTTP los seis
 * escenarios restantes (anonymous, password_change_required,
 * authenticated-sin-proyecto, ready, timeout, stale_session) con sesiones
 * artificiales reales.
 */
#[Group('puro')]
final class SessionApiControllerEstadoTest extends TestCase
{
    /** @return array<string, array{string|null, array{state:string, authenticated:bool, reason:string|null}}> */
    public static function razones(): array
    {
        return [
            'sin sesión (missing_session) → anonymous' => [
                'missing_session',
                ['state' => 'anonymous', 'authenticated' => false, 'reason' => 'missing_session'],
            ],
            'timeout → anonymous (el cliente lo lee como expirada)' => [
                'timeout',
                ['state' => 'anonymous', 'authenticated' => false, 'reason' => 'timeout'],
            ],
            'inactive → anonymous' => [
                'inactive',
                ['state' => 'anonymous', 'authenticated' => false, 'reason' => 'inactive'],
            ],
            'stale_session → anonymous' => [
                'stale_session',
                ['state' => 'anonymous', 'authenticated' => false, 'reason' => 'stale_session'],
            ],
            'session_unverified (fallo recuperable de servidor) → anonymous' => [
                'session_unverified',
                ['state' => 'anonymous', 'authenticated' => false, 'reason' => 'session_unverified'],
            ],
            'password_change_required → su propio estado, sin reason' => [
                SessionMiddleware::REASON_PASSWORD_CHANGE_REQUIRED,
                ['state' => 'password_change_required', 'authenticated' => false, 'reason' => null],
            ],
            'sin razón (null) → authenticated' => [
                null,
                ['state' => 'authenticated', 'authenticated' => true, 'reason' => null],
            ],
        ];
    }

    /** @param array{state:string, authenticated:bool, reason:string|null} $esperado */
    #[DataProvider('razones')]
    public function testMapeaCadaRazonASuEstado(?string $razon, array $esperado): void
    {
        $this->assertSame($esperado, SessionApiController::estadoParaRazon($razon));
    }

    public function testPasswordChangeRequiredNuncaSeConfundeConAnonymous(): void
    {
        $estado = SessionApiController::estadoParaRazon(SessionMiddleware::REASON_PASSWORD_CHANGE_REQUIRED);

        $this->assertSame('password_change_required', $estado['state']);
        $this->assertNotSame('anonymous', $estado['state']);
    }
}
