<?php

// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SpaRouter;

$fallos = 0;

/**
 * Matriz canónica: método + ruta -> ¿la sirve la SPA? Cada fila que falla dice exactamente cuál
 * combinación se rompió, no solo "algo falló".
 *
 * @param list<array{0:string,1:string,2:bool}> $matriz
 */
function comprobarMatrizSpa(array $matriz): void
{
    global $fallos;

    foreach ($matriz as [$metodo, $ruta, $esperado]) {
        $obtenido = SpaRouter::sirveLaSpa($ruta, $metodo);
        if ($obtenido !== $esperado) {
            $palabra = $esperado ? 'deberia' : 'NO deberia';
            echo "FALLO: {$metodo} '{$ruta}' {$palabra} servirla la SPA (obtenido: " . ($obtenido ? 'true' : 'false') . ")\n";
            $fallos++;
        }
    }
}

// --- Matriz canónica S01: solo GET/HEAD cruzan; POST /login sigue en la ventana de rollback. ---
comprobarMatrizSpa([
    ['GET', '/', true], ['HEAD', '/', true], ['POST', '/', false],
    ['GET', '/login', true], ['HEAD', '/login', true], ['POST', '/login', false],
    // Otros verbos de mutación tampoco cruzan al host SPA — fijado por prueba, no solo
    // inferido de la lista blanca GET/HEAD dentro de coincideConMapa().
    ['PUT', '/login', false], ['DELETE', '/login', false], ['OPTIONS', '/login', false],
    // Estas tres NUNCA deben pasar al host SPA: S03 sin migrar, API y assets del bundle.
    ['GET', '/password/reset', false],
    ['GET', '/api/session', false],
    ['GET', '/app/assets/x.js', false],
]);

// --- Matriz canónica S02: '/password/forgot' cruza en GET/HEAD; POST no lo sirve la SPA y, desde
// la Tarea 10, tampoco el legado (sin controlador: 404 controlado, ver test_spa_frontera_http.php). ---
comprobarMatrizSpa([
    ['GET', '/password/forgot', true],
    ['HEAD', '/password/forgot', true],
    ['POST', '/password/forgot', false],
    ['GET', '/password/reset', false],
    ['POST', '/api/auth/password/forgot', false],
    ['GET', '/app/password/forgot', true],
]);

// --- El resto del sitio PHP no migrado sigue intacto. ---
comprobarMatrizSpa([
    ['GET', '/proyectos', false],
    ['GET', '/programa-general', false],
    ['GET', '/plan-compras', false],
    ['GET', '/dashboard', false],
]);

// --- El shell bajo /app (prefijo migrado) sigue vivo, en GET y HEAD, con deep links. ---
comprobarMatrizSpa([
    ['GET', '/app', true],
    ['HEAD', '/app', true],
    ['GET', '/app/login', true],
    ['GET', '/app/proyectos', true],
    ['POST', '/app', false],
]);

// --- La API nunca la sirve la SPA, ni aunque empiece por /app. ---
comprobarMatrizSpa([
    ['GET', '/api/session', false],
    ['GET', '/api/proyectos', false],
]);

// --- Los assets del bundle tampoco: los sirve el servidor como archivos. ---
comprobarMatrizSpa([
    ['GET', '/app/assets/index.js', false],
    ['GET', '/app/assets', false],
]);

// --- Rollback de verdad, sin editar constantes: coincideConMapa() con un mapa hipotético. ---
// Quitar '/login' (y '/') del mapa de exactas, dejando solo el prefijo piloto '/app', demuestra
// que el legado vuelve a servir GET /login sin tocar SpaRouter::RUTAS_EXACTAS_MIGRADAS ni
// mutar ningún estado compartido entre llamadas.
comprobarRollbackDrill();

