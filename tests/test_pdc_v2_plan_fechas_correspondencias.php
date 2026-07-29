<?php
// tests/test_pdc_v2_plan_fechas_correspondencias.php — A4.2 sobre MySQL real (proyecto 999905).
//
// Archivo aparte del test de A4 a propósito: aquél lo está tocando la tarea de pasos configurables,
// y compartir fichero garantizaba un choque. Aquí solo vive el puente rama → nodo del cronograma.
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
$P = 999905;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_correcciones_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_rama_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_rama_frente WHERE creado_por = 'test-a42'");
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a42'");
    $db->query('DELETE FROM pdc_presupuesto_apu_insumos WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// ---------------------------------------------------------------- cronograma
// Un encabezado temprano y una HOJA tardía que cuelga de él: es la forma exacta del caso CUBIERTA,
// donde anclar al encabezado adelantaría la contratación casi un año.
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);
$nodos = [
    [1, 7001, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE X]</small>', 1, '2026-08-18'],
    [2, 7002, '<b>LOSA AEREA CUBIERTA, </b> <small>[Capítulo: ESTRUCTURA, TORRE X]</small>', 0, '2027-07-27'],
    [3, 7003, '<b>ACABADOS, </b> <small>[Capítulo: TORRE X]</small>', 1, '2027-02-09'],
];
foreach ($nodos as [$cons, $uid, $act, $titulo, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio) VALUES (?, ?, ?, ?, ?, ?)',
        [$P, $cons, $uid, $act, $titulo, $ini],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, $cons, $uid, $cons, $act, $titulo, $ini],
    );
}

// ---------------------------------------------------------------- presupuesto
$db->query(
    'INSERT INTO pdc_presupuesto_versiones (project_id, archivo_nombre, archivo_hash, activa, created_at) VALUES (?, ?, ?, 1, NOW())',
    [$P, 'v-test-a42.xlsx', str_repeat('a', 64)],
);
$vid = (int) $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P])->fetchColumn();

// Jerarquía: capítulo → subcapítulo CUBIERTA → actividad. La rama que importa es el subcapítulo.
$items = [
    ['01', null, 1, 'capitulo', 'COSTO DIRECTO'],
    ['01.07', '01', 2, 'subcapitulo', 'ZZTEST CUBIERTA A42'],
    ['01.07.01', '01.07', 3, 'actividad', 'CUBIERTA LIVIANA'],
];
foreach ($items as [$cod, $padre, $nivel, $tipo, $desc]) {
    $db->query(
        'INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$P, $vid, $cod, $padre, $nivel, $tipo, $desc],
    );
}
$itemId = (int) $db->query(
    'SELECT id FROM pdc_presupuesto_items WHERE project_id = ? AND codigo = ?',
    [$P, '01.07.01'],
)->fetchColumn();
$db->query(
    'INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, unidad, valor_total) VALUES (?, ?, ?, ?, ?, ?)',
    [$P, $vid, $itemId, 'TEJA METALICA', 'M2', 1000000],
);

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A42 CUBIERTAS', 'TEST A42 CUBIERTAS', 'a_todo_costo', 'contrato', 1, 'test-a42', NOW())",
);
$paq = (int) $db->query("SELECT id FROM general_paquetes_contratacion WHERE creado_por = 'test-a42'")->fetchColumn();
$db->query(
    'INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, origen, asignado_por, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
    [$P, 'TEJA METALICA', 'M2', $paq, 'humano', 'test-a42'],
);

$svc = new PlanFechasService($db);

// ---------------------------------------------------------------- 1. sin correspondencia
$r = $svc->sugerenciasYMotivos($P);
$assert(!isset($r['sugerencias'][$paq]), 'Sin correspondencia, el paquete no recibe propuesta inventada.');
$assert(
    isset($r['motivos'][$paq]) && str_contains($r['motivos'][$paq]['texto'], 'ZZTEST CUBIERTA A42'),
    'La fila sin propuesta dice qué rama falta. Dio: ' . ($r['motivos'][$paq]['texto'] ?? 'nada'),
);
$assert(($r['motivos'][$paq]['rama'] ?? null) === 'ZZTEST CUBIERTA A42', 'El motivo nombra la rama para el atajo.');

