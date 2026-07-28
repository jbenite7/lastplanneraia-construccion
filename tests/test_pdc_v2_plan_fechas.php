<?php
// tests/test_pdc_v2_plan_fechas.php — PlanFechasService sobre MySQL real (proyecto 999903).
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999903;
// Segundo proyecto de pruebas — solo para el hallazgo 4 (aislamiento entre proyectos): su propio
// cronograma mínimo (semana activa + un frente), reusando el catálogo global `test-a4` (los
// paquetes no llevan project_id, así que el mismo paqueteId puede amarrarse en los dos proyectos).
$P2 = 999904;

$limpiar = static function () use ($db, $P, $P2): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a4'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'TEST A4 DURACION'");
    $db->query('DELETE FROM pdc_presupuesto_apu_insumos WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM programa WHERE project_id IN (?, ?)', [$P, $P2]);
    $db->query('DELETE FROM semanas_activas WHERE project_id IN (?, ?)', [$P, $P2]);
};
$limpiar();

// Fixture: dos semanas consolidadas; la activa es la 2. Encabezados (Titulo=1) y una hoja (Titulo=0).
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?), (?, 2, 2, ?, ?)',
    [$P, '2026-07-27', '2026-08-02', $P, '2026-08-03', '2026-08-09'],
);

// `programa` es la versión viva (no versionada por semana): una fila por unique_id, con el
// Consecutivo que referencian los snapshots de `programa_consolidado` vía FK.
// El frente «MOVIMIENTO DE TIERRA» (uid 9004) reproduce el hallazgo 1: es un nombre de dos
// palabras con «DE» en medio, el mismo patrón que fabricaba propuestas falsas en producción.
$programa = [
    // [Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio]
    [1, 9001, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-08-18'],
    [2, 9002, '<b>PRELIMINARES, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-05-25'],
    [3, 9003, '<b>VACIADO LOSA PISO 3, </b> <small>[Capítulo: ESTRUCTURA, TORRE 1]</small>', 0, '2026-09-10'],
    [4, 9004, '<b>MOVIMIENTO DE TIERRA, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-09-01'],
];
foreach ($programa as [$cons, $uid, $act, $tit, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$P, $cons, $uid, $act, $tit, $ini],
    );
}

$filas = [
    // [Consecutivo, Consecutivo_en_Programa, Semana, unique_id, Titulo, Actividad, Fecha_Inicio]
    [101, 1, 1, 9001, 1, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-08-01'],
    [102, 1, 2, 9001, 1, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-08-18'],
    [103, 2, 2, 9002, 1, '<b>PRELIMINARES, </b> <small>[Capítulo: TORRE 1]</small>', '2026-05-25'],
    [104, 3, 2, 9003, 0, '<b>VACIADO LOSA PISO 3, </b> <small>[Capítulo: ESTRUCTURA, TORRE 1]</small>', '2026-09-10'],
    [105, 4, 2, 9004, 1, '<b>MOVIMIENTO DE TIERRA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-09-01'],
];
foreach ($filas as [$cons, $consProg, $sem, $uid, $tit, $act, $ini]) {
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, $cons, $sem, $uid, $consProg, $act, $tit, $ini],
    );
}

// Fixture de presupuesto: versión activa + árbol capítulo→subcapítulo→actividad→APU, para las
// señales de rama (subcapitulosDePaquete) y el reparto por project_id (hallazgo 2).
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-A4', 1, 'test-a4.xlsx', REPEAT('a', 64), 3, 3, 1000, 1, 'test-a4', NOW())",
    [$P],
);
$vid = (int) $db->lastInsertId();

