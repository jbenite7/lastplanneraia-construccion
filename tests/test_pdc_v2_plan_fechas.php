<?php
// tests/test_pdc_v2_plan_fechas.php — PlanFechasService sobre MySQL real (proyecto 999903).
declare(strict_types=1);
// @requiere: datos-proyecto


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
    $db->query('DELETE FROM project_members WHERE project_id IN (?, ?)', [$P, $P2]);
    // Usuarios sintéticos del bloque «responsable»: se borran DESPUÉS de project_members y de
    // pdc_plan_paquete (la FK fk_ppp_responsable es ON DELETE SET NULL, así que el orden no rompe,
    // pero borrarlos al final deja el rastro más fácil de leer si algo falla a media corrida).
    $db->query("DELETE FROM general_usuarios WHERE usuario LIKE 'zztest-a4-%'");
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a4'");
    // LIKE en vez de la lista exacta: cada bloque de este archivo añade su propia fila de duración
    // sintética («TEST A4 DURACION», «... CORTA», «... SUMINISTRO COMPLETO», etc.) y todas comparten
    // el prefijo «TEST A4»; con un DELETE por valor exacto por cada una, olvidar añadir la nueva aquí
    // deja residuo entre corridas sin que ningún assert lo note.
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST A4%'");
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
// A4.2: la rama, vía correspondencia, resuelve ANTES que el nombre. La capa 'similitud' quedó como
// respaldo para cuando ninguna rama del paquete tiene correspondencia, así que aquí basta con que la
// propuesta exista y venga de una de las dos capas del motor.
$assert($s !== null && in_array($s['origen'], ['correspondencia', 'similitud'], true), 'La propuesta por nombre viene de una capa del motor. Dio ' . ($s['origen'] ?? 'nada'));
$assert($s !== null && str_contains($s['evidencia'], 'ESTRUCTURA'), 'La evidencia nombra el frente: ' . ($s['evidencia'] ?? ''));

$assert(!isset($sug[$paqRaro]), 'Sin señal, no hay propuesta: el paquete queda pendiente.');

$assert(!isset($sug[$paqAlquiler]), 'Hallazgo 1: «DE» repetido no basta para proponer MOVIMIENTO DE TIERRA.');

$assert(!isset($sug[$paqNoUsado]), 'Hallazgo 2: sin insumos asignados en el proyecto, no se propone aunque el nombre coincida.');
$assert(!isset($sug[$paqNoContratable]), 'Hallazgo 2: modalidad no_contratable queda fuera del plan de fechas.');
$assert(!isset($sug[$paqConsumoDirecto]), 'Hallazgo 2: modalidad consumo_directo queda fuera del plan de fechas.');

$rm = $sug[$paqRamaMulti] ?? null;
$assert($rm !== null, 'Hallazgo 4: la señal de rama sí produce una propuesta cuando el nombre no basta.');
// A4.2: la capa 'rama' pasó a llamarse 'correspondencia'. No es un renombre cosmético: antes
// comparaba el nombre del subcapítulo contra los frentes, y ahora consulta el puente curado
// rama → nodo del cronograma, que es lo que permite que CIELOS RASOS llegue a ACABADOS.
$assert($rm !== null && $rm['origen'] === 'correspondencia', 'El origen de esa propuesta es «correspondencia». Dio ' . ($rm['origen'] ?? 'nada'));
$assert($rm !== null && $rm['uniqueId'] === 9002, 'Hallazgo 3: elige PRELIMINARES (mayor valor), no ESTRUCTURA (primera fila).');
// A4.2: una correspondencia CONFIRMADA por una persona sí da confianza alta (f06). El salto que este
// assert protegía era el de la vieja capa 'rama', que deducía por parecido de nombres y nunca tenía
// evidencia humana detrás. La confianza la da la evidencia, no la capa.
$assert($rm !== null && in_array($rm['confianza'], ['alta', 'media'], true), 'La propuesta por rama declara su confianza. Dio ' . ($rm['confianza'] ?? 'nada'));
$assert($rm !== null && str_contains($rm['evidencia'], 'PRELIMINARES'), 'La evidencia por rama nombra el subcapítulo: ' . ($rm['evidencia'] ?? ''));

// Ningún origen fuera de {correspondencia, similitud} (A4.2 sustituyó 'rama' por 'correspondencia').
foreach ($sug as $pid => $s2) {
    $assert(in_array($s2['origen'], ['correspondencia', 'similitud'], true), "Origen válido en el paquete $pid: {$s2['origen']}");
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

// --- Importante 1 (review Task 6): amarrar() rechaza modalidades sin proceso de contratación ---
// $paqNoContratable y $paqConsumoDirecto vienen de la fixture de sugerirFrentes (arriba): activos,
// con insumos asignados, sin amarre previo. sugerirFrentes() ya los excluía de las propuestas; el
// hallazgo era que un amarre MANUAL (este llamado directo) no tenía ninguna defensa.
$malNoContratable = $svc->amarrar($P, $paqNoContratable, 9001, 'test-a4');
$assert(($malNoContratable['ok'] ?? true) === false && ($malNoContratable['code'] ?? '') === 'MODALIDAD_NO_CONTRATABLE',
    'Importante 1: un paquete no_contratable no se puede amarrar a ningún frente.');
$malConsumoDirecto = $svc->amarrar($P, $paqConsumoDirecto, 9001, 'test-a4');
$assert(($malConsumoDirecto['ok'] ?? true) === false && ($malConsumoDirecto['code'] ?? '') === 'MODALIDAD_NO_CONTRATABLE',
    'Importante 1: un paquete consumo_directo tampoco.');
// Ninguno de los dos rechazos debió dejar fila en pdc_paquete_frente.
$sinAmarreNoContratable = $svc->amarres($P);
$assert(!isset($sinAmarreNoContratable[$paqNoContratable]) && !isset($sinAmarreNoContratable[$paqConsumoDirecto]),
    'Importante 1: el rechazo no deja amarre a medias.');

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

// --- Importante 2 (review Task 6): un desglose ausente o incompleto nunca vale como plazo real ---
// `general_dias_procesos_contratacion`/`general_paquetes_contratacion` son catálogos GLOBALES —
// comparten datos reales de producción (162 de 209 paquetes activos ya apuntan a una fila real,
// según A3.3) — así que este test no puede asumir que la mediana de «suministro» está vacía ni
// adivinar su valor exacto de antemano. En vez de eso, calcula la mediana esperada con la misma
// query CORREGIDA (exige las siete columnas no nulas) de forma independiente, y compara contra lo
// que produce el servicio: si `medianasPorTipo()` volviera a contar una fila incompleta como cero,
// ambos cálculos divergirían porque el de este test sí la excluye.
$sqlMedianaSuministro = "SELECT (d.diasElaboracionPliegos + d.diasEntregaPliegos + d.diasReciboPropuestas
                                 + d.diasCuadrosComparativos + d.diasLegalizacionContrato + d.diasFabricacion
                                 + d.diasInsumosObra) tot
                         FROM general_paquetes_contratacion p
                         JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
                         WHERE p.activo = 1 AND p.tipo_negociacion = 'suministro'
                           AND d.diasElaboracionPliegos IS NOT NULL AND d.diasEntregaPliegos IS NOT NULL
                           AND d.diasReciboPropuestas IS NOT NULL AND d.diasCuadrosComparativos IS NOT NULL
                           AND d.diasLegalizacionContrato IS NOT NULL AND d.diasFabricacion IS NOT NULL
                           AND d.diasInsumosObra IS NOT NULL
                         ORDER BY tot";
