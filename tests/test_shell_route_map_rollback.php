<?php

// @requiere: puro

/**
 * Tarea 9 (T01), paso 2 — actualizado en la Tarea 13 (S01) cuando `sirveLaSpa()` pasó a tomar
 * método HTTP en vez de un mapa de rutas por parámetro. El ejercicio de rollback ahora vive en
 * `SpaRouter::coincideConMapa()`, que sigue siendo pura: recibe el mapa (exactas + prefijos) por
 * parámetro en cada llamada, sin mutar ningún estado compartido. Ver también
 * `tests/test_spa_frontera.php`, que ejercita el mismo mecanismo contra el corte real de S01
 * (`/` y `/login`).
 *
 * Prueba que quitar UNA pantalla migrada de muestra del mapa hace que esa ruta vuelva a caer en
 * su adaptador PHP (el router de `public/index.php` sigue teniendo `/programa-general` registrado
 * contra `ProgramaGeneralController`, sin tocar nada de este ejercicio), y que restaurar el mapa
 * hace que React vuelva a servirla. Usa `/programa-general` como "pantalla migrada de muestra"
 * hipotética: hoy NO está en `RUTAS_EXACTAS_MIGRADAS` (solo `/`, `/login` y el prefijo `/app` lo
 * están), así que este test simula el escenario general de rollback sin depender de que exista
 * una segunda pantalla ya migrada.
 *
 * Alcance explícito del rollback (plan T01, restricciones globales): el rollback cambia SOLO el
 * mapa explícito de rutas y sus adaptadores. Este test NO toca RLS/DataScope, no hace DDL/DML, no
 * edita `.env` ni sesión, y no dispara ningún I/O — todo el ejercicio vive en literales de array
 * PHP pasados por parámetro.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SpaRouter;

$fallos = 0;

function comprobarRollback(bool $condicion, string $mensaje): void
{
    global $fallos;
    if (!$condicion) {
        echo "FALLO: {$mensaje}\n";
        $fallos++;
    }
}

$exactasReales = SpaRouter::RUTAS_EXACTAS_MIGRADAS;
$prefijosReales = SpaRouter::PREFIJOS_MIGRADOS;
$exactasConPantallaDeMuestraMigrada = [...$exactasReales, '/programa-general'];

// --- 0. Baseline real: hoy '/programa-general' NO está migrada, es del sitio PHP. ---
comprobarRollback(
    !SpaRouter::coincideConMapa('/programa-general', 'GET', $exactasReales, $prefijosReales),
    "baseline: '/programa-general' hoy es del sitio PHP (no está en el mapa de exactas)"
);

// --- 1. Simular la migración de la pantalla de muestra: entra al mapa explícito. ---
comprobarRollback(
    SpaRouter::coincideConMapa('/programa-general', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "tras 'migrar' '/programa-general', React debe servirla"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/app', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "'/app' sigue migrada mientras se ejercita el rollback de la pantalla de muestra"
);

// --- 2. Rollback: se quita SOLO la pantalla de muestra del mapa explícito (vuelve al mapa real). ---
comprobarRollback(
    !SpaRouter::coincideConMapa('/programa-general', 'GET', $exactasReales, $prefijosReales),
    "tras el rollback, '/programa-general' debe volver a su adaptador PHP"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/app', 'GET', $exactasReales, $prefijosReales),
    "el rollback de UNA pantalla no debe arrastrar a otras rutas migradas ('/app' sigue viva)"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/login', 'GET', $exactasReales, $prefijosReales),
    "el rollback de '/programa-general' no debe arrastrar a '/login' (S01)"
);

// --- 3. Restaurar el mapa: React vuelve a servir la pantalla de muestra. ---
comprobarRollback(
    SpaRouter::coincideConMapa('/programa-general', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "tras restaurar el mapa, React debe volver a servir '/programa-general'"
);

// --- 4. El mapa real de producción (sin argumento explícito) nunca se movió durante el ejercicio. ---
comprobarRollback(
    !SpaRouter::sirveLaSpa('/programa-general'),
    "el mapa real de producción (default de sirveLaSpa) sigue excluyendo '/programa-general'"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/app'),
    "el mapa real de producción sigue sirviendo '/app'"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/login'),
    "el mapa real de producción sigue sirviendo '/login' (S01)"
);

echo $fallos === 0 ? "OK: rollback del mapa de rutas SPA\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
