<?php

declare(strict_types=1);

namespace App\Services\Auth;

/**
 * Centraliza la transición de sesión del cambio obligatorio de contraseña, hoy dispersa entre
 * la vista de login y `AuthenticationService`. Une la persistencia de la nueva clave
 * (`UserPasswordService`) con la promoción de sesión (`AuthenticationService`) en un único
 * punto: la promoción solo ocurre si la persistencia tuvo éxito.
 */
final class ForcedPasswordChangeService
{
    /**
     * `$sessionDestroyer` es el punto de extensión que hace observable la destrucción de
     * sesión en pruebas puras: por defecto replica `session_unset()` + `session_destroy()`
     * (lo que hace hoy `LoginController::cancelPasswordChange()`), pero bajo PHPUnit/CLI sin
     * sesión activa `session_destroy()` no es invocable, así que un test puede inyectar un
     * espía para comprobar que se llamó, sin invocar las funciones globales de sesión.
     */
    public function __construct(
        private UserPasswordService $passwords,
        private AuthenticationService $authentication,
        private ?\Closure $sessionDestroyer = null,
    ) {
    }

    /**
     * Una sesión de cambio obligatorio está pendiente cuando existe `usuario_temp` y
     * `must_change_password`, y todavía no hay una sesión completa (`usuario`). Esta última
     * condición es la que impide que esta transición se dispare sobre un usuario ya
     * autenticado.
     */
    public function isPending(): bool
    {
        $pendingUsername = $_SESSION['usuario_temp'] ?? null;

        return !empty($_SESSION['must_change_password'])
            && is_string($pendingUsername)
            && $pendingUsername !== ''
            && empty($_SESSION['usuario']);
    }

    /**
     * @return array{success:bool,message:?string,fieldErrors:array<string,list<string>>}
     */
    public function change(string $password, string $confirmation): array
    {
        if (!$this->isPending()) {
            return ['success' => false, 'message' => 'Acceso no permitido', 'fieldErrors' => []];
        }

        $username = (string) $_SESSION['usuario_temp'];
        $result = $this->passwords->changePasswordForUsername($username, $password, $confirmation, true);

        if ($result['success']) {
            $this->authentication->completePasswordChange($username);
        }

        return $result;
    }

    /**
     * Descarta la sesión pendiente. Es no-op sobre una sesión completa: el que llama nunca
     * puede tumbar la sesión de un usuario ya autenticado a través de esta transición.
     */
    public function cancel(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->destroySession();

        return true;
    }

    private function destroySession(): void
    {
        if ($this->sessionDestroyer !== null) {
            ($this->sessionDestroyer)();

            return;
        }

        session_unset();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
    }
}