$medianaDe = static function () use ($db, $sqlMedianaSuministro): int {
    $totales = $db->query($sqlMedianaSuministro)->fetchAll(\PDO::FETCH_COLUMN);
    $n = count($totales);
    if ($n === 0) {
        return 0;
    }
    return (int) round($n % 2 === 1
        ? $totales[intdiv($n, 2)]
        : ($totales[intdiv($n, 2) - 1] + $totales[intdiv($n, 2)]) / 2);
};
// Conteo SIN el filtro de completitud: sirve para probar que el JOIN sí encuentra ambas filas
// nuevas (su duracion_ref existe) — la exclusión de PARCIAL es por columnas NULL, no porque el
// `JOIN` no la encuentre.
$contarTodasSuministro = static function () use ($db): int {
    return (int) $db->query(
        "SELECT COUNT(*) FROM general_paquetes_contratacion p
         JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
         WHERE p.activo = 1 AND p.tipo_negociacion = 'suministro'",
    )->fetchColumn();
};
$todasAntes = $contarTodasSuministro();

// A) fila de duración COMPLETA (70 días) — debe entrar en la muestra de la mediana.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST A4 SUMINISTRO COMPLETO', 'Suministro', 10, 10, 10, 10, 10, 10, 10)",
);
$durCompletaId = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 MEDIANA COMPLETA', 'TEST A4 MEDIANA COMPLETA', 'suministro', 'contrato', ?, 1, 'test-a4', NOW())",
    [$durCompletaId],
);

// B) fila de duración PARCIAL — un solo `dias*` en NULL basta para que la suma SQL dé NULL. Si el
// código todavía la contara como cero (hallazgo original), esta fila entraría a la muestra de la
// mediana como un 0 fantasma Y, además, el propio paquete que la usa (paqParcial más abajo)
// calcularía un plan de 0 días en vez de caer a la mediana.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST A4 SUMINISTRO PARCIAL', 'Suministro', 5, NULL, 5, 5, 5, 5, 5)",
);
$durParcialId = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 PARCIAL', 'TEST A4 PARCIAL', 'suministro', 'contrato', ?, 1, 'test-a4', NOW())",
    [$durParcialId],
);
$paqParcial = (int) $db->lastInsertId();
$svc->amarrar($P, $paqParcial, 9002, 'test-a4');

// C) duracion_ref COLGADO — apunta a un id que nunca existió (equivalente a una fila borrada: no
// hay FK que lo impida). El LEFT JOIN de calcular() debe devolver las siete columnas en NULL, no
// fallar ni dar 0 días.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 DURACION HUERFANA', 'TEST A4 DURACION HUERFANA', 'suministro', 'contrato', 999999999, 1, 'test-a4', NOW())",
);
$paqHuerfano = (int) $db->lastInsertId();
$svc->amarrar($P, $paqHuerfano, 9002, 'test-a4');

// D) sin duracion_ref propia — es quien realmente lee la mediana de «suministro».
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 SIN DURACION SUMINISTRO', 'TEST A4 SIN DURACION SUMINISTRO', 'suministro', 'contrato', 1, 'test-a4', NOW())",
);
$paqSinDuracionSuministro = (int) $db->lastInsertId();
$svc->amarrar($P, $paqSinDuracionSuministro, 9002, 'test-a4');

$todasDespues = $contarTodasSuministro();
$assert($todasDespues === $todasAntes + 2,
    'Importante 2: el JOIN sí encuentra las dos filas nuevas (COMPLETA y PARCIAL) por duracion_ref: '
    . $todasAntes . ' → ' . $todasDespues);

$medianaEsperada = $medianaDe();

$svc->calcular($P, 'test-a4');
$plan3 = $svc->plan($P);
$porId3 = [];
foreach ($plan3 as $f) { $porId3[$f['paqueteId']] = $f; }

$assert(($porId3[$paqParcial]['duracionProvisional'] ?? null) === true,
    'Importante 2: un desglose con un solo `dias*` en NULL se trata como "sin duración" (provisional).');
$assert(($porId3[$paqParcial]['diasTotales'] ?? -1) === $medianaEsperada,
    "Importante 2: paqParcial cae en la mediana de \"suministro\" ({$medianaEsperada}), no en 0 ni en una suma con NULL. Dio "
    . ($porId3[$paqParcial]['diasTotales'] ?? 'null'));

$assert(($porId3[$paqHuerfano]['duracionProvisional'] ?? null) === true,
    'Importante 2: un duracion_ref colgado (fila borrada) también se trata como "sin duración".');
$assert(($porId3[$paqHuerfano]['diasTotales'] ?? -1) === $medianaEsperada,
    "Importante 2: paqHuerfano también cae en la mediana ({$medianaEsperada}), no en 0. Dio "
    . ($porId3[$paqHuerfano]['diasTotales'] ?? 'null'));

$assert(($porId3[$paqSinDuracionSuministro]['diasTotales'] ?? -1) === $medianaEsperada,
    "Importante 2: la mediana de \"suministro\" calculada por el servicio coincide con la esperada"
    . " ({$medianaEsperada}) — la fila PARCIAL quedó excluida de la muestra, no contada como 0. Dio "
    . ($porId3[$paqSinDuracionSuministro]['diasTotales'] ?? 'null'));

// --- Importante 3 (review Task 6): diasRetraso, orden «vencidos primero», responsable persiste ---
// Dos frentes con fechas deliberadamente lejanas (año 2000 y año 2099): uno queda vencido y el
// otro no sin importar qué día real corra este test, a diferencia de las fechas de 2026 del resto
// del fixture (cuya relación con "hoy" sí depende del reloj de la máquina que ejecute la prueba).
$db->query(
    "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
     VALUES (?, 11, 9005, '<b>TEST A4 VENCIDO, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2000-01-01'),
            (?, 12, 9006, '<b>TEST A4 LEJOS, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2099-01-01')",
    [$P, $P],
);
$db->query(
    'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
         Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
         Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
     VALUES (?, 301, 2, 9005, 11, ?, 1, ?, 0, "", "", "", "", "", "", "", 1, 0, 0),
            (?, 302, 2, 9006, 12, ?, 1, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
    [
        $P, '<b>TEST A4 VENCIDO, </b> <small>[Capítulo: TORRE 1]</small>', '2000-01-01',
        $P, '<b>TEST A4 LEJOS, </b> <small>[Capítulo: TORRE 1]</small>', '2099-01-01',
    ],
);

// Duración corta y determinista (7 días) para no acercar el arranque calculado a "hoy" por accidente.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST A4 DURACION CORTA', 'Suministro', 1, 1, 1, 1, 1, 1, 1)",
);
$durCortaId = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 VENCIDO', 'TEST A4 VENCIDO', 'mano_obra', 'contrato', ?, 1, 'test-a4', NOW())",
    [$durCortaId],
);
$paqVencido = (int) $db->lastInsertId();
$svc->amarrar($P, $paqVencido, 9005, 'test-a4');

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 LEJOS', 'TEST A4 LEJOS', 'mano_obra', 'contrato', ?, 1, 'test-a4', NOW())",
    [$durCortaId],
);
$paqLejos = (int) $db->lastInsertId();
$svc->amarrar($P, $paqLejos, 9006, 'test-a4');

