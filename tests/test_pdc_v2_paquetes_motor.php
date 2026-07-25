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
$ceramicosId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O INSTALACIÓN DE PISOS CERÁMICOS')])->fetchColumn() ?: 0);
$maderaId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O INSTALACIÓN DE PISOS EN MADERA')])->fetchColumn() ?: 0);
$enchapesId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O ENCHAPES CERÁMICOS')])->fetchColumn() ?: 0);
// El cajón de sastre debe estar retirado (su alcance se repartió por oficio).
$cajonActivo = (int) ($db->query("SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O INSTALACION DE PISOS')])->fetchColumn() ?: 0);
$assert($concretoId > 0 && $mamposteriaId > 0 && $indirectosId > 0, 'Catálogo con Suministro CONCRETO, M. de O MAMPOSTERÍA e Indirectos (seeds aplicados).');
$assert($ceramicosId > 0 && $maderaId > 0 && $enchapesId > 0, 'Catálogo con los paquetes de piso por oficio (cerámicos, madera) y enchapes de muro.');
$assert($cajonActivo === 0, 'El cajón de sastre «M. de O INSTALACION DE PISOS» está retirado (activo=0).');

// Maestro (tipo_recurso) para los insumos de prueba.
$maestro = [
    ['CONCRETO PRUEBA A3', 'M3', 'MATERIAL'],
    ['CONCRETO MO PRUEBA A3', 'HC', 'MANO DE OBRA'],
    ['M.O. GENERICO PRUEBA A3', 'HC', 'MANO DE OBRA'],
    ['M.O. ZOCALO PORCELANATO PRUEBA A3', 'M', 'MANO DE OBRA'],
    ['M.O. INSTALACION MUDA PRUEBA A3', 'M2', 'MANO DE OBRA'],
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
// Actividad dominante de 'M.O. ZOCALO PORCELANATO PRUEBA A3' = ZOCALO EN MADERA: el material real lo
// da la actividad padre, no el nombre del insumo (caso real DAPORTO 01.14.04.02).
$db->query(
    "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
     VALUES (?, ?, '01.14.04.02', NULL, 4, 'actividad', 'ZOCALO EN MADERA', 'M', 100)",
    [$P1, $vid],
);
$itemZocalo = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
     VALUES (?, ?, ?, 'M.O. ZOCALO PORCELANATO PRUEBA A3', 'X', 'M', 1, 1, 100, 12000, 1200000, 0)",
    [$P1, $vid, $itemZocalo],
);

// Actividad padre de 'M.O. INSTALACION MUDA PRUEBA A3': la descripción NO nombra material, así que
// el oficio lo debe aportar la actividad (pasada 2).
$db->query(
    "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
     VALUES (?, ?, '01.14.02.09', NULL, 4, 'actividad', 'PISO EN TABLETA DE GRES SAHARA', 'M2', 50)",
    [$P1, $vid],
);
$itemMudo = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
     VALUES (?, ?, ?, 'M.O. INSTALACION MUDA PRUEBA A3', 'X', 'M2', 1, 1, 50, 10000, 500000, 0)",
    [$P1, $vid, $itemMudo],
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

// DOCTRINA DE PRECEDENCIA (usuario 2026-07-25):
//  (a) si la DESCRIPCIÓN nombra el material, manda sobre la actividad padre;
//  (b) si la descripción calla, el material lo aporta la actividad padre.
// «M.O. ZOCALO PORCELANATO PRUEBA A3» tiene material explícito (PORCELANATO) y contexto de piso
// (ZOCALO), aunque su actividad dominante sea «ZOCALO EN MADERA»: gana la descripción.
$sZoc = $byNorm['M.O. ZOCALO PORCELANATO PRUEBA A3'] ?? null;
$assert($sZoc !== null && (int) $sZoc['paqueteId'] === $ceramicosId, 'M.O.: el material explícito de la descripción («PORCELANATO») manda sobre la actividad («ZOCALO EN MADERA»).');
$assert($sZoc !== null && (int) $sZoc['paqueteId'] !== $enchapesId, 'El zócalo de piso NO cae en Enchapes cerámicos (que es enchape de muro).');
$assert($sZoc !== null && (int) $sZoc['paqueteId'] !== $maderaId, 'El zócalo de porcelanato NO cae en el paquete de pisos en madera.');
$assert($sZoc !== null && str_contains($sZoc['evidencia'], 'descripcion del insumo'), 'La evidencia indica que se decidió por la descripción.');

// Descripción muda: el oficio lo aporta la actividad padre (pasada 2 sigue viva).
$sMudo = $byNorm['M.O. INSTALACION MUDA PRUEBA A3'] ?? null;
$assert($sMudo !== null && (int) $sMudo['paqueteId'] === $ceramicosId, 'M.O. con descripción muda: la actividad padre («PISO EN TABLETA DE GRES») aporta el oficio.');
$assert($sMudo !== null && str_contains($sMudo['evidencia'], 'actividad padre'), 'La evidencia del caso mudo cita la actividad padre.');

// Frontera de palabra: «PISO» dentro de APISONADOR no debe disparar reglas de piso.
$assert(!\str_contains(\json_encode($byNorm, JSON_UNESCAPED_UNICODE), 'APISONADOR'), 'Sin falsos positivos por subcadena (APISONADOR).');

// Filtro por tipo_recurso: la REGLA material (CONCRETO) NO coloca a un insumo de mano de obra.
// (Otras capas cross-proyecto pueden ubicarlo; lo que se verifica es que la regla material no aplicó.)
$sMoConc = $byNorm['CONCRETO MO PRUEBA A3'] ?? null;
$assert(!($sMoConc !== null && $sMoConc['capa'] === 'reglas' && (int) $sMoConc['paqueteId'] === $concretoId), 'La regla material (CONCRETO) no aplica a un insumo de mano de obra (tipo_recurso).');

// MODALIDADES SIN PROCESO (A3.1): el bucket único se partió por naturaleza — lo que no se le compra a
// nadie (nómina, imprevistos) va a paquetes `no_contratable`, y lo que se pide a necesidad contra
// almacén va a `consumo_directo`. Así no contaminan la cobertura ni los semáforos de seguimiento.
$impId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar(PaquetesService::PAQUETE_IMPREVISTOS)])->fetchColumn() ?: 0);
$nomId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar(PaquetesService::PAQUETE_NOMINA)])->fetchColumn() ?: 0);
$assert($impId > 0 && $nomId > 0, 'Catálogo con los buckets de imprevistos y nómina.');

