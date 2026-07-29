<?php

declare(strict_types=1);

namespace App\Support;

use Database;

/**
 * Resuelve datos del usuario autenticado que la sesión no guarda directamente.
 *
 * La sesión solo guarda el login (`$_SESSION['usuario']`), no el id numérico: hay que resolverlo
 * contra `general_usuarios`. Sin login en sesión o sin coincidencia devuelve `null` en vez de
 * romper — quien llama decide si eso inutiliza una funcionalidad puntual (p. ej. un filtro) o si
 * es un error real.
 */
final class SesionUsuario
{
    public static function resolverId(Database $db): ?int
    {
        $login = (string) ($_SESSION['usuario'] ?? '');
        if ($login === '') {
            return null;
        }

        $stmt = $db->prepare('SELECT id FROM general_usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$login]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}
