<?php
/**
 * Gate de la fase B1 (Seguimiento al Plan de Compras).
 *
 * Corre contra DAPORTO (project_id = 73) y PUEDE dejarlo alterado: es la decision de datos de B1.
 * Lo que si exige es ser idempotente — cada corrida limpia su propio rastro al empezar, de modo que
 * dos ejecuciones seguidas dejan el mismo estado.
 *
 * Autoejecutable: imprime PASS:/FAIL: y sale con 0/1. No hay PHPUnit en este repo.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

const P = 73;

$db = Database::getInstance();
$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    echo ($cond ? 'PASS: ' : 'FAIL: '), $msg, "\n";
    if (!$cond) {
        $fallos++;
    }
};

// Limpieza previa: este test escribe fechas reales sobre paquetes de verdad. Borrarlas al empezar
// —y no al terminar— es lo que hace que una corrida interrumpida no envenene la siguiente.
$db->query("UPDATE pdc_plan_paso SET fecha_real = NULL, registrado_por = '', registrado_at = NULL
            WHERE project_id = ? AND registrado_por LIKE 'test-b1%'", [P]);

$svc = new SeguimientoService($db);

// --- La proyeccion, sin base de datos ---
// Tres pasos de 5, 10 y 3 dias desde el 2026-03-02. Nada cumplido y hoy muy anterior: la proyeccion
// es identica al plan.
$pasos = [
    ['dias' => 5, 'fechaFin' => '2026-03-07', 'fechaReal' => null],
    ['dias' => 10, 'fechaFin' => '2026-03-17', 'fechaReal' => null],
    ['dias' => 3, 'fechaFin' => '2026-03-20', 'fechaReal' => null],
];
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['proyectadoInicio'] === '2026-03-02' && $p[0]['proyectadoFin'] === '2026-03-07',
    'Proyeccion: sin nada cumplido, el primer paso se proyecta donde dice el plan. Dio ' . json_encode($p[0]));
$assert($p[2]['proyectadoFin'] === '2026-03-20',
    'Proyeccion: sin nada cumplido, el ultimo paso termina donde dice el plan. Dio ' . $p[2]['proyectadoFin']);
$assert($p[0]['desfaseDias'] === null,
    'Proyeccion: un paso sin fecha real no tiene desfase, tiene null (no cero: cero significaria «llego puntual»).');

// El primer paso se cumplio 10 dias tarde: lo que sigue se corre esos 10 dias.
$pasos[0]['fechaReal'] = '2026-03-17';
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['desfaseDias'] === 10,
    'Proyeccion: el desfase de un paso cumplido es real menos programado. Dio ' . var_export($p[0]['desfaseDias'], true));
$assert($p[0]['proyectadoFin'] === '2026-03-17',
    'Proyeccion: la proyectada de un paso cumplido ES su fecha real.');
$assert($p[1]['proyectadoInicio'] === '2026-03-17' && $p[1]['proyectadoFin'] === '2026-03-27',
    'Proyeccion: el paso siguiente arranca donde termino lo real, no donde decia el plan. Dio ' . json_encode($p[1]));
$assert($p[2]['proyectadoFin'] === '2026-03-30',
    'Proyeccion: el atraso se arrastra hasta el final. Dio ' . $p[2]['proyectadoFin']);

// Un paso adelantado tambien mueve la cadena, hacia atras.
$pasos[0]['fechaReal'] = '2026-03-04';
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['desfaseDias'] === -3,
    'Proyeccion: cumplir antes de tiempo da desfase negativo. Dio ' . var_export($p[0]['desfaseDias'], true));
$assert($p[2]['proyectadoFin'] === '2026-03-17',
    'Proyeccion: adelantarse tambien mueve la cadena. Dio ' . $p[2]['proyectadoFin']);

// Todo pendiente y el plan ya vencido: proyectar hacia el pasado no informa de nada, asi que el
// cursor se adelanta a hoy.
$pasos[0]['fechaReal'] = null;
$p = $svc->proyectar('2026-03-02', $pasos, '2026-06-01');
$assert($p[0]['proyectadoInicio'] === '2026-06-01',
    'Proyeccion: si lo pendiente ya vencio, la proyeccion arranca hoy, no en el pasado. Dio ' . $p[0]['proyectadoInicio']);
$assert($p[2]['proyectadoFin'] === '2026-06-19',
    'Proyeccion: y la cadena entera se corre con el. Dio ' . $p[2]['proyectadoFin']);

// Un paso sin fecha programada (reamarre pendiente de recalculo) no tiene desfase contra que medirse.
$sinPlan = [['dias' => 4, 'fechaFin' => null, 'fechaReal' => '2026-03-10']];
$p = $svc->proyectar('2026-03-02', $sinPlan, '2026-01-01');
$assert($p[0]['desfaseDias'] === null,
    'Proyeccion: sin fecha programada no hay desfase, aunque haya fecha real.');

// --- Lectura contra la base ---
$paquete = (int) $db->query(
    'SELECT paquete_id FROM pdc_plan_paso WHERE project_id = ? ORDER BY paquete_id LIMIT 1', [P],
)->fetchColumn();
$assert($paquete > 0, 'DAPORTO tiene al menos un paquete con pasos calculados (ver Task 0 del plan).');

$detalle = $svc->pasosDePaquete(P, $paquete);
$assert(count($detalle) > 0, 'Detalle: el paquete devuelve sus pasos. Dio ' . count($detalle));
$assert(array_key_exists('proyectadoFin', $detalle[0]) && array_key_exists('fechaReal', $detalle[0]),
    'Detalle: cada paso trae programado, real y proyectado.');
$assert($detalle[0]['fechaReal'] === null,
    'Detalle: sin registrar nada, la fecha real es null.');

$resumen = $svc->resumen(P);
$assert(count($resumen) > 0, 'Resumen: devuelve una fila por paquete con plan. Dio ' . count($resumen));
$fila = null;
foreach ($resumen as $r) {
    if ($r['paqueteId'] === $paquete) {
        $fila = $r;
    }
}
$assert($fila !== null, 'Resumen: el paquete de prueba esta en el resumen.');
$assert(($fila['estado'] ?? '') === 'sin_empezar',
    'Resumen: sin ningun paso cumplido, el paquete esta «sin empezar». Dio ' . ($fila['estado'] ?? 'null'));
$assert(($fila['cumplidos'] ?? -1) === 0 && ($fila['total'] ?? 0) === count($detalle),
    'Resumen: cuenta 0 cumplidos de ' . count($detalle) . '. Dio ' . json_encode([$fila['cumplidos'] ?? null, $fila['total'] ?? null]));
$assert(($fila['pasoActual'] ?? '') === $detalle[0]['paso'],
    'Resumen: el paso actual es el primero sin fecha real. Dio ' . ($fila['pasoActual'] ?? 'null'));

$assert($fallos === 0, 'Sin fallos');
echo $fallos === 0 ? "=== OK ===\n" : "=== {$fallos} FALLOS ===\n";
exit($fallos === 0 ? 0 : 1);