$idItem = static function (string $codigo, ?string $padre, int $nivel, string $tipoFila, string $desc) use ($db, $P, $vid): int {
    $db->query(
        "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 0)",
        [$P, $vid, $codigo, $padre, $nivel, $tipoFila, $desc],
    );
    return (int) $db->lastInsertId();
};
$idCapitulo = $idItem('01', null, 1, 'capitulo', 'COSTO DIRECTO');
$idEstructuraSub = $idItem('01.01', '01', 2, 'subcapitulo', 'ESTRUCTURA');
$idPreliminaresSub = $idItem('01.02', '01', 2, 'subcapitulo', 'PRELIMINARES');
$idAcabadosSub = $idItem('01.03', '01', 2, 'subcapitulo', 'ACABADOS VARIOS');
$idVigaAct = $idItem('01.01.01', '01.01', 3, 'actividad', 'VIGA DE AMARRE');
$idDescapoteAct = $idItem('01.02.01', '01.02', 3, 'actividad', 'DESCAPOTE');
$idPinturaAct = $idItem('01.03.01', '01.03', 3, 'actividad', 'PINTURA GENERAL');

$apu = static function (int $itemId, string $desc, string $unidad, float $valor) use ($db, $P, $vid): void {
    $db->query(
        "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
         VALUES (?, ?, ?, ?, 'MATERIAL', ?, 1, 1, 1, ?, ?, 0)",
        [$P, $vid, $itemId, $desc, $unidad, $valor, $valor],
    );
};
// «MEZCLA CONCRETO ESTRUCTURAL/M3» vive en dos actividades de dos subcapítulos distintos: una
// bajo ESTRUCTURA con poco valor (se inserta primero) y otra bajo PRELIMINARES con mucho más
// valor (se inserta después). Si el código se queda con la primera fila que devuelva MySQL en
// vez de la de mayor valor (hallazgo 3), el subcapítulo elegido sería ESTRUCTURA en vez de
// PRELIMINARES — el orden de inserción está pensado para que ese bug, si reaparece, se note.
$apu($idVigaAct, 'MEZCLA CONCRETO ESTRUCTURAL', 'M3', 500000);
$apu($idVigaAct, 'ACERO FIGURADO', 'KG', 300000);
$apu($idDescapoteAct, 'MEZCLA CONCRETO ESTRUCTURAL', 'M3', 9000000);
$apu($idPinturaAct, 'PINTURA VINILO TIPO 1', 'GL', 200000);

$asignarInsumo = static function (string $descNorm, string $unidad, int $paqueteId) use ($db, $P): void {
    $db->query(
        "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
         VALUES (?, ?, ?, ?, 0, 'test-a4', NOW())",
        [$P, $descNorm, $unidad, $paqueteId],
    );
};

echo "=== PDC v2 A4: plan con fechas ===\n";
$svc = new PlanFechasService($db);

// --- limpiarActividad ---
$l = PlanFechasService::limpiarActividad('<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>');
$assert($l['nombre'] === 'ESTRUCTURA', 'Quita el HTML y la coma final del nombre: ' . $l['nombre']);
$assert($l['capitulo'] === 'TORRE 1', 'Extrae el capítulo embebido: ' . $l['capitulo']);
$assert(PlanFechasService::limpiarActividad('SIN HTML')['nombre'] === 'SIN HTML', 'Un texto sin HTML pasa igual.');

// --- frentesDisponibles ---
$f = $svc->frentesDisponibles($P);
$assert(count($f) === 3, 'Solo los encabezados de la semana activa (3 de 5 filas): ' . count($f));
$uids = array_column($f, 'uniqueId');
$assert(!in_array(9003, $uids, true), 'La actividad hoja (Titulo=0) no es un frente.');
$assert($f[0]['uniqueId'] === 9002 && $f[0]['fechaInicio'] === '2026-05-25', 'Ordena por fecha ascendente: primero PRELIMINARES.');
$assert($f[1]['fechaInicio'] === '2026-08-18', 'Toma la fecha de la semana ACTIVA (2), no de la 1.');
$assert($f[2]['uniqueId'] === 9004 && $f[2]['nombre'] === 'MOVIMIENTO DE TIERRA', 'El tercer frente es MOVIMIENTO DE TIERRA.');
$assert($svc->frentesDisponibles(999999) === [], 'Proyecto sin cronograma → lista vacía.');

