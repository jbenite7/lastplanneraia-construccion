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

    public function forgot()
    {
        $this->renderForgot();
    }

    public function sendLink()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /password/forgot');
            exit();
        }

        $emailValue = trim((string) ($_POST['email'] ?? ''));
        if (!CsrfTokenManager::validate($_POST['csrf_token'] ?? null, 'password_forgot')) {
            $this->renderForgot('No fue posible validar la solicitud. Intenta nuevamente.', 'danger', $emailValue);
            return;
        }

        if ($emailValue === '' || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            $this->renderForgot('Ingresa un correo electrónico válido.', 'danger', $emailValue);
            return;
        }

        // El resultado se MIRA. Antes se descartaba y siempre se pintaba el mensaje de éxito, así
        // que una caída total del correo se veía igual que un envío correcto (B-10).
        $resultado = $this->service->request($emailValue, 'app');

        if ($resultado === PasswordResetService::RESULTADO_FALLIDO) {
            // Fallo de TRANSPORTE: el relay no responde, credenciales caducadas... Le pasaría a
            // cualquier dirección, así que decirlo no revela si esta tiene cuenta. El token ya se
            // borró en el servicio, de modo que reintentar es seguro.
            $this->renderForgot(
                'No pudimos enviar el correo en este momento por un problema técnico. '
                . 'Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador.',
                'danger',
                $emailValue,
            );
            return;
        }

        // `enviado` e `ignorado` comparten mensaje A PROPÓSITO: es lo que impide averiguar qué
        // correos tienen cuenta.
        $this->renderForgot(
            'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.',
            'success',
        );
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

    private function renderForgot(string $message = '', string $messageType = '', string $emailValue = ''): void
    {
        $csrfToken = CsrfTokenManager::generate('password_forgot');
        require PROJECT_ROOT . '/views/auth/password-forgot.view.php';
    }

    private function renderReset(string $token, bool $isTokenValid, string $message = '', string $messageType = ''): void
    {
        $csrfToken = CsrfTokenManager::generate('password_reset');
        require PROJECT_ROOT . '/views/auth/password-reset.view.php';
    }
}
