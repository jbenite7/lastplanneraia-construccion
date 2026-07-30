<?php
// tests/test_pdc_v2_frentes_remapeados.php — los frentes en una base REMAPEADA.
//
// `20260712_remap_consolidado_unique_id.php` deja los encabezados (`Titulo=1`) sin `unique_id`: un
// encabezado no es una tarea de MS Project y no tiene identidad que sobreviva a una reprogramación.
// Sin identificador no hay a qué amarrar, y el desplegable «Elegir frente…» se queda sin un solo
// frente — le pasó a `prueba-lps` el 2026-07-29.
//
// La decisión (Felipe, 2026-07-30): anclar el frente a la hoja MÁS TEMPRANA de su subárbol WBS. El
// usuario sigue eligiendo «ESTRUCTURA»; por debajo se guarda una actividad real con `unique_id`
// estable, sin tocar el remap ni `programa_consolidado`, que son del LPS.
//
// La base local NO está remapeada, así que este test la simula: siembra encabezados con `unique_id`
// en NULL. Es el único sitio donde ese camino se puede probar antes del servidor.
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
$P = 999990;

// ── Por qué este test puede quedarse sin ejecutar ────────────────────────────
// La base local lleva `trg_programa_consolidado_unique_id_INSERT/UPDATE`, un trigger que rellena
// `unique_id` con `Consecutivo_en_Programa` en cuanto llega nulo. Con él puesto, el estado que este
// test necesita —encabezado SIN identificador— es literalmente inalcanzable: son 0 de las 53.705
// filas de esta base. En `prueba-lps` los encabezados sí están en NULL, así que allí el trigger no
// está: hay deriva de esquema entre local y servidor, y es la razón de que esto no se viera antes.
//
// Se declara y se sale en vez de tocar los triggers de una base compartida. La verificación real de
// este camino es en el servidor, DESPUÉS del remap.
$triggers = (int) $db->query(
    "SELECT COUNT(*) FROM information_schema.TRIGGERS
      WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'programa_consolidado'
        AND TRIGGER_NAME LIKE 'trg_programa_consolidado_unique_id%'",
)->fetchColumn();
if ($triggers > 0) {
    fwrite(STDOUT,
        "SKIP: esta base tiene {$triggers} trigger(s) que rellenan `programa_consolidado.unique_id`,\n"
        . "      así que un encabezado sin identificador no puede existir aquí y el camino remapeado\n"
        . "      no es reproducible. Verificar en el servidor después del remap.\n");
    echo "\n=== OK (omitido) ===\n";
    exit(0);
}

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// El fixture simula un esquema que ESTA base resiste: aquí `programa_consolidado` conserva las FK
// contra `programa`, y la fila de un encabezado remapeado no las satisface (no hay encabezado en
// `programa` al que apuntar). Se siembran con las comprobaciones desactivadas y se restauran justo
// después, antes de ejercitar nada: lo que se prueba es la resolución del ancla, no la integridad.
$db->query('SET FOREIGN_KEY_CHECKS = 0');

$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-02-23', '2026-03-01'],
);

// `programa` es la tabla viva y da la identidad: solo las HOJAS llevan unique_id en este escenario.
$hojas = [
    // [unique_id, Id (ruta WBS), Actividad, Fecha_Inicio]
    [9101, '1.1.1', 'VACIADO PLACA', '2026-03-15'],
    [9102, '1.1.2', 'ACERO DE REFUERZO', '2026-03-10'],   // la más temprana de 1.1
    [9103, '1.3.1.1', 'PINTURA INTERNA', '2026-05-20'],   // única hoja bajo 1.3 y bajo 1.3.1
    [9104, '1.10.1', 'HOJA DE LA RAMA DIEZ', '2026-06-01'],
];
foreach ($hojas as $i => [$uid, $id, $act, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, ?, 0, ?)',
        [$P, 500 + $i, $uid, $id, $act, $ini],
    );
}

$insertarConsolidado = static function (
    int $cons, ?int $uid, string $id, string $act, int $titulo, string $ini
) use ($db, $P): void {
    // `Consecutivo_en_Programa` es NOT NULL en este esquema y tiene su propia FK contra
    // `programa.Consecutivo`, así que apunta a una fila real. No participa en la resolución del
    // ancla —el servicio solo lee unique_id, Titulo, Id y Fecha_Inicio—, pero sin él no se puede
    // sembrar la fila.
    //
    // TODOS los valores van como marcador, sin literales mezclados: con un `null` posicional al
    // lado de un literal, la capa desplaza los parámetros y el 500 acababa dentro de `unique_id`,
    // que es justo la columna que este test necesita en NULL.
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Id, Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, $cons, $uid, 500, $id, $act, $titulo, $ini],
    );
};

