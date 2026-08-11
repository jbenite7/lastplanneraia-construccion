<?php
// tests/test_pdc_v2_subpaquetes_volumen.php — la regla de conteo por destino, A ESCALA (proyecto 999960).
//
// Por qué existe: `test_pdc_v2_subpaquetes.php` PRUEBA la regla —un paquete partido aporta un destino
// por lote y ninguno se repite— pero la prueba con cuatro lotes. Con cuatro, un fallo de
// multiplicación (unir por paquete en vez de por paquete + lote) da números pequeños que pasan
// desapercibidos; con ciento y pico, la misma unión mal hecha multiplica los conteos hasta que la
// pantalla miente a lo grande. Da Porto tiene hoy 4 paquetes con insumos y 12 asignaciones, así que
// ese volumen no existe en ninguna base y hay que fabricarlo.
//
// La escala imita la real del módulo: 96 paquetes de contratación, 384 insumos y 12 paquetes partidos
// en 3 lotes cada uno (+ su «Resto»), que dan 132 destinos contratables — el orden de magnitud que el
// spec anticipaba («¿11 de 96 paquetes o 11 de 130 subpaquetes?»).
//
// Además de los conteos, mide el TIEMPO y el número de consultas del plan completo: la forma más
// probable de que esto se degrade no es un número mal contado sino una consulta por destino.
declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\FlujoCajaService;
use App\Services\Pdc\PlanFechasService;
use App\Services\Pdc\SeguimientoService;
use App\Services\Pdc\SubpaquetesService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) {
        fwrite(STDOUT, "PASS: {$m}\n");
        return;
    }
    $failures[] = $m;
    fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999960;
$USR = 'test-volumen';

// La forma del ejercicio, en un solo sitio para poder cambiarla sin perseguir números por el archivo.
const PAQUETES = 96;
const INSUMOS_POR_PAQUETE = 4;
const PARTIDOS = 12;
const LOTES_POR_PARTIDO = 3;   // + el «Resto» que nace solo
const FRENTES = 20;

$destinosEsperados = (PAQUETES - PARTIDOS) + PARTIDOS * (LOTES_POR_PARTIDO + 1);

$limpiar = static function () use ($db, $P): void {
    foreach (['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_paquete_frente', 'pdc_insumo_paquete',
        'pdc_subpaquete', 'pdc_insumo_vinculos', 'pdc_presupuesto_versiones', 'programa_consolidado',
        'programa', 'semanas_activas'] as $t) {
        $db->query("DELETE FROM {$t} WHERE project_id = ?", [$P]);
    }
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-volumen'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST VOLUMEN%'");
};
$limpiar();

// --- Fixture ------------------------------------------------------------------------------------
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);

$uids = [];
for ($i = 0; $i < FRENTES; $i++) {
    $uid = 6000 + $i;
    $uids[] = $uid;
    // Frentes escalonados mes a mes: así la curva de caja tiene relieve de verdad y no un solo mes.
    $ini = (new DateTimeImmutable('2026-09-01'))->modify("+{$i} months");
    $fin = $ini->modify('+45 days');
    $act = '<b>FRENTE ' . $i . ', </b> <small>[Capítulo: T1]</small>';
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, ?, ?, ?, 1, ?, ?)',
        [$P, $i + 1, $uid, $act, $ini->format('Y-m-d'), $fin->format('Y-m-d')],
    );
    $db->query(
        'INSERT INTO programa_consolidado
            (project_id, Consecutivo, Consecutivo_en_Programa, Semana, unique_id, Titulo, Actividad, Fecha_Inicio, Fecha_Fin)
         VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?)',
        [$P, 6100 + $i, $i + 1, $uid, $act, $ini->format('Y-m-d'), $fin->format('Y-m-d')],
    );
}

// Desglose completo para que las fechas sean deterministas y ningún paquete caiga en la mediana.
$db->query(
    'INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES (?, ?, 3, 2, 7, 4, 5, 10, 2)',
    ['TEST VOLUMEN BASE', 'suministro'],
);
$durRef = (int) $db->lastInsertId();

