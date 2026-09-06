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
    // Estas cuatro NUNCA deben pasar al host SPA: S02/S03 sin migrar, API y assets del bundle.
    ['GET', '/password/forgot', false],
    ['GET', '/password/reset', false],
    ['GET', '/api/session', false],
    ['GET', '/app/assets/x.js', false],
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

echo $fallos === 0 ? "OK: frontera SPA/PHP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