$sImp = $byNorm['IMPREVISTOS PRUEBA A3'] ?? null;
$assert($sImp !== null && (int) $sImp['paqueteId'] === $impId, 'IMPREVISTOS → Imprevistos y provisiones (no_contratable), no al bucket administrativo.');
$sNom = $byNorm['NOMINA PRUEBA A3'] ?? null;
$assert($sNom !== null && (int) $sNom['paqueteId'] === $nomId, 'NOMINA (tipo_recurso) → Nómina de obra (no_contratable).');

// La modalidad viaja en el catálogo y por defecto es 'contrato' (cero regresión para los 199 existentes).
$modCat = $db->query("SELECT modalidad_contratacion FROM general_paquetes_contratacion WHERE id = ?", [$pisosId])->fetchColumn();
$assert($modCat === 'contrato', 'Un paquete creado sin modalidad nace como «contrato».');
$modOC = $db->query("SELECT modalidad_contratacion FROM general_paquetes_contratacion WHERE nombre_norm = ?", [\App\Services\Pdc\MaestroInsumosService::normalizar('Suministro CONCRETO')])->fetchColumn();
$assert($modOC === 'orden_compra', 'Suministro CONCRETO quedó como orden_compra (respaldado por el catálogo legacy de duraciones).');
$malaMod = $svc->crearPaquete('TEST A3 Modalidad Mala', 'suministro', 'test-a3', 'inventada');
$assert($malaMod['ok'] === false && $malaMod['code'] === 'PAQUETE_INVALIDO', 'Modalidad inválida rechazada.');

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
