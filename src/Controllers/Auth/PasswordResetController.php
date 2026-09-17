<?php

namespace App\Controllers\Auth;

use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;

class PasswordResetController
{
    private $service;

    public function __construct()
    {
        $this->service = new PasswordResetService();
    }

    public function reset()
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $tokenData = $this->service->findValidToken($token, 'app');

        if ($tokenData === null) {
            $this->renderReset($token, false, 'El enlace no es válido o ya expiró. Solicita uno nuevo.', 'danger');
            return;
        }

        $this->renderReset($token, true);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /password/forgot');
            exit();
        }

        $token = trim((string) ($_POST['token'] ?? ''));
        if (!CsrfTokenManager::validate($_POST['csrf_token'] ?? null, 'password_reset')) {
            $this->renderReset($token, $this->service->findValidToken($token, 'app') !== null, 'No fue posible validar la solicitud. Intenta nuevamente.', 'danger');
            return;
        }

        $result = $this->service->reset(
            $token,
            'app',
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? ''),
        );

        if ($result['success']) {
            header('Location: /login?reset=1');
            exit();
        }

        $this->renderReset($token, $this->service->findValidToken($token, 'app') !== null, (string) $result['message'], 'danger');
    }

    private function renderReset(string $token, bool $isTokenValid, string $message = '', string $messageType = ''): void
    {
        $csrfToken = CsrfTokenManager::generate('password_reset');
        require PROJECT_ROOT . '/views/auth/password-reset.view.php';
    }
}
