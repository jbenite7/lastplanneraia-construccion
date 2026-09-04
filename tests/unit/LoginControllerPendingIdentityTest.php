<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Auth\LoginController;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * `LoginController::updatePassword()` resolvía la identidad con
 * `$_SESSION['usuario'] ?? $_SESSION['usuario_temp']`, priorizando la identidad completa
 * *vieja* sobre el cambio de contraseña pendiente. En el estado donde ambas conviven —un
 * cambio obligatorio disparado sobre una sesión que ya tenía una identidad completa, que
 * `AuthenticationService::beginPasswordChange()` ya no permite pero que la propia forma de la
 * guarda de `updatePassword()` (`!isset(usuario) && !isset(usuario_temp)`) no impedía— le
 * habría cambiado la contraseña a la cuenta equivocada. Corregido en la Tarea 5, S01: la
 * identidad pendiente manda.
 *
 * Se prueba por reflexión sobre `identidadParaCambioDeClave()`, el método privado extraído
 * para hacer esta resolución observable sin invocar `changePasswordForUsername()` contra la
 * base — nivel `puro`.
 */
#[Group('puro')]
final class LoginControllerPendingIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testPrefersThePendingIdentityWhenBothCoexist(): void
    {
        $_SESSION = ['usuario' => 'identidad-anterior', 'usuario_temp' => 'identidad-pendiente'];

        self::assertSame('identidad-pendiente', $this->resolve());
    }

    public function testFallsBackToTheCompleteIdentityWhenNothingIsPending(): void
    {
        $_SESSION = ['usuario' => 'identidad-completa'];

        self::assertSame('identidad-completa', $this->resolve());
    }

    public function testReturnsNullWhenNeitherIdentityExists(): void
    {
        $_SESSION = [];

        self::assertNull($this->resolve());
    }

    private function resolve(): ?string
    {
        $method = new \ReflectionMethod(LoginController::class, 'identidadParaCambioDeClave');
        $method->setAccessible(true);

        return $method->invoke(null);
    }
}