// --- sugerirFrentes: por nombre ---
// Database::query() siempre devuelve un PDOStatement (nunca null; lanza excepción si falla), así
// que comparar su valor de retorno contra null (patrón sugerido en el brief) es un chequeo muerto:
// castear un PDOStatement a (int) además dispara un warning de PHP. Se separa en dos sentencias.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 ESTRUCTURA', 'TEST A4 ESTRUCTURA', 'a_todo_costo', 'contrato', 1, 'test-a4', NOW())",
);
$paqEstructura = (int) $db->lastInsertId();
$asignarInsumo('ACERO FIGURADO', 'KG', $paqEstructura); // el proyecto sí lo usa (hallazgo 2)

// Un paquete sin parecido con ningún frente no recibe propuesta: no se inventa. Su insumo cuelga
// de un subcapítulo (ACABADOS VARIOS) que tampoco se parece a ningún frente: ninguna señal aplica.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 ZZZQQQ', 'TEST A4 ZZZQQQ', 'suministro', 'contrato', 1, 'test-a4', NOW())",
);
$paqRaro = (int) $db->lastInsertId();
$asignarInsumo('PINTURA VINILO TIPO 1', 'GL', $paqRaro);

// --- hallazgo 1: las palabras vacías repetidas no cuentan doble ---
// «Vigilancia de acceso de personal» comparte solo «DE» con el frente «MOVIMIENTO DE TIERRA»,
// pero «DE» aparece dos veces en el nombre del paquete (mismo patrón que el hallazgo reportó con
// nombres reales del catálogo AIA, p. ej. «Alquiler de transporte de personal»; se usa un nombre
// sintético aquí para no depender de la siembra). Con el numerador sin deduplicar, Jaccard daba
// 2/6 ≈ 0,3333 (pasa el umbral); deduplicado da 1/6 ≈ 0,1667 (no pasa).
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('Vigilancia de acceso de personal', 'VIGILANCIA DE ACCESO DE PERSONAL', 'consumibles', 'contrato', 1, 'test-a4', NOW())",
);
$paqAlquiler = (int) $db->lastInsertId();
$asignarInsumo('VIGILANCIA ACCESO PERSONAL', 'UN', $paqAlquiler);

// --- hallazgo 2: el reparto respeta project_id y excluye modalidades sin proceso de contratación ---
// Mismo patrón de coincidencia que «TEST A4 ESTRUCTURA» (3 tokens, 1 en común con el frente →
// Jaccard = 1/3 ≈ 0,3333, justo el umbral), pero sin uso real en el proyecto o con una modalidad
// que no genera fecha: ninguno debe aparecer en la propuesta.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST D4 PRELIMINARES', 'TEST D4 PRELIMINARES', 'a_todo_costo', 'contrato', 1, 'test-a4', NOW())",
);
$paqNoUsado = (int) $db->lastInsertId(); // sin fila en pdc_insumo_paquete: el proyecto no lo usa

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST B4 ESTRUCTURA', 'TEST B4 ESTRUCTURA', 'a_todo_costo', 'no_contratable', 1, 'test-a4', NOW())",
);
$paqNoContratable = (int) $db->lastInsertId();
$asignarInsumo('DUMMY B4', 'UN', $paqNoContratable); // sí lo usa, pero la modalidad lo excluye

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST C4 ESTRUCTURA', 'TEST C4 ESTRUCTURA', 'a_todo_costo', 'consumo_directo', 1, 'test-a4', NOW())",
);
$paqConsumoDirecto = (int) $db->lastInsertId();
$asignarInsumo('DUMMY C4', 'UN', $paqConsumoDirecto); // idem: consumo_directo tampoco genera fecha

// --- hallazgo 3 + 4: la rama elige el subcapítulo de mayor valor, y la señal queda cubierta ---
// «TEST A4 RAMA MULTI» no se parece a ningún frente por su nombre (señal 1 no aplica). Su único
// insumo asignado vive en dos actividades de dos subcapítulos: ESTRUCTURA (bajo valor, insertada
// primero) y PRELIMINARES (alto valor, insertada después). La propuesta correcta es PRELIMINARES.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 RAMA MULTI', 'TEST A4 RAMA MULTI', 'mano_obra', 'contrato', 1, 'test-a4', NOW())",
);
$paqRamaMulti = (int) $db->lastInsertId();
$asignarInsumo('MEZCLA CONCRETO ESTRUCTURAL', 'M3', $paqRamaMulti);