// --- Responsable como usuario del proyecto ---
// Tres usuarios sintéticos en vez de tomar «el primero que haya» en general_usuarios: hacen falta
// los tres casos de elegibilidad (miembro activo / ajeno al proyecto / miembro dado de baja) y
// tomarlos de datos reales dejaría el test a merced de qué haya sembrado en la base.
$crearUsuario = static function (string $sufijo, string $nombre, string $cargo, int $activo) use ($db): int {
    $db->query(
        'INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, activo)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$nombre, "zztest-a4-{$sufijo}@example.test", $cargo, "zztest-a4-{$sufijo}", 'x', $activo],
    );
    return (int) $db->lastInsertId();
};
$uid = $crearUsuario('miembro', 'ZZ Test Residente', 'Residente de Obra', 1);
$uidExterno = $crearUsuario('externo', 'ZZ Test Externo', 'Ajeno al proyecto', 1);
$uidBaja = $crearUsuario('baja', 'ZZ Test De Baja', 'Dado de baja', 0);

$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uidBaja, 'U']);

$elegibles = $svc->responsablesElegibles($P);
$idsElegibles = array_column($elegibles, 'id');

$assert(in_array($uid, $idsElegibles, true),
    'Elegibles: el miembro activo del proyecto aparece en la lista. Dio ' . json_encode($idsElegibles));
$assert(!in_array($uidExterno, $idsElegibles, true),
    'Elegibles: quien NO es miembro del proyecto queda fuera de la lista.');
$assert(!in_array($uidBaja, $idsElegibles, true),
    'Elegibles: un miembro con activo = 0 queda fuera de la lista.');

$elegido = null;
foreach ($elegibles as $e) { if ($e['id'] === $uid) { $elegido = $e; } }
$assert($elegido !== null && $elegido['nombre'] === 'ZZ Test Residente' && $elegido['cargo'] === 'Residente de Obra',
    'Elegibles: cada fila trae id, nombre y cargo. Dio ' . json_encode($elegido));

$nombresElegibles = array_column($elegibles, 'nombre');
$ordenados = $nombresElegibles;
sort($ordenados, SORT_STRING);
$assert($nombresElegibles === $ordenados,
    'Elegibles: la lista sale ordenada por nombre. Dio ' . json_encode($nombresElegibles));

$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

// --- Asignar responsable (lo que el endpoint roto hacía contra la columna eliminada) ---
$leerResponsable = static function () use ($db, $P, $paqEstructura): array {
    $r = $db->query(
        'SELECT responsable_user_id, responsable_asignado_por, responsable_asignado_at
         FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
        [$P, $paqEstructura],
    )->fetch(\PDO::FETCH_ASSOC);
    return $r === false ? [] : $r;
};

$r = $svc->asignarResponsable($P, [$paqEstructura], $uid, 'jefa-compras');
$assert(($r['ok'] ?? false) === true, 'Asignar: asignar a un miembro activo funciona. Dio ' . json_encode($r));

$guardado = $leerResponsable();
$assert((int) ($guardado['responsable_user_id'] ?? 0) === $uid,
    'Asignar: se guarda el id del usuario. Dio ' . var_export($guardado['responsable_user_id'] ?? null, true));
$assert(($guardado['responsable_asignado_por'] ?? '') === 'jefa-compras',
    'Asignar: se guarda QUIÉN asignó. Dio ' . var_export($guardado['responsable_asignado_por'] ?? null, true));
$assert(($guardado['responsable_asignado_at'] ?? null) !== null,
    'Asignar: se guarda CUÁNDO se asignó. Dio ' . var_export($guardado['responsable_asignado_at'] ?? null, true));

// Alguien de otro proyecto: la FK lo aceptaría (el usuario existe), así que esta es la única
// defensa real contra asignar un paquete a quien no trabaja en esta obra.
$r = $svc->asignarResponsable($P, [$paqEstructura], $uidExterno, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'RESPONSABLE_NO_ELEGIBLE',
    'Asignar: un usuario ajeno al proyecto se rechaza con RESPONSABLE_NO_ELEGIBLE. Dio ' . json_encode($r));
$assert((int) ($leerResponsable()['responsable_user_id'] ?? 0) === $uid,
    'Asignar: un rechazo NO pisa el responsable que ya estaba guardado.');

$r = $svc->asignarResponsable($P, [$paqEstructura], $uidBaja, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'RESPONSABLE_NO_ELEGIBLE',
    'Asignar: un miembro dado de baja también se rechaza. Dio ' . json_encode($r));

// Vaciar: se conserva el rastro de quién lo quitó — la columna es «quién tocó la asignación»,
// no «quién puso a alguien», y perder eso deja un paquete sin dueño sin saber quién lo dejó así.
$r = $svc->asignarResponsable($P, [$paqEstructura], null, 'auditor');
$assert(($r['ok'] ?? false) === true, 'Asignar: vaciar el responsable funciona. Dio ' . json_encode($r));
$vaciado = $leerResponsable();
$assert($vaciado['responsable_user_id'] === null,
    'Asignar: vaciar deja responsable_user_id en NULL. Dio ' . var_export($vaciado['responsable_user_id'], true));
$assert(($vaciado['responsable_asignado_por'] ?? '') === 'auditor',
    'Asignar: vaciar deja constancia de quién lo quitó. Dio ' . var_export($vaciado['responsable_asignado_por'] ?? null, true));

// Paquete sin plan calculado: el id 987654321 no existe en pdc_plan_paquete de este proyecto.
$r = $svc->asignarResponsable($P, [987654321], $uid, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'PAQUETE_SIN_PLAN',
    'Asignar: un paquete sin plan calculado da PAQUETE_SIN_PLAN. Dio ' . json_encode($r));

// Se deja asignado para las comprobaciones de plan() que vienen después.
$svc->asignarResponsable($P, [$paqEstructura], $uid, 'jefa-compras');

$svc->calcular($P, 'test-a4');
$plan4 = $svc->plan($P);
$porId4 = [];
foreach ($plan4 as $f) { $porId4[$f['paqueteId']] = $f; }

$assert(($porId4[$paqVencido]['diasRetraso'] ?? -1) === (int) (new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable('1999-12-25'))->days,
    'Importante 3: diasRetraso de un paquete vencido es exacto (hoy - fecha_arranque). Dio '
    . ($porId4[$paqVencido]['diasRetraso'] ?? 'null') . ', fechaArranque=' . ($porId4[$paqVencido]['fechaArranque'] ?? '?'));
$assert(($porId4[$paqVencido]['diasRetraso'] ?? 0) > 0, 'Importante 3: diasRetraso de un paquete vencido es positivo.');