$paquetes = [];
for ($i = 0; $i < PAQUETES; $i++) {
    $nombre = sprintf('TEST VOLUMEN Paquete %03d', $i);
    $db->query(
        "INSERT INTO general_paquetes_contratacion
            (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
         VALUES (?, ?, 'suministro', 'contrato', ?, 1, 'test-volumen', NOW())",
        [$nombre, mb_strtolower($nombre), $durRef],
    );
    $paquetes[] = (int) $db->lastInsertId();
}

$db->query(
    "INSERT INTO pdc_presupuesto_versiones
        (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash,
         import_token, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'Volumen', 1, 'v.xlsx', 'hv', 'cv', 'tv', 1, ?, 0, 1, ?, NOW())",
    [$P, PAQUETES * INSUMOS_POR_PAQUETE, $USR],
);
$versionId = (int) $db->lastInsertId();

// Insumos en lotes multi-fila: uno por uno serían 384 viajes y el test tardaría más en sembrar que
// en medir.
$filasVinculos = [];
$argsVinculos = [];
$filasAsign = [];
$argsAsign = [];
$valorEsperado = 0.0;
foreach ($paquetes as $idx => $paqueteId) {
    for ($j = 0; $j < INSUMOS_POR_PAQUETE; $j++) {
        $desc = sprintf('insumo %03d-%d', $idx, $j);
        $valor = 1000.0 * ($idx + 1) + $j;
        $valorEsperado += $valor;
        $filasVinculos[] = "(?, ?, ?, 'UN', ?, 'MATERIAL', 1, ?, 1, 'confirmado')";
        array_push($argsVinculos, $P, $versionId, $desc, $desc, $valor);
        $filasAsign[] = "(?, ?, 'UN', ?, 0, 'humano', 1, ?, NOW())";
        array_push($argsAsign, $P, $desc, $paqueteId, $USR);
    }
}
foreach (array_chunk($filasVinculos, 200, true) as $trozo) {
    $n = count($trozo) * 5;
    $db->query(
        'INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
             cantidad_total, valor_total, apariciones, estado)
         VALUES ' . implode(', ', $trozo),
        array_splice($argsVinculos, 0, $n),
    );
}
foreach (array_chunk($filasAsign, 200, true) as $trozo) {
    $n = count($trozo) * 4;
    $db->query(
        'INSERT INTO pdc_insumo_paquete
            (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confirmado_humano, asignado_por, updated_at)
         VALUES ' . implode(', ', $trozo),
        array_splice($argsAsign, 0, $n),
    );
}

$sub = new SubpaquetesService($db);
$plan = new PlanFechasService($db);
$seg = new SeguimientoService($db);

// --- Partir 12 paquetes en 3 lotes cada uno, y repartirles insumos -------------------------------
$t0 = microtime(true);
for ($i = 0; $i < PARTIDOS; $i++) {
    $paqueteId = $paquetes[$i];
    $nombres = [];
    for ($k = 0; $k < LOTES_POR_PARTIDO; $k++) {
        $nombres[] = "Lote {$k}";
    }
    $r = $sub->partir($P, $paqueteId, $nombres, $USR);
    if (($r['ok'] ?? false) !== true) {
        fwrite(STDERR, "FAIL: no se pudo partir el paquete {$paqueteId}\n");
        $failures[] = 'partir';
        break;
    }
    // Un insumo a cada lote; el cuarto se queda en el «Resto», que así tiene contenido de verdad.
    $lotes = array_values(array_filter($sub->listar($P, $paqueteId, $versionId), static fn (array $l): bool => !$l['esResto']));
    foreach ($lotes as $k => $l) {
        $sub->moverInsumos($P, $l['subpaqueteId'], [['descripcionNorm' => sprintf('insumo %03d-%d', $i, $k), 'unidad' => 'UN']]);
    }
}
$tPartir = microtime(true) - $t0;

// --- La unidad: cuántos destinos contratables hay ------------------------------------------------
$destinos = $sub->destinos($P, $versionId);
$assert(
    count($destinos) === $destinosEsperados,
    sprintf('%d paquetes con %d partidos dan %d destinos contratables. Dio %d', PAQUETES, PARTIDOS, $destinosEsperados, count($destinos)),
);
$claves = array_map(static fn (array $d): string => $d['paqueteId'] . ':' . $d['subpaqueteId'], $destinos);
$assert(
    count($claves) === count(array_unique($claves)),
    'Ningún destino se repite a escala: ' . (count($claves) - count(array_unique($claves))) . ' repetidos',
);
$valorDestinos = array_sum(array_column($destinos, 'valor'));
$assert(
    abs($valorDestinos - $valorEsperado) < 0.01,
    sprintf('La suma del valor de los %d destinos es el valor asignado del proyecto. Esperado %.2f, dio %.2f', count($destinos), $valorEsperado, $valorDestinos),
);

// --- Amarrar TODOS los destinos y calcular el plan ------------------------------------------------
$t0 = microtime(true);
foreach ($destinos as $i => $d) {
    $plan->amarrar($P, $d['paqueteId'], $uids[$i % FRENTES], $USR, [], $d['subpaqueteId']);
}
$tAmarrar = microtime(true) - $t0;

