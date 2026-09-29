<?php

declare(strict_types=1);
// @requiere: puro

/**
 * La pantalla nueva de Programa General guarda en la sesión la semana que ella misma resuelve.
 * Decisión de Felipe, 2026-09-28 (opción 1, vista en maqueta con cuatro situaciones).
 *
 * Por qué: `/api/programa-general/context` resuelve la semana (la de la sesión, o la última si no hay o
 * está fuera de rango) pero no la escribía. Tras «quitar semana» (`POST /context/clear-week`, la deja en
 * 0) la pantalla cargaba con su semana propia y el resto del shell seguía sin ninguna: la barra verde de
 * cabecera y el selector no aparecían, porque `/api/session` devuelve `week: null`.
 *
 * `ProgramaGeneralContextService::semanaPorGuardar()` decide, sin tocar la sesión, qué semana hay que
 * escribir; el controlador solo la escribe. Devuelve null cuando no hay nada que guardar.
 *
 * No abre la base ni la sesión.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ProgramaGeneralContextService;

$failures = [];
$caso = static function (string $nombre, mixed $esperado, mixed $obtenido) use (&$failures): void {
    if ($esperado !== $obtenido) {
        $failures[] = "{$nombre}: debería ser " . var_export($esperado, true) . ' y es ' . var_export($obtenido, true);
    }
};

// Sin semana en la sesión (tras «quitar semana»): se guarda la que la pantalla resolvió.
$caso('sesión vacía', 20, ProgramaGeneralContextService::semanaPorGuardar([], 20));
$caso('sesión en 0 (clear-week)', 20, ProgramaGeneralContextService::semanaPorGuardar(['semana' => 0], 20));
$caso('sesión con texto vacío', 20, ProgramaGeneralContextService::semanaPorGuardar(['semana' => ''], 20));

// La sesión ya tiene esa semana: nada que escribir.
$caso('ya guardada (entero)', null, ProgramaGeneralContextService::semanaPorGuardar(['semana' => 18], 18));
$caso('ya guardada (texto)', null, ProgramaGeneralContextService::semanaPorGuardar(['semana' => '18'], 18));

// Semana de la sesión fuera de rango: el servicio la corrige a la última y se guarda la corregida.
$caso('sesión fuera de rango', 20, ProgramaGeneralContextService::semanaPorGuardar(['semana' => 99], 20));

// Proyecto sin semanas: no hay semana que guardar, y jamás se escribe un 0.
$caso('proyecto sin semanas', null, ProgramaGeneralContextService::semanaPorGuardar([], 0));
$caso('sin semanas y sesión previa', null, ProgramaGeneralContextService::semanaPorGuardar(['semana' => 5], 0));

// El servicio lee también la clave con mayúscula; guardamos en la que consume el shell.
$caso('solo clave «Semana»', 18, ProgramaGeneralContextService::semanaPorGuardar(['Semana' => 18], 18));

if ($failures !== []) {
    fwrite(STDERR, 'FALLAN ' . count($failures) . " comprobaciones:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

echo "OK: la pantalla guarda la semana que resuelve (9 casos)\n";
