<?php

declare(strict_types=1);
// @requiere: puro

/**
 * «Bloqueadas (restricciones)» sigue el PORCENTAJE de las actividades de la ventana de 6 semanas que tienen
 * alguna restricción DURA sin liberar, no su conteo. Cortes decididos por Felipe el 2026-09-29, PROVISIONALES:
 *
 *   menos de 10%      -> verde    (estado OK, sin acción)
 *   de 10% a 49%      -> amarillo (estado «Medio», acción «Liberar»)
 *   50% o más         -> rojo     (estado «Alto riesgo», acción «Liberar»)
 *
 * El valor que se muestra sigue siendo el conteo de actividades bloqueadas; el porcentaje solo decide el estado.
 * El denominador es «¿Qué hacer?» (`activities_to_do_count`): las actividades de la ventana de 6 semanas.
 *
 * Por qué existe: con la regla de conteo («1 o más = amarillo») este indicador salía amarillo en 24 de 25
 * proyectos de desarrollo, y un indicador amarillo casi siempre deja de avisar. Aclaración de Felipe: solo aplica
 * a restricciones duras. La fórmula que decide «lista» usa únicamente columnas duras (`hard_restrictions_ready`).
 *
 * No abre la base: el scorecard es puro, así que se instancia sin constructor.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$bi = (new ReflectionClass(ControlTowerService::class))->newInstanceWithoutConstructor();
$failures = [];

function bloqueadas(object $bi, int $enVentana, int $bloqueadas): ?array
{
    $data = [['activities_to_do_count' => $enVentana, 'hard_restriction_blocked_count' => $bloqueadas]];
    foreach ((new ReflectionMethod($bi, 'scorecardOverview'))->invoke($bi, $data) as $kpi) {
        if ($kpi['kpi'] === 'Bloqueadas (restricciones)') {
            return $kpi;
        }
    }

    return null;
}

// [actividades en la ventana, bloqueadas, estado esperado, ¿lleva acción?, descripción]
$casos = [
    [0, 0, 'OK', false, 'sin actividades en la ventana'],
    [100, 0, 'OK', false, '0%'],
    [100, 9, 'OK', false, '9%: justo debajo del primer corte'],
    [100, 10, 'Medio', true, '10%: primer corte, ya es amarillo'],
    [100, 49, 'Medio', true, '49%: justo debajo del rojo'],
    [100, 50, 'Alto riesgo', true, '50%: segundo corte, ya es rojo'],
    [100, 100, 'Alto riesgo', true, '100%'],
    [10, 1, 'Medio', true, '1 de 10 = 10%, con cantidades pequeñas'],
    [1, 1, 'Alto riesgo', true, '1 de 1 = 100%'],
    [1000, 99, 'OK', false, '9,9%: no se redondea hacia arriba'],
];

foreach ($casos as [$enVentana, $bloq, $estado, $conAccion, $descripcion]) {
    $kpi = bloqueadas($bi, $enVentana, $bloq);
    if ($kpi === null) {
        $failures[] = "{$descripcion}: el indicador no existe en el scorecard";
        continue;
    }
    if ($kpi['status'] !== $estado) {
        $failures[] = "{$descripcion} ({$bloq} de {$enVentana}): el estado debe ser «{$estado}», salió «{$kpi['status']}»";
    }
    if ($kpi['value'] !== $bloq) {
        $failures[] = "{$descripcion}: el valor debe seguir siendo el conteo ({$bloq}), salió " . json_encode($kpi['value']);
    }
    if ($conAccion && $kpi['action'] !== 'Liberar') {
        $failures[] = "{$descripcion}: debe llevar la acción «Liberar», salió " . json_encode($kpi['action']);
    }
    if (!$conAccion && $kpi['action'] !== null) {
        $failures[] = "{$descripcion}: en verde no debe pedir acción, salió " . json_encode($kpi['action']);
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: «Bloqueadas» sale verde por debajo de 10%, amarillo de 10% a 49% y rojo desde 50%\n";