// Encabezados SIN unique_id (así los deja el remap) + sus hojas CON unique_id.
$insertarConsolidado(600, null, '1', 'OBRA COMPLETA', 1, '2026-03-10');
$insertarConsolidado(601, null, '1.1', 'ESTRUCTURA', 1, '2026-03-10');
$insertarConsolidado(602, 9102, '1.1.2', 'ACERO DE REFUERZO', 0, '2026-03-10');
$insertarConsolidado(603, 9101, '1.1.1', 'VACIADO PLACA', 0, '2026-03-15');
// Anidamiento: 1.3 y 1.3.1 comparten la única hoja de su subárbol.
$insertarConsolidado(604, null, '1.3', 'ACABADOS', 1, '2026-05-20');
$insertarConsolidado(605, null, '1.3.1', 'MAMPOSTERIA', 1, '2026-05-20');
$insertarConsolidado(606, 9103, '1.3.1.1', 'PINTURA INTERNA', 0, '2026-05-20');
// Encabezado sin ninguna hoja debajo: no se puede anclar.
$insertarConsolidado(607, null, '1.5', 'CAPITULO VACIO', 1, '2026-07-01');
// Trampa del prefijo: «1.1» no puede quedarse con la hoja de «1.10».
$insertarConsolidado(608, null, '1.10', 'RAMA DIEZ', 1, '2026-06-01');
$insertarConsolidado(609, 9104, '1.10.1', 'HOJA DE LA RAMA DIEZ', 0, '2026-06-01');

$db->query('SET FOREIGN_KEY_CHECKS = 1');

$svc = new PlanFechasService($db);

echo "=== PDC v2: frentes sobre una base remapeada ===\n";

$r = $svc->frentesYCobertura($P);
$frentes = $r['frentes'];
$porNombre = [];
foreach ($frentes as $f) {
    $porNombre[$f['nombre']] = $f;
}

// ── El síntoma que hay que borrar: sin frentes, la pantalla es inservible ────
$conFrente = array_values(array_filter($frentes, static fn (array $f): bool => $f['esFrente']));
$assert($conFrente !== [], 'Una base remapeada vuelve a ofrecer frentes. Dio ' . count($conFrente));

// ── El ancla es la hoja MÁS TEMPRANA, no la primera por orden ────────────────
$assert(
    ($porNombre['ESTRUCTURA']['uniqueId'] ?? null) === 9102,
    'ESTRUCTURA se ancla a la hoja más temprana (ACERO, 9102), no a la primera por orden (VACIADO, 9101). Dio '
    . var_export($porNombre['ESTRUCTURA']['uniqueId'] ?? null, true),
);
$assert(
    ($porNombre['ESTRUCTURA']['fechaInicio'] ?? null) === '2026-03-10',
    'Y reproduce exactamente la fecha del encabezado. Dio ' . var_export($porNombre['ESTRUCTURA']['fechaInicio'] ?? null, true),
);
$assert(($porNombre['ESTRUCTURA']['esFrente'] ?? false) === true, 'ESTRUCTURA sigue marcándose como frente, no como actividad.');

// ── Anidamiento: padre e hijo comparten hoja; gana el más alto del árbol ─────
$assert(
    isset($porNombre['ACABADOS']),
    'Del par anidado se ofrece el encabezado más alto: ACABADOS.',
);
$assert(
    !isset($porNombre['MAMPOSTERIA']),
    'Y NO el de abajo: dos opciones con el mismo unique_id son indistinguibles para amarrar(), '
    . 'que resuelve por primera coincidencia y guardaría un nombre que el usuario no eligió.',
);
$ids = array_column($frentes, 'uniqueId');
$assert(count($ids) === count(array_unique($ids)), 'Ningún unique_id se repite en la lista. Dio ' . json_encode($ids));

// ── La trampa del prefijo ───────────────────────────────────────────────────
$assert(
    ($porNombre['RAMA DIEZ']['uniqueId'] ?? null) === 9104,
    '«1.10» se queda con su propia hoja. Dio ' . var_export($porNombre['RAMA DIEZ']['uniqueId'] ?? null, true),
);
$assert(
    ($porNombre['ESTRUCTURA']['uniqueId'] ?? null) !== 9104,
    '«1.1» no se roba la hoja de «1.10»: el prefijo lleva punto.',
);

// ── Lo que no se puede anclar se cuenta, no se calla ────────────────────────
$assert(!isset($porNombre['CAPITULO VACIO']), 'Un capítulo sin ninguna actividad debajo no se ofrece.');
$assert($r['sinAncla'] >= 1, 'Y se cuenta para que la pantalla pueda decirlo. Dio ' . $r['sinAncla']);

// ── Amarrar contra el ancla resuelta funciona de extremo a extremo ──────────
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST REMAP PAQUETE', 'TEST REMAP PAQUETE', 'a_todo_costo', 'contrato', 1, 'test-remap', NOW())",
);
$paq = (int) $db->lastInsertId();
$a = $svc->amarrar($P, $paq, (int) $porNombre['ESTRUCTURA']['uniqueId'], 'test-remap');
$assert(($a['ok'] ?? false) === true, 'Se puede amarrar un paquete al frente resuelto: ' . json_encode($a));
$guardado = $db->query(
    'SELECT unique_id, fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(\PDO::FETCH_ASSOC);
$assert(
    (int) $guardado['unique_id'] === 9102 && $guardado['fecha_ancla'] === '2026-03-10',
    'El amarre queda contra la hoja real y con la fecha que se enseñó: ' . json_encode($guardado),
);
$assert($svc->desfases($P) === [], 'Y no nace desfasado: lo guardado coincide con lo que ofrece el cronograma.');

$db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
$db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
$db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
$db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-remap'");
$limpiar();

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
