<?php
// tests/test_pdc_v2_subpaquetes.php — Subpaquetes sobre MySQL real (proyecto 999940).
//
// Prueba la condición de hecho del spec `2026-07-29-subpaquetes-obra-design.md`, con el caso literal
// del comité: partir «Pisos» en porcelanato, tableta gres y cerámica, darle a cada uno su fecha, y
// verlos en el plan con fechas distintas.
//
// El punto 4 —«un proyecto sin ningún paquete partido produce EXACTAMENTE el mismo plan»— se prueba
// aquí sobre un proyecto sintético comparando fila a fila antes y después de que EXISTA la
// funcionalidad, y además se comprobó sobre el proyecto real de Da Porto contra la foto
// `goals/pdc-preparar-b1/evidence/linea-base-plan-antes-subpaquetes.txt`.
declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

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
$P = 999940;
$USR = 'test-subpaquetes';

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_subpaquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-subpaquetes'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST SUBPAQ%'");
};
$limpiar();

// --- Fixture -------------------------------------------------------------------------------------
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);

// Tres frentes con fechas MUY distintas: es lo que permite comprobar que cada lote toma la suya.
// «eso lo contrato en 2 meses, tírelo para dentro de 2 meses; o eso lo necesito ya».
$frentes = [
    [1, 8801, 'ACABADOS PISO 1', '2026-09-01'],
    [2, 8802, 'ACABADOS PISO 5', '2027-01-15'],
    [3, 8803, 'ACABADOS BAÑOS', '2027-06-10'],
];
foreach ($frentes as [$cons, $uid, $nombre, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, 1, ?)',
        [$P, $cons, $uid, '<b>' . $nombre . ', </b> <small>[Capítulo: TORRE 1]</small>', $ini],
    );
    $db->query(
        'INSERT INTO programa_consolidado
            (project_id, Consecutivo, Consecutivo_en_Programa, Semana, unique_id, Titulo, Actividad, Fecha_Inicio)
         VALUES (?, ?, ?, 1, ?, 1, ?, ?)',
        [$P, 100 + $cons, $cons, $uid, '<b>' . $nombre . ', </b> <small>[Capítulo: TORRE 1]</small>', $ini],
    );
}

// Duración con desglose completo, para que las fechas sean deterministas y no dependan de la
// mediana de la empresa (que cambia con el catálogo).
$db->query(
    'INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES (?, ?, 3, 2, 7, 4, 5, 10, 2)',
    ['TEST SUBPAQ PISOS', 'suministro'],
);
$duracionRef = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO general_paquetes_contratacion
        (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES (?, ?, 'suministro', 'contrato', ?, 1, 'test-subpaquetes', NOW())",
    ['TEST SUBPAQ Suministro PISOS', 'test subpaq suministro pisos', $duracionRef],
);
$paqPisos = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO general_paquetes_contratacion
        (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES (?, ?, 'suministro', 'contrato', ?, 1, 'test-subpaquetes', NOW())",
    ['TEST SUBPAQ Suministro ENCHAPES', 'test subpaq suministro enchapes', $duracionRef],
);
$paqEnchapes = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO pdc_presupuesto_versiones
        (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash,
         import_token, total_actividades, total_insumos, costo_total, activa,
         importado_por, created_at)
     VALUES (?, 'Versión 1 · test', 1, 'test.xlsx', 'h1', 'c1', 't1', 1, 5, 1000, 1, ?, NOW())",
    [$P, $USR],
);
$versionId = (int) $db->lastInsertId();

// Cinco insumos: los del comité (porcelanato ×2, tableta gres, cerámica) más uno que nadie moverá,
// para que el lote «Resto» tenga contenido de verdad.
$insumos = [
    ['porcelanato beige 60x60', 'M2', 100.0],
    ['porcelanato gris 60x60', 'M2', 200.0],
    ['tableta gres 30x30', 'M2', 50.0],
    ['ceramica blanca muro', 'M2', 25.0],
    ['guardaescoba en madera', 'ML', 10.0],
];
foreach ($insumos as [$desc, $un, $valor]) {
    $db->query(
        'INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
             cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, 1, ?)',
        [$P, $versionId, $desc, $un, $desc, 'MATERIAL', $valor, 'confirmado'],
    );
    $db->query(
        "INSERT INTO pdc_insumo_paquete
            (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confirmado_humano,
             asignado_por, updated_at)
         VALUES (?, ?, ?, ?, 0, 'humano', 1, ?, NOW())",
        [$P, $desc, $un, $paqPisos, $USR],
    );
}
// Un insumo en OTRO paquete, que nunca se parte: es el control del punto 4 dentro de este mismo test.
$db->query(
    'INSERT INTO pdc_insumo_vinculos
        (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
         cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, ?, 1, ?)',
    [$P, $versionId, 'enchape muro cocina', 'M2', 'enchape muro cocina', 'MATERIAL', 400.0, 'confirmado'],
);
$db->query(
    "INSERT INTO pdc_insumo_paquete
        (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confirmado_humano, asignado_por, updated_at)
     VALUES (?, 'enchape muro cocina', 'M2', ?, 0, 'humano', 1, ?, NOW())",
    [$P, $paqEnchapes, $USR],
);

