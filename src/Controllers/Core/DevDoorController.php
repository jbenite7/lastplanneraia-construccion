<?php

declare(strict_types=1);

namespace App\Controllers\Core;

use App\Core\DevDoor;
use Database;

/**
 * Entrada de desarrollo: abre sesión sin teclear credenciales.
 *
 * GET /dev/entrar?u=<login>&p=<Proyecto_Proceso>
 *
 * La ruta solo se registra si DevDoor::isOpen() (ver public/index.php), de modo que fuera
 * de desarrollo no existe. Cualquier rechazo dentro del controlador responde 404 y no un
 * mensaje que distinga la causa: quien no debería estar aquí tampoco debería aprender por
 * qué falló.
 *
 * Ver docs/superpowers/specs/2026-07-30-dev-door-design.md
 */
class DevDoorController
{
    public function enter(): void
    {
        $login = trim((string) ($_GET['u'] ?? ''));

        if (!DevDoor::allows($login)) {
            $this->notFound();
        }

        if (!$this->userIsActive($login)) {
            $this->notFound();
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = $login;
        $_SESSION['nombreUsuario'] = $login;
        $_SESSION['timeout'] = time();

        // El proyecto se elige después del login en el flujo real, así que aquí se limpia
        // lo que hubiera de una entrada anterior antes de establecer el nuevo contexto.
        unset(
            $_SESSION['proyecto'],
            $_SESSION['project_id'],
            $_SESSION['db'],
            $_SESSION['permiso'],
            $_SESSION['permiso_canonico'],
            $_SESSION['area'],
            $_SESSION['semana'],
            $_SESSION['pdcActivo'],
        );

        $proyecto = trim((string) ($_GET['p'] ?? ''));

        if ($proyecto === '') {
            header('Location: /proyectos');
            exit();
        }

        // Delega en la lógica real de entrada a proyecto: verifica membresía contra
        // project_members, normaliza el rol y resuelve la semana de aterrizaje.
        (new ProjectSelectorController())->enterProject($login, $proyecto);
    }

    private function userIsActive(string $login): bool
    {
        try {
            $stmt = Database::getInstance()
                ->prepare('SELECT activo FROM general_usuarios WHERE usuario = ? LIMIT 1');
            $stmt->execute([$login]);
            $user = $stmt->fetch();
        } catch (\Throwable $e) {
            error_log('DevDoor: no se pudo validar el usuario: ' . $e->getMessage());

            return false;
        }

        return $user !== false && (int) ($user['activo'] ?? 0) === 1;
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit();
    }
}
