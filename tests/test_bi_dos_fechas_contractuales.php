<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Dos fechas en «Variación probable de fecha final», decisión de Felipe (opción B, 2026-09-29):
 *
 *   - «Proyecto (toda la obra)»: la línea base declarada, la de siempre.
 *   - «Filtro»: hasta cuándo terminaban las actividades del alcance filtrado en el PRIMER programa
 *     registrado. Se rotula «Fin según el primer programa», no «contractual»: nadie declara una fecha
 *     por contratista, es una deducción del primer corte.
 *   - Diferencia en días (proyecto menos filtro): positiva = el filtro terminaba ANTES que el proyecto.
 *
 * Sin filtro la fecha del filtro no aplica y se muestra una sola. Sin filas del primer corte, la fecha del
 * filtro queda vacía y la diferencia también: no se inventa.
 *
 * No abre la base: el cálculo recibe las filas del primer corte que `programaCurveContext()` ya arma
 * (`contractual_baseline_by_project`), así que se instancia sin constructor.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$bi = (new ReflectionClass(ControlTowerService::class))->newInstanceWithoutConstructor();
$failures = [];

function dosFechas(object $bi, array $primerCorte, ?string $fin, array $filtros): array
{
    return (new ReflectionMethod($bi, 'fechasContractualesDelAlcance'))->invoke($bi, $primerCorte, $fin, $filtros);
}

function esperar(array &$failures, string $caso, array $obtenido, array $esperado): void
{
    foreach ($esperado as $clave => $valor) {
        // array_key_exists y no `??`: un null legítimo («sin fecha») no es lo mismo que una clave ausente.
        $existe = array_key_exists($clave, $obtenido);
        if (!$existe || $obtenido[$clave] !== $valor) {
            $failures[] = "{$caso}: '{$clave}' debería ser " . var_export($valor, true)
                . ' y es ' . ($existe ? var_export($obtenido[$clave], true) : 'una clave ausente');
        }
    }
}

$sinFiltro = ['desde' => '', 'hasta' => '', 'sub' => '', 'resp' => '', 'etapa' => ''];
$porSub = ['desde' => '', 'hasta' => '', 'sub' => 'Contratista X', 'resp' => '', 'etapa' => ''];
$corte = static fn(string ...$fines): array => [
    59 => array_map(static fn(string $f): array => ['Fecha_Inicio' => '2026-06-01', 'Fecha_Fin' => $f], $fines),
];

// 1. Sin filtro: una sola fecha, la del proyecto.
esperar($failures, 'sin filtro', dosFechas($bi, $corte('2026-11-15', '2026-11-30'), '2026-11-30', $sinFiltro), [
    'filtered' => false, 'project_finish' => '2026-11-30', 'first_program_finish' => null, 'difference_days' => null,
]);

// 2. Filtrado: el filtro termina 15 días antes del proyecto.
esperar($failures, 'filtro antes', dosFechas($bi, $corte('2026-10-01', '2026-11-15'), '2026-11-30', $porSub), [
    'filtered' => true, 'project_finish' => '2026-11-30', 'first_program_finish' => '2026-11-15', 'difference_days' => 15,
    'first_program_basis' => 'first_program_of_filtered_scope',
]);

// 3. El filtro termina DESPUÉS del proyecto: diferencia negativa.
esperar($failures, 'filtro después', dosFechas($bi, $corte('2026-12-05'), '2026-11-30', $porSub), [
    'first_program_finish' => '2026-12-05', 'difference_days' => -5,
]);

// 4. Mismo día: diferencia cero.
esperar($failures, 'mismo día', dosFechas($bi, $corte('2026-11-30'), '2026-11-30', $porSub), [
    'difference_days' => 0,
]);

// 5. Filtrado sin filas en el primer corte (el contratista llegó después): no se inventa nada.
esperar($failures, 'sin filas', dosFechas($bi, [], '2026-11-30', $porSub), [
    'filtered' => true, 'project_finish' => '2026-11-30', 'first_program_finish' => null, 'difference_days' => null,
]);

// 6. Proyecto sin línea base declarada: hay fecha del filtro pero no diferencia.
esperar($failures, 'sin línea base', dosFechas($bi, $corte('2026-11-15'), null, $porSub), [
    'filtered' => true, 'project_finish' => null, 'first_program_finish' => '2026-11-15', 'difference_days' => null,
]);

// 7. Un rango de fechas también es filtro.
esperar($failures, 'rango de fechas', dosFechas($bi, $corte('2026-11-15'), '2026-11-30',
    ['desde' => '2026-08-01', 'hasta' => '', 'sub' => '', 'resp' => '', 'etapa' => '']), [
    'filtered' => true, 'difference_days' => 15,
]);

// 8. Varios proyectos: manda la fecha más tardía entre todas las filas; fechas basura se ignoran.
esperar($failures, 'varios proyectos', dosFechas($bi, [
    59 => [['Fecha_Fin' => '2026-10-10'], ['Fecha_Fin' => 'no es fecha'], ['Fecha_Fin' => null]],
    68 => [['Fecha_Fin' => '2026-11-20']],
], '2026-11-30', $porSub), [
    'first_program_finish' => '2026-11-20', 'difference_days' => 10,
]);

if ($failures !== []) {
    fwrite(STDERR, "FALLAN " . count($failures) . " comprobaciones:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

echo "OK: las dos fechas contractuales (8 casos)\n";
