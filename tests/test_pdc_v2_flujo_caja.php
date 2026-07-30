<?php
// tests/test_pdc_v2_flujo_caja.php — FlujoCajaService sobre MySQL real (proyecto 999941).
//
// Prueba la condición de hecho del spec `2026-07-29-flujo-caja-desembolsos-design.md`.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\FlujoCajaService;
use App\Services\Pdc\PlanFechasService;
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

// --- Aritmética pura del reparto, sin base de datos ---------------------------------------------
// Va primero porque es donde vive el riesgo real: si el reparto no cuadra, todo lo demás da igual.

// El caso del spec: un frente de febrero a abril aporta a esos tres meses y a ningún otro.
$r = FlujoCajaService::repartirLineal(89000.0, '2026-02-01', '2026-04-30');
$assert(array_keys($r) === ['2026-02', '2026-03', '2026-04'], 'Un frente de febrero a abril aporta a esos tres meses. Dio: ' . implode(' ', array_keys($r)));
$assert(abs(array_sum($r) - 89000.0) < 0.001, 'La suma de los meses es EXACTAMENTE el valor repartido. Dio ' . array_sum($r));
// 28, 31 y 30 días de 89.
$assert(abs($r['2026-02'] - 89000.0 * 28 / 89) < 0.01, 'Febrero recibe a prorrata de sus 28 días. Dio ' . $r['2026-02']);
$assert(abs($r['2026-03'] - 89000.0 * 31 / 89) < 0.01, 'Marzo recibe a prorrata de sus 31 días. Dio ' . $r['2026-03']);
$assert($r['2026-04'] > $r['2026-02'], 'Abril (30 días) recibe más que febrero (28).');

// Un solo día: todo en su mes, nada perdido.
$r1 = FlujoCajaService::repartirLineal(500.0, '2026-05-10', '2026-05-10');
$assert($r1 === ['2026-05' => 500.0], 'Un frente de un solo día pone todo su valor en ese mes.');

// Céntimos imposibles de dividir: el residuo va al último mes y la suma sigue cuadrando.
$r2 = FlujoCajaService::repartirLineal(100.0, '2026-01-01', '2026-03-31');
$assert(abs(array_sum($r2) - 100.0) < 0.0001, 'Con valores que no dividen exacto, la suma sigue siendo el total. Dio ' . array_sum($r2));

// Fin anterior al inicio: dato malo del cronograma, no se pierde el valor ni se reparte hacia atrás.
$r3 = FlujoCajaService::repartirLineal(700.0, '2026-08-10', '2026-08-01');
$assert($r3 === ['2026-08' => 700.0], 'Un frente con fin anterior al inicio se trata como un día y conserva su valor.');

// Un año largo: cruza de año sin perder nada.
$r4 = FlujoCajaService::repartirLineal(1000.0, '2026-11-15', '2027-02-15');
$assert(
    array_keys($r4) === ['2026-11', '2026-12', '2027-01', '2027-02'] && abs(array_sum($r4) - 1000.0) < 0.001,
    'Un frente que cruza el año reparte en los cuatro meses y cuadra.',
);

// --- Sobre base de datos -------------------------------------------------------------------------
$db = Database::getInstance();
$P = 999941;
$USR = 'test-flujo-caja';

$limpiar = static function () use ($db, $P): void {
    foreach (['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_paquete_frente', 'pdc_insumo_paquete',
        'pdc_subpaquete', 'pdc_insumo_vinculos', 'pdc_presupuesto_versiones', 'programa_consolidado',
        'programa', 'semanas_activas'] as $t) {
        $db->query("DELETE FROM {$t} WHERE project_id = ?", [$P]);
    }
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-flujo-caja'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST FLUJO%'");
};
$limpiar();

$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);
// Dos frentes con duración conocida: uno de 3 meses exactos y otro de un mes.
$frentes = [
    [1, 7701, 'ESTRUCTURA', '2026-02-01', '2026-04-30'],
    [2, 7702, 'CUBIERTA', '2026-06-01', '2026-06-30'],
];
foreach ($frentes as [$cons, $uid, $nombre, $ini, $fin]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, ?, ?, ?, 1, ?, ?)',
        [$P, $cons, $uid, '<b>' . $nombre . ', </b> <small>[Capítulo: T1]</small>', $ini, $fin],
    );
    $db->query(
        'INSERT INTO programa_consolidado
            (project_id, Consecutivo, Consecutivo_en_Programa, Semana, unique_id, Titulo, Actividad, Fecha_Inicio, Fecha_Fin)
         VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?)',
        [$P, 700 + $cons, $cons, $uid, '<b>' . $nombre . ', </b> <small>[Capítulo: T1]</small>', $ini, $fin],
    );
}