$assert(($porId4[$paqLejos]['diasRetraso'] ?? -1) === 0,
    'Importante 3: diasRetraso de un paquete no vencido da 0 (nunca negativo). Dio ' . ($porId4[$paqLejos]['diasRetraso'] ?? 'null'));

$posVencido = null;
foreach ($plan4 as $i => $f) { if ($f['paqueteId'] === $paqVencido) { $posVencido = $i; } }
$assert($posVencido === 0, 'Importante 3: el paquete vencido queda de primero en el plan. Posición: ' . $posVencido);
foreach ($plan4 as $f) {
    if ($f['paqueteId'] !== $paqVencido) {
        $assert($f['diasRetraso'] <= $porId4[$paqVencido]['diasRetraso'],
            "Importante 3: ningún paquete no vencido queda por delante del vencido (paquete {$f['paqueteId']}).");
    }
}

$assert(($porId4[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Responsable: `responsable_user_id` sobrevive a un recálculo (el ON DUPLICATE KEY UPDATE no lo lista). Dio '
    . var_export($porId4[$paqEstructura]['responsableUserId'] ?? null, true) . ' esperando ' . $uid);

// --- Responsable: lectura con tres estados ---
$planR = $svc->plan($P);
$porIdR = [];
foreach ($planR as $f) { $porIdR[$f['paqueteId']] = $f; }

// Tres estados de lectura. El tercero (huérfano) es el que impide que sacar a alguien del
// proyecto haga desaparecer su nombre de la pantalla sin decir nada.
$filaR = $porIdR[$paqEstructura];
$assert(($filaR['responsableNombre'] ?? '') !== '',
    'Responsable: un responsable vigente trae su nombre resuelto. Dio ' . var_export($filaR['responsableNombre'] ?? null, true));
$assert(($filaR['responsableHuerfano'] ?? null) === false,
    'Responsable: un miembro vigente NO es huérfano.');
$assert(($porIdR[$paqEstructura]['responsableCargo'] ?? null) === 'Residente de Obra',
    'Plan: la fila trae el cargo del responsable (desempata nombres parecidos en el selector). Dio '
    . var_export($porIdR[$paqEstructura]['responsableCargo'] ?? null, true));

// Sacarlo del proyecto (sin borrar su ficha) debe marcarlo huérfano, no borrarlo.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $uid]);
$planH = $svc->plan($P);
$porIdH = [];
foreach ($planH as $f) { $porIdH[$f['paqueteId']] = $f; }
$assert(($porIdH[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Responsable huérfano: el enlace NO se borra al salir del proyecto.');
$assert(($porIdH[$paqEstructura]['responsableNombre'] ?? '') !== '',
    'Responsable huérfano: el nombre se sigue viendo.');
$assert(($porIdH[$paqEstructura]['responsableHuerfano'] ?? null) === true,
    'Responsable huérfano: queda marcado como tal.');

// Restaurar la membresía para no dejar el estado sucio a los bloques siguientes.
$db->query('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);

// Huérfano por cuenta desactivada: seguir siendo miembro no basta si `general_usuarios.activo = 0`
// — es la otra mitad de la condición que usa `plan()` (`(int) $r['responsable_activo'] !== 1`), la
// que `responsable_miembro === null` por sí sola no puede detectar. Sin este caso, dar de baja una
// cuenta sin sacarla del proyecto dejaría un paquete con responsable inactivo sin que nada lo señale.
$db->query('UPDATE general_usuarios SET activo = 0 WHERE id = ?', [$uid]);
$planInactivo = $svc->plan($P);
$porIdInactivo = [];
foreach ($planInactivo as $f) { $porIdInactivo[$f['paqueteId']] = $f; }
$assert(($porIdInactivo[$paqEstructura]['responsableHuerfano'] ?? null) === true,
    'Responsable huérfano: un miembro con activo = 0 también queda marcado como huérfano.');
$assert(($porIdInactivo[$paqEstructura]['responsableNombre'] ?? '') !== '',
    'Responsable huérfano: su nombre se sigue viendo aunque la cuenta esté desactivada.');
// Se restaura para no dejar el fixture a medias: el resto del archivo asume cuentas activas.
$db->query('UPDATE general_usuarios SET activo = 1 WHERE id = ?', [$uid]);

// Sin asignar es un estado válido, no un error.
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = NULL WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura]);
$planN = $svc->plan($P);
$porIdN = [];
foreach ($planN as $f) { $porIdN[$f['paqueteId']] = $f; }
// Comparación directa, no `?? centinela`: con valor esperado null, cualquier centinela de
// reserva («x») queda indistinguible de un null real (`null ?? 'x'` siempre da 'x'), así que
// `(...) ?? 'x') === null` no podría pasar nunca sin importar la implementación. La clave
// `responsableUserId` siempre está presente en la fila (el mapeo la fija siempre, nunca
// condicional), así que el acceso directo es seguro.
$assert($porIdN[$paqEstructura]['responsableUserId'] === null,
    'Responsable: «sin asignar» es NULL, no cadena vacía.');
$assert(($porIdN[$paqEstructura]['responsableHuerfano'] ?? null) === false,
    'Responsable: sin asignar NO es huérfano (no hay nadie a quien marcar).');

// --- B1: recalcular no debe destruir las filas de pdc_plan_paso ---
// B1 (Seguimiento) va a colgar `fecha_real` de estas filas. Mientras `calcular()` hiciera
// DELETE + INSERT, cada recálculo las borraba y creaba otras nuevas: el avance real se perdía sin
// aviso. Esa columna todavía no existe, así que aquí se prueba el invariante observable
// equivalente — si el `id` de la fila sobrevive, la fila es la misma y con ella cualquier columna
// que B1 le añada.
$idsPaso = static function () use ($db, $P, $paqEstructura): array {
    return $db->query(
        'SELECT orden, id FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? ORDER BY orden',
        [$P, $paqEstructura],
    )->fetchAll(\PDO::FETCH_KEY_PAIR);
};

$idsAntes = $idsPaso();
$assert(count($idsAntes) === count(PlanFechasService::PASOS),
    'B1: el paquete de referencia tiene una fila por paso antes de recalcular. Hay ' . count($idsAntes));

$svc->calcular($P, 'test-a4');
$idsDespues = $idsPaso();

$assert($idsAntes === $idsDespues,
    'B1: recalcular conserva las MISMAS filas de pdc_plan_paso (mismos ids por orden), no las borra y recrea.');

// El upsert tiene que seguir actualizando las fechas programadas: conservar la fila no puede
// significar dejarla congelada. Se mueve el frente, se recalcula y se comprueba que las fechas
// cambiaron.
$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-10-15" WHERE project_id = ? AND unique_id = 9001 AND Semana = 2', [$P]);
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');   // reamarre al mismo frente movido: invalida el plan viejo
$svc->calcular($P, 'test-a4');
$finDespues = $db->query(
    'SELECT fecha_fin FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden = ?',
    [$P, $paqEstructura, count(PlanFechasService::PASOS) - 1],
)->fetchColumn();
$assert($finDespues === '2026-10-15',
    'B1: el upsert sigue reescribiendo las fechas programadas — el último paso termina en la fecha nueva del frente. Dio ' . var_export($finDespues, true));

// Los pasos sobrantes se retiran si el proceso se acortara. Desde A4.1 el borrado es por IDENTIDAD
// del paso (`paso_id`), no por posición: es lo único que funciona cuando la obra reordena su proceso
// o lo hace más largo de siete. Una fila sin `paso_id` —residuo de un cálculo hecho con el esquema
// anterior— también sobra, y por eso el DELETE lleva `paso_id IS NULL OR ...`: sin esa mitad,
// `NULL NOT IN (...)` vale NULL y la fila sobreviviría a todos los recálculos, invisible.
$db->query(
    'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin)
     VALUES (?, ?, ?, NULL, ?, ?, ?, ?)',
    [$P, $paqEstructura, 99, 'PASO FANTASMA', 5, '2026-01-01', '2026-01-06'],
);
$svc->calcular($P, 'test-a4');
$sobrantes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso = ?',
    [$P, $paqEstructura, 'PASO FANTASMA'],
)->fetchColumn();
$assert($sobrantes === 0,
    'B1: una fila sin identidad de paso se borra en el siguiente recálculo. Quedaron ' . $sobrantes);

// Este bloque movió el frente 9001 y reamarró paqEstructura: se deja el estado como estaba para que
// los bloques de abajo (desfases, aislamiento entre proyectos) sigan midiendo contra el ancla
// 2026-08-18 que esperan. Sin esta restauración, el assert «fechaGuardada === 2026-08-18» de más
// abajo empezaría a fallar por un efecto colateral de este bloque, no por el comportamiento que mide.
$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-08-18" WHERE project_id = ? AND unique_id = 9001 AND Semana = 2', [$P]);
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');

// --- desfases: el cronograma se movió y el plan quedó viejo ---
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');   // ancla 2026-08-18
$assert($svc->desfases($P) === [], 'Recién amarrado no hay desfase.');

$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-09-08" WHERE project_id = ? AND unique_id = 9001 AND Semana = 2', [$P]);
$d = $svc->desfases($P);
$assert(count($d) === 1, 'Mover el frente genera un desfase. Hay ' . count($d));
$assert($d[0]['fechaGuardada'] === '2026-08-18' && $d[0]['fechaActual'] === '2026-09-08',
    'El desfase dice de qué fecha a cuál se movió.');
$assert($d[0]['diasMovidos'] === 21, 'Cuenta los días que se corrió: ' . $d[0]['diasMovidos']);

// --- desfases: convención de signo cuando el frente se ADELANTA (no solo se atrasa) ---
// paqRaro sigue amarrado a PRELIMINARES (uid 9002, ancla 2026-05-25) desde el bloque «hueco 2»
// de arriba. Se adelanta ese frente 24 días para cubrir el signo contrario al caso de atraso: la
// decisión es que `diasMovidos` es positivo en atraso y negativo en adelanto (documentado en
// PlanFechasService::desfases()).
$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-05-01" WHERE project_id = ? AND unique_id = 9002 AND Semana = 2', [$P]);
$d2 = $svc->desfases($P);
$dRaro = null;
foreach ($d2 as $x) {
    if ($x['paqueteId'] === $paqRaro) { $dRaro = $x; }
}
$assert($dRaro !== null, 'El desfase de paqRaro aparece cuando su frente se adelanta.');
$assert($dRaro !== null && $dRaro['fechaActual'] === '2026-05-01', 'La fecha actual refleja el adelanto: ' . ($dRaro['fechaActual'] ?? '?'));
$assert($dRaro !== null && $dRaro['diasMovidos'] === -24, 'Adelantar el frente da diasMovidos negativo: ' . ($dRaro['diasMovidos'] ?? 'null'));

// --- desfases: el frente amarrado desapareció del cronograma (no es lo mismo que «se movió») ---
// Caso real y distinto: la reprogramación no dejó el frente en otra fecha, lo sacó por completo de
// la semana activa (se borró, o dejó de ser encabezado). No hay ninguna fecha nueva que comparar,
// así que se reporta con fechaActual/diasMovidos en null — no se calla el amarre huérfano.
$db->query(
    "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
     VALUES (?, 13, 9010, '<b>TEST A4 FRENTE EFIMERO, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-10-01')",
    [$P],
);
$db->query(
    "INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
         Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
         Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
     VALUES (?, 401, 2, 9010, 13, '<b>TEST A4 FRENTE EFIMERO, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-10-01', 0, \"\", \"\", \"\", \"\", \"\", \"\", \"\", 1, 0, 0)",
    [$P],
);
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 FRENTE EFIMERO', 'TEST A4 FRENTE EFIMERO', 'suministro', 'contrato', 1, 'test-a4', NOW())",
);
$paqFrenteEfimero = (int) $db->lastInsertId();
$svc->amarrar($P, $paqFrenteEfimero, 9010, 'test-a4');

$db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND unique_id = 9010', [$P]);

$d3 = $svc->desfases($P);
$dEfimero = null;
foreach ($d3 as $x) {
    if ($x['paqueteId'] === $paqFrenteEfimero) { $dEfimero = $x; }
}
$assert($dEfimero !== null, 'Un frente amarrado que desapareció del cronograma también se reporta.');
$assert($dEfimero !== null && $dEfimero['fechaActual'] === null, 'Sin frente actual, fechaActual queda en null (no se inventa una fecha).');
$assert($dEfimero !== null && $dEfimero['diasMovidos'] === null, 'Sin frente actual, diasMovidos queda en null: no es un número de días.');
$assert($dEfimero !== null && $dEfimero['fechaGuardada'] === '2026-10-01', 'La fecha guardada se conserva aunque el frente ya no exista.');

// --- hueco 4 (bis): desfases() tampoco cruza proyectos ---
// `desfases()` filtra por `project_id` en su SELECT y compara contra los frentes de la semana activa
// de ESE proyecto, pero hasta aquí ningún assert lo demostraba: la cobertura era incidental (todos
// los casos de arriba viven en un solo proyecto, así que quitar el `WHERE f.project_id = ?` los
// seguía dejando en verde). Se reusa el segundo proyecto del hueco 4 —$P2, con su propio cronograma
// y $paqEstructura amarrado a CIMENTACIÓN (uid 9101, ancla 2026-08-05)— y se mueve SU frente, para
// que los dos proyectos tengan un desfase vivo al mismo tiempo sobre el MISMO paqueteId: así el
// aislamiento no puede confundirse con «cada proyecto tiene paquetes distintos».
$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-09-14" WHERE project_id = ? AND unique_id = 9101 AND Semana = 1', [$P2]);

$dP2 = $svc->desfases($P2);
$assert(count($dP2) === 1, 'Hueco 4 bis: desfases($P2) solo trae su propio amarre, no los de $P. Trajo ' . count($dP2));
$assert(count($dP2) === 1 && $dP2[0]['paqueteId'] === $paqEstructura && $dP2[0]['frenteNombre'] === 'CIMENTACIÓN',
    'Hueco 4 bis: el desfase de $P2 es el de su frente CIMENTACIÓN.');
$assert(count($dP2) === 1 && $dP2[0]['fechaGuardada'] === '2026-08-05' && $dP2[0]['fechaActual'] === '2026-09-14'
    && $dP2[0]['diasMovidos'] === 40,
    'Hueco 4 bis: el desfase de $P2 se mide contra el cronograma de $P2 (2026-08-05 → 2026-09-14, 40 días).');

$dP1 = $svc->desfases($P);
$frentesEnP1 = array_column($dP1, 'frenteNombre');
$assert(!in_array('CIMENTACIÓN', $frentesEnP1, true),
    'Hueco 4 bis: el frente de $P2 no se cuela en la lista de desfases de $P.');
$dEstructuraEnP1 = null;
foreach ($dP1 as $x) {
    if ($x['paqueteId'] === $paqEstructura) { $dEstructuraEnP1 = $x; }
}
$assert($dEstructuraEnP1 !== null && $dEstructuraEnP1['frenteNombre'] === 'ESTRUCTURA'
    && $dEstructuraEnP1['fechaGuardada'] === '2026-08-18',
    'Hueco 4 bis: el desfase del mismo paqueteId en $P sigue siendo el de SU frente (ESTRUCTURA, ancla 2026-08-18), no el de $P2.');

// --- Importante 1 (review final A4): reamarrar a OTRO frente invalida el plan viejo ---
// Antes de este fix, `plan()` unía la cabecera de `pdc_plan_paquete` con el `frente_nombre` VIGENTE
// de `pdc_paquete_frente` solo por `paquete_id` — sin comparar `unique_id`: la fila mostraba el
// nombre del frente nuevo junto a un arranque calculado contra el frente viejo, sin que nada lo
// avisara (ni `desfases()`, porque el amarre ya estaba al día).
$frentesVigentes = $svc->frentesDisponibles($P);
$frenteA = $frentesVigentes[0]; // el que tenga la fecha más temprana, cualquiera sirve
$frenteB = null;
foreach ($frentesVigentes as $f) {
    if ($f['uniqueId'] !== $frenteA['uniqueId']) { $frenteB = $f; break; }
}
$assert($frenteB !== null, 'Importante 1: el fixture tiene al menos dos frentes distintos para reamarrar.');

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('TEST A4 REAMARRE INVALIDA', 'TEST A4 REAMARRE INVALIDA', 'mano_obra', 'contrato', ?, 1, 'test-a4', NOW())",
    [$durCortaId],
);
$paqReamarre = (int) $db->lastInsertId();

$svc->amarrar($P, $paqReamarre, $frenteA['uniqueId'], 'test-a4');
$svc->calcular($P, 'test-a4');

$cabeceraAntes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$pasosAntes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($cabeceraAntes === 1 && $pasosAntes === 7, 'Importante 1: el plan queda calculado contra el frente A antes de reamarrar.');

// Reamarre a un frente DISTINTO: el plan viejo (calculado contra A) debe desaparecer entero.
$svc->amarrar($P, $paqReamarre, $frenteB['uniqueId'], 'test-a4');

$cabeceraDespues = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$pasosDespues = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($cabeceraDespues === 0, 'Importante 1: reamarrar a otro frente borra la cabecera vieja del plan.');
$assert($pasosDespues === 0, 'Importante 1: reamarrar a otro frente borra los pasos viejos del plan.');

$planTrasReamarre = $svc->plan($P);
$sigueEnPlan = false;
foreach ($planTrasReamarre as $f) { if ($f['paqueteId'] === $paqReamarre) { $sigueEnPlan = true; } }
$assert(!$sigueEnPlan, 'Importante 1: plan() ya no muestra el paquete con una fecha calculada contra el frente viejo.');

$amarreTrasReamarre = $svc->amarres($P);
$assert($amarreTrasReamarre[$paqReamarre]['uniqueId'] === $frenteB['uniqueId'],
    'Importante 1: el amarre sí quedó actualizado al frente B (solo se invalida el plan, no el amarre).');

// Reamarrar al MISMO frente (no-op de contenido) no debe invalidar nada: no hay nada viejo que purgar.
$svc->amarrar($P, $paqReamarre, $frenteB['uniqueId'], 'test-a4');
$svc->calcular($P, 'test-a4');
$svc->amarrar($P, $paqReamarre, $frenteB['uniqueId'], 'test-a4');
$cabeceraMismoFrente = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($cabeceraMismoFrente === 1, 'Importante 1: reamarrar al mismo frente no invalida un plan que sigue siendo válido.');

// --- Importante 2 (review final de A4): reamarrar al MISMO frente DESPUÉS de que el cronograma lo
// movió sí debe invalidar el plan viejo. El caso de arriba reamarra al mismo frente con el
// cronograma quieto (unique_id igual, fecha_ancla igual) y por eso no detecta este hallazgo: aquí
// el frente sí se mueve entre el primer y el segundo amarre, así que unique_id sigue igual pero la
// fecha_ancla ya no coincide con la que el plan calculado tiene guardada.
$db->query(
    'UPDATE programa_consolidado SET Fecha_Inicio = "2027-01-15" WHERE project_id = ? AND unique_id = ? AND Semana = 2',
    [$P, $frenteB['uniqueId']],
);

$cabeceraAntesDeMover = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($cabeceraAntesDeMover === 1, 'Importante 2: el plan sigue calculado antes de reamarrar al mismo frente ya movido.');

$svc->amarrar($P, $paqReamarre, $frenteB['uniqueId'], 'test-a4');

$cabeceraTrasMoverYReamarrar = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($cabeceraTrasMoverYReamarrar === 0,
    'Importante 2: reamarrar al MISMO frente después de que el cronograma lo movió SÍ invalida el plan viejo (unique_id igual, fecha_ancla distinta).');

$pasosTrasMoverYReamarrar = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqReamarre],
)->fetchColumn();
$assert($pasosTrasMoverYReamarrar === 0, 'Importante 2: los pasos viejos del plan también se purgan.');