$sug = $svc->sugerirFrentes($P);

$s = $sug[$paqEstructura] ?? null;
$assert($s !== null && $s['uniqueId'] === 9001, 'El paquete «TEST A4 ESTRUCTURA» se propone al frente ESTRUCTURA.');
$assert($s !== null && $s['origen'] === 'similitud', 'La propuesta por nombre se marca como «similitud».');
$assert($s !== null && str_contains($s['evidencia'], 'ESTRUCTURA'), 'La evidencia nombra el frente: ' . ($s['evidencia'] ?? ''));

$assert(!isset($sug[$paqRaro]), 'Sin señal, no hay propuesta: el paquete queda pendiente.');

$assert(!isset($sug[$paqAlquiler]), 'Hallazgo 1: «DE» repetido no basta para proponer MOVIMIENTO DE TIERRA.');

$assert(!isset($sug[$paqNoUsado]), 'Hallazgo 2: sin insumos asignados en el proyecto, no se propone aunque el nombre coincida.');
$assert(!isset($sug[$paqNoContratable]), 'Hallazgo 2: modalidad no_contratable queda fuera del plan de fechas.');
$assert(!isset($sug[$paqConsumoDirecto]), 'Hallazgo 2: modalidad consumo_directo queda fuera del plan de fechas.');

$rm = $sug[$paqRamaMulti] ?? null;
$assert($rm !== null, 'Hallazgo 4: la señal de rama sí produce una propuesta cuando el nombre no basta.');
$assert($rm !== null && $rm['origen'] === 'rama', 'El origen de esa propuesta es «rama».');
$assert($rm !== null && $rm['uniqueId'] === 9002, 'Hallazgo 3: elige PRELIMINARES (mayor valor), no ESTRUCTURA (primera fila).');
$assert($rm !== null && $rm['confianza'] === 'media', 'La propuesta por rama nunca es de confianza alta: hay un salto.');
$assert($rm !== null && str_contains($rm['evidencia'], 'PRELIMINARES'), 'La evidencia por rama nombra el subcapítulo: ' . ($rm['evidencia'] ?? ''));

// Ningún origen fuera de {similitud, rama}.
foreach ($sug as $pid => $s2) {
    $assert(in_array($s2['origen'], ['similitud', 'rama'], true), "Origen válido en el paquete $pid: {$s2['origen']}");
}

// --- amarrar ---
$r = $svc->amarrar($P, $paqEstructura, 9001, 'test-a4', [
    'origen' => 'similitud', 'confianza' => 'alta', 'evidencia' => 'Coincide el nombre.', 'confirmado' => true,
]);
$assert(($r['ok'] ?? false) === true, 'Amarrar un paquete a un frente existente.');
$a = $svc->amarres($P);
$assert(isset($a[$paqEstructura]), 'El amarre se puede leer de vuelta.');
$assert($a[$paqEstructura]['fechaAncla'] === '2026-08-18', 'El amarre guarda la fecha que el frente tenía al amarrarlo.');
$assert($a[$paqEstructura]['origen'] === 'similitud' && $a[$paqEstructura]['confirmadoHumano'] === true,
    'Aceptar la propuesta conserva la capa Y queda confirmada.');

