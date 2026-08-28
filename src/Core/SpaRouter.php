<?php

namespace App\Core;

/**
 * Decide qué rutas sirve la SPA y cuáles siguen siendo del sitio PHP.
 *
 * La convivencia conserva las URLs legadas: solo los prefijos declarados aquí
 * pasan al shell React. Cada módulo migrado añade su prefijo a la constante.
 */
class SpaRouter
{
    /** Prefijos que ya sirve la SPA. Crece un renglón por módulo migrado. */
    public const RUTAS_MIGRADAS = ['/app'];

    public static function sirveLaSpa(string $ruta): bool
    {
        // La API responde JSON siempre, aunque una ruta migrada exista cerca.
        if (str_starts_with($ruta, '/api/')) {
            return false;
        }

        // Los archivos del bundle se sirven directamente desde public/app/assets.
        if ($ruta === '/app/assets' || str_starts_with($ruta, '/app/assets/')) {
            return false;
        }

        foreach (self::RUTAS_MIGRADAS as $prefijo) {
            if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
                return true;
            }
        }

        return false;
    }
}
