<?php

// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SpaRouter;

$fallos = 0;

// Las rutas del shell las sirve la SPA.
foreach (['/app', '/app/login', '/app/proyectos'] as $ruta) {
    if (!SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '{$ruta}' deberia servirla la SPA\n";
        $fallos++;
    }
}

// Las viejas NO. Si esto se rompe, el sitio entero deja de funcionar.
foreach (['/login', '/proyectos', '/programa-general', '/plan-compras', '/dashboard'] as $ruta) {
    if (SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '{$ruta}' es del sitio PHP y la SPA se la esta robando\n";
        $fallos++;
    }
}

// La API nunca la sirve la SPA, ni aunque empiece por /app.
foreach (['/api/session', '/api/proyectos'] as $ruta) {
    if (SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '{$ruta}' es API, no debe devolver el HTML de la SPA\n";
        $fallos++;
    }
}

// Los assets del bundle tampoco: los sirve el servidor como archivos.
if (SpaRouter::sirveLaSpa('/app/assets/index.js')) {
    echo "FALLO: los assets del bundle no deben devolver el HTML\n";
    $fallos++;
}

echo $fallos === 0 ? "OK: frontera SPA/PHP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