$amarreTrasMover = $svc->amarres($P);
$assert(($amarreTrasMover[$paqReamarre]['fechaAncla'] ?? null) === '2027-01-15',
    'Importante 2: el amarre sí guarda la fecha ancla nueva del frente movido.');

// --- Pesos del reparto provisional: derivados del catálogo, no inventados ---
// Cuando un paquete no tiene desglose propio, `calcular()` reparte la mediana de su tipo entre los
// siete pasos. Ese reparto se hacía con siete números escritos a mano cuyo comentario decía «el peso
// típico del catálogo» sin que nadie los hubiera medido: Fabricación iba a 0,16 cuando el catálogo
// real dice 0,249 (−36 %). Estos asserts fijan las tres propiedades que el fix debe cumplir.

// 1) La derivación en vivo: la media de las proporciones fila a fila del catálogo. Se recalcula aquí
// con SQL independiente —igual que la mediana de «Importante 2»— para no comparar el servicio contra
// una copia de sí mismo.
$pesosEsperados = $db->query(
    'SELECT AVG(diasElaboracionPliegos / t) p1, AVG(diasEntregaPliegos / t) p2, AVG(diasReciboPropuestas / t) p3,
            AVG(diasCuadrosComparativos / t) p4, AVG(diasLegalizacionContrato / t) p5, AVG(diasFabricacion / t) p6,
            AVG(diasInsumosObra / t) p7
     FROM (SELECT *, (diasElaboracionPliegos + diasEntregaPliegos + diasReciboPropuestas + diasCuadrosComparativos
                      + diasLegalizacionContrato + diasFabricacion + diasInsumosObra) t
           FROM general_dias_procesos_contratacion
           WHERE diasElaboracionPliegos IS NOT NULL AND diasEntregaPliegos IS NOT NULL
             AND diasReciboPropuestas IS NOT NULL AND diasCuadrosComparativos IS NOT NULL
             AND diasLegalizacionContrato IS NOT NULL AND diasFabricacion IS NOT NULL
             AND diasInsumosObra IS NOT NULL) x
     WHERE t > 0',
)->fetch(\PDO::FETCH_NUM);
$pesosEsperados = array_map('floatval', $pesosEsperados);

