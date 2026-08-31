<?php

// @requiere: puro

/**
 * Caracterización de línea base (Tarea 1, plan T01) del mapa de rutas actual de
 * `SpaRouter::sirveLaSpa()`. No es el mismo test que `tests/test_spa_frontera.php`
 * (ese ya cubre /app, /api/*, /app/assets/* y algunas rutas legadas): este congela
 * comportamiento adicional que el plan pide explícitamente y que el otro no toca —
 * rutas parecidas que NO están migradas (falsos positivos de un match por substring),
 * y el comportamiento de "recarga/deep-link" (cualquier subruta profunda bajo /app
 * cae al mismo host SPA, sin que el router PHP la despache).
 *
 * Si este test se rompe, o el mapa de rutas cambió (nueva migración) y hay que
 * actualizarlo a propósito, o alguien debilitó la frontera exacta/por-prefijo de
 * `SpaRouter` sin querer.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SpaRouter;

$fallos = 0;

function comprobar(bool $condicion, string $mensaje): void
{
    global $fallos;
    if (!$condicion) {
        echo "FALLO: {$mensaje}\n";
        $fallos++;
    }
}

// --- Rutas parecidas a /app que NO deben servirla la SPA (falso positivo de substring) ---
foreach ([
    '/apps',
    '/appearance',
    '/application',
    '/app-config',
    '/App',        // sensible a mayúsculas: no es un alias case-insensitive
    '/apptitude',
] as $ruta) {
    comprobar(!SpaRouter::sirveLaSpa($ruta), "'{$ruta}' se parece a /app pero no está migrada y no debe servirla la SPA");
}

// --- Rutas migradas exactas y sus variantes con slash final ---
comprobar(SpaRouter::sirveLaSpa('/app'), "'/app' exacto debe servirlo la SPA");
comprobar(SpaRouter::sirveLaSpa('/app/'), "'/app/' con slash final debe servirlo la SPA (mismo prefijo)");

// --- Recarga / deep-link: cualquier profundidad bajo /app cae al mismo host SPA ---
foreach ([
    '/app/proyectos/42/semana/8',
    '/app/programa-general/actualizar/detalle',
    '/app/a/b/c/d/e',
    '/app/login?redirigido=1',
] as $ruta) {
    comprobar(SpaRouter::sirveLaSpa($ruta), "deep-link '{$ruta}' debe caer al mismo host SPA que /app");
}

// --- /app/assets/* excluido incluso con subrutas profundas o parámetros ---
foreach ([
    '/app/assets/js/chunks/vendor.abc123.js',
    '/app/assets/index.css?v=2',
] as $ruta) {
    comprobar(!SpaRouter::sirveLaSpa($ruta), "'{$ruta}' es un asset del bundle, no debe devolver el HTML del shell");
}

// --- /api/* excluido incluso anidado bajo una ruta que de otro modo migraría ---
comprobar(!SpaRouter::sirveLaSpa('/api/app/lo-que-sea'), "'/api/app/lo-que-sea' sigue siendo API, el prefijo /app no aplica dentro de /api/*");

// --- Rutas legadas parecidas por nombre a un módulo migrado, pero no lo son ---
foreach (['/programa-generales', '/plan-compras-viejo', '/proyecto'] as $ruta) {
    comprobar(!SpaRouter::sirveLaSpa($ruta), "'{$ruta}' no es una ruta migrada exacta, sigue siendo del sitio PHP");
}

echo $fallos === 0 ? "OK: línea base del mapa de rutas SPA\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