// Reamarrar mueve, no duplica.
$svc->amarrar($P, $paqEstructura, 9002, 'test-a4');
$filas = (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ?', [$P])->fetchColumn();
$assert($filas === 1, 'Un paquete, un frente: reamarrar no duplica filas.');
$a2 = $svc->amarres($P);
$assert($a2[$paqEstructura]['uniqueId'] === 9002 && $a2[$paqEstructura]['fechaAncla'] === '2026-05-25',
    'Al reamarrar se actualiza también la fecha ancla.');
$assert($a2[$paqEstructura]['origen'] === 'humano', 'Elegir a mano es una decisión humana.');

// Un frente que no existe en la semana activa se rechaza.
$mal = $svc->amarrar($P, $paqEstructura, 999999, 'test-a4');
$assert(($mal['ok'] ?? true) === false && ($mal['code'] ?? '') === 'FRENTE_INVALIDO', 'Frente inexistente rechazado.');

// --- hueco 1: PAQUETE_INVALIDO por los dos caminos ---
// El uniqueId (9001) es un frente válido: si no lo fuera, amarrar() saldría por FRENTE_INVALIDO
// antes de llegar siquiera a mirar el paquete, y este test no probaría lo que dice probar.
$malPaquete = $svc->amarrar($P, 999999999, 9001, 'test-a4');
$assert(($malPaquete['ok'] ?? true) === false && ($malPaquete['code'] ?? '') === 'PAQUETE_INVALIDO',
    'Paquete inexistente: PAQUETE_INVALIDO.');

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 INACTIVO', 'TEST A4 INACTIVO', 'suministro', 'contrato', 0, 'test-a4', NOW())",
);
$paqInactivo = (int) $db->lastInsertId();
$malInactivo = $svc->amarrar($P, $paqInactivo, 9001, 'test-a4');
$assert(($malInactivo['ok'] ?? true) === false && ($malInactivo['code'] ?? '') === 'PAQUETE_INVALIDO',
    'Paquete existente pero activo=0: también PAQUETE_INVALIDO.');

// --- hueco 2: origen del motor sin confirmar humano queda confirmado_humano = 0 ---
// Caso contrario al ya cubierto (propuesta aceptada y confirmada): una sugerencia aplicada que
// todavía nadie revisó. $paqRaro está en la fixture, activo, y sin amarre previo.
$svc->amarrar($P, $paqRaro, 9002, 'test-a4', ['origen' => 'similitud', 'confianza' => 'media', 'evidencia' => 'Sugerencia sin revisar.']);
$aRaro = $svc->amarres($P);
$assert($aRaro[$paqRaro]['origen'] === 'similitud', 'Origen del motor se conserva aunque nadie lo confirme.');
$assert($aRaro[$paqRaro]['confirmadoHumano'] === false,
    'Hueco 2: sugerencia del motor sin `confirmado` (ni true) deja confirmado_humano = 0.');

// --- hueco 3: semana_origen se llena con la semana ACTIVA (MAX de semanas_activas), no otra ---
// La única semana activa del fixture es la 2 (MAX de {1,2}); si el código guardara otra cosa
// (la primera fila, o una constante), esta consulta directa lo delataría.
$semOrigen = (int) $db->query(
    'SELECT semana_origen FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura],
)->fetchColumn();
$assert($semOrigen === 2, 'Hueco 3: semana_origen guarda la semana activa (2), no otra: ' . $semOrigen);

