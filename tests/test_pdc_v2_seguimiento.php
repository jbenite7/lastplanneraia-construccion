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
// Rastro de las pruebas del recalculo: la fila heredada sin paso_id y la configuracion propia de
// pasos de la obra. Ambas se retiran al empezar por lo mismo que las fechas: una corrida cortada a
// mitad no puede cambiar el resultado de la siguiente.
$db->query("DELETE FROM pdc_plan_paso WHERE project_id = ? AND paso_id IS NULL AND paso LIKE 'test-b1%'", [P]);
(new \App\Services\Pdc\PasosContratacionService($db))->restablecer(P, 'test-b1');

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

// --- Registro del avance ---
$pasoId = $detalle[0]['pasoId'];
$assert($pasoId !== null, 'El paso de prueba tiene identidad (paso_id). Sin ella el registro no puede direccionarse.');

$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');
$assert(($r['ok'] ?? false) === true, 'Registro: guardar una fecha real responde ok. Dio ' . json_encode($r));

$tras = $svc->pasosDePaquete(P, $paquete);
$assert($tras[0]['fechaReal'] === '2026-04-15',
    'Registro: la fecha real queda guardada. Dio ' . var_export($tras[0]['fechaReal'], true));
$assert($tras[0]['registradoPor'] === 'test-b1',
    'Registro: queda constancia de quien la puso. Dio ' . $tras[0]['registradoPor']);
$assert($tras[0]['registradoAt'] !== null,
    'Registro: y de cuando la puso.');
$assert($tras[0]['proyectadoFin'] === '2026-04-15',
    'Registro: la proyectada del paso pasa a ser la real.');

// El paquete deja de estar «sin empezar» sin que nadie guarde ningun estado.
$fila2 = null;
foreach ($svc->resumen(P) as $x) {
    if ($x['paqueteId'] === $paquete) {
        $fila2 = $x;
    }
}
$assert(($fila2['estado'] ?? '') === 'en_curso',
    'Registro: con un paso cumplido de varios, el paquete pasa a «en curso». Dio ' . ($fila2['estado'] ?? 'null'));
$assert(($fila2['cumplidos'] ?? 0) === 1, 'Registro: el resumen cuenta un paso cumplido.');

// Un paso que no es de este paquete se rechaza. La FK garantiza que el paso existe en el catalogo,
// no que pertenezca a este plan: sin esta comprobacion, un cliente podria escribir en el paquete de
// otro pasandole el paso_id correcto y el paquete_id equivocado.
$otroPaquete = (int) $db->query(
    'SELECT paquete_id FROM pdc_plan_paso WHERE project_id = ? AND paquete_id <> ? LIMIT 1',
    [P, $paquete],
)->fetchColumn();
if ($otroPaquete > 0) {
    $r = $svc->registrarPaso(P, $otroPaquete, 999999, '2026-04-15', 'test-b1');
    $assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'PASO_INVALIDO',
        'Registro: un paso que no pertenece al plan del paquete se rechaza. Dio ' . json_encode($r));
}

// Fecha con formato invalido: se rechaza en vez de guardar basura que luego reviente al proyectar.
$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, '15/04/2026', 'test-b1');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'FECHA_INVALIDA',
    'Registro: una fecha mal formada se rechaza. Dio ' . json_encode($r));

// Otro proyecto no puede tocar los pasos de este.
$r = $svc->registrarPaso(999999, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');
$assert(($r['ok'] ?? true) === false,
    'Registro: el aislamiento por project_id se respeta. Dio ' . json_encode($r));
$assert($svc->pasosDePaquete(P, $paquete)[0]['fechaReal'] === '2026-04-15',
    'Registro: y el intento del otro proyecto no altero el dato de este.');

// Deshacer: null borra el registro y su auditoria.
$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, null, 'test-b1');
$assert(($r['ok'] ?? false) === true, 'Registro: borrar una fecha responde ok.');
$borrado = $svc->pasosDePaquete(P, $paquete);
$assert($borrado[0]['fechaReal'] === null && $borrado[0]['registradoAt'] === null,
    'Registro: deshacer deja el paso como si nunca se hubiera registrado.');