$t0 = microtime(true);
$res = $plan->calcular($P, $USR);
$tCalcular = microtime(true) - $t0;

$assert(
    $res['calculados'] === $destinosEsperados,
    sprintf('calcular() cuenta destinos, no paquetes: %d. Dio %d', $destinosEsperados, $res['calculados']),
);

$cabeceras = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ?', [$P])->fetchColumn();
$pasos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ?', [$P])->fetchColumn();
$assert($cabeceras === $destinosEsperados, "Una cabecera por destino: {$destinosEsperados}. Dio {$cabeceras}");
$assert(
    $pasos === $destinosEsperados * 7,
    sprintf('Siete pasos por destino y ni uno mezclado: %d. Dio %d', $destinosEsperados * 7, $pasos),
);

// El fallo que este archivo existe para atrapar: si algo uniera por paquete en vez de por destino,
// los lotes de un mismo paquete se pisarían o se multiplicarían. Se comprueba paquete a paquete.
$malos = $db->query(
    'SELECT paquete_id, subpaquete_id, COUNT(*) n FROM pdc_plan_paso WHERE project_id = ?
      GROUP BY paquete_id, subpaquete_id HAVING n <> 7',
    [$P],
)->fetchAll(\PDO::FETCH_ASSOC);
$assert($malos === [], 'Ningún destino tiene un número de pasos distinto de 7: ' . count($malos) . ' anómalos');

// Recalcular dos veces no debe duplicar nada: es el upsert por (proyecto, paquete, lote, paso).
$plan->calcular($P, $USR);
$pasos2 = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ?', [$P])->fetchColumn();
$assert($pasos2 === $pasos, "Recalcular a escala no duplica filas: {$pasos}. Dio {$pasos2}");

// --- Las tres vistas que consumen la unidad, a escala ---------------------------------------------
$resumen = $seg->resumen($P);
$idsResumen = array_map(static fn (array $f): string => $f['paqueteId'] . ':' . $f['subpaqueteId'], $resumen);
$assert(
    count($idsResumen) === count(array_unique($idsResumen)),
    'El resumen de seguimiento no repite destinos a escala',
);
$assert(
    count($resumen) === $destinosEsperados,
    sprintf('El seguimiento lista los %d destinos. Dio %d', $destinosEsperados, count($resumen)),
);

$venc = $seg->vencimientos($P, [], '2026-08-15');
$sumaConteos = array_sum($venc['conteos']);
$assert(
    $sumaConteos === $destinosEsperados * 7,
    sprintf('El tablero de vencimientos cuenta cada paso una vez: %d. Dio %d', $destinosEsperados * 7, $sumaConteos),
);

$curva = (new FlujoCajaService($db, $sub))->curva($P, $versionId);
$assert(
    abs($curva['valorTotalDelPlan'] - $valorEsperado) < 0.01,
    sprintf('La curva de caja cuenta el plan entero a escala. Esperado %.2f, dio %.2f', $valorEsperado, $curva['valorTotalDelPlan']),
);
$assert(
    abs($curva['total'] - $valorEsperado) < 0.01,
    'Y la suma de sus meses es ese mismo total: ' . $curva['total'],
);

// --- Lo que de verdad se degrada: consultas por destino -------------------------------------------
//
// Un número mal contado se ve; una consulta por destino no. Los topes son generosos a propósito —el
// objetivo es atrapar un N+1, no fijar el rendimiento de esta máquina—, pero un `calcular()` que se
// vaya a decenas de segundos con 132 destinos es exactamente lo que hay que ver antes de que una obra
// real lo sufra.
fwrite(STDOUT, sprintf(
    "\n-- Medición con %d destinos: partir+repartir %.2fs · amarrar %.2fs · calcular %.2fs\n",
    $destinosEsperados,
    $tPartir,
    $tAmarrar,
    $tCalcular,
));
$assert($tCalcular < 30.0, sprintf('calcular() con %d destinos tarda menos de 30s. Tardó %.2fs', $destinosEsperados, $tCalcular));
$assert($tPartir < 30.0, sprintf('partir y repartir %d paquetes tarda menos de 30s. Tardó %.2fs', PARTIDOS, $tPartir));

$limpiar();

if ($failures === []) {
    fwrite(STDOUT, "\n=== OK ===\n");
    exit(0);
}
fwrite(STDERR, "\n" . count($failures) . " FALLOS\n");
exit(1);