$pesosVivos = $svc->pesosDelCatalogo();
$assert(count($pesosVivos) === 7, 'pesosDelCatalogo() devuelve un peso por paso. Dio ' . count($pesosVivos));
$assert(abs(array_sum($pesosVivos) - 1.0) < 1e-9, 'Los pesos derivados suman 1. Suman ' . array_sum($pesosVivos));
foreach ($pesosVivos as $i => $w) {
    $assert(abs($w - $pesosEsperados[$i]) < 1e-6, sprintf(
        'El peso derivado del paso %d («%s») coincide con la media de proporciones del catálogo: %.6f vs %.6f.',
        $i, PlanFechasService::PASOS[$i]['paso'], $w, $pesosEsperados[$i],
    ));
}

// 2) Centinela de deriva: la constante congelada sigue representando al catálogo. El catálogo es
// legacy y se edita fuera de este módulo, así que la constante puede quedarse vieja en silencio;
// esto la vigila. Si falla, hay que volver a generarla con `scripts/pdc/derivar-pesos-reparto.php`,
// no subir la tolerancia. Margen: 0,01 absoluto — las tres filas sintéticas «TEST A4» que este
// archivo inserta en el catálogo mueven los pesos menos de 0,002.
foreach (PlanFechasService::PESOS_REPARTO as $i => $w) {
    $assert(abs($w - $pesosVivos[$i]) <= 0.01, sprintf(
        'Centinela: la constante PESOS_REPARTO[%d] («%s») sigue al día con el catálogo (%.6f vs %.6f en vivo).'
        . ' Si esto falla, regenera la constante con scripts/pdc/derivar-pesos-reparto.php.',
        $i, PlanFechasService::PASOS[$i]['paso'], $w, $pesosVivos[$i],
    ));
}