$plan = new PlanFechasService($db);
$sub = new SubpaquetesService($db);
$seg = new SeguimientoService($db);

// --- Punto 4 (primera mitad): el plan SIN partir nada -------------------------------------------
$plan->amarrar($P, $paqPisos, 8801, $USR);
$plan->amarrar($P, $paqEnchapes, 8801, $USR);
$plan->calcular($P, $USR);

// Foto del plan del paquete que NADIE va a partir. Es el control del punto 4 dentro de este test:
// se toma antes de partir su vecino y se compara al final, carácter por carácter.
$soloEnchapes = static function () use ($db, $P, $paqEnchapes): string {
    $c = $db->query(
        'SELECT subpaquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales
           FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
        [$P, $paqEnchapes],
    )->fetchAll(\PDO::FETCH_ASSOC);
    $p = $db->query(
        'SELECT subpaquete_id, orden, dias, fecha_inicio, fecha_fin
           FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? ORDER BY subpaquete_id, orden',
        [$P, $paqEnchapes],
    )->fetchAll(\PDO::FETCH_ASSOC);
    return json_encode(['cabecera' => $c, 'pasos' => $p], JSON_THROW_ON_ERROR);
};
$fotoEnchapesAntes = $soloEnchapes();

$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ?', [$P])->fetchColumn() === 2,
    'Sin partir nada: dos cabeceras, una por paquete.',
);
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND subpaquete_id = 0', [$P])->fetchColumn() === 2,
    'Sin partir nada, las dos cabeceras llevan subpaquete_id = 0 (el centinela «sin partir»).',
);

// --- Punto 1: partir «Pisos» en tres y darle a cada uno su fecha --------------------------------
$r = $sub->partir($P, $paqPisos, ['Porcelanato', 'Tableta gres', 'Cerámica'], $USR);
$assert(($r['ok'] ?? false) === true, 'Partir «Pisos» en tres lotes funciona.');
$lotes = $sub->listar($P, $paqPisos, $versionId);
$assert(count($lotes) === 4, 'Quedan cuatro lotes: los tres pedidos más el «Resto» automático. Dio ' . count($lotes));
$restos = array_values(array_filter($lotes, static fn (array $l): bool => $l['esResto']));
$assert(count($restos) === 1, 'Hay exactamente un lote «Resto».');
$assert(
    $restos[0]['insumos'] === 5,
    'Los cinco insumos que estaban en el paquete cayeron en el «Resto», ninguno quedó sin destino. Dio ' . $restos[0]['insumos'],
);
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = 0', [$P, $paqPisos])->fetchColumn() === 0,
    'En un paquete partido no queda ningún insumo con subpaquete_id = 0 (sería un destino fantasma).',
);

$porNombre = [];
foreach ($lotes as $l) {
    $porNombre[$l['nombre']] = $l['subpaqueteId'];
}
// Mover los insumos a su lote, como haría la oficina técnica.
$sub->moverInsumos($P, $porNombre['Porcelanato'], [
    ['descripcionNorm' => 'porcelanato beige 60x60', 'unidad' => 'M2'],
    ['descripcionNorm' => 'porcelanato gris 60x60', 'unidad' => 'M2'],
]);
$sub->moverInsumos($P, $porNombre['Tableta gres'], [['descripcionNorm' => 'tableta gres 30x30', 'unidad' => 'M2']]);
$sub->moverInsumos($P, $porNombre['Cerámica'], [['descripcionNorm' => 'ceramica blanca muro', 'unidad' => 'M2']]);

// Cada lote a su frente: porcelanato ya, gres en enero, cerámica en junio.
$plan->amarrar($P, $paqPisos, 8801, $USR, [], $porNombre['Porcelanato']);
$plan->amarrar($P, $paqPisos, 8802, $USR, [], $porNombre['Tableta gres']);
$plan->amarrar($P, $paqPisos, 8803, $USR, [], $porNombre['Cerámica']);
$plan->calcular($P, $USR);