// --- hueco 4: amarres() no filtra entre proyectos — un segundo proyecto con su propio cronograma ---
// Cronograma mínimo de $P2: una sola semana activa (1) y un solo frente (uid 9101). Se reusa
// $paqEstructura (el catálogo global no lleva project_id) para demostrar que el aislamiento lo da
// `project_id` en `pdc_paquete_frente`, no el paqueteId.
$db->query('INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P2, '2026-07-27', '2026-08-02']);
$db->query(
    "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
     VALUES (?, 1, 9101, '<b>CIMENTACIÓN, </b> <small>[Capítulo: TORRE 2]</small>', 1, '2026-08-05')",
    [$P2],
);
$db->query(
    'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
         Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
         Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
     VALUES (?, 201, 1, 9101, 1, ?, 1, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
    [$P2, '<b>CIMENTACIÓN, </b> <small>[Capítulo: TORRE 2]</small>', '2026-08-05'],
);

$rP2 = $svc->amarrar($P2, $paqEstructura, 9101, 'test-a4');
$assert(($rP2['ok'] ?? false) === true, 'Hueco 4: amarrar en el segundo proyecto funciona con su propio cronograma.');

$aP2 = $svc->amarres($P2);
$assert(count($aP2) === 1 && isset($aP2[$paqEstructura]) && $aP2[$paqEstructura]['uniqueId'] === 9101,
    'Hueco 4: amarres($P2) solo trae el amarre de $P2.');

$aP1 = $svc->amarres($P);
$assert($aP1[$paqEstructura]['uniqueId'] === 9002,
    'Hueco 4: amarres($P) no se contamina con el amarre de $P2 al mismo paqueteId: ' . $aP1[$paqEstructura]['uniqueId']);
$uidsEnP1 = array_column($aP1, 'uniqueId');
$assert(!in_array(9101, $uidsEnP1, true),
    'Hueco 4: el frente de $P2 (9101) no aparece colgado de ningún paquete de $P.');

// --- calcular: la resta hacia atrás ---
// Catálogo de duraciones de juguete: 7+5+7+25+20 de proceso + 8 fabricación + 15 en obra = 87.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST A4 DURACION', 'Suministro', 7, 5, 7, 25, 20, 8, 15)",
);
$durId = (int) $db->lastInsertId();
$db->query('UPDATE general_paquetes_contratacion SET duracion_ref = ? WHERE id = ?', [$durId, $paqEstructura]);

// Reamarrar a ESTRUCTURA (18-ago-2026) para tener una fecha ancla conocida.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$c = $svc->calcular($P, 'test-a4');
// El fixture ya trae otro amarre vigente en $P a esta altura del archivo: $paqRaro quedó amarrado
// a PRELIMINARES en el bloque «hueco 2» (arriba, sin duracion_ref propia). calcular() opera sobre
// TODOS los amarres del proyecto, no solo el que acaba de tocar este bloque — así que lo correcto
// es 2 calculados (paqEstructura + paqRaro) y 1 sin duración (paqRaro, que cae en la mediana).
$assert(($c['ok'] ?? false) === true && ($c['calculados'] ?? 0) === 2 && ($c['sinDuracion'] ?? 0) === 1,
    'Se calcula el plan de los paquetes amarrados (paqEstructura con duración propia, paqRaro sin ella).');

$plan = $svc->plan($P);
$fila = null;
foreach ($plan as $f) { if ($f['paqueteId'] === $paqEstructura) { $fila = $f; } }
$assert($fila !== null, 'El plan trae el paquete calculado.');
$assert($fila['diasTotales'] === 87, 'Suma los siete pasos: 87 días. Dio ' . ($fila['diasTotales'] ?? 0));
$assert($fila['fechaArranque'] === '2026-05-23', 'Arranque = ancla menos 87 días calendario. Dio ' . ($fila['fechaArranque'] ?? ''));
$assert(count($fila['pasos']) === 7, 'Guarda una fila por paso.');
$assert($fila['pasos'][0]['paso'] === 'Elaboración de pliegos' && $fila['pasos'][0]['fechaInicio'] === '2026-05-23',
    'El primer paso arranca en la fecha de arranque.');
$assert(end($fila['pasos'])['fechaFin'] === '2026-08-18', 'El último paso TERMINA en la fecha del frente.');
$assert($fila['duracionProvisional'] === false, 'Con duracion_ref real, no es provisional.');

// Recalcular no duplica pasos.
$svc->calcular($P, 'test-a4');
$np = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqEstructura])->fetchColumn();
$assert($np === 7, 'Recalcular reemplaza los pasos, no los acumula. Hay ' . $np);

// Sin duracion_ref: se usa la mediana del tipo y queda marcado como provisional.
$db->query('UPDATE general_paquetes_contratacion SET duracion_ref = NULL WHERE id = ?', [$paqEstructura]);
$svc->calcular($P, 'test-a4');
$plan2 = $svc->plan($P);
$fila2 = null;
foreach ($plan2 as $f) { if ($f['paqueteId'] === $paqEstructura) { $fila2 = $f; } }
$assert($fila2 !== null && $fila2['duracionProvisional'] === true, 'Sin duración propia, el plazo se marca provisional.');
$assert($fila2 !== null && $fila2['diasTotales'] > 0, 'La mediana del tipo da un plazo mayor que cero.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