// 3) El reparto en sí: suma exacta (la fecha de arranque y el plazo total no pueden moverse) y
// ningún paso a más de un día de su parte proporcional — el residuo de redondeo se reparte por
// resto mayor en vez de caer entero sobre el último paso, que era lo que dejaba «Insumos en obra»
// absorbiendo hasta tres días que no le tocaban.
$sumaPesos = array_sum(PlanFechasService::PESOS_REPARTO);
foreach ([0, 1, 3, 7, 30, 87, 90, 120, 365] as $total) {
    $dias = PlanFechasService::repartirMediana($total);
    $assert(count($dias) === 7, "Reparto de {$total} días: siete pasos. Dio " . count($dias));
    $assert(array_sum($dias) === $total,
        "Reparto de {$total} días: la suma es exactamente el total (el arranque no se mueve). Dio " . array_sum($dias));
    foreach ($dias as $i => $d) {
        $assert($d >= 0, "Reparto de {$total} días: el paso {$i} no es negativo. Dio {$d}");
        $exacto = $total * PlanFechasService::PESOS_REPARTO[$i] / $sumaPesos;
        $assert(abs($d - $exacto) < 1.0, sprintf(
            'Reparto de %d días: el paso %d («%s») queda a menos de un día de su parte proporcional (%d vs %.2f).',
            $total, $i, PlanFechasService::PASOS[$i]['paso'], $d, $exacto,
        ));
    }
}

// 4) El hallazgo concreto, con números: con 90 días de mediana, Fabricación es el paso más largo
// (22 días) y no los 14 que producían los pesos inventados; Cuadros comparativos deja de ser el
// más largo (17, antes 22). Es el caso que engañaba a quien usa el cronograma para hablar con un
// proveedor.
$r90 = PlanFechasService::repartirMediana(90);
$assert($r90[5] === 22, 'Con 90 días, Fabricación recibe 22 (antes 14). Dio ' . $r90[5]);
$assert($r90[3] === 17, 'Con 90 días, Cuadros comparativos recibe 17 (antes 22). Dio ' . $r90[3]);
$assert($r90[5] === max($r90), 'Con 90 días, Fabricación es el paso más largo del proceso.');
$assert(array_sum($r90) === 90, 'Con 90 días, el reparto sigue sumando 90.');

// 5) Y el mismo reparto es el que llega a la base: los pasos guardados de un paquete sin desglose
// propio son exactamente `repartirMediana(mediana de su tipo)`.
$svc->calcular($P, 'test-a4');
$planPesos = $svc->plan($P);
$filaSinDuracion = null;
foreach ($planPesos as $f) {
    if ($f['paqueteId'] === $paqSinDuracionSuministro) { $filaSinDuracion = $f; }
}
$medianaAhora = $medianaDe();
$assert($filaSinDuracion !== null, 'El paquete sin duración propia sigue en el plan para comprobar sus pasos.');
$assert($filaSinDuracion !== null && array_column($filaSinDuracion['pasos'], 'dias') === PlanFechasService::repartirMediana($medianaAhora),
    'Los pasos guardados de un paquete provisional son el reparto derivado de la mediana de su tipo. Dio ['
    . implode(', ', array_column($filaSinDuracion['pasos'] ?? [], 'dias')) . '] contra ['
    . implode(', ', PlanFechasService::repartirMediana($medianaAhora)) . ']');
$assert($filaSinDuracion !== null && $filaSinDuracion['diasTotales'] === $medianaAhora,
    'El plazo total de un paquete provisional sigue siendo la mediana exacta de su tipo: ' . $medianaAhora
    . ' vs ' . ($filaSinDuracion['diasTotales'] ?? 'null'));

// --- Asignación en masa ---
// El grilleo la pidió explícitamente: con más de 100 paquetes por proyecto, repartir de uno en uno
// es la diferencia entre cinco minutos y una hora.
//
// `$otro` es un usuario propio de este bloque (no se reutiliza `$uidExterno`/`$uidBaja`, que ya
// tienen su propio historial de estados más arriba en el archivo): se agrega como miembro activo
// del proyecto para que el DELETE de más abajo represente algo real —alguien elegible que deja de
// serlo a mitad de la prueba—, no un no-op contra una fila que nunca existió.
$otro = $crearUsuario('masa', 'ZZ Test Masa', 'Miembro para prueba de masa', 1);
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $otro, 'U']);

$idsMasa = array_slice(array_map(static fn (array $f): int => $f['paqueteId'], $svc->plan($P)), 0, 2);
$assert(count($idsMasa) === 2, 'Masa: hay al menos 2 paquetes con plan para la prueba.');

