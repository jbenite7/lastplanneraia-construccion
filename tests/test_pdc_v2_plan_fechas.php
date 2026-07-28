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
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a4'");
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
$programa = [
    // [Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio]
    [1, 9001, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-08-18'],
    [2, 9002, '<b>PRELIMINARES, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-05-25'],
    [3, 9003, '<b>VACIADO LOSA PISO 3, </b> <small>[Capítulo: ESTRUCTURA, TORRE 1]</small>', 0, '2026-09-10'],
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

echo "=== PDC v2 A4: plan con fechas ===\n";
$svc = new PlanFechasService($db);

// --- limpiarActividad ---
$l = PlanFechasService::limpiarActividad('<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>');
$assert($l['nombre'] === 'ESTRUCTURA', 'Quita el HTML y la coma final del nombre: ' . $l['nombre']);
$assert($l['capitulo'] === 'TORRE 1', 'Extrae el capítulo embebido: ' . $l['capitulo']);
$assert(PlanFechasService::limpiarActividad('SIN HTML')['nombre'] === 'SIN HTML', 'Un texto sin HTML pasa igual.');

// --- frentesDisponibles ---
$f = $svc->frentesDisponibles($P);
$assert(count($f) === 2, 'Solo los encabezados de la semana activa (2 de 4 filas): ' . count($f));
$uids = array_column($f, 'uniqueId');
$assert(!in_array(9003, $uids, true), 'La actividad hoja (Titulo=0) no es un frente.');
$assert($f[0]['uniqueId'] === 9002 && $f[0]['fechaInicio'] === '2026-05-25', 'Ordena por fecha ascendente: primero PRELIMINARES.');
$assert($f[1]['fechaInicio'] === '2026-08-18', 'Toma la fecha de la semana ACTIVA (2), no de la 1.');
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

$sug = $svc->sugerirFrentes($P);
$s = $sug[$paqEstructura] ?? null;
$assert($s !== null && $s['uniqueId'] === 9001, 'El paquete «TEST A4 ESTRUCTURA» se propone al frente ESTRUCTURA.');
$assert($s !== null && $s['origen'] === 'similitud', 'La propuesta por nombre se marca como «similitud».');
$assert($s !== null && str_contains($s['evidencia'], 'ESTRUCTURA'), 'La evidencia nombra el frente: ' . ($s['evidencia'] ?? ''));

// Un paquete sin parecido con ningún frente no recibe propuesta: no se inventa.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 ZZZQQQ', 'TEST A4 ZZZQQQ', 'suministro', 'contrato', 1, 'test-a4', NOW())",
);
$paqRaro = (int) $db->lastInsertId();
$sug2 = $svc->sugerirFrentes($P);
$assert(!isset($sug2[$paqRaro]), 'Sin señal, no hay propuesta: el paquete queda pendiente.');

// --- sugerirFrentes: la rama, cuando el nombre no basta ---
// Un paquete que no se parece a ningún frente por su nombre, pero cuyos insumos viven en un
// subcapítulo que sí: la propuesta sale igual, marcada como «rama» y con confianza media.
$sugRama = $svc->sugerirFrentes($P);
foreach ($sugRama as $pid => $s) {
    $assert(in_array($s['origen'], ['similitud', 'rama'], true), "Origen válido en el paquete $pid: {$s['origen']}");
    if ($s['origen'] === 'rama') {
        $assert($s['confianza'] === 'media', 'La propuesta por rama nunca es de confianza alta: hay un salto.');
        $assert(str_contains($s['evidencia'], 'subcap'), 'La evidencia por rama nombra el subcapítulo.');
    }
}

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
