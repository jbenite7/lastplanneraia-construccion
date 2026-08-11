<?php

declare(strict_types=1);
// @requiere: puro


/**
 * N-1: sin Responsable AIA asignado no se gestionan restricciones.
 *
 * Este test existe porque la regla vivía SOLO en el cliente
 * (`public/js/modules/programacion_intermedia/hot.js`) y el servidor la ignoraba,
 * así que el lote compartido escribía restricciones en filas que la UI mostraba
 * con candado. Aquí se comprueba que:
 *
 *  1. La regla del servidor (`App\Support\ResponsableAiaPolicy`) considera «sin
 *     asignar` exactamente lo mismo que el cliente: vacío, espacios o el
 *     placeholder «➕ Crear Profesional...».
 *  2. El cliente sigue usando ese mismo placeholder y ese mismo cálculo (si alguien
 *     cambia el texto en el JS, este test lo caza antes de que vuelvan las dos verdades).
 *  3. El lote (`ProgramacionIntermediaController`) sabe listar las actividades sin
 *     responsable y su endpoint las bloquea salvo que el propio lote las asigne.
 *  4. El guardado por celda (`src/Legacy/guardar_programacion_intermedia.php`)
 *     rechaza cambios de restricción sin responsable.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

use App\Support\ResponsableAiaPolicy;

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, $obtenido, $esperado): void
{
    global $fallos, $total;
    $total++;

    if ($obtenido === $esperado) {
        echo "  OK   {$descripcion}\n";

        return;
    }

    $fallos++;
    echo "  FALLA {$descripcion}\n";
    echo '        esperado: ' . var_export($esperado, true) . "\n";
    echo '        obtenido: ' . var_export($obtenido, true) . "\n";
}

echo "\n== 1. Qué cuenta como Responsable AIA asignado ==\n";
comprobar('cadena vacía → sin asignar', ResponsableAiaPolicy::hasAssigned(''), false);
comprobar('solo espacios → sin asignar', ResponsableAiaPolicy::hasAssigned('   '), false);
comprobar('null → sin asignar', ResponsableAiaPolicy::hasAssigned(null), false);
comprobar('placeholder → sin asignar', ResponsableAiaPolicy::hasAssigned(ResponsableAiaPolicy::CREATE_PLACEHOLDER), false);
comprobar('nombre real → asignado', ResponsableAiaPolicy::hasAssigned('Ana Pérez'), true);
comprobar('nombre con espacios alrededor → asignado', ResponsableAiaPolicy::hasAssigned('  Ana Pérez  '), true);
comprobar('cadena "0" → asignado (no es un caso especial)', ResponsableAiaPolicy::hasAssigned('0'), true);

echo "\n== 2. El cliente calcula lo mismo ==\n";
$hotJs = (string) file_get_contents(__DIR__ . '/../public/js/modules/programacion_intermedia/hot.js');
comprobar(
    'hot.js declara el mismo placeholder que la política PHP',
    strpos($hotJs, "var PI_CREATE_PROF = '" . ResponsableAiaPolicy::CREATE_PLACEHOLDER . "';") !== false,
    true,
);
comprobar(
    'hot.js sigue derivando hasResponsable de Responsable_AIA',
    (bool) preg_match('/hasResponsable:.*hasAssignedValue\(.*Responsable_AIA, PI_CREATE_PROF\)/', $hotJs),
    true,
);
comprobar(
    'hot.js bloquea el lote compartido sobre filas sin responsable',
    strpos($hotJs, 'findActivityIdsWithoutResponsable(activityIds)') !== false,
    true,
);

echo "\n== 3. El lote compartido identifica y bloquea ==\n";
$controller = (new ReflectionClass(\App\Controllers\Programacion\ProgramacionIntermediaController::class))
    ->newInstanceWithoutConstructor();
$listar = new ReflectionMethod($controller, 'activitiesWithoutResponsable');
$listar->setAccessible(true);

$filas = [
    ['unique_id' => '101', 'Responsable_AIA' => 'Ana Pérez'],
    ['unique_id' => '102', 'Responsable_AIA' => ''],
    ['unique_id' => '103', 'Responsable_AIA' => '   '],
    ['unique_id' => '104', 'Responsable_AIA' => ResponsableAiaPolicy::CREATE_PLACEHOLDER],
    ['unique_id' => '105', 'Responsable_AIA' => null],
    ['unique_id' => '106'],
];
comprobar(
    'lista exactamente las filas sin responsable',
    $listar->invoke($controller, $filas),
    ['102', '103', '104', '105', '106'],
);
comprobar('no marca nada cuando todas tienen responsable', $listar->invoke($controller, [$filas[0]]), []);

$controllerSrc = (string) file_get_contents(__DIR__ . '/../src/Controllers/Programacion/ProgramacionIntermediaController.php');
comprobar(
    'applySharedConstraints corta antes de escribir si falta responsable',
    (bool) preg_match(
        '/if \(\$applyRestriction && !\(\$applyAssignments && ResponsableAiaPolicy::hasAssigned\(\$responsableAia\)\)\) \{/',
        $controllerSrc,
    ),
    true,
);
comprobar(
    'el error del lote nombra qué falta',
    strpos(ResponsableAiaPolicy::mensajeLote(['102', '103']), 'Responsable AIA') !== false
        && strpos(ResponsableAiaPolicy::mensajeLote(['102', '103']), '102, 103') !== false,
    true,
);

echo "\n== 4. El guardado por celda rechaza cambios de restricción ==\n";
$legacySrc = (string) file_get_contents(__DIR__ . '/../src/Legacy/guardar_programacion_intermedia.php');
comprobar(
    'guardar_programacion_intermedia consulta la política',
    strpos($legacySrc, 'ResponsableAiaPolicy::hasAssigned($Responsable_AIA)') !== false,
    true,
);
comprobar(
    'y responde con el mensaje que dice qué falta',
    strpos($legacySrc, 'ResponsableAiaPolicy::MENSAJE_FALTA_RESPONSABLE') !== false,
    true,
);
comprobar(
    'el mensaje nombra el Responsable AIA',
    strpos(ResponsableAiaPolicy::MENSAJE_FALTA_RESPONSABLE, 'Responsable AIA') !== false,
    true,
);

echo "\n";
if ($fallos > 0) {
    echo "FALLARON {$fallos} de {$total} comprobaciones\n";
    exit(1);
}

echo "PASARON {$total} comprobaciones\n";
exit(0);