// Y se vuelve a poner, porque la Task 4 necesita un paso con avance.
$svc->registrarPaso(P, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');

// --- El avance sobrevive a un recalculo ---
(new \App\Services\Pdc\PlanFechasService($db))->calcular(P, 'test-b1');
$trasCalculo = $svc->pasosDePaquete(P, $paquete);
$assert($trasCalculo[0]['fechaReal'] === '2026-04-15',
    'Recalculo: la fecha real sobrevive (el upsert de calcular() no lista esa columna). Dio ' . var_export($trasCalculo[0]['fechaReal'], true));
$assert($trasCalculo[0]['registradoPor'] === 'test-b1',
    'Recalculo: y su auditoria tambien.');

// --- El avance sobrevive a un reamarre ---
// Desamarrar invalida el plan: las fechas se calcularon contra un frente que ya no es el suyo. Pero
// una propuesta ya recibida no deja de haberse recibido porque la obra se reprograme, asi que la
// fila con avance se conserva —sin fechas programadas— y el siguiente calculo se las repone.
$plan = new \App\Services\Pdc\PlanFechasService($db);
// Se guarda la fila ENTERA, no solo el unique_id: volver a amarrar sin procedencia deja el amarre
// como decision humana (`origen = 'humano'`, `confirmado_humano = 1`), y esa bandera es la que B2
// leera para no pisar lo que decidio una persona. Un test no puede firmar amarres en nombre de
// nadie, asi que al final se repone tal cual estaba.
$amarreFila = $db->query(
    'SELECT unique_id, origen, confianza, evidencia, confirmado_humano, asignado_por
       FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
)->fetch(\PDO::FETCH_ASSOC);
$amarreOriginal = $amarreFila === false ? false : $amarreFila['unique_id'];
$assert($amarreOriginal !== false, 'El paquete de prueba esta amarrado a un frente (precondicion del reamarre).');
$reponerAmarre = static function () use ($db, $amarreFila, $paquete): void {
    if ($amarreFila === false) {
        return;
    }
    $db->query(
        'UPDATE pdc_paquete_frente
            SET origen = ?, confianza = ?, evidencia = ?, confirmado_humano = ?, asignado_por = ?
          WHERE project_id = ? AND paquete_id = ?',
        [
            $amarreFila['origen'], $amarreFila['confianza'], $amarreFila['evidencia'],
            $amarreFila['confirmado_humano'], $amarreFila['asignado_por'], P, $paquete,
        ],
    );
};

$plan->desamarrar(P, $paquete);
$trasDesamarrar = $db->query(
    'SELECT fecha_real, fecha_inicio, fecha_fin, registrado_por FROM pdc_plan_paso
      WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
    [P, $paquete],
)->fetchAll(\PDO::FETCH_ASSOC);
$assert(count($trasDesamarrar) === 1,
    'Reamarre: la fila con avance real sobrevive al desamarre. Quedaron ' . count($trasDesamarrar));
$assert(($trasDesamarrar[0]['fecha_real'] ?? null) === '2026-04-15',
    'Reamarre: con su fecha real intacta.');
$assert(($trasDesamarrar[0]['registrado_por'] ?? '') === 'test-b1',
    'Reamarre: y con su auditoria intacta.');
$assert($trasDesamarrar[0]['fecha_inicio'] === null && $trasDesamarrar[0]['fecha_fin'] === null,
    'Reamarre: lo programado si se limpia — se calculo contra un frente que ya no vale.');

$sinAvance = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NULL',
    [P, $paquete],
)->fetchColumn();
$assert($sinAvance === 0,
    'Reamarre: las filas SIN avance si se borran, como siempre. Quedaron ' . $sinAvance);

