<?php
// tests/test_pdc_v2_reprogramacion.php — PDC v2 · fase B2: simular y aplicar la reprogramación.
// Sobre MySQL real (proyectos sintéticos 999952). Ver el diseño en
// docs/superpowers/specs/2026-07-29-rematching-reprogramacion-design.md y la medición previa en
// goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md.
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
$P = 999952;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-b2'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST B2%'");
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// ── Fixture: una semana activa y dos frentes encabezado ──────────────────────
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);
$frentes = [
    [1, 8801, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-09-01'],
    [2, 8802, '<b>ACABADOS, </b> <small>[Capítulo: TORRE 1]</small>', '2026-10-01'],
];
foreach ($frentes as [$cons, $uid, $act, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, 1, ?)',
        [$P, $cons, $uid, $act, $ini],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, 1, ?, ?, ?, 1, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, 100 + $cons, $uid, $cons, $act, $ini],
    );
}

// Dos paquetes con desglose COMPLETO de duración: 33 días el que se mueve, 20 el que no.
$crearPaquete = static function (string $nombre, array $dias) use ($db): int {
    $db->query(
        "INSERT INTO general_dias_procesos_contratacion
            (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
             diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
         VALUES (?, 'a_todo_costo', ?, ?, ?, ?, ?, ?, ?)",
        array_merge([$nombre], $dias),
    );
    $ref = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
         VALUES (?, ?, 'a_todo_costo', 'contrato', ?, 1, 'test-b2', NOW())",
        [$nombre, $nombre, $ref],
    );
    return (int) $db->lastInsertId();
};
$paq = $crearPaquete('TEST B2 CONCRETO', [3, 2, 7, 4, 5, 10, 2]);        // 33 días
$paqQuieto = $crearPaquete('TEST B2 PINTURA', [2, 2, 4, 2, 4, 4, 2]);    // 20 días

$svc = new PlanFechasService($db);

echo "=== PDC v2 B2: simular y aplicar la reprogramación ===\n";

// ── Estado de partida: los dos amarrados y calculados contra el cronograma original ──
$svc->amarrar($P, $paq, 8801, 'test-b2');
$svc->amarrar($P, $paqQuieto, 8802, 'test-b2');
$svc->calcular($P, 'test-b2');