$anclas = $db->query(
    'SELECT subpaquete_id, fecha_ancla, fecha_arranque FROM pdc_plan_paquete
      WHERE project_id = ? AND paquete_id = ? ORDER BY fecha_ancla',
    [$P, $paqPisos],
)->fetchAll(\PDO::FETCH_ASSOC);
$assert(count($anclas) === 4, 'El paquete partido tiene una cabecera por lote (tres + Resto). Dio ' . count($anclas));
$fechasDistintas = array_unique(array_column($anclas, 'fecha_ancla'));
$assert(
    count($fechasDistintas) === 3,
    'Los lotes tienen fechas distintas: ' . implode(' · ', $fechasDistintas),
);
$assert(
    in_array('2026-09-01', $fechasDistintas, true) && in_array('2027-01-15', $fechasDistintas, true)
        && in_array('2027-06-10', $fechasDistintas, true),
    'Cada lote tomó la fecha del frente al que se amarró, no la del paquete.',
);
// 33 días de proceso: la fecha de arranque de cada lote es su ancla menos 33.
foreach ($anclas as $a) {
    $esperado = (new DateTimeImmutable((string) $a['fecha_ancla']))->modify('-33 days')->format('Y-m-d');
    $assert(
        (string) $a['fecha_arranque'] === $esperado,
        "El lote {$a['subpaquete_id']} arranca 33 días antes de su ancla ({$esperado}). Dio {$a['fecha_arranque']}",
    );
}
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqPisos])->fetchColumn() === 28,
    'Cuatro lotes × siete pasos = 28 filas de paso, ni una mezclada ni una perdida.',
);

// --- Punto 2: el sombrilla resume ---------------------------------------------------------------
$res = $sub->resumenSombrilla($P, $paqPisos, $versionId);
$assert($res !== null, 'El paquete partido tiene resumen de sombrilla.');
$assert($res['lotes'] === 4, 'El resumen cuenta los cuatro lotes.');
$assert(
    $res['hasta'] === '2027-06-10',
    'El sombrilla llega hasta la última fecha de sus lotes (2027-06-10). Dio ' . (string) $res['hasta'],
);
$assert(
    abs($res['valorTotal'] - 385.0) < 0.01,
    'El valor del sombrilla es la suma de sus lotes (385). Dio ' . $res['valorTotal'],
);
$assert($res['pasos'] === 28 && $res['pasosCumplidos'] === 0, 'El avance agregado parte de 0 sobre 28 pasos.');

// Registrar avance en UN lote no contamina a sus hermanos.
$pasoId = (int) $db->query(
    'SELECT paso_id FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ? AND orden = 0',
    [$P, $paqPisos, $porNombre['Porcelanato']],
)->fetchColumn();
$seg->registrarPaso($P, $paqPisos, $pasoId, '2026-07-30', $USR, $porNombre['Porcelanato']);
$conReal = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
    [$P, $paqPisos],
)->fetchColumn();
$assert($conReal === 1, 'Registrar un paso en un lote marca UNA fila, no la de sus hermanos con el mismo paso_id. Dio ' . $conReal);
$res2 = $sub->resumenSombrilla($P, $paqPisos, $versionId);
$assert($res2['pasosCumplidos'] === 1, 'El avance del sombrilla es la suma del de sus lotes.');

// --- Punto 3: el tablero de vencimientos lista lotes, no el sombrilla ---------------------------
$venc = $seg->vencimientos($P, [], '2026-08-15');
$nombresVenc = array_unique(array_column($venc['filas'], 'paquete'));
$assert(
    !in_array('TEST SUBPAQ Suministro PISOS', $nombresVenc, true),
    'El paquete sombrilla NO aparece como fila del tablero de vencimientos.',
);
$assert(
    in_array('Porcelanato', $nombresVenc, true),
    'Los lotes SÍ aparecen en el tablero: es donde de verdad se contrata. Vio: ' . implode(' · ', $nombresVenc),
);
// El conteo, y no solo los nombres: sin esta aserción, una unión hecha por paquete en vez de por
// destino multiplica las filas del tablero y este test sigue pasando —los nombres que espera están
// todos, solo que repetidos—. Se comprobó rompiendo la unión a propósito: sin esto, verde.
// 5 destinos × 7 pasos = 35, menos el paso de «Porcelanato» que se registró como cumplido más
// arriba: el tablero solo mira lo pendiente.
$assert(
    array_sum($venc['conteos']) === 5 * 7 - 1,
    'El tablero cuenta cada paso pendiente UNA vez: 34. Dio ' . array_sum($venc['conteos']),
);

