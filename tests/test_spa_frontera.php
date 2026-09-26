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
    // Estas dos NUNCA deben pasar al host SPA: API y assets del bundle.
    ['GET', '/api/session', false],
    ['GET', '/app/assets/x.js', false],
]);

// --- Matriz canónica S02: '/password/forgot' cruza en GET/HEAD; POST no lo sirve la SPA y, desde
// la Tarea 10, tampoco el legado (sin controlador: 404 controlado, ver test_spa_frontera_http.php). ---
comprobarMatrizSpa([
    ['GET', '/password/forgot', true],
    ['HEAD', '/password/forgot', true],
    ['POST', '/password/forgot', false],
    ['POST', '/api/auth/password/forgot', false],
    ['GET', '/app/password/forgot', true],
]);

// --- Matriz canónica S03 (Tarea 8): '/password/reset' cruza en GET/HEAD; POST sigue en el
// legado durante la ventana de rollback; ni subrutas ni la API caen en la ruta exacta. ---
comprobarMatrizSpa([
    ['GET', '/password/reset', true],
    ['HEAD', '/password/reset', true],
    ['POST', '/password/reset', false],
    ['GET', '/password/reset-extra', false],
    ['GET', '/password/reset/x', false],
    ['GET', '/api/auth/password/reset', false],
    ['POST', '/api/auth/password/reset', false],
    ['GET', '/api/auth/password/reset/validate', false],
    ['GET', '/app/password/reset', true],
    ['HEAD', '/app/password/reset', true],
]);

// --- Tarea 10 (S04, «Corte, conservando el PHP», Felipe 2026-09-18): GET/HEAD '/proyectos'
// cruzan al shell React; POST sigue sin cruzar (no hay POST a esa ruta exacta). El legado
// (vista, controlador y CSS) NO se retira — a diferencia de S02/S03, el rollback de esta ruta
// vuelve a servir la pantalla PHP real, no un 404 controlado. ---
comprobarMatrizSpa([
    ['GET', '/proyectos', true],
    ['HEAD', '/proyectos', true],
    ['POST', '/proyectos', false],
]);

