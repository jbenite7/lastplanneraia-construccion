<?php

declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\Security;
use Admin\Models\User;
use App\Core\DevDoor;
use App\Security\RbacService;

/**
 * Entrada de desarrollo para el panel `admin/`: abre sesión sin teclear credenciales.
 *
 * GET /admin/dev/entrar?u=<login>
 *
 * Reutiliza `App\Core\DevDoor` tal cual (no una copia bajo `Admin\Core`): `admin/` comparte
 * proceso, `.env` y `APP_ENV` con la app principal, así que los tres candados son los mismos.
 * La ruta solo se registra si `DevDoor::isOpen()` (ver admin/public/index.php), de modo que
 * fuera de desarrollo no existe — 404 natural del router, nunca 403.
 *
 * Escribe exactamente el mismo `$_SESSION['admin_user']` que `AuthController::login()`
 * (mismas cuatro subclaves, mismo `getHighestRoleForUser()` vía `User::findByUsername()`,
 * mismo `RbacService::normalizeRole()`), así que `requireAdminRole()` y el resto de guards
 * de `admin/` se aplican después exactamente igual que con un login real.
 *
 * Ver docs/superpowers/specs/2026-08-03-admin-dev-door-design.md
 */
class DevDoorController extends BaseController
{
    private $userModel;
    private $rbac;

    public function __construct()
    {
        $db = \Database::getInstance();
        $this->userModel = new User($db);
        $this->rbac = new RbacService($db);
    }

    public function enter(): void
    {
        $login = trim((string) ($_GET['u'] ?? ''));

        if (!DevDoor::allows($login)) {
            $this->notFound();
        }

        $user = $this->userModel->findByUsername($login);

        if (!$user || (int) ($user['activo'] ?? 0) !== 1) {
            $this->notFound();
        }

        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'usuario' => $user['usuario'],
            'permiso' => $this->rbac->normalizeRole($user['permiso']),
        ];

        Security::regenerateSession();

        header('Location: /admin/');
        exit();
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit();
    }
}
