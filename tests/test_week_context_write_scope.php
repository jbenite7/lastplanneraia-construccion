<?php

declare(strict_types=1);

/**
 * Fija QUIÉN puede escribir la semana de la sesión.
 *
 * Hasta el 2026-08-04, `SessionMiddleware::check()` escribía `$_SESSION['semana']` con cualquier
 * `?semana=` de CUALQUIER petición, incluidas las XHR de fondo. Eso hacía que una petición rezagada
 * de la carga anterior devolviera al usuario a una semana que no pidió — medido en el navegador con
 * `/api/general/restriction-config?semana=7`, una ruta que no tiene nada que ver con semanas.
 *
 * La regla vigente: la semana de la URL solo la aplica un controlador de PÁGINA, vía
 * `BaseController::syncRequestedWeekContext()`, que además valida contra `semanas_activas`.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "  OK   {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$descripcion}\n";
}

$raiz = dirname(__DIR__);

// 1. El middleware global no escribe la semana.
$middleware = (string) file_get_contents($raiz . '/src/Core/SessionMiddleware.php');
comprobar(
    'SessionMiddleware no asigna $_SESSION[\'semana\']',
    preg_match('/\$_SESSION\s*\[\s*[\'"]semana[\'"]\s*\]\s*=/', $middleware) !== 1,
);

// 2. Los controladores de página que honran ?semana= lo hacen por la vía validada.
$paginas = [
    'src/Controllers/Programacion/ProgramaGeneralController.php',
    'src/Controllers/Programacion/ProgramacionSemanalController.php',
    'src/Controllers/Programacion/ProgramacionIntermediaController.php',
    'src/Controllers/Gestion/IndicadoresController.php',
    'src/Controllers/Gestion/PdcController.php',
];
foreach ($paginas as $ruta) {
    $fuente = (string) file_get_contents($raiz . '/' . $ruta);
    comprobar(
        basename($ruta) . ' llama a syncRequestedWeekContext()',
        str_contains($fuente, 'syncRequestedWeekContext()'),
    );
}

// 3. Las APIs no persisten la semana del request.
$apis = [
    'src/Controllers/Api/SemanalApiController.php',
    'src/Controllers/Api/GeneralApiController.php',
];
foreach ($apis as $ruta) {
    $fuente = (string) file_get_contents($raiz . '/' . $ruta);
    comprobar(
        basename($ruta) . ' no asigna $_SESSION[\'semana\']',
        preg_match('/\$_SESSION\s*\[\s*[\'"]semana[\'"]\s*\]\s*=/', $fuente) !== 1,
    );
}

echo "\n{$total} comprobaciones, {$fallos} fallidas\n";
exit($fallos === 0 ? 0 : 1);