// --- El resto del sitio PHP no migrado sigue intacto. ---
comprobarMatrizSpa([
    ['GET', '/lookahead', false],
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

// --- S03 tras la Tarea 10: el corte de '/password/reset' depende SOLO del mapa de SpaRouter,
// porque ya no hay vista ni controlador legados a los que volver (se retiraron con el gate
// explícito de Felipe). Se fija que el mapa gobierna y que public/index.php no registra
// handler PHP para la ruta. ---
comprobarRollbackS03();

function comprobarRollbackS03(): void
{
    global $fallos;

    $mapaConReset = ['/', '/login', '/password/forgot', '/password/reset'];
    $mapaSinReset = ['/', '/login', '/password/forgot'];
    $prefijoPiloto = ['/app'];

    foreach (['GET', 'HEAD'] as $metodo) {
        if (!SpaRouter::coincideConMapa('/password/reset', $metodo, $mapaConReset, $prefijoPiloto)) {
            echo "FALLO: S03 — {$metodo} '/password/reset' debe servirlo la SPA con la ruta en el mapa\n";
            $fallos++;
        }
        if (SpaRouter::coincideConMapa('/password/reset', $metodo, $mapaSinReset, $prefijoPiloto)) {
            echo "FALLO: S03 — rollback: sin '/password/reset' en el mapa, {$metodo} debe volver al legado\n";
            $fallos++;
        }
        if (!SpaRouter::coincideConMapa('/app/password/reset', $metodo, $mapaSinReset, $prefijoPiloto)) {
            echo "FALLO: S03 — rollback: el piloto '/app/password/reset' sigue en React por el prefijo\n";
            $fallos++;
        }
    }
    if (!SpaRouter::coincideConMapa('/password/forgot', 'GET', $mapaSinReset, $prefijoPiloto)) {
        echo "FALLO: S03 — sacar '/password/reset' del mapa no debe arrastrar a '/password/forgot'\n";
        $fallos++;
    }

    // No hay legado al que volver: ningún `$router->get|head|post|any(...)` para la ruta, y el
    // POST anónimo debe caer en el 404 controlado, así que la ruta sigue en $publicRoutes.
    $index = (string) file_get_contents(__DIR__ . '/../public/index.php');
    if (preg_match("~\\\$router->\\w+\\(\\s*'/password/reset'~", $index) === 1) {
        echo "FALLO: S03 — public/index.php registra un handler PHP para '/password/reset'; el legado se retiró en la Tarea 10\n";
        $fallos++;
    }
    if (preg_match("~\\\$publicRoutes = \\[[^\\]]*'/password/reset'~", $index) !== 1) {
        echo "FALLO: S03 — '/password/reset' debe seguir en \$publicRoutes para que el POST retirado caiga en el 404 controlado\n";
        $fallos++;
    }
    if (is_file(__DIR__ . '/../views/auth/password-reset.view.php')) {
        echo "FALLO: S03 — views/auth/password-reset.view.php debe estar retirada (Tarea 10)\n";
        $fallos++;
    }
    if (is_file(__DIR__ . '/../src/Controllers/Auth/PasswordResetController.php')) {
        echo "FALLO: S03 — src/Controllers/Auth/PasswordResetController.php debe estar retirado (Tarea 10)\n";
        $fallos++;
    }

    // El mapa real de producción incluye la ruta tras el corte.
    if (!SpaRouter::sirveLaSpa('/password/reset') || !SpaRouter::sirveLaSpa('/password/reset', 'HEAD')) {
        echo "FALLO: S03 — el mapa real de producción debe servir GET/HEAD '/password/reset' desde la SPA\n";
        $fallos++;
    }
    if (SpaRouter::sirveLaSpa('/password/reset', 'POST')) {
        echo "FALLO: S03 — POST '/password/reset' no debe servirlo la SPA\n";
        $fallos++;
    }
}

// --- Rollback de la Tarea 10 (S04): a diferencia de '/password/forgot' y '/password/reset'
// (S02/S03, legado RETIRADO), aquí el legado se CONSERVA a propósito — «Corte, conservando el
// PHP», Felipe 2026-09-18. Rollback real es sacar '/proyectos' del mapa; el legado ya está listo
// para recibir la petición porque nunca se tocó. ---
comprobarRollbackConservandoElPhpS04();

function comprobarRollbackConservandoElPhpS04(): void
{
    global $fallos;

    $mapaConProyectos = ['/', '/login', '/password/forgot', '/password/reset', '/proyectos'];
    $mapaSinProyectos = ['/', '/login', '/password/forgot', '/password/reset'];
    $prefijoPiloto = ['/app'];

    foreach (['GET', 'HEAD'] as $metodo) {
        if (!SpaRouter::coincideConMapa('/proyectos', $metodo, $mapaConProyectos, $prefijoPiloto)) {
            echo "FALLO: S04 — {$metodo} '/proyectos' debe servirlo la SPA con la ruta en el mapa\n";
            $fallos++;
        }
        if (SpaRouter::coincideConMapa('/proyectos', $metodo, $mapaSinProyectos, $prefijoPiloto)) {
            echo "FALLO: S04 — rollback: sin '/proyectos' en el mapa, {$metodo} debe volver al legado\n";
            $fallos++;
        }
    }
    if (!SpaRouter::coincideConMapa('/app/proyectos', 'GET', $mapaSinProyectos, $prefijoPiloto)) {
        echo "FALLO: S04 — rollback: el piloto '/app/proyectos' sigue en React por el prefijo\n";
        $fallos++;
    }
    if (!SpaRouter::coincideConMapa('/password/reset', 'GET', $mapaSinProyectos, $prefijoPiloto)) {
        echo "FALLO: S04 — sacar '/proyectos' del mapa no debe arrastrar a '/password/reset'\n";
        $fallos++;
    }

    // El legado SIGUE en el repo y SIGUE registrado en el router: el rollback de esta ruta no es
    // un 404 controlado, es una pantalla PHP real esperando la petición (a diferencia de S02/S03).
    $index = (string) file_get_contents(__DIR__ . '/../public/index.php');
    if (preg_match("~\\\$router->get\\(\\s*'/proyectos'~", $index) !== 1) {
        echo "FALLO: S04 — public/index.php debe seguir registrando el GET legado de '/proyectos' (rollback conserva el PHP)\n";
        $fallos++;
    }
    if (preg_match("~\\\$router->head\\(\\s*'/proyectos'~", $index) !== 1) {
        echo "FALLO: S04 — public/index.php debe registrar también el HEAD legado de '/proyectos', como '/' y '/login'\n";
        $fallos++;
    }
    if (preg_match("~\\\$router->post\\(\\s*'/proyecto/seleccionar'~", $index) !== 1) {
        echo "FALLO: S04 — POST '/proyecto/seleccionar' debe seguir registrado (no se retira en la Tarea 10)\n";
        $fallos++;
    }
    if (!is_file(__DIR__ . '/../views/core/project_selector.view.php')) {
        echo "FALLO: S04 — views/core/project_selector.view.php NO debe borrarse (decisión de Felipe: conservar el PHP)\n";
        $fallos++;
    }
    if (!is_file(__DIR__ . '/../src/Controllers/Core/ProjectSelectorController.php')) {
        echo "FALLO: S04 — src/Controllers/Core/ProjectSelectorController.php NO debe borrarse (decisión de Felipe: conservar el PHP)\n";
        $fallos++;
    }
    if (!is_file(__DIR__ . '/../public/css/project-selector.css')) {
        echo "FALLO: S04 — public/css/project-selector.css NO debe borrarse (decisión de Felipe: conservar el PHP)\n";
        $fallos++;
    }

    // El mapa real de producción sirve GET/HEAD desde React tras el corte.
    if (!SpaRouter::sirveLaSpa('/proyectos') || !SpaRouter::sirveLaSpa('/proyectos', 'HEAD')) {
        echo "FALLO: S04 — el mapa real de producción debe servir GET/HEAD '/proyectos' desde la SPA\n";
        $fallos++;
    }
    if (SpaRouter::sirveLaSpa('/proyectos', 'POST')) {
        echo "FALLO: S04 — POST '/proyectos' no debe servirlo la SPA\n";
        $fallos++;
    }
}

// --- S05: el corte de '/programa-general' en SpaRouter ---
if (!SpaRouter::sirveLaSpa('/programa-general') || !SpaRouter::sirveLaSpa('/programa-general', 'HEAD')) {
    echo "FALLO: S05 — el mapa real de producción debe servir GET/HEAD '/programa-general' desde la SPA\n";
    $fallos++;
}
if (SpaRouter::sirveLaSpa('/programa-general', 'POST')) {
    echo "FALLO: S05 — POST '/programa-general' no debe servirlo la SPA\n";
    $fallos++;
}

echo $fallos === 0 ? "OK: frontera SPA/PHP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
