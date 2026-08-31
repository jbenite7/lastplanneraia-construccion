<?php

// @requiere: puro

/**
 * Tarea 9 (T01), paso 2: ejercicio de rollback del mapa explícito de rutas de `SpaRouter`.
 *
 * Prueba que quitar UNA pantalla migrada de muestra del mapa hace que esa ruta vuelva a caer en
 * su adaptador PHP (el router de `public/index.php` sigue teniendo `/programa-general` registrado
 * contra `ProgramaGeneralController`, sin tocar nada de este ejercicio), y que restaurar el mapa
 * hace que React vuelva a servirla. Usa `/programa-general` como "pantalla migrada de muestra"
 * hipotética: hoy NO está en `RUTAS_MIGRADAS` (solo `/app` lo está), así que este test simula el
 * escenario general de rollback sin depender de que exista una segunda pantalla ya migrada.
 *
 * `sirveLaSpa()` es una función pura (`$ruta`, `$rutasMigradas = RUTAS_MIGRADAS`, sin efecto de
 * lado): el rollback se ejercita pasando un array distinto en cada llamada, nunca mutando estado
 * compartido. No hace falta ningún `finally` ni override — no hay nada que restaurar entre
 * pruebas ni entre procesos, y `public/index.php` sigue llamando `sirveLaSpa($requestUri)` con un
 * solo argumento, ajeno por completo a este ejercicio.
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

$mapaReal = SpaRouter::RUTAS_MIGRADAS;
$mapaConPantallaDeMuestraMigrada = [...$mapaReal, '/programa-general'];

// --- 0. Baseline real: hoy '/programa-general' NO está migrada, es del sitio PHP. ---
comprobarRollback(
    !SpaRouter::sirveLaSpa('/programa-general'),
    "baseline: '/programa-general' hoy es del sitio PHP (no está en RUTAS_MIGRADAS)"
);

// --- 1. Simular la migración de la pantalla de muestra: entra al mapa explícito. ---
comprobarRollback(
    SpaRouter::sirveLaSpa('/programa-general', $mapaConPantallaDeMuestraMigrada),
    "tras 'migrar' '/programa-general', React debe servirla"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/programa-general/actualizar/detalle', $mapaConPantallaDeMuestraMigrada),
    "deep-link bajo '/programa-general' migrada también debe caer al shell React"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/app', $mapaConPantallaDeMuestraMigrada),
    "'/app' sigue migrada mientras se ejercita el rollback de la pantalla de muestra"
);

// --- 2. Rollback: se quita SOLO la pantalla de muestra del mapa explícito (vuelve al mapa real). ---
comprobarRollback(
    !SpaRouter::sirveLaSpa('/programa-general', $mapaReal),
    "tras el rollback, '/programa-general' debe volver a su adaptador PHP"
);
comprobarRollback(
    !SpaRouter::sirveLaSpa('/programa-general/actualizar/detalle', $mapaReal),
    "tras el rollback, el deep-link bajo '/programa-general' también vuelve al sitio PHP"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/app', $mapaReal),
    "el rollback de UNA pantalla no debe arrastrar a otras rutas migradas ('/app' sigue viva)"
);

// --- 3. Restaurar el mapa: React vuelve a servir la pantalla de muestra. ---
comprobarRollback(
    SpaRouter::sirveLaSpa('/programa-general', $mapaConPantallaDeMuestraMigrada),
    "tras restaurar el mapa, React debe volver a servir '/programa-general'"
);

// --- 4. El mapa real de producción (sin argumento explícito) nunca se movió durante el ejercicio. ---
comprobarRollback(
    !SpaRouter::sirveLaSpa('/programa-general'),
    "el mapa real de producción (default de sirveLaSpa) sigue excluyendo '/programa-general'"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/app'),
    "el mapa real de producción sigue teniendo a '/app' como única ruta migrada"
);

echo $fallos === 0 ? "OK: rollback del mapa de rutas SPA\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
