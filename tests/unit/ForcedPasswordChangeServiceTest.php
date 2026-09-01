<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Auth\AuthenticationService;
use App\Services\Auth\ForcedPasswordChangeService;
use App\Services\Auth\UserPasswordService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Aísla la transición de sesión del cambio obligatorio de contraseña, hoy dispersa entre
 * `LoginController::updatePassword()`/`cancelPasswordChange()` y `AuthenticationService`.
 * Usa mocks de `UserPasswordService` y `AuthenticationService`: no toca base de datos ni HTTP.
 *
 * Nivel `puro`.
 */
#[Group('puro')]
final class ForcedPasswordChangeServiceTest extends TestCase
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

    public function testChangePromotesOnlyAfterPasswordWasPersisted(): void
    {
        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        $passwords = $this->createMock(UserPasswordService::class);
        $authentication = $this->createMock(AuthenticationService::class);
        $passwords->expects(self::once())->method('changePasswordForUsername')
            ->with('fixture', 'Nueva!', 'Nueva!', true)
            ->willReturn(['success' => true, 'message' => null, 'fieldErrors' => []]);
        $authentication->expects(self::once())->method('completePasswordChange')->with('fixture');

        $result = (new ForcedPasswordChangeService($passwords, $authentication))
            ->change('Nueva!', 'Nueva!');
        self::assertTrue($result['success']);
    }

    public function testChangeDoesNotPromoteWhenPersistenceFails(): void
    {
        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        $passwords = $this->createMock(UserPasswordService::class);
        $authentication = $this->createMock(AuthenticationService::class);
        $passwords->expects(self::once())->method('changePasswordForUsername')
            ->with('fixture', 'debil', 'debil', true)
            ->willReturn([
                'success' => false,
                'message' => 'Contraseña inválida.',
                'fieldErrors' => ['password' => ['Debe tener al menos 6 caracteres.']],
            ]);
        $authentication->expects(self::never())->method('completePasswordChange');

        $result = (new ForcedPasswordChangeService($passwords, $authentication))
            ->change('debil', 'debil');

        self::assertFalse($result['success']);
        self::assertSame(['password' => ['Debe tener al menos 6 caracteres.']], $result['fieldErrors']);
    }

    public function testChangeRejectsWhenNoTransitionIsPending(): void
    {
        $_SESSION = ['usuario' => 'autenticado'];
        $passwords = $this->createMock(UserPasswordService::class);
        $authentication = $this->createMock(AuthenticationService::class);
        $passwords->expects(self::never())->method('changePasswordForUsername');
        $authentication->expects(self::never())->method('completePasswordChange');

        $result = (new ForcedPasswordChangeService($passwords, $authentication))
            ->change('Nueva!', 'Nueva!');

        self::assertFalse($result['success']);
        self::assertSame('Acceso no permitido', $result['message']);
    }

    public function testIsPendingRequiresTempUserFlagAndAbsenceOfFullSession(): void
    {
        $service = new ForcedPasswordChangeService(
            $this->createStub(UserPasswordService::class),
            $this->createStub(AuthenticationService::class),
        );

        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        self::assertTrue($service->isPending());

        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true, 'usuario' => 'autenticado'];
        self::assertFalse($service->isPending());

        $_SESSION = ['must_change_password' => true];
        self::assertFalse($service->isPending());

        $_SESSION = ['usuario_temp' => 'fixture'];
        self::assertFalse($service->isPending());
    }

    public function testCancelDestroysAPendingSession(): void
    {
        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        $service = new ForcedPasswordChangeService(
            $this->createStub(UserPasswordService::class),
            $this->createStub(AuthenticationService::class),
        );

        self::assertTrue($service->cancel());
        self::assertSame([], $_SESSION);
    }

    public function testCancelDoesNotDestroyACompleteSession(): void
    {
        $_SESSION = ['usuario' => 'autenticado', 'project_id' => 73];
        $service = new ForcedPasswordChangeService(
            $this->createStub(UserPasswordService::class),
            $this->createStub(AuthenticationService::class),
        );
        self::assertFalse($service->cancel());
        self::assertSame('autenticado', $_SESSION['usuario']);
        self::assertSame(73, $_SESSION['project_id']);
    }

    public function testCancelInvokesSessionDestructionOnlyWhenPending(): void
    {
        $calls = 0;
        $destroyer = function () use (&$calls): void {
            $calls++;
            $_SESSION = [];
        };

        $_SESSION = ['usuario' => 'autenticado'];
        $service = new ForcedPasswordChangeService(
            $this->createStub(UserPasswordService::class),
            $this->createStub(AuthenticationService::class),
            $destroyer,
        );
        self::assertFalse($service->cancel());
        self::assertSame(0, $calls);

        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        self::assertTrue($service->cancel());
        self::assertSame(1, $calls);
    }

    public function testCancelIsNoopWhenNothingIsPending(): void
    {
        $_SESSION = [];
        $service = new ForcedPasswordChangeService(
            $this->createStub(UserPasswordService::class),
            $this->createStub(AuthenticationService::class),
        );
        self::assertFalse($service->cancel());
        self::assertSame([], $_SESSION);
    }

    public function testCompletePasswordChangePromotesUserAndClearsProjectContext(): void
    {
        $_SESSION = [
            'usuario_temp' => 'fixture',
            'must_change_password' => true,
            'proyecto' => 'obra-1',
            'permiso' => 'R',
        ];

        $authentication = new AuthenticationService($this->createStub(\Database::class));
        $authentication->completePasswordChange('fixture');

        self::assertSame('fixture', $_SESSION['usuario']);
        self::assertArrayNotHasKey('usuario_temp', $_SESSION);
        self::assertArrayNotHasKey('must_change_password', $_SESSION);
        self::assertArrayNotHasKey('proyecto', $_SESSION);
        self::assertArrayNotHasKey('permiso', $_SESSION);
    }

    public function testCompletePasswordChangeRejectsMismatchedUsername(): void
    {
        $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
        $authentication = new AuthenticationService($this->createStub(\Database::class));

        $this->expectException(\LogicException::class);
        $authentication->completePasswordChange('otro');
    }

    /**
     * `POST /login` es ruta pública sin guardia de sesión vacía: un usuario ya autenticado
     * puede volver a postear el formulario. Si la cuenta objetivo exige cambio de contraseña,
     * `beginPasswordChange()` debe limpiar la identidad y el contexto de proyecto previos —
     * simétrico a lo que `beginAuthenticatedSession()` hace en la dirección contraria — para
     * que `usuario` y `usuario_temp` nunca coexistan.
     */
    public function testBeginPasswordChangeClearsPriorAuthenticatedIdentityAndProject(): void
    {
        $_SESSION = [
            'usuario' => 'anterior',
            'proyecto' => 'obra-1',
            'db' => 'obra_1',
            'semana' => 3,
            'permiso' => 'R',
            'pdcActivo' => true,
        ];

        $authentication = new AuthenticationService($this->createStub(\Database::class));
        $authentication->beginPasswordChange('nuevo', ['nombre' => 'Nuevo Usuario']);

        self::assertArrayNotHasKey('usuario', $_SESSION);
        self::assertSame('nuevo', $_SESSION['usuario_temp']);
        self::assertTrue($_SESSION['must_change_password']);
        self::assertArrayNotHasKey('proyecto', $_SESSION);
        self::assertArrayNotHasKey('db', $_SESSION);
        self::assertArrayNotHasKey('semana', $_SESSION);
        self::assertArrayNotHasKey('permiso', $_SESSION);
        self::assertArrayNotHasKey('pdcActivo', $_SESSION);
    }
}
