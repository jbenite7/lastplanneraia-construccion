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

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a4'");
    $db->query('DELETE FROM pdc_presupuesto_apu_insumos WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
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

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