$db->query(
    'INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES (?, ?, 3, 2, 7, 4, 5, 10, 2)',
    ['TEST FLUJO CONCRETO', 'suministro'],
);
$durRef = (int) $db->lastInsertId();

$paquetes = [];
foreach ([
    ['TEST FLUJO Suministro CONCRETO', 'contrato'],
    ['TEST FLUJO Suministro ACERO', 'contrato'],
    ['TEST FLUJO Provisiones', 'no_contratable'],
    ['TEST FLUJO Sin frente', 'contrato'],
] as [$nombre, $modalidad]) {
    $db->query(
        "INSERT INTO general_paquetes_contratacion
            (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
         VALUES (?, ?, 'suministro', ?, ?, 1, 'test-flujo-caja', NOW())",
        [$nombre, mb_strtolower($nombre), $modalidad, $durRef],
    );
    $paquetes[$nombre] = (int) $db->lastInsertId();
}

$db->query(
    "INSERT INTO pdc_presupuesto_versiones
        (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash,
         import_token, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'Versión 1 · test', 1, 't.xlsx', 'h', 'c', 'tk', 1, 4, 1000, 1, ?, NOW())",
    [$P, $USR],
);
$versionId = (int) $db->lastInsertId();

// Un insumo por paquete, con valores redondos para poder verificar a mano.
$asignaciones = [
    ['concreto 3000 psi', 'M3', 89000.0, 'TEST FLUJO Suministro CONCRETO'],
    ['acero 60000 psi', 'KG', 30000.0, 'TEST FLUJO Suministro ACERO'],
    ['provision imprevistos', 'SG', 5000.0, 'TEST FLUJO Provisiones'],
    ['ventaneria aluminio', 'M2', 7000.0, 'TEST FLUJO Sin frente'],
];
foreach ($asignaciones as [$desc, $un, $valor, $paq]) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
             cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, 'MATERIAL', 1, ?, 1, 'confirmado')",
        [$P, $versionId, $desc, $un, $desc, $valor],
    );
    $db->query(
        "INSERT INTO pdc_insumo_paquete
            (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confirmado_humano, asignado_por, updated_at)
         VALUES (?, ?, ?, ?, 0, 'humano', 1, ?, NOW())",
        [$P, $desc, $un, $paquetes[$paq], $USR],
    );
}

$plan = new PlanFechasService($db);
$plan->amarrar($P, $paquetes['TEST FLUJO Suministro CONCRETO'], 7701, $USR);
$plan->amarrar($P, $paquetes['TEST FLUJO Suministro ACERO'], 7702, $USR);
// «Provisiones» es no_contratable: amarrar() lo rechaza, que es justo por lo que queda fuera.
// «Sin frente» no se amarra a propósito.
$plan->calcular($P, $USR);

$flujo = new FlujoCajaService($db);
$c = $flujo->curva($P, $versionId);

// Punto 1: la curva cuenta el PLAN ENTERO, y la suma de los meses es ese total.
//
// Cambió respecto a la primera versión de este servicio, por decisión del dueño del producto
// (2026-07-30): «debería contar todo, lo que no se contrata distribuirlo en toda la duración de la
// obra». La nómina y los imprevistos también salen de caja, y todos los meses.
$assert(
    abs($c['total'] - 131000.0) < 0.01,
    'La curva suma el valor total del plan (119.000 contratados + 5.000 de provisión + 7.000 sin frente). Dio ' . $c['total'],
);
$assert(
    abs($c['total'] - $c['valorTotalDelPlan']) < 0.01,
    'El total de la curva ES el valor total del plan: no se queda nada fuera cuando la obra tiene fechas.',
);
$assert($c['excluidos']['destinos'] === 0, 'Con la obra fechada, nada queda fuera de la curva. Dio ' . $c['excluidos']['destinos']);

