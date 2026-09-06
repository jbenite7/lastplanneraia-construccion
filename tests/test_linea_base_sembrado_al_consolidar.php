<?php
// @requiere: puro
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Corregido en Tarea 5 (T01): el sembrado de línea base ya no vive en `nueva_semana.php` — se
 * extrajo a `WeekAdministrationService::crear()`, que delega la ejecución en
 * `App\Contracts\Shell\WeekAdministrationRepository::sembrarLineaBaseSiFalta()` (implementado por
 * `DatabaseWeekAdministrationRepository`, que a su vez llama `LineaBaseContractualService::
 * sembrarSiFalta()`). Este archivo ahora escanea esos tres puntos en vez del script legado, que
 * quedó como llamador de compatibilidad sin lógica propia (AGENTS.md: "no dupliques reglas de
 * negocio en el legado").
 */
function codigoSinComentarios(string $ruta): string
{
    $codigo = '';
    foreach (token_get_all(file_get_contents($ruta)) as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $codigo .= $token[1];
            continue;
        }
        $codigo .= $token;
    }

    return $codigo;
}

$fallos = [];

$nuevaSemana = codigoSinComentarios(__DIR__ . '/../src/Legacy/nueva_semana.php');
if (!str_contains($nuevaSemana, 'WeekAdministrationService')) {
    $fallos[] = 'nueva_semana.php ya no delega en WeekAdministrationService';
}
if (str_contains($nuevaSemana, 'LineaBaseContractualService') || str_contains($nuevaSemana, 'sembrarSiFalta')) {
    $fallos[] = 'nueva_semana.php volvió a acoplarse directo a la línea base — debe pasar por el servicio';
}

$servicio = codigoSinComentarios(__DIR__ . '/../src/Services/Shell/WeekAdministrationService.php');
if (!str_contains($servicio, 'sembrarLineaBaseSiFalta')) {
    $fallos[] = 'WeekAdministrationService no invoca el sembrado de la línea base';
}
// El sembrado solo aplica a semanas >= 2 (conteo > 0): no hay "semana anterior" que arrastrar en
// la primera. Una sola invocación dentro de esa rama, no dos.
if (substr_count($servicio, 'sembrarLineaBaseSiFalta') !== 1) {
    $fallos[] = 'sembrarLineaBaseSiFalta debe aparecer exactamente una vez en el servicio';
}

$repositorio = codigoSinComentarios(__DIR__ . '/../src/Services/Shell/DatabaseWeekAdministrationRepository.php');
if (!str_contains($repositorio, 'LineaBaseContractualService') || !str_contains($repositorio, 'sembrarSiFalta')) {
    $fallos[] = 'DatabaseWeekAdministrationRepository no delega en LineaBaseContractualService::sembrarSiFalta()';
}

if ($fallos) {
    foreach ($fallos as $f) {
        echo "FAIL: $f\n";
    }
    exit(1);
}
echo "OK: la consolidacion de semana siembra la linea base (vía WeekAdministrationService)\n";