$resumen = $seg->resumen($P);
$idsResumen = array_map(static fn (array $f): string => $f['paqueteId'] . ':' . $f['subpaqueteId'], $resumen);
$assert(
    count($idsResumen) === count(array_unique($idsResumen)),
    'El resumen no repite destinos (la unión por paquete los multiplicaba).',
);
$assert(
    count($resumen) === 5,
    'Cinco destinos con plan: cuatro lotes de Pisos más Enchapes sin partir. Dio ' . count($resumen),
);

// --- Punto 5: la cobertura cuenta destinos contratables ----------------------------------------
$destinos = $sub->destinos($P, $versionId);
$assert(count($destinos) === 5, 'destinos() da cinco unidades contratables. Dio ' . count($destinos));
$valorDestinos = array_sum(array_column($destinos, 'valor'));
$assert(
    abs($valorDestinos - 785.0) < 0.01,
    'La suma del valor de los destinos es el valor asignado del proyecto (785). Dio ' . $valorDestinos,
);
$assert(
    count(array_filter($destinos, static fn (array $d): bool => $d['paqueteId'] === $paqPisos)) === 4
        && count(array_filter($destinos, static fn (array $d): bool => $d['paqueteId'] === $paqEnchapes)) === 1,
    'Un paquete partido aporta tantos destinos como lotes; uno sin partir, uno.',
);

// --- Punto 6: ningún lote en el catálogo global -------------------------------------------------
$enCatalogo = (int) $db->query(
    "SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre IN ('Porcelanato', 'Tableta gres', 'Cerámica')",
)->fetchColumn();
$assert($enCatalogo === 0, 'Ningún lote se volvió paquete del catálogo global.');

// --- Punto 4 (segunda mitad): el paquete NO partido no se movió un día -------------------------
$assert(
    $soloEnchapes() === $fotoEnchapesAntes,
    'El paquete que nadie partió tiene EXACTAMENTE el mismo plan, fila a fila, que antes de partir su vecino.',
);

// --- Modalidad propia del lote: lo que no genera proceso queda fuera y se declara ---------------
$sub->actualizar($P, $porNombre['Cerámica'], ['modalidad' => 'no_contratable'], $USR);
$plan->calcular($P, $USR);
$res3 = $sub->resumenSombrilla($P, $paqPisos, $versionId);
$assert(
    abs($res3['valorFueraDelPlan'] - 25.0) < 0.01,
    'El sombrilla declara cuánto de su valor no entra al plan (25, la cerámica). Dio ' . $res3['valorFueraDelPlan'],
);
$assert(
    count($res3['lotesFueraDelPlan']) === 1 && $res3['lotesFueraDelPlan'][0]['nombre'] === 'Cerámica',
    'Y dice QUÉ lote es y por qué, en vez de callarlo.',
);
$planFilas = $plan->plan($P);
$nombresPlan = array_column($planFilas, 'nombre');
$assert(
    !in_array('Cerámica', $nombresPlan, true),
    'El lote no contratable sale del plan de fechas, como cualquier paquete de esa modalidad.',
);

// --- Deshacer la partición devuelve el paquete a su estado original ----------------------------
foreach (['Porcelanato', 'Tableta gres', 'Cerámica'] as $n) {
    $sub->eliminar($P, $porNombre[$n]);
}
$assert(
    !$sub->estaPartido($P, $paqPisos),
    'Al borrar el último lote de verdad, el paquete se desparte: no se queda con un «Resto» huérfano.',
);
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = 0', [$P, $paqPisos])->fetchColumn() === 5,
    'Y sus cinco insumos vuelven a subpaquete_id = 0, exactamente como estaban.',
);
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_subpaquete WHERE project_id = ?', [$P])->fetchColumn() === 0,
    'No queda ninguna fila de lote en la obra.',
);