// Se devuelve al estado anterior y se recalcula: las programadas vuelven, el avance sigue ahi.
$plan->amarrar(P, $paquete, (int) $amarreOriginal, 'test-b1');
$plan->calcular(P, 'test-b1');
$restaurado = $svc->pasosDePaquete(P, $paquete);
$assert(count($restaurado) === count($detalle),
    'Reamarre: recalcular repone todos los pasos. Dio ' . count($restaurado) . ' de ' . count($detalle));
$assert($restaurado[0]['fechaReal'] === '2026-04-15',
    'Reamarre: el avance sigue ahi tras recalcular.');
$assert($restaurado[0]['fechaFin'] !== null,
    'Reamarre: y las fechas programadas volvieron.');

// --- El avance sobrevive a que el paso salga del proceso de la obra ---
// Quien configura los Pasos de contratacion puede quitar uno. El recalculo retira entonces esa fila
// del plan por sobrante — y si dentro habia una fecha real, se llevaria por delante trabajo que
// ocurrio de verdad, sin aviso ni auditoria. La fila con avance se conserva; lo que se limpia son
// sus fechas programadas, que ya no las calcula nadie (mismo criterio que el reamarre).
$pasosSvc = new \App\Services\Pdc\PasosContratacionService($db);
$configOriginal = $pasosSvc->deProyecto(P);
$assert(count($configOriginal) > 1, 'La obra tiene mas de un paso configurado (precondicion).');

$reducida = [];
foreach ($configOriginal as $i => $p) {
    if ($i === 0) {
        continue; // justo el paso que lleva el avance registrado
    }
    $reducida[] = ['clave' => $p['clave'], 'diasFijos' => $p['diasFijos'] ?? 1];
}
$g = $pasosSvc->guardar(P, $reducida, 'test-b1');
$assert(($g['ok'] ?? false) === true, 'Configuracion: la obra acepta un proceso sin el primer paso. Dio ' . json_encode($g));

$plan->calcular(P, 'test-b1');
$huerfano = $db->query(
    'SELECT fecha_real, registrado_por, fecha_inicio, fecha_fin FROM pdc_plan_paso
      WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
    [P, $paquete, (int) $pasoId],
)->fetch(\PDO::FETCH_ASSOC);
$assert($huerfano !== false,
    'Sobrantes: quitar un paso del proceso NO borra la fila que llevaba avance registrado.');
$assert(($huerfano['fecha_real'] ?? null) === '2026-04-15' && ($huerfano['registrado_por'] ?? '') === 'test-b1',
    'Sobrantes: la fila conservada mantiene su fecha real y su auditoria. Dio ' . json_encode($huerfano));
$assert(($huerfano['fecha_inicio'] ?? null) === null && ($huerfano['fecha_fin'] ?? null) === null,
    'Sobrantes: y se queda sin fechas programadas, porque ya nadie las calcula. Dio ' . json_encode($huerfano));

// El resto del proceso si se recalculo con normalidad.
$vigentes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_fin IS NOT NULL',
    [P, $paquete],
)->fetchColumn();
$assert($vigentes === count($reducida),
    'Sobrantes: los pasos que siguen en el proceso conservan sus fechas. Dio ' . $vigentes . ' de ' . count($reducida));

$pasosSvc->restablecer(P, 'test-b1');
$plan->calcular(P, 'test-b1');
$assert($svc->pasosDePaquete(P, $paquete)[0]['fechaFin'] !== null,
    'Sobrantes: devolver el paso al proceso le repone sus fechas programadas.');

// --- Una fila heredada, sin identidad de paso, tampoco pierde su avance ---
// Los calculos anteriores a A4.1 dejaron filas sin `paso_id`. El recalculo las limpia a proposito
// —sin identidad no se pueden direccionar—, pero limpiar no puede significar borrar una fecha real.
$db->query(
    "INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin,
                                fecha_real, registrado_por, registrado_at)
     VALUES (?, ?, 90, NULL, 'test-b1 heredado con avance', 3, NULL, NULL, '2026-04-20', 'test-b1', NOW()),
            (?, ?, 91, NULL, 'test-b1 heredado sin avance', 3, NULL, NULL, NULL, '', NULL)",
    [P, $paquete, P, $paquete],
);
$plan->calcular(P, 'test-b1');
$heredados = $db->query(
    "SELECT orden, fecha_real FROM pdc_plan_paso
      WHERE project_id = ? AND paquete_id = ? AND paso_id IS NULL AND paso LIKE 'test-b1%' ORDER BY orden",
    [P, $paquete],
)->fetchAll(\PDO::FETCH_ASSOC);
$assert(count($heredados) === 1 && (int) $heredados[0]['orden'] === 90,
    'Heredadas: la fila sin paso_id CON avance sobrevive y la que no lo tiene se limpia. Dio ' . json_encode($heredados));
