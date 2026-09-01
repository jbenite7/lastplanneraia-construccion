<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Verifica que el modo mantenimiento deje pasar los entrypoints CSS del design system.
 *
 * Por qué existe: bajo mantenimiento la página de la ruta oculta SÍ se sirve, pero sus hojas
 * de estilo son peticiones aparte. Las que van por PHP (`/runtime/css/*`, servidas por
 * `DesignSystemAssetController`) pasaban por el chequeo de mantenimiento y devolvían el HTML
 * del cartel con un 503 en lugar del CSS, mientras las estáticas (`/css/*`, servidas por
 * Apache) cargaban con normalidad. Resultado medido en produccion el 2026-08-12: la pantalla
 * de entrada a medio estilizar — 3 de 5 `<link>` bien y 2 rotos, entre ellos `core.css`, que
 * es el nucleo del design system.
 *
 * La causa de fondo era que la misma lista de rutas vivia duplicada en tres sitios y solo dos
 * se actualizaron. Por eso este test no compara contra una lista escrita a mano: comprueba que
 * **toda** ruta servida por el controlador este exenta, de modo que un entrypoint nuevo quede
 * cubierto sin tocar este archivo, y que quitar la exencion ponga el test en rojo.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Core\DesignSystemAssetController;
use App\Core\MaintenanceMode;

$fallos = 0;
$total = 0;

function comprobar(string $caso, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "  OK    $caso\n";

        return;
    }
    $fallos++;
    echo "  FALLA $caso\n";
}

echo "Exencion de assets del design system durante mantenimiento\n";

$rutas = DesignSystemAssetController::publicRoutePaths();

comprobar('el controlador declara al menos un entrypoint', $rutas !== []);

foreach ($rutas as $ruta) {
    comprobar("exenta: $ruta", MaintenanceMode::isExemptRoute($ruta));
}

// El nucleo del design system es el que rompe la pantalla entera si falta.
comprobar(
    'el entrypoint core esta entre las rutas declaradas',
    in_array('/runtime/css/design-system/entrypoints/core.css', $rutas, true),
);

// La puerta oculta y su configuracion siguen exentas: el arreglo no debe estrecharlas.
comprobar('la ruta oculta sigue exenta', MaintenanceMode::isExemptRoute(MaintenanceMode::SECRET_PATH));
comprobar('frontend-config.js sigue exento', MaintenanceMode::isExemptRoute('/runtime/frontend-config.js'));

// Tarea 12 (S01): el shell React de la ruta oculta necesita su propio bundle exento, sin
// abrir la SPA completa — solo el prefijo de assets, nunca `/app` en si.
comprobar('un asset del bundle del shell queda exento', MaintenanceMode::isExemptRoute('/app/assets/app.js'));
comprobar('app sigue cerrada en mantenimiento', !MaintenanceMode::isExemptRoute('/app'));

// La exencion no puede abrir el sitio: cualquier ruta de aplicacion sigue cerrada.
foreach (['/', '/login', '/plan-compras', '/programacion-intermedia', '/indicadores'] as $cerrada) {
    comprobar("sigue cerrada: $cerrada", !MaintenanceMode::isExemptRoute($cerrada));
}

// Las rutas declaradas por el controlador deben ser las que el router registra en public/index.php.
// Si alguien agrega un entrypoint al controlador y olvida enrutarlo (o al reves), esto lo delata.
$indice = (string) file_get_contents(__DIR__ . '/../public/index.php');
foreach ($rutas as $ruta) {
    comprobar(
        "enrutada en public/index.php: $ruta",
        str_contains($indice, "'" . $ruta . "'"),
    );
}

echo "\n$total comprobaciones, $fallos fallos\n";
exit($fallos === 0 ? 0 : 1);
