<?php

namespace App\Core;

/**
 * Decide qué rutas sirve la SPA y cuáles siguen siendo del sitio PHP.
 *
 * La convivencia conserva las URLs legadas: solo las rutas declaradas aquí pasan al shell React,
 * y solo por GET/HEAD. Cada módulo migrado añade su entrada a `RUTAS_EXACTAS_MIGRADAS` (rutas
 * puntuales, como `/login`) o a `PREFIJOS_MIGRADOS` (árboles enteros, como `/app`).
 */
class SpaRouter
{
    /**
     * Rutas puntuales que ya sirve la SPA. No cubren subrutas: `/login/cancelar` NO cae aquí a
     * menos que se liste explícitamente.
     */
    public const RUTAS_EXACTAS_MIGRADAS = ['/', '/login'];

    /** Prefijos que ya sirve la SPA (esa ruta y todo lo que cuelgue de ella). */
    public const PREFIJOS_MIGRADOS = ['/app'];

    /**
     * `$metodo` distingue lectura de mutación: solo GET/HEAD cruzan al shell React. POST
     * (por ejemplo `POST /login`) sigue yendo al adaptador PHP mientras dure la ventana de
     * rollback de la Tarea 13 — moverlo también habría hecho el rollback irreversible, porque
     * quitar una ruta del mapa no le devuelve la mutación al legado si el legado nunca la tuvo.
     *
     * Función pura, sin estado ni efecto de lado: producción (`public/index.php` y cualquier
     * otro llamador) nunca pasa el tercer/cuarto argumento, así que siempre corre contra las
     * constantes de esta clase, el único origen de verdad. El rollback se ejercita con
     * `coincideConMapa()` pasando arrays hipotéticos, sin tocar esta clase ni mutar nada
     * compartido entre llamadas (ver `tests/test_spa_frontera.php` y
     * `tests/test_shell_route_map_rollback.php`).
     *
     * @param list<string> $rutasExactas
     * @param list<string> $prefijos
     */
    public static function sirveLaSpa(
        string $ruta,
        string $metodo = 'GET',
        array $rutasExactas = self::RUTAS_EXACTAS_MIGRADAS,
        array $prefijos = self::PREFIJOS_MIGRADOS,
    ): bool {
        return self::coincideConMapa($ruta, $metodo, $rutasExactas, $prefijos);
    }

    /**
     * Núcleo puro de la decisión: dado un mapa de rutas (exactas + prefijos) y un método HTTP,
     * dice si esa combinación cae en el shell React. Separado de `sirveLaSpa()` para que el
     * rollback se pueda probar pasando mapas distintos por parámetro, sin editar constantes ni
     * depender de estado mutable compartido.
     *
     * @param list<string> $exactas
     * @param list<string> $prefijos
     */
    public static function coincideConMapa(
        string $ruta,
        string $metodo,
        array $exactas,
        array $prefijos,
    ): bool {
        // Solo lectura cruza al shell: POST, PUT, DELETE, etc. siguen su adaptador PHP.
        if (!in_array(strtoupper($metodo), ['GET', 'HEAD'], true)) {
            return false;
        }

        // La API responde JSON siempre, aunque una ruta migrada exista cerca.
        if (str_starts_with($ruta, '/api/')) {
            return false;
        }

        // Los archivos del bundle se sirven directamente desde public/app/assets.
        if ($ruta === '/app/assets' || str_starts_with($ruta, '/app/assets/')) {
            return false;
        }

        if (in_array($ruta, $exactas, true)) {
            return true;
        }

        foreach ($prefijos as $prefijo) {
            if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
                return true;
            }
        }

        return false;
    }
}