// Los tres orígenes, cada uno por su camino y con su nombre.
$assert(
    abs($c['porOrigen']['contratado']['valor'] - 119000.0) < 0.01,
    'Lo contratado con fecha propia son 119.000. Dio ' . $c['porOrigen']['contratado']['valor'],
);
$assert(
    abs($c['porOrigen']['permanente']['valor'] - 5000.0) < 0.01,
    'La provisión (no contratable) son 5.000 repartidos sobre toda la obra. Dio ' . $c['porOrigen']['permanente']['valor'],
);
$assert(
    abs($c['porOrigen']['provisional']['valor'] - 7000.0) < 0.01,
    'Lo que se contratará pero no tiene frente son 7.000, y va MARCADO aparte. Dio ' . $c['porOrigen']['provisional']['valor'],
);

// La duración de la obra sale del cronograma, que es la misma fuente del resto de la curva.
$assert($c['duracionObra'] !== null, 'La curva sabe de cuándo a cuándo va la obra.');
$assert(
    $c['duracionObra']['origen'] === 'cronograma' && $c['duracionObra']['desde'] === '2026-02-01',
    'Y la toma del cronograma: ' . json_encode($c['duracionObra']),
);

// Punto 2: lo contratado aporta a los meses de SU frente y a ningún otro. Se mira en la columna
// `contratado`, porque ahora el total del mes incluye además el reparto de toda la obra.
$porMesContratado = array_column($c['meses'], 'contratado', 'mes');
$assert(
    abs($porMesContratado['2026-06'] - 30000.0) < 0.01,
    'Junio recibe el acero completo (30.000) en la columna de lo contratado. Dio ' . $porMesContratado['2026-06'],
);
$assert(
    abs(($porMesContratado['2026-05'] ?? 0.0)) < 0.01,
    'Mayo no recibe nada contratado: ningún frente lo toca.',
);
// Pero mayo SÍ existe ahora como mes de la curva, porque la nómina corre todos los meses de la obra.
$porMesTotal = array_column($c['meses'], 'previsto', 'mes');
$assert(isset($porMesTotal['2026-05']), 'Mayo aparece en la curva: la nómina no descansa. Meses: ' . implode(' ', array_keys($porMesTotal)));
$assert(
    array_column($c['meses'], 'mes')[0] === '2026-02',
    'La curva arranca cuando arranca la obra. Dio ' . array_column($c['meses'], 'mes')[0],
);

// El acumulado es monótono y termina en el total.
$acum = array_column($c['meses'], 'acumulado');
$ordenado = $acum;
sort($ordenado);
$assert($acum === $ordenado, 'El acumulado nunca baja.');
$assert(abs(end($acum) - $c['total']) < 0.01, 'El acumulado del último mes es el total de la curva.');

// La advertencia viaja en la respuesta y nombra los tres caminos.
$assert(
    str_contains($c['nota'], 'lineal') && str_contains($c['nota'], 'condiciones de pago')
        && str_contains($c['nota'], 'toda la duración de la obra'),
    'La respuesta declara el reparto lineal, que no considera condiciones de pago, y que hay una parte repartida sobre toda la obra.',
);

// Punto 4: la exportación lleva los mismos números, las tres columnas y la misma advertencia.
$csv = $flujo->csv($P, $versionId);
$assert(str_starts_with($csv, "\xEF\xBB\xBF"), 'El CSV lleva BOM: Excel lo abre sin romper las tildes.');
$assert(str_contains($csv, 'condiciones de pago'), 'La advertencia va DENTRO del archivo, que es lo que viaja al comité.');
$assert(
    str_contains($csv, 'Provisional (sin frente todavía)'),
    'El CSV separa en su propia columna la parte que se va a mover.',
);
$assert(str_contains($csv, 'Duración de obra usada para el reparto'), 'Y dice sobre qué rango repartió lo que no tiene frente.');
$assert(str_contains($csv, '"2026-06"'), 'El CSV trae las filas de mes.');
$assert(str_contains($csv, 'Total en la curva'), 'Y el total.');

// Punto 5: mover el frente y recalcular mueve la curva de forma coherente, sin cambiar el total.
$db->query(
    'UPDATE programa_consolidado SET Fecha_Inicio = ?, Fecha_Fin = ? WHERE project_id = ? AND unique_id = ?',
    ['2026-09-01', '2026-09-30', $P, 7702],
);
$c2 = $flujo->curva($P, $versionId);
$porMes2 = array_column($c2['meses'], 'contratado', 'mes');
$assert(abs(($porMes2['2026-06'] ?? 0.0)) < 0.01, 'Al mover el frente del acero, junio deja de tener contratación.');
$assert(abs(($porMes2['2026-09'] ?? 0.0) - 30000.0) < 0.01, 'Y su valor completo aparece en septiembre. Dio ' . ($porMes2['2026-09'] ?? 0));
$assert(abs($c2['total'] - $c['total']) < 0.01, 'Mover un frente no cambia el total de la curva, solo cuándo cae.');

