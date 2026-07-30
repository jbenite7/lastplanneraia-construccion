<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Puerta de servicio de desarrollo: permite abrir una sesión sin teclear credenciales.
 *
 * Es un atajo deliberado sobre la autenticación, así que su única defensa es este candado.
 * Por eso son TRES condiciones independientes y todas obligatorias:
 *
 *   1. APP_ENV es development o testing (AppEnvironment cae a production ante cualquier
 *      valor desconocido, de modo que un .env corrupto CIERRA la puerta).
 *   2. La petición viene de la propia máquina o de la red privada de Docker.
 *   3. DEV_DOOR=1 explícito en .env, y DEV_DOOR_USERS no vacío.
 *
 * Además, la lista de usuarios admitidos son cuentas de prueba `test.*` sembradas: aunque
 * el candado fallara, no habría forma de suplantar a un usuario real del proyecto.
 *
 * Quien registra la ruta debe consultar isOpen() ANTES de registrarla, para que fuera de
 * desarrollo el endpoint devuelva 404 y no un 403 que confirmaría su existencia.
 *
 * Ver docs/superpowers/specs/2026-07-30-dev-door-design.md
 */
final class DevDoor
{
    public static function isOpen(): bool
    {
        return AppEnvironment::allowsInternalTools()
            && self::requestIsLocal()
            && self::flagEnabled()
            && self::allowedUsers() !== [];
    }

    /**
     * ¿Este login puede entrar por la puerta? Con el candado cerrado, nadie puede.
     */
    public static function allows(string $login): bool
    {
        if (!self::isOpen()) {
            return false;
        }

        $login = trim($login);

        return $login !== '' && in_array($login, self::allowedUsers(), true);
    }

    /**
     * @return list<string>
     */
    public static function allowedUsers(): array
    {
        $raw = (string) (self::env('DEV_DOOR_USERS') ?? '');

        $users = array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $user): bool => $user !== '',
        );

        return array_values($users);
    }

    private static function flagEnabled(): bool
    {
        return (string) (self::env('DEV_DOOR') ?? '') === '1';
    }

    private static function requestIsLocal(): bool
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        if ($remoteAddr === '') {
            return false;
        }

        if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        // Redes privadas: el contenedor `app` ve la IP interna de la red de Compose, no
        // la del host, así que sin esto la puerta nunca abriría dentro de Docker.
        return filter_var(
            $remoteAddr,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    private static function env(string $key): ?string
    {
        if (isset($_ENV[$key])) {
            return (string) $_ENV[$key];
        }

        $value = getenv($key);

        return $value === false ? null : $value;
    }
}
