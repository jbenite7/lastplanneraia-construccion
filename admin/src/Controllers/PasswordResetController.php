<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use App\Services\Auth\PasswordResetService;

class PasswordResetController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = new PasswordResetService();
    }

    public function forgotView()
    {
        $this->renderForgot();
    }

    public function sendResetLink()
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->renderForgot('No fue posible validar la solicitud. Intenta nuevamente.', 'danger', $email);
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderForgot('Ingresa un correo electrónico válido.', 'danger', $email);
            return;
        }

        $this->service->request($email, 'admin');
        $this->renderForgot(
            'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.',
            'success',
        );
    }

    public function resetView()
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $tokenData = $this->service->findValidToken($token, 'admin');

        if ($tokenData === null) {
            $this->renderReset($token, false, 'El enlace no es válido o ya expiró. Solicita uno nuevo.', 'danger');
            return;
        }

        $this->renderReset($token, true);
    }

    public function resetPassword()
    {
        $token = trim((string) ($_POST['token'] ?? ''));

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->renderReset($token, $this->service->findValidToken($token, 'admin') !== null, 'No fue posible validar la solicitud. Intenta nuevamente.', 'danger');
            return;
        }

        $result = $this->service->reset(
            $token,
            'admin',
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? ''),
        );

        if ($result['success']) {
            header('Location: /admin/login?reset=1');
            exit;
        }

        $this->renderReset($token, $this->service->findValidToken($token, 'admin') !== null, (string) $result['message'], 'danger');
    }

    private function renderForgot(string $message = '', string $messageType = '', string $emailValue = ''): void
    {
        $this->render('password-forgot', [
            'title' => 'Restablecer Contraseña - Admin Panel',
            'csrf_token' => Security::generateCsrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'emailValue' => $emailValue,
        ], false);
    }

    private function renderReset(string $token, bool $isTokenValid, string $message = '', string $messageType = ''): void
    {
        $this->render('password-reset', [
            'title' => 'Nueva Contraseña - Admin Panel',
            'csrf_token' => Security::generateCsrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'token' => $token,
            'isTokenValid' => $isTokenValid,
        ], false);
    }
}