// Sin fechas de obra, lo que no tiene frente propio vuelve a quedar declarado fuera: no se inventa
// un rango para que el total cuadre. Va en un proyecto APARTE y no borrando el cronograma de este:
// `programa_consolidado` cuelga de `semanas_activas` por clave foránea en cascada, así que borrar la
// semana se lleva el cronograma entero y lo que se estaría midiendo sería otra cosa.
$P2 = 999942;
$db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$P2]);
$db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$P2]);
$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P2]);
$db->query(
    "INSERT INTO pdc_presupuesto_versiones
        (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash,
         import_token, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'Versión 1 · sin obra', 1, 't.xlsx', 'h2', 'c2', 'tk2', 1, 1, 5000, 1, ?, NOW())",
    [$P2, $USR],
);
$v2 = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO pdc_insumo_vinculos
        (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
         cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, 'provision sin obra', 'SG', 'provision sin obra', 'MATERIAL', 1, 5000, 1, 'confirmado')",
    [$P2, $v2],
);
$db->query(
    "INSERT INTO pdc_insumo_paquete
        (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confirmado_humano, asignado_por, updated_at)
     VALUES (?, 'provision sin obra', 'SG', ?, 0, 'humano', 1, ?, NOW())",
    [$P2, $paquetes['TEST FLUJO Provisiones'], $USR],
);
$cSinObra = $flujo->curva($P2, $v2);
$assert($cSinObra['duracionObra'] === null, 'Sin cronograma consolidado ni línea base, la obra no tiene duración conocida.');
$assert(
    $cSinObra['excluidos']['destinos'] === 1 && abs($cSinObra['excluidos']['valor'] - 5000.0) < 0.01,
    'Y entonces ese destino queda declarado fuera con su motivo, en vez de repartido sobre un rango inventado. Dio '
        . json_encode($cSinObra['excluidos']),
);
$assert(
    array_key_exists('La obra no tiene fechas de inicio y fin con las que repartir', $cSinObra['excluidos']['motivos']),
    'Y el motivo lo dice con palabras: ' . implode(' | ', array_keys($cSinObra['excluidos']['motivos'])),
);
$db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$P2]);
$db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$P2]);
$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P2]);

// Un lote hereda el comportamiento: partir un paquete reparte por lote.
$sub = new SubpaquetesService($db);
$sub->partir($P, $paquetes['TEST FLUJO Suministro CONCRETO'], ['Concreto premezclado'], $USR);
$lotes = $sub->listar($P, $paquetes['TEST FLUJO Suministro CONCRETO'], $versionId);
$c3 = $flujo->curva($P, $versionId);
$assert(
    abs($c3['total'] - $c2['total']) < 0.01,
    'Partir un paquete no cambia el total de la curva: el valor es el mismo, solo cambia quién lo aporta. Dio ' . $c3['total'],
);
// Se crearon DOS lotes (el pedido y el «Resto» automático) pero el único insumo del paquete cayó en
// el «Resto», así que el lote vacío no es un destino contratable: no hay nada que comprar en él.
$assert(count($lotes) === 2, 'Partir con un nombre deja dos lotes: el pedido y el «Resto».');
$assert(
    count($c3['detalle']) === 4,
    'El desglose lista un destino por lote CON insumos, más los otros tres paquetes. Un lote vacío no '
        . 'aparece: no tiene valor que repartir. Dio ' . count($c3['detalle']),
);
$assert(
    count(array_filter($c3['detalle'], static fn (array $f): bool => $f['origen'] === 'provisional')) === 1,
    'Y cada fila del desglose dice por qué camino entró a la curva.',
);
$nombresDetalle = array_column($c3['detalle'], 'nombre');
$assert(
    !in_array('TEST FLUJO Suministro CONCRETO', $nombresDetalle, true),
    'Y el paquete sombrilla deja de aparecer por su cuenta en el desglose.',
);

$limpiar();

if ($failures === []) {
    fwrite(STDOUT, "\n=== OK ===\n");
    exit(0);
}
fwrite(STDERR, "\n" . count($failures) . " FALLOS\n");
exit(1);