function comprobarRollbackDrill(): void
{
    global $fallos;

    $exactasVacias = [];
    $prefijoPiloto = ['/app'];

    if (SpaRouter::coincideConMapa('/login', 'GET', $exactasVacias, $prefijoPiloto)) {
        echo "FALLO: rollback drill — sin '/login' en el mapa de exactas, debe volver a PHP\n";
        $fallos++;
    }
    if (SpaRouter::coincideConMapa('/', 'GET', $exactasVacias, $prefijoPiloto)) {
        echo "FALLO: rollback drill — sin '/' en el mapa de exactas, debe volver a PHP\n";
        $fallos++;
    }
    if (!SpaRouter::coincideConMapa('/app', 'GET', $exactasVacias, $prefijoPiloto)) {
        echo "FALLO: rollback drill — '/app' debe seguir sirviéndola React (prefijo intacto)\n";
        $fallos++;
    }

    // El mapa real de producción (sin argumento explícito) nunca se movió durante el ejercicio.
    if (!SpaRouter::sirveLaSpa('/login')) {
        echo "FALLO: rollback drill — el mapa real de producción no debe verse afectado\n";
        $fallos++;
    }
    if (!SpaRouter::sirveLaSpa('/')) {
        echo "FALLO: rollback drill — el mapa real de producción no debe verse afectado ('/')\n";
        $fallos++;
    }
}

// --- S02 ya NO tiene rollback a PHP (Tarea 10, «sí, retira la pantalla PHP», Felipe 2026-09-16).
// Este bloque antes simulaba quitar '/password/forgot' del mapa y afirmaba que «volvía al legado»;
// tras retirar la vista y el controlador eso es falso: quitarla del mapa daría 404. Lo que es
// verdad hoy, y es lo que se fija aquí: (1) GET/HEAD '/password/forgot' dependen SOLO del corte de
// SpaRouter — sin la ruta en el mapa, SpaRouter no la sirve —, y (2) `public/index.php` no registra
// ningún handler para '/password/forgot', así que no queda pantalla PHP a la que volver. ---
comprobarSinRollbackS02();

function comprobarSinRollbackS02(): void
{
    global $fallos;

    $exactasSinForgot = ['/', '/login'];
    $prefijoPiloto = ['/app'];

    foreach (['GET', 'HEAD'] as $metodo) {
        if (!SpaRouter::coincideConMapa('/password/forgot', $metodo, ['/', '/login', '/password/forgot'], $prefijoPiloto)) {
            echo "FALLO: S02 — {$metodo} '/password/forgot' debe servirlo la SPA cuando la ruta está en el mapa\n";
            $fallos++;
        }
        if (SpaRouter::coincideConMapa('/password/forgot', $metodo, $exactasSinForgot, $prefijoPiloto)) {
            echo "FALLO: S02 — sin '/password/forgot' en el mapa, SpaRouter no debe servir {$metodo}: el corte depende solo del mapa\n";
            $fallos++;
        }
    }
    if (!SpaRouter::coincideConMapa('/login', 'GET', $exactasSinForgot, $prefijoPiloto)) {
        echo "FALLO: S02 — sacar '/password/forgot' del mapa no debe arrastrar a '/login'\n";
        $fallos++;
    }

    // No hay legado al que volver: ningún `$router->get|head|post|any(...)` para la ruta.
    $index = (string) file_get_contents(__DIR__ . '/../public/index.php');
    if (preg_match("~\\\$router->\\w+\\(\\s*'/password/forgot'~", $index) === 1) {
        echo "FALLO: S02 — public/index.php registra un handler PHP para '/password/forgot'; el legado se retiró en la Tarea 10\n";
        $fallos++;
    }

    // El mapa real de producción (sin argumento explícito) sigue incluyendo la ruta.
    if (!SpaRouter::sirveLaSpa('/password/forgot')) {
        echo "FALLO: S02 — el mapa real de producción debe seguir sirviendo '/password/forgot' desde la SPA\n";
        $fallos++;
    }
}

echo $fallos === 0 ? "OK: frontera SPA/PHP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
