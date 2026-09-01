<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Api\AuthApiController;
use App\Security\CsrfTokenManager;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\ForcedPasswordChangeService;
use App\Services\Auth\UserPasswordService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre, con dobles y sin tocar la base, las dos ramas de `/api/auth/*` que solo existen con
 * una cuenta `force_password_change=1`: el `next:'password_change'` de `login()` y el
 * `password_change_not_pending` de `changePassword()`.
 *
 * `tests/test_api_auth_contract.php` (nivel `http`) cubre el resto del contrato contra la app
 * servida, sin crear usuarios (Tarea 5, S01: crear y borrar fixtures de usuario choca con la
 * restricción del frente de no usar DDL/DML como evidencia).
 *
 * El CSRF real de `AuthApiController` pasa por `CsrfTokenManager`, que exige una sesión PHP
 * activa: `setUp()` abre una (sesión de proceso local, sin tocar MySQL) y siembra el token
 * esperado, igual que el resto del cuerpo de sesión que cada prueba maneja a mano.
 *
 * Nivel `puro`.
 */
#[Group('puro')]
final class AuthApiControllerTest extends TestCase
{
    private const CSRF_FORM_KEY = 'shell_api';

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $_SERVER['HTTP_X_CSRF_TOKEN'] = CsrfTokenManager::generate(self::CSRF_FORM_KEY);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        parent::tearDown();
    }

    public function testLoginRespondsPasswordChangeNextForAccountWithForcedPasswordChange(): void
    {
        $authentication = $this->createMock(AuthenticationService::class);
        $authentication->expects(self::once())->method('verifyCredentials')
            ->with('fixture', 'Cualquiera1!')
            ->willReturn(['usuario' => 'fixture', 'activo' => 1, 'force_password_change' => 1]);
        $authentication->expects(self::once())->method('beginPasswordChange')->with('fixture');
        $authentication->expects(self::never())->method('beginAuthenticatedSession');

        $controller = new TestableAuthApiController($this->createStub(\Database::class), $authentication);
        $controller->body = self::encode(['username' => 'fixture', 'password' => 'Cualquiera1!']);

        [$status, $body] = $this->invoke($controller, 'login');

        self::assertSame(200, $status);
        self::assertSame(['success' => true, 'next' => 'password_change', 'message' => null], $body);
    }

    public function testLoginRespondsProjectsNextForAnOrdinaryAccount(): void
    {
        $authentication = $this->createMock(AuthenticationService::class);
        $authentication->expects(self::once())->method('verifyCredentials')
            ->willReturn(['usuario' => 'fixture', 'activo' => 1, 'force_password_change' => 0]);
        $authentication->expects(self::once())->method('beginAuthenticatedSession')->with('fixture');
        $authentication->expects(self::never())->method('beginPasswordChange');

        $controller = new TestableAuthApiController($this->createStub(\Database::class), $authentication);
        $controller->body = self::encode(['username' => 'fixture', 'password' => 'Cualquiera1!']);

        [$status, $body] = $this->invoke($controller, 'login');

        self::assertSame(200, $status);
        self::assertSame(['success' => true, 'next' => 'projects', 'message' => null], $body);
    }

    public function testChangePasswordRespondsNotPendingWhenNoTransitionIsPending(): void
    {
        // Sesión sin usuario_temp: ForcedPasswordChangeService::isPending() es real (no un
        // doble), y esta rama depende exactamente de que sea false.
        $passwords = $this->createMock(UserPasswordService::class);
        $passwords->expects(self::never())->method('changePasswordForUsername');
        $authentication = $this->createStub(AuthenticationService::class);
        $forcedPasswordChange = new ForcedPasswordChangeService($passwords, $authentication);

        $controller = new TestableAuthApiController(
            $this->createStub(\Database::class),
            $this->createStub(AuthenticationService::class),
            $forcedPasswordChange,
        );
        $controller->body = self::encode(['password' => 'Nueva!123', 'confirmation' => 'Nueva!123']);

        [$status, $body] = $this->invoke($controller, 'changePassword');

        self::assertSame(401, $status);
        self::assertSame('password_change_not_pending', $body['code'] ?? null);
        self::assertSame(false, $body['success'] ?? null);
    }

    /**
     * Fija el puente de coexistencia: `frontend/src/lib/api/cliente.ts` solo lee `error.codigo`,
     * `error.mensaje` y `error.campos`; sin este bloque anidado, un 422 le llega al cliente
     * como mensaje genérico y sin ningún error por campo, pese a que el servidor sí los calculó.
     */
    public function testValidationErrorPublishesBothTheFlatAndTheNestedErrorForm(): void
    {
        $authentication = $this->createStub(AuthenticationService::class);
        $controller = new TestableAuthApiController($this->createStub(\Database::class), $authentication);
        $controller->body = self::encode(['username' => '', 'password' => '']);

        [$status, $body] = $this->invoke($controller, 'login');

        self::assertSame(422, $status);

        // Forma plana, para consumidores legados.
        self::assertSame('validation_error', $body['code'] ?? null);
        self::assertSame(false, $body['success'] ?? null);
        self::assertIsArray($body['fieldErrors'] ?? null);

        // Forma anidada, la que `pedir()` en cliente.ts realmente lee.
        self::assertIsArray($body['error'] ?? null);
        self::assertSame('validation_error', $body['error']['codigo'] ?? null);
        self::assertSame($body['message'] ?? null, $body['error']['mensaje'] ?? null);
        self::assertIsArray($body['error']['campos'] ?? null);
        self::assertSame($body['fieldErrors'], $body['error']['campos']);

        // `error.campos` es Record<string,string>: un mensaje por campo, no una lista.
        foreach ($body['error']['campos'] as $mensajePorCampo) {
            self::assertIsString($mensajePorCampo);
        }
    }

    /** @param array<string, mixed> $payload */
    private static function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    private function invoke(TestableAuthApiController $controller, string $method): array
    {
        ob_start();
        $controller->{$method}();
        $raw = (string) ob_get_clean();

        $status = http_response_code();
        self::assertIsInt($status);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return [$status, $decoded];
    }
}

/**
 * Expone el cuerpo de la petición como propiedad pública en vez de `php://input`, que no admite
 * rebind por prueba.
 */
final class TestableAuthApiController extends AuthApiController
{
    public string $body = '';

    protected function requestBody(): string
    {
        return $this->body;
    }
}