$db->query("DELETE FROM pdc_plan_paso WHERE project_id = ? AND paso_id IS NULL AND paso LIKE 'test-b1%'", [P]);

// --- Un paquete sin responsable con avance no pierde su cabecera al desamarrarse ---
// `limpiarPlanCalculado()` borra la cabecera cuando no hay responsable que conservar. Con avance
// registrado eso deja filas de paso sin cabecera: no hay clave foranea entre las dos tablas, asi que
// nadie lo impide, y el resultado es un avance escrito que ninguna pantalla puede ya mostrar ni
// editar (el resumen une por cabecera y el detalle necesita su fecha de arranque).
$responsableOriginal = $db->query(
    'SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
)->fetchColumn();
$db->query(
    'UPDATE pdc_plan_paquete SET responsable_user_id = NULL WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
);
$plan->desamarrar(P, $paquete);
$cabecera = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
)->fetchColumn();
$assert($cabecera === 1,
    'Cabecera: un paquete sin responsable pero CON avance conserva su cabecera al desamarrarse. Dio ' . $cabecera);
$conAvance = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
    [P, $paquete],
)->fetchColumn();
$assert($conAvance === 1, 'Cabecera: y el avance sigue ahi. Dio ' . $conAvance);

$plan->amarrar(P, $paquete, (int) $amarreOriginal, 'test-b1');
$plan->calcular(P, 'test-b1');
$db->query(
    'UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$responsableOriginal === false ? null : $responsableOriginal, P, $paquete],
);

// --- «Fin programado» es el fin del ULTIMO paso, no la ultima fecha que haya ---
// Un paso conservado sin fechas programadas (reamarre o paso retirado) deja huecos en la columna. Si
// se toma «la ultima no nula» al recorrer, la pantalla anuncia como fin del proceso la fecha de un
// paso intermedio, que es una fecha real de la obra y por eso nadie la lee como error.
$ultimo = $svc->pasosDePaquete(P, $paquete);
$ultimoOrden = (int) $ultimo[count($ultimo) - 1]['orden'];
$db->query(
    'UPDATE pdc_plan_paso SET fecha_fin = NULL WHERE project_id = ? AND paquete_id = ? AND orden = ?',
    [P, $paquete, $ultimoOrden],
);
$filaFin = null;
foreach ($svc->resumen(P) as $x) {
    if ($x['paqueteId'] === $paquete) {
        $filaFin = $x;
    }
}
$assert($filaFin !== null && $filaFin['finProgramado'] === null,
    'Fin programado: sin fecha en el ultimo paso no hay fin programado que anunciar, no la del anterior. Dio '
    . var_export($filaFin['finProgramado'] ?? null, true));
$plan->calcular(P, 'test-b1');

$reponerAmarre();
$amarreFinal = $db->query(
    'SELECT origen, confirmado_humano FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
)->fetch(\PDO::FETCH_ASSOC);
$assert($amarreFinal !== false && $amarreFinal['origen'] === $amarreFila['origen']
    && (int) $amarreFinal['confirmado_humano'] === (int) $amarreFila['confirmado_humano'],
    'El test devuelve el amarre a su procedencia original: no deja firmado como humano lo que decidio el motor.');

$assert($fallos === 0, 'Sin fallos');
echo $fallos === 0 ? "=== OK ===\n" : "=== {$fallos} FALLOS ===\n";
exit($fallos === 0 ? 0 : 1);
