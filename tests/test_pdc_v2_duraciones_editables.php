<?php
// tests/test_pdc_v2_duraciones_editables.php — A4.1 · diferido nº 4: cambiar una duración del
// catálogo legacy sin entrar a la base, y que mueva la fecha que dependía de ella y SOLO esa.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\DuracionesCatalogoService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999970;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-dur'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST DUR%'");
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// ── Fixture ─────────────────────────────────────────────────────────────────
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);
$db->query(
    'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
     VALUES (?, 1, 7701, ?, 1, ?)',
    [$P, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-09-01'],
);
$db->query(
    'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
         Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
         Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
     VALUES (?, 101, 1, 7701, 1, ?, 1, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
    [$P, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-09-01'],
);

$crearPaquete = static function (string $nombre, array $dias) use ($db): array {
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
         VALUES (?, ?, 'a_todo_costo', 'contrato', ?, 1, 'test-dur', NOW())",
        [$nombre, $nombre, $ref],
    );
    return ['paquete' => (int) $db->lastInsertId(), 'ref' => $ref];
};
$a = $crearPaquete('TEST DUR CONCRETO', [3, 2, 7, 4, 5, 10, 2]);   // 33 días
$b = $crearPaquete('TEST DUR PINTURA', [2, 2, 4, 2, 4, 4, 2]);     // 20 días
// Una fila del catálogo que existe pero que NINGÚN paquete de esta obra usa.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST DUR AJENA', 'a_todo_costo', 1, 1, 1, 1, 1, 1, 1)",
);
$refAjeno = (int) $db->lastInsertId();

$plan = new PlanFechasService($db);
$plan->amarrar($P, $a['paquete'], 7701, 'test-dur');
$plan->amarrar($P, $b['paquete'], 7701, 'test-dur');
$plan->calcular($P, 'test-dur');

$svc = new DuracionesCatalogoService($db);

echo "=== A4.1 · duraciones del catálogo editables ===\n";

// ── Solo se ofrecen las filas que esta obra usa de verdad ───────────────────
$lista = $svc->deProyecto($P);
$refs = array_column($lista, 'duracionRef');
$assert(in_array($a['ref'], $refs, true), 'La duración que usa el paquete se ofrece.');
$assert(!in_array($refAjeno, $refs, true), 'Una fila que ningún paquete de esta obra usa no se ofrece.');
$filaA = null;
foreach ($lista as $l) {
    if ($l['duracionRef'] === $a['ref']) { $filaA = $l; }
}
$assert($filaA !== null && $filaA['paquetesQueLaUsan'] === 1,
    'Cada fila dice cuántos paquetes de esta obra la usan: ' . json_encode($filaA['paquetesQueLaUsan'] ?? null));
$assert(($filaA['dias']['diasFabricacion'] ?? null) === 10, 'Y trae las siete duraciones actuales.');

$antes = $db->query(
    'SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $a['paquete']],
)->fetch(PDO::FETCH_ASSOC);
$assert((int) $antes['dias_totales'] === 33, 'Punto de partida: 33 días. Dio ' . $antes['dias_totales']);
$assert($antes['fecha_arranque'] === '2026-07-30', 'Punto de partida: arranque 2026-07-30. Dio ' . $antes['fecha_arranque']);
$totalesB = (int) $db->query(
    'SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $b['paquete']],
)->fetchColumn();

// ── Cambiar un día mueve la fecha que dependía de él ────────────────────────
$r = $svc->actualizar($a['ref'], ['diasFabricacion' => 15], 'test-dur');
$assert(($r['ok'] ?? false) === true, 'Se actualizó: ' . json_encode($r));
$plan->calcular($P, 'test-dur');

$despues = $db->query(
    'SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $a['paquete']],
)->fetch(PDO::FETCH_ASSOC);
$assert((int) $despues['dias_totales'] === 38, 'El total subió cinco días: ' . $despues['dias_totales']);
$assert($despues['fecha_arranque'] === '2026-07-25', 'Y el arranque se adelantó cinco días: ' . $despues['fecha_arranque']);

// ── «y solo esa» ────────────────────────────────────────────────────────────
$otro = (int) $db->query(
    'SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $b['paquete']],
)->fetchColumn();
$assert($otro === $totalesB, 'El paquete con otra duración no se movió: ' . $otro . ' (era ' . $totalesB . ')');

// ── Validación ──────────────────────────────────────────────────────────────
$assert($svc->actualizar($a['ref'], ['diasFabricacion' => -1], 'test-dur')['code'] === 'DIAS_INVALIDOS',
    'Un número negativo de días se rechaza.');
$assert($svc->actualizar($a['ref'], ['diasInventados' => 3], 'test-dur')['code'] === 'COLUMNA_DESCONOCIDA',
    'Una columna fuera de la lista blanca se rechaza (es lo que evita la inyección).');
$assert($svc->actualizar($a['ref'], [], 'test-dur')['code'] === 'SIN_CAMBIOS',
    'Sin nada que cambiar no se escribe.');
$assert($svc->actualizar(99999999, ['diasFabricacion' => 5], 'test-dur')['code'] === 'DURACION_NO_EXISTE',
    'Una fila inexistente se reporta en vez de fingir que se guardó.');

// Guardar el MISMO número dos veces sigue siendo un éxito: MySQL reporta 0 filas modificadas y eso
// no significa que la fila no exista (este repo no activa PDO::MYSQL_ATTR_FOUND_ROWS).
$assert(($svc->actualizar($a['ref'], ['diasFabricacion' => 15], 'test-dur')['ok'] ?? false) === true,
    'Guardar el mismo valor otra vez no se confunde con una fila inexistente.');

$limpiar();
$db->query('DELETE FROM general_dias_procesos_contratacion WHERE id = ?', [$refAjeno]);

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
