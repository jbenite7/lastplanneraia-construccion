<?php

namespace App\Core;

/**
 * Interruptores globales leídos de `general_flags`.
 *
 * Contrato (spec 2026-08-20): `isOn()` devuelve true SOLO si la fila existe y su
 * valor es exactamente '1'. Fila ausente, tabla ausente, error de base o valor
 * raro => false. El fail-safe es deliberado: un flag que no se puede leer se
 * comporta como apagado y nunca tumba la página con un 500.
 *
 * Cache por request (estático): una consulta por clave y por request. Sin TTL ni
 * invalidación — los cambios se hacen desde /admin y aplican al siguiente request.
 */
final class FlagsService
{
    /** @var array<string,bool> */
    private static array $cache = [];

    /** @var array<string,bool>|null Solo para pruebas: evita tocar la base. */
    private static ?array $override = null;

    public static function isOn(string $clave): bool
    {
        if (self::$override !== null) {
            return self::$override[$clave] ?? false;
        }

        if (array_key_exists($clave, self::$cache)) {
            return self::$cache[$clave];
        }

        try {
            $valor = \Database::getInstance()
                ->query('SELECT valor FROM general_flags WHERE clave = ? LIMIT 1', [$clave])
                ->fetchColumn();
            self::$cache[$clave] = ($valor === '1');
        } catch (\Throwable) {
            self::$cache[$clave] = false;
        }

        return self::$cache[$clave];
    }

    /**
     * @param array<string,bool>|null $flags Mapa clave => estado; null limpia el
     *                                       override y el cache.
     */
    public static function overrideForTests(?array $flags): void
    {
        self::$override = $flags;
        self::$cache = [];
    }
}