// ---------------------------------------------------------------- 2. el ancla puede ser una HOJA
$g = $svc->guardarCorrespondencia($P, 'ZZTEST CUBIERTA A42', 'LOSA AEREA CUBIERTA', 'proyecto', 'test-a42');
$assert($g['ok'] === true, 'Se guarda una correspondencia que apunta a una actividad, no a un encabezado.');

$r = $svc->sugerenciasYMotivos($P);
$s = $r['sugerencias'][$paq] ?? null;
$assert($s !== null && $s['uniqueId'] === 7002, 'La propuesta ancla en la HOJA (7002), no en el encabezado.');
$assert(
    $s !== null && $s['fechaInicio'] === '2027-07-27',
    'La fecha es la de la losa (2027-07-27) y no la del encabezado (2026-08-18): es el caso que costaba 11 meses. Dio ' . ($s['fechaInicio'] ?? 'nada'),
);
$assert($s !== null && $s['origen'] === 'correspondencia', 'El origen de la propuesta es «correspondencia».');
$assert($s !== null && $s['confianza'] === 'alta', 'Una correspondencia confirmada da confianza alta.');

// ---------------------------------------------------------------- 3. un ancla inventada se rechaza
$mal = $svc->guardarCorrespondencia($P, 'ZZTEST CUBIERTA A42', 'FRENTE QUE NO EXISTE', 'proyecto', 'test-a42');
$assert(
    $mal['ok'] === false && $mal['code'] === 'ANCLA_INVALIDA',
    'Una correspondencia hacia un nodo inexistente se rechaza al guardarla, no meses después.',
);

// ---------------------------------------------------------------- 4. guardar no amarra nada
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ?', [$P])->fetchColumn() === 0,
    'Guardar una correspondencia NO escribe ningún amarre: solo cambia lo que se propone.',
);

// ---------------------------------------------------------------- 5. el panel
$panel = $svc->correspondencias($P);
$assert($panel['confirmadas'] >= 1, 'El panel cuenta las correspondencias confirmadas.');
$assert($panel['pendientes'] === [], 'Resuelta la rama, deja de aparecer como pendiente.');

// ---------------------------------------------------------------- 6. corregir al motor se registra
$svc->amarrar($P, $paq, 7003, 'test-a42', [
    'sugeridoUniqueId' => 7002, 'origenSugerido' => 'correspondencia', 'confianza' => 'alta',
]);
$c = $db->query(
    'SELECT unique_id_sugerido, unique_id_elegido, capa_sugerida FROM pdc_correcciones_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(\PDO::FETCH_ASSOC);
$assert(
    $c !== false && (int) $c['unique_id_sugerido'] === 7002 && (int) $c['unique_id_elegido'] === 7003,
    'Elegir un destino distinto del propuesto registra el par sugerido → elegido.',
);
$assert($c !== false && $c['capa_sugerida'] === 'correspondencia', 'La corrección guarda qué capa se equivocó.');

// Aceptar la propuesta tal cual NO es una corrección.
$db->query('DELETE FROM pdc_correcciones_frente WHERE project_id = ?', [$P]);
$svc->amarrar($P, $paq, 7002, 'test-a42', [
    'origen' => 'correspondencia', 'sugeridoUniqueId' => 7002, 'confianza' => 'alta', 'confirmado' => true,
]);
$assert(
    (int) $db->query('SELECT COUNT(*) FROM pdc_correcciones_frente WHERE project_id = ?', [$P])->fetchColumn() === 0,
    'Aceptar la propuesta tal cual no cuenta como corrección.',
);
$origenGuardado = $db->query(
    'SELECT origen FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetchColumn();
$assert($origenGuardado === 'correspondencia', 'Aceptar la propuesta acredita el acierto al motor. Dio ' . var_export($origenGuardado, true));

// ---------------------------------------------------------------- 7. amarrar a una hoja es válido
$assert(
    count(array_filter($svc->anclasDisponibles($P), static fn (array $a): bool => !$a['esFrente'])) >= 1,
    'Las anclas incluyen las actividades, no solo los encabezados.',
);

$limpiar();

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FAILED ===\n");
    exit(1);
}
fwrite(STDOUT, "\n=== TODO EN VERDE ===\n");
