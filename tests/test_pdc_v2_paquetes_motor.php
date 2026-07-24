<?php
// tests/test_pdc_v2_paquetes_motor.php — motor de sugerencias/sembrado (A3 + A3.1) sobre MySQL real.
// Cascada: IA → exacta → reglas → tokens → indirectos → agrupación. Usa el catálogo real sembrado
// (Suministro CONCRETO, Indirectos / Administración). Fixtures sin colisión de keywords para aislar capas.

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P1 = 999901; $P2 = 999902;
$limpiar = static function () use ($db, $P1, $P2): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a3'");
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_apu_insumos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_maestro_insumos WHERE creado_por = 'test-a3'");
};
$limpiar();

echo "=== PDC v2: motor de sembrado (cascada reglas/indirectos + cross-proyecto) ===\n";
$svc = new PaquetesService($db);
$pisosId = (int) $svc->crearPaquete('TEST A3 Pisos', 'suministro', 'test-a3')['paquete']['id'];

// El catálogo real debe tener los paquetes destino de reglas/indirectos.
$concretoId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ?", [\App\Services\Pdc\MaestroInsumosService::normalizar('Suministro CONCRETO')])->fetchColumn() ?: 0);
$mamposteriaId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ?", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O MAMPOSTERÍA')])->fetchColumn() ?: 0);
$indirectosId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ?", [\App\Services\Pdc\MaestroInsumosService::normalizar(PaquetesService::PAQUETE_INDIRECTOS)])->fetchColumn() ?: 0);
$assert($concretoId > 0 && $mamposteriaId > 0 && $indirectosId > 0, 'Catálogo con Suministro CONCRETO, M. de O MAMPOSTERÍA e Indirectos (seeds aplicados).');

// Maestro (tipo_recurso) para los insumos de prueba.
$maestro = [
    ['CONCRETO PRUEBA A3', 'M3', 'MATERIAL'],
    ['CONCRETO MO PRUEBA A3', 'HC', 'MANO DE OBRA'],
    ['M.O. GENERICO PRUEBA A3', 'HC', 'MANO DE OBRA'],
    ['IMPREVISTOS PRUEBA A3', 'GL', 'SUBCONTRATO'],
    ['NOMINA PRUEBA A3', 'MES', 'NOMINA'],
    ['ZZZ ALFA A3', 'UN', 'MATERIAL'],
    ['ZZZ SINMATCH A3', 'UN', 'MATERIAL'],
];
foreach ($maestro as $m) {
    $db->query(
        "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
         VALUES (?, ?, ?, 'X', ?, 1, 'test-a3', NOW())",
        [$m[0], $m[0], $m[1], $m[2]],
    );
}
$mid = static fn (string $n, string $u): int => (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm=? AND unidad=?", [$n, $u])->fetchColumn();

// Historial P2 (para la capa exacta): ZZZ ALFA A3 → TEST A3 Pisos.
$db->query(
    "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
     VALUES (?, 'ZZZ ALFA A3', 'UN', ?, 0, 'test-a3', NOW())",
    [$P2, $pisosId],
);

// Versión activa P1 con los insumos de prueba.
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-MOTOR', 1, 'motor.xlsx', REPEAT('c', 64), 1, 7, 100, 1, 'test-a3', NOW())",
    [$P1],
);
$vid = (int) $db->lastInsertId();
foreach ($maestro as $m) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, 100, 1, ?, 'pendiente')",
        [$P1, $vid, $m[0], $m[1], $m[0], $mid($m[0], $m[1])],
    );
}
// Actividad dominante de 'M.O. GENERICO PRUEBA A3' = un muro en ladrillo (para la regla por actividad).
$db->query(
    "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
     VALUES (?, ?, '01.05.01.01', NULL, 4, 'actividad', 'MURO EN LADRILLO CATALAN INTERIOR', 'M2', 100)",
    [$P1, $vid],
);
$itemMuro = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
     VALUES (?, ?, ?, 'M.O. GENERICO PRUEBA A3', 'X', 'HC', 1, 1, 40, 25000, 1000000, 0)",
    [$P1, $vid, $itemMuro],
);

$r = $svc->sugerencias($P1);
$assert($r !== null, 'Con versión activa hay respuesta.');
$byNorm = [];
foreach ($r['sugerencias'] as $s) { $byNorm[$s['descripcionNorm']] = $s; }

// Capa exacta (cross-proyecto) sigue teniendo prioridad sobre reglas para insumos sin keyword.
$sExacta = $byNorm['ZZZ ALFA A3'] ?? null;
$assert($sExacta !== null && $sExacta['capa'] === 'exacta' && (int) $sExacta['paqueteId'] === $pisosId, 'Capa exacta: ZZZ ALFA A3 → TEST A3 Pisos (alta).');

// Capa reglas — material por descripción.
$sConc = $byNorm['CONCRETO PRUEBA A3'] ?? null;
$assert($sConc !== null && $sConc['capa'] === 'reglas' && (int) $sConc['paqueteId'] === $concretoId, 'Regla material: CONCRETO → Suministro CONCRETO.');

// Capa reglas — mano de obra por ACTIVIDAD dominante (la descripción del insumo no tiene keyword).
$sMuro = $byNorm['M.O. GENERICO PRUEBA A3'] ?? null;
$assert($sMuro !== null && $sMuro['capa'] === 'reglas' && (int) $sMuro['paqueteId'] === $mamposteriaId, 'Regla por actividad: M.O. de un MURO EN LADRILLO → M. de O MAMPOSTERÍA.');

// Filtro por tipo_recurso: un insumo de MANO DE OBRA con "CONCRETO" en el nombre NO cae en el paquete de material.
$sMoConc = $byNorm['CONCRETO MO PRUEBA A3'] ?? null;
$assert($sMoConc === null || (int) $sMoConc['paqueteId'] !== $concretoId, 'La regla material (CONCRETO) no aplica a un insumo de mano de obra (tipo_recurso).');

// Capa indirectos — por keyword y por tipo_recurso.
$sImp = $byNorm['IMPREVISTOS PRUEBA A3'] ?? null;
$assert($sImp !== null && $sImp['capa'] === 'indirectos' && (int) $sImp['paqueteId'] === $indirectosId, 'Indirectos por keyword: IMPREVISTOS → Indirectos.');
$sNom = $byNorm['NOMINA PRUEBA A3'] ?? null;
$assert($sNom !== null && $sNom['capa'] === 'indirectos' && (int) $sNom['paqueteId'] === $indirectosId, 'Indirectos por tipo_recurso NOMINA → Indirectos.');

// Sin match → sin sugerencia (no se inventa).
$assert(!isset($byNorm['ZZZ SINMATCH A3']), 'Insumo sin señal: sin sugerencia.');

// candidatosParaPaquete sigue operativo (reusa sin_asignar) — con % y _ escapados.
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, 'SOLUCION 50%_ ESPECIAL', 'LT', 'Solucion', 'X', 1, 10, 1, 'pendiente')",
    [$P1, $vid],
);
$cand = $svc->candidatosParaPaquete($P1, $pisosId);
$assert($cand !== null && is_array($cand['candidatos']), 'candidatosParaPaquete responde sin romper (comodines escapados).');
$assert($svc->sugerencias($P2) === null, 'P2 sin presupuesto → null.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