$cabInicial = $db->query(
    'SELECT fecha_ancla, fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(PDO::FETCH_ASSOC);
$assert($cabInicial['fecha_arranque'] === '2026-07-30', 'Punto de partida: arranque 2026-07-30. Dio ' . $cabInicial['fecha_arranque']);
$assert((int) $cabInicial['dias_totales'] === 33, 'Punto de partida: 33 días. Dio ' . $cabInicial['dias_totales']);

// Un paso YA OCURRIÓ: es lo que nunca se puede borrar.
$db->query(
    "UPDATE pdc_plan_paso SET fecha_real = '2026-08-05', registrado_por = 'test-b2', registrado_at = NOW()
      WHERE project_id = ? AND paquete_id = ? AND orden = 1",
    [$P, $paq],
);

// El frente ESTRUCTURA se reprograma +21 días. ACABADOS no se mueve.
$db->query("UPDATE programa SET Fecha_Inicio = '2026-09-22' WHERE project_id = ? AND unique_id = 8801", [$P]);
$db->query("UPDATE programa_consolidado SET Fecha_Inicio = '2026-09-22' WHERE project_id = ? AND unique_id = 8801", [$P]);

// ── simularReprogramacion ────────────────────────────────────────────────────
echo "\n--- simular ---\n";
$sim = $svc->simularReprogramacion($P);

$assert(count($sim['movidos']) === 1, 'Un solo paquete movido: ' . count($sim['movidos']));
$m = $sim['movidos'][0];
$assert($m['paqueteId'] === $paq, 'Y es el del frente que se movió.');
$assert($m['diasMovidos'] === 21, 'El frente se atrasó 21 días: ' . $m['diasMovidos']);
$assert($m['anclaActual'] === '2026-09-01', 'Ancla actual: ' . $m['anclaActual']);
$assert($m['anclaNueva'] === '2026-09-22', 'Ancla nueva: ' . $m['anclaNueva']);
$assert($m['arranqueActual'] === '2026-07-30', 'Arranque actual: ' . (string) $m['arranqueActual']);
$assert($m['arranqueNuevo'] === '2026-08-20', 'Arranque nuevo, 21 días después: ' . $m['arranqueNuevo']);
$assert($m['pasosQueSeMueven'] === 7, 'Se mueven los siete pasos: ' . $m['pasosQueSeMueven']);
$assert($m['pasosConFechaReal'] === 1, 'Uno ya ocurrió y conserva su fecha real: ' . $m['pasosConFechaReal']);
$assert($sim['huerfanos'] === [], 'Sin huérfanos mientras el frente exista.');

$assert(
    !in_array($paqQuieto, array_column($sim['movidos'], 'paqueteId'), true),
    'Un paquete cuyo frente no se movió no entra en el delta.',
);

// Simular NO escribe.
$cabTrasSimular = $db->query(
    'SELECT fecha_ancla, fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(PDO::FETCH_ASSOC);
$assert($cabTrasSimular['fecha_ancla'] === '2026-09-01', 'Simular no tocó el ancla guardada.');
$assert($cabTrasSimular['fecha_arranque'] === '2026-07-30', 'Simular no tocó el arranque guardado.');
$anclaAmarre = $db->query(
    'SELECT fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetchColumn();
$assert($anclaAmarre === '2026-09-01', 'Simular tampoco tocó el amarre.');

// ── aplicarReprogramacion ────────────────────────────────────────────────────
echo "\n--- aplicar ---\n";
$assert(
    $svc->aplicarReprogramacion($P, [], 'test-b2')['aplicados'] === 0,
    'Sin paquetes confirmados no se escribe nada.',
);
$cabTrasCancelar = $db->query(
    'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetchColumn();
$assert($cabTrasCancelar === '2026-07-30', 'Cancelar deja el plan exactamente como estaba.');

$r = $svc->aplicarReprogramacion($P, [$paq], 'test-b2');
$assert($r['aplicados'] === 1, 'Se aplicó un paquete: ' . json_encode($r));

$cab = $db->query(
    'SELECT fecha_ancla, fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(PDO::FETCH_ASSOC);
$assert($cab['fecha_ancla'] === '2026-09-22', 'El ancla se refrescó al cronograma nuevo: ' . $cab['fecha_ancla']);
$assert($cab['fecha_arranque'] === '2026-08-20', 'El arranque se corrió 21 días: ' . $cab['fecha_arranque']);

$ancla = $db->query(
    'SELECT fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetchColumn();
$assert($ancla === '2026-09-22', 'El amarre también quedó al día: ' . (string) $ancla);

// El bug que encontró la medición: antes, tras recalcular el desfase seguía ahí para siempre.
$assert($svc->desfases($P) === [], 'Aplicado el delta, ya no queda desfase que avisar.');

// Lo ocurrido NO se borró. Regla no negociable heredada de B1.
$real = $db->query(
    'SELECT fecha_real FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden = 1',
    [$P, $paq],
)->fetchColumn();
$assert($real === '2026-08-05', 'El paso que ya ocurrió conserva su fecha real: ' . var_export($real, true));

// El paquete que no se pidió no se tocó.
$otro = $db->query(
    'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqQuieto],
)->fetchColumn();
$assert($otro === '2026-09-11', 'El paquete cuyo frente no se movió sigue igual: ' . (string) $otro);

// ── Un frente borrado deja el paquete huérfano y NUNCA se reamarra solo ──────
echo "\n--- frente borrado ---\n";
$db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND unique_id = 8802', [$P]);
$db->query('DELETE FROM programa WHERE project_id = ? AND unique_id = 8802', [$P]);

$sim2 = $svc->simularReprogramacion($P);
$assert($sim2['movidos'] === [], 'Un frente que ya no existe no produce un delta que aplicar.');
$assert(count($sim2['huerfanos']) === 1, 'Se lista como huérfano para que lo decida un humano: ' . count($sim2['huerfanos']));
$assert($sim2['huerfanos'][0]['paqueteId'] === $paqQuieto, 'Y es el paquete cuyo frente desapareció.');

$rh = $svc->aplicarReprogramacion($P, [$paqQuieto], 'test-b2');
$assert(
    $rh['aplicados'] === 0 && $rh['ignorados'] === 1,
    'Un amarre huérfano se ignora y se cuenta, no se reamarra: ' . json_encode($rh),
);
$uidHuerfano = $db->query(
    'SELECT unique_id FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqQuieto],
)->fetchColumn();
$assert((int) $uidHuerfano === 8802, 'Sigue apuntando a su frente desaparecido, sin que nadie le elija otro.');

$limpiar();

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