// --- Asignar a un paquete YA PARTIDO no puede resucitar al sombrilla ---------------------------
//
// Regresión encontrada revisando el 2026-07-30: `PaquetesService::asignar()` no ponía
// `subpaquete_id`, así que un insumo asignado a un paquete partido se quedaba en `0` DENTRO de un
// paquete partido, y `destinos()` volvía a listar el sombrilla al lado de sus propios lotes: se
// contrataba a sí mismo, que es justo lo que el «Resto» existe para impedir. Pasa por el camino
// normal —el motor y la grilla masiva asignan a nivel de paquete—, no por uno raro.
// El bloque anterior deshizo la partición, así que aquí se vuelve a partir: sin esto la comprobación
// mediría un paquete SIN partir, donde `subpaquete_id = 0` es lo correcto y el test pasaría en verde
// sin haber probado nada.
$sub->partir($P, $paqPisos, ['Porcelanato otra vez'], $USR);
$paquetesSvc = new \App\Services\Pdc\PaquetesService($db);
$db->query(
    "INSERT INTO pdc_insumo_vinculos
        (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo,
         cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, 'listelo decorativo', 'ML', 'listelo decorativo', 'MATERIAL', 1, 33, 1, 'confirmado')",
    [$P, $versionId],
);
$paquetesSvc->asignar($P, [['descripcionNorm' => 'listelo decorativo', 'unidad' => 'ML']], $paqPisos, $USR);

$huerfanos = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = 0',
    [$P, $paqPisos],
)->fetchColumn();
$assert(
    $huerfanos === 0,
    'Asignar un insumo a un paquete partido NO lo deja en subpaquete_id = 0. Dio ' . $huerfanos,
);
$destinosTrasAsignar = array_filter($sub->destinos($P, $versionId), static fn (array $d): bool => $d['paqueteId'] === $paqPisos);
$assert(
    count(array_filter($destinosTrasAsignar, static fn (array $d): bool => !$d['esLote'])) === 0,
    'Y el paquete sombrilla NO reaparece como destino contratable al lado de sus lotes.',
);
$assert(
    count(array_filter($destinosTrasAsignar, static fn (array $d): bool => $d['esResto'])) === 1,
    'El insumo nuevo aterriza en el «Resto», que es el único destino al que el motor puede llegar solo.',
);

// Y mover un insumo de un paquete a OTRO no le deja el lote del paquete viejo: la fila ya existe, y
// sin `subpaquete_id` en el ON DUPLICATE KEY UPDATE conservaba un lote que no es de su paquete.
$paquetesSvc->asignar($P, [['descripcionNorm' => 'listelo decorativo', 'unidad' => 'ML']], $paqEnchapes, $USR);
$loteTrasMudanza = (int) $db->query(
    "SELECT subpaquete_id FROM pdc_insumo_paquete
      WHERE project_id = ? AND descripcion_norm = 'listelo decorativo' AND unidad = 'ML'",
    [$P],
)->fetchColumn();
$assert(
    $loteTrasMudanza === 0,
    'Al mudar el insumo a un paquete sin partir, su lote vuelve a 0 en vez de apuntar al del paquete anterior. Dio ' . $loteTrasMudanza,
);

// Se deshace la partición que abrió este bloque: el siguiente empieza con el paquete entero, y
// dejarlo partido le hacía creer que un nombre repetido era nuevo.
foreach ($sub->listar($P, $paqPisos, $versionId) as $l) {
    if (!$l['esResto']) {
        $sub->eliminar($P, $l['subpaqueteId']);
    }
}
$assert(!$sub->estaPartido($P, $paqPisos), 'El bloque deja el paquete como lo encontró: sin partir.');

// --- Las prohibiciones del alcance -------------------------------------------------------------
$sub->partir($P, $paqPisos, ['Porcelanato'], $USR);
$dup = $sub->partir($P, $paqPisos, ['Otro'], $USR);
$assert(($dup['code'] ?? '') === 'YA_PARTIDO', 'Un paquete ya partido no se vuelve a partir.');
$repe = $sub->agregar($P, $paqPisos, 'Porcelanato', 'contrato', $USR);
$assert(($repe['code'] ?? '') === 'NOMBRE_REPETIDO', 'Dos lotes del mismo paquete no pueden llamarse igual.');
$lotesAhora = $sub->listar($P, $paqPisos, $versionId);
$restoAhora = array_values(array_filter($lotesAhora, static fn (array $l): bool => $l['esResto']))[0];
$borrarResto = $sub->eliminar($P, $restoAhora['subpaqueteId']);
$assert(($borrarResto['code'] ?? '') === 'RESTO_NO_SE_BORRA', 'El «Resto» no se borra por su cuenta.');

$limpiar();

if ($failures === []) {
    fwrite(STDOUT, "\n=== OK ===\n");
    exit(0);
}
fwrite(STDERR, "\n" . count($failures) . " FALLOS\n");
exit(1);