$rMasa = $svc->asignarResponsable($P, $idsMasa, $uid, 'test-a4');
$assert(($rMasa['ok'] ?? false) === true, 'Masa: la asignación múltiple responde ok. Dio ' . var_export($rMasa, true));
$assert(($rMasa['asignados'] ?? 0) === 2, 'Masa: informa 2 asignados. Dio ' . var_export($rMasa['asignados'] ?? null, true));

$marcasM = implode(',', array_fill(0, count($idsMasa), '?'));
$conResp = (int) $db->query(
    "SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND responsable_user_id = ? AND paquete_id IN ($marcasM)",
    array_merge([$P, $uid], $idsMasa),
)->fetchColumn();
$assert($conResp === 2, 'Masa: los 2 paquetes quedaron con el responsable en la base. Dio ' . $conResp);

// Un no elegible dentro de una asignación múltiple la rechaza ENTERA: dejar la mitad hecha sería
// peor que no hacer nada, porque nadie sabría qué mitad.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $otro]);
$rNo = $svc->asignarResponsable($P, $idsMasa, $otro, 'test-a4');
$assert(($rNo['ok'] ?? true) === false, 'Masa: se rechaza si el responsable no es elegible.');
$sigueM = (int) $db->query(
    "SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND responsable_user_id = ? AND paquete_id IN ($marcasM)",
    array_merge([$P, $uid], $idsMasa),
)->fetchColumn();
$assert($sigueM === 2, 'Masa: un rechazo no deja la asignación a medias. Dio ' . $sigueM);

// Un paquete sin plan dentro del lote también rechaza el lote entero.
$rSinPlan = $svc->asignarResponsable($P, array_merge($idsMasa, [999999]), $uid, 'test-a4');
$assert(($rSinPlan['ok'] ?? true) === false && ($rSinPlan['code'] ?? '') === 'PAQUETE_SIN_PLAN',
    'Masa: un paquete sin plan en el lote rechaza el lote. Dio ' . var_export($rSinPlan, true));

// --- Desamarrar (f10-f13 de la revisión de UX) ---
// Amarrar era una decisión sin retorno: no había forma de corregir un frente mal elegido, ni
// botón, ni endpoint, ni método en el servicio.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

$r = $svc->desamarrar($P, $paqEstructura);
$assert(($r['ok'] ?? false) === true, 'Desamarrar: responde ok. Dio ' . var_export($r, true));

$quedaAmarre = (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert($quedaAmarre === 0, 'Desamarrar: el amarre desaparece, el paquete vuelve a «sin frente». Dio ' . $quedaAmarre);

// f12: sin frente no hay fecha que pueda leerse como vigente.
$quedanPasos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert($quedanPasos === 0, 'Desamarrar: las fechas calculadas se borran. Quedaron ' . $quedanPasos . ' pasos.');

$fechasHuerfanas = $db->query(
    'SELECT fecha_arranque, fecha_ancla, unique_id, dias_totales FROM pdc_plan_paquete
      WHERE project_id = ? AND paquete_id = ?', [$P, $paqEstructura])->fetch(PDO::FETCH_ASSOC);
$assert($fechasHuerfanas !== false && $fechasHuerfanas['fecha_arranque'] === null
        && $fechasHuerfanas['fecha_ancla'] === null && $fechasHuerfanas['unique_id'] === null
        && $fechasHuerfanas['dias_totales'] === null,
    'Desamarrar: la cabecera no conserva ninguna fecha vieja. Dio ' . var_export($fechasHuerfanas, true));

// El paquete desaparece de la grilla del plan: sin frente no tiene fechas que mostrar.
$enPlan = false;
foreach ($svc->plan($P) as $f) { if ($f['paqueteId'] === $paqEstructura) { $enPlan = true; } }
$assert($enPlan === false, 'Desamarrar: el paquete sale de la grilla del plan.');

// f11: pero el responsable NO se pierde. Es el corazón de esta tarea.
$respTrasDesamarrar = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert((int) $respTrasDesamarrar === $uid,
    'Desamarrar: el responsable sobrevive. Dio ' . var_export($respTrasDesamarrar, true) . ' esperando ' . $uid);

// Reamarrar y volver a calcular deja el paquete como estaba, con su dueño.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$planTras = [];
foreach ($svc->plan($P) as $f) { $planTras[$f['paqueteId']] = $f; }
$assert(($planTras[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Desamarrar y reamarrar: el paquete vuelve al plan con su responsable intacto.');

// Desamarrar algo que no está amarrado no es un error: es un no-op.
$rNoop = $svc->desamarrar($P, 999999);
$assert(isset($rNoop['ok']), 'Desamarrar algo sin amarre responde sin reventar. Dio ' . var_export($rNoop, true));

// Un desamarre en un proyecto no toca el amarre del mismo paquete en otro (los paquetes son
// globales: el mismo paqueteId vive en los dos proyectos con amarres distintos).
$svc->amarrar($P2, $paqEstructura, 9101, 'test-a4');
$svc->desamarrar($P, $paqEstructura);
$sigueP2 = (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P2, $paqEstructura])->fetchColumn();
$assert($sigueP2 === 1, 'Desamarrar respeta el project_id: el otro proyecto conserva su amarre. Dio ' . $sigueP2);
$svc->desamarrar($P2, $paqEstructura);

// --- Reamarrar a otro frente conserva el responsable (f11, f14) ---
// Esto NO es una función nueva: es un fallo que ya existía. Cambiar de frente invalida el plan
// calculado y hasta ahora ese DELETE se llevaba la fila entera, con el responsable dentro, sin
// decir nada. Quien corregía un frente mal elegido descubría después que el paquete había perdido
// a su dueño.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

$svc->amarrar($P, $paqEstructura, 9002, 'test-a4');   // otro frente: invalida el plan viejo
$tras = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert((int) $tras === $uid,
    'Reamarre: cambiar de frente NO borra al responsable. Dio ' . var_export($tras, true));

// Y el plan viejo sí se invalida: las fechas de un frente ya no vigente no pueden quedarse.
$pasosTras = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert($pasosTras === 0, 'Reamarre: el plan calculado contra el frente viejo se invalida. Quedaron ' . $pasosTras);

$enPlanTras = false;
foreach ($svc->plan($P) as $f) { if ($f['paqueteId'] === $paqEstructura) { $enPlanTras = true; } }
$assert($enPlanTras === false,
    'Reamarre: hasta recalcular, el paquete no aparece en la grilla con fechas del frente viejo.');

// Recalcular lo devuelve a la grilla, ya contra el frente nuevo y con su responsable.
$svc->calcular($P, 'test-a4');
$planRe = [];
foreach ($svc->plan($P) as $f) { $planRe[$f['paqueteId']] = $f; }
$assert(($planRe[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Reamarre + recalcular: el paquete vuelve con su responsable. Dio ' . var_export($planRe[$paqEstructura]['responsableUserId'] ?? null, true));
$assert(($planRe[$paqEstructura]['uniqueId'] ?? null) === 9002,
    'Reamarre + recalcular: el plan queda calculado contra el frente NUEVO.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
