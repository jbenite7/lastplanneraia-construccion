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
    $db->query('DELETE FROM pdc_insumo_actividades WHERE project_id IN (?, ?)', [$P1, $P2]);
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
// A3.5 · Cuatro paquetes se retiraron tras la revisión en obra: su alcance se fusionó con otro.
$retirados = ['M. de O ENCHAPES CERÁMICOS', 'M. de O TOPOGRAFÍA',
              'Sum + Inst PUERTAS EN MADERA', 'Sum + Inst IMPERMEABILIZACIÓN FOSO DE ASCENSOR'];
foreach ($retirados as $nom) {
    $vivo = (int) ($db->query("SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1",
        [\App\Services\Pdc\MaestroInsumosService::normalizar($nom)])->fetchColumn() ?: 0);
    $assert($vivo === 0, "«{$nom}» está retirado del catálogo (activo=0).");
}
// El cajón de sastre debe estar retirado (su alcance se repartió por oficio).
$cajonActivo = (int) ($db->query("SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('M. de O INSTALACION DE PISOS')])->fetchColumn() ?: 0);
$assert($concretoId > 0 && $mamposteriaId > 0 && $indirectosId > 0, 'Catálogo con Suministro CONCRETO, M. de O MAMPOSTERÍA e Indirectos (seeds aplicados).');
$assert($ceramicosId > 0 && $maderaId > 0, 'Catálogo con los paquetes de piso por oficio (cerámicos, madera).');
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
// JERARQUÍA REAL del presupuesto: hasta A3.3 los fixtures insertaban actividades con codigo_padre
// NULL, así que cualquier lógica que suba por los ancestros no habría encontrado cadena y los tests
// habrían dado un falso verde. Aquí se construye el árbol completo, como lo deja el importador.
$item = static function (string $codigo, ?string $padre, int $nivel, string $tipo, string $desc, ?string $und = null, float $cant = 0) use ($db, $P1, $vid): int {
    $db->query(
        "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$P1, $vid, $codigo, $padre, $nivel, $tipo, $desc, $und, $cant],
    );
    return (int) $db->lastInsertId();
};
$item('01', null, 1, 'capitulo', 'COSTO DIRECTO');
$item('02', null, 1, 'capitulo', 'COSTO INDIRECTO');
$item('01.05', '01', 2, 'subcapitulo', 'MAMPOSTERIA Y REVOQUE');
$item('01.05.01', '01.05', 3, 'grupo', 'MUROS Y CHAPAS');
$item('01.14', '01', 2, 'subcapitulo', 'PISOS Y ENCHAPES');
$item('01.14.02', '01.14', 3, 'grupo', 'PISOS EN ZONAS PRIVADAS');
$item('01.14.04', '01.14', 3, 'grupo', 'ZOCALOS Y GUARDAESCOBAS');
$item('02.01', '02', 2, 'subcapitulo', 'ADMINISTRACION DE OBRA');
$item('02.01.01', '02.01', 3, 'grupo', 'PERSONAL Y GASTOS GENERALES');

// Actividad dominante de 'M.O. GENERICO PRUEBA A3' = un muro en ladrillo (para la regla por actividad).
$db->query(
    "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
     VALUES (?, ?, '01.05.01.01', '01.05.01', 4, 'actividad', 'MURO EN LADRILLO CATALAN INTERIOR', 'M2', 100)",
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
     VALUES (?, ?, '01.14.04.02', '01.14.04', 4, 'actividad', 'ZOCALO EN MADERA', 'M', 100)",
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
     VALUES (?, ?, '01.14.02.09', '01.14.02', 4, 'actividad', 'PISO EN TABLETA DE GRES SAHARA', 'M2', 50)",
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

// --- A3.3 · Mapa completo insumo↔actividades -------------------------------------------------
// Requisito de Seguimiento: para una orden de compra hay que programar la PRIMERA entrega, así que
// no basta con la actividad dominante — hay que conocer todas las actividades que consumen el
// insumo. En A4 cada fila recibirá el unique_id de programa_consolidado.
$mat = $svc->materializarActividades($P1, $vid);
$assert($mat !== null && $mat['filas'] > 0, 'materializarActividades persiste el mapa de la versión.');
$filasMapa = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?',
    [$P1, $vid],
)->fetchColumn();
$assert($filasMapa === $mat['filas'], 'El conteo devuelto coincide con lo persistido.');
$svc->materializarActividades($P1, $vid);
$filasOtraVez = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?',
    [$P1, $vid],
)->fetchColumn();
$assert($filasOtraVez === $filasMapa, 'Materializar dos veces no duplica (idempotente).');
// B1: el amarre ya no «lo llena A4» — lo resuelve `materializarActividades()` al escribir. Este
// proyecto de prueba no tiene cronograma, así que el `unique_id` queda NULL; lo que se exige es que
// no quede MUDO: sin `origen_amarre` no habría cómo distinguir «no hay frente» de «nunca se calculó»,
// que es exactamente cómo DAPORTO llegó a 820 filas vacías.
$mudas = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND unique_id IS NULL AND origen_amarre IS NOT NULL',
    [$P1],
)->fetchColumn();
$assert($mudas === 0, 'Sin cronograma el amarre queda NULL y sin marcar: no se inventa un origen.');

// --- A3.3 · Desempate por tipo de recurso -----------------------------------------------------
// Un MATERIAL se compra por lo que es, no por dónde se usa: la actividad deja de influir. En DAPORTO
// el mortero vive en 33 actividades de 9 oficios distintos y la dominante solo concentra el 36%.
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
     VALUES ('CONCRETO DISPERSO A3', 'CONCRETO DISPERSO A3', 'M3', 'X', 'MATERIAL', 1, 'test-a3', NOW())",
);
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
     VALUES (?, ?, 'CONCRETO DISPERSO A3', 'M3', 'CONCRETO DISPERSO A3', 'X', 1, 100, 1, ?, 'pendiente')",
    [$P1, $vid, $mid('CONCRETO DISPERSO A3', 'M3')],
);
// Se consume sobre todo en una actividad de mampostería: si la actividad mandara, acabaría ahí.
$db->query(
    "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
     VALUES (?, ?, ?, 'CONCRETO DISPERSO A3', 'X', 'M3', 1, 1, 10, 500000, 5000000, 0)",
    [$P1, $vid, $itemMuro],
);
$sug2 = $svc->sugerencias($P1);
$by2 = [];
foreach ($sug2['sugerencias'] as $s) { $by2[$s['descripcionNorm']] = $s; }
$sDisp = $by2['CONCRETO DISPERSO A3'] ?? null;
$assert($sDisp !== null && (int) $sDisp['paqueteId'] === $concretoId, 'MATERIAL: decide la descripción, no la actividad donde se consume.');

// En cambio la mano de obra sí depende del frente — pero si la actividad dominante concentra poco,
// elegirla es una moneda al aire: se marca confianza baja para que no se auto-asigne.
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
     VALUES ('M.O. REPARTIDA A3', 'M.O. REPARTIDA A3', 'M2', 'X', 'MANO DE OBRA', 1, 'test-a3', NOW())",
);
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
     VALUES (?, ?, 'M.O. REPARTIDA A3', 'M2', 'M.O. REPARTIDA A3', 'X', 2, 200, 2, ?, 'pendiente')",
    [$P1, $vid, $mid('M.O. REPARTIDA A3', 'M2')],
);
// 51% en el muro y 49% en el zócalo: ninguna actividad manda de verdad.
foreach ([[$itemMuro, 5100000], [$itemZocalo, 4900000]] as $par) {
    $db->query(
        "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
         VALUES (?, ?, ?, 'M.O. REPARTIDA A3', 'X', 'M2', 1, 1, 10, 100, ?, 0)",
        [$P1, $vid, $par[0], $par[1]],
    );
}
$sug3 = $svc->sugerencias($P1);
$by3 = [];
foreach ($sug3['sugerencias'] as $s) { $by3[$s['descripcionNorm']] = $s; }
$sRep = $by3['M.O. REPARTIDA A3'] ?? null;
$assert($sRep !== null, 'La mano de obra repartida sí recibe propuesta (no se abandona).');
$assert($sRep === null || $sRep['confianza'] === 'baja', 'Dominancia débil (<60%) ⇒ confianza baja: va a revisión, no se auto-asigna.');
$assert($sRep === null || str_contains(mb_strtolower($sRep['evidencia']), 'reparte'), 'La evidencia avisa de que el insumo se reparte entre actividades.');
$sGen = $by3['M.O. GENERICO PRUEBA A3'] ?? null;
$assert($sGen !== null && $sGen['confianza'] !== 'baja', 'Con actividad dominante clara la confianza no se degrada.');

// --- A3.3 · Cola larga: los oficios y familias que el motor no sabía colocar -------------------
// Mapeos decididos con el usuario sobre los 71 insumos que DAPORTO dejaba sin destino ($653M).
// Cada caso es un insumo real del presupuesto, con su tipo de recurso real.
$colaLarga = [
    // [descripción, unidad, tipo_recurso, paquete destino esperado]
    ['M.O. SOLADO A3', 'M2', 'MANO DE OBRA', 'M. de O CIMENTACIÓN SUPERFICIAL EN CONCRETO'],
    ['M.O. ANDEN EN CONCRETO A3', 'ML', 'MANO DE OBRA', 'M. de O URBANISMO (MUROS, ANDENES, ESCALAS, GRAMA, ETC)'],
    ['M.O. NIVELACION DEL TERRENO A3', 'M2', 'MANO DE OBRA', 'M. de O MOVIMIENTOS DE TIERRA (EXCAVACIONES Y RELLENOS)'],
    ['M.O. INSTALACION SANITARIO A3', 'UN', 'MANO DE OBRA', 'M. de O APARATOS SANITARIOS Y GRIFERÍA'],
    // Talón y rebanco cuelgan de «PISOS EN ZONAS PRIVADAS»: manda el subcapítulo, no el material.
    ['M.O. TALON EN CONCRETO A3', 'M', 'MANO DE OBRA', 'M. de O MORTEROS DE PISO'],
    ['M.O. REBANCO EN CONCRETO A3', 'M2', 'MANO DE OBRA', 'M. de O MORTEROS DE PISO'],
    // Sellantes de junta: NO son aditivos de concreto (ya nos costó un error antes).
    ['SIKAROD 7/8 A3', 'M', 'MATERIAL', 'Suministro JUNTA DE DILATACIÓN'],
    ['SISMOFLEX CORONA A3', 'KG', 'MATERIAL', 'Suministro JUNTA DE DILATACIÓN'],
    // Hidráulicos sueltos → a sus paquetes de siempre.
    ['TANQUE 18000 L EN FIBRA DE VIDRIO A3', 'UN', 'MATERIAL', 'Suministro TANQUES DE RESERVA DE AGUA'],
    ['LLAVE BOCAMANGUERA CON EXTENSION A3', 'UN', 'MATERIAL', 'Suministro APARATOS SANITARIOS Y GRIFERÍA'],
    // Familias nuevas.
    ['VIBRADORES DE CONCRETO COMPRA EQUIPO A3', 'UN', 'EQUIPO', 'Equipos y maquinaria de obra'],
    ['CANGURO APISONADOR COMPRA EQUIPO A3', 'UN', 'EQUIPO', 'Equipos y maquinaria de obra'],
    ['COMPUTADORES A3', 'UN', 'EQUIPO', 'Tecnología y software de obra'],
    ['SERVICIO SOFTWARE ASSEMBLE A3', 'MES', 'EQUIPO', 'Tecnología y software de obra'],
    ['ACARREOS A3', 'UN', 'TRANSPORTE', 'Transporte y acarreos'],
    ['PARTIDA PRESUPUESTAL PORTERIA A3', 'SG', 'SUBCONTRATO', 'Provisiones y partidas globales'],
    ['SUMINISTRO E INSTALACION DE ARBOLES A3', 'UN', 'SUBCONTRATO', 'Sum + Inst PAISAJISMO Y ZONAS VERDES'],
    // Destinos que ya existían en el catálogo y el motor no usaba.
    ['LOCALIZACION Y REPLANTEO A3', 'M2', 'SUBCONTRATO', 'Sum + Inst TOPOGRAFÍA'],
    ['M.O. LECHO FILTRANTE A3', 'M3', 'MANO DE OBRA', 'M. de O FILTROS'],
    ['M.O. INSTALACION DE CUNETAS A3', 'M', 'MANO DE OBRA', 'M. de O CUNETA TALUD'],
    ['SUMINISTRO E INSTALACION DE GRAMA A3', 'M2', 'SUBCONTRATO', 'Sum + Inst ENGRAMADO'],
];
foreach ($colaLarga as [$desc, $und, $tipo, $_]) {
    $db->query(
        "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
         VALUES (?, ?, ?, 'X', ?, 1, 'test-a3', NOW())",
        [$desc, $desc, $und, $tipo],
    );
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, 100, 1, ?, 'pendiente')",
        [$P1, $vid, $desc, $und, $desc, $mid($desc, $und)],
    );
}
$sugCola = $svc->sugerencias($P1);
$byCola = [];
foreach ($sugCola['sugerencias'] as $s) { $byCola[$s['descripcionNorm']] = $s; }
foreach ($colaLarga as [$desc, $und, $tipo, $destino]) {
    $s = $byCola[$desc] ?? null;
    $ok = $s !== null && mb_strtoupper($s['paqueteNombre']) === mb_strtoupper($destino);
    $assert($ok, sprintf('%s → %s%s', $desc, $destino, $ok ? '' : ' (dio: ' . ($s['paqueteNombre'] ?? 'sin propuesta') . ')'));
}

// --- A3.3 · Un material no cae en un paquete «a todo costo» ------------------------------------
// Si se contrata suministro + instalación, el material lo pone el contratista: que además figure
// como insumo del presupuesto asignado a ese mismo paquete es contarlo dos veces. La excepción
// existe para paquetes que sí absorben materiales por naturaleza (dotación), y va marcada.
$aTodoCostoId = (int) $svc->crearPaquete('TEST A3 Sum + Inst Cerrado', 'a_todo_costo', 'test-a3')['paquete']['id'];
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
     VALUES ('MATERIAL PARA CERRADO A3', 'MATERIAL PARA CERRADO A3', 'UN', 'X', 'MATERIAL', 1, 'test-a3', NOW())",
);
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
     VALUES (?, ?, 'MATERIAL PARA CERRADO A3', 'UN', 'MATERIAL PARA CERRADO A3', 'X', 1, 100, 1, ?, 'pendiente')",
    [$P1, $vid, $mid('MATERIAL PARA CERRADO A3', 'UN')],
);
$assert($svc->tipoRecursoAdmitido('MATERIAL', $aTodoCostoId) === false, 'Un MATERIAL no es admisible en un paquete a todo costo.');
$db->query('UPDATE general_paquetes_contratacion SET admite_materiales = 1 WHERE id = ?', [$aTodoCostoId]);
$assert($svc->tipoRecursoAdmitido('MATERIAL', $aTodoCostoId) === true, 'Con la excepción marcada en el paquete, sí se admite.');
$db->query('UPDATE general_paquetes_contratacion SET admite_materiales = 0 WHERE id = ?', [$aTodoCostoId]);
$assert($svc->tipoRecursoAdmitido('MANO DE OBRA', $aTodoCostoId) === true, 'La mano de obra sigue siendo admisible a todo costo.');
// Los paquetes de dotación absorben materiales por naturaleza: el seed los marca.
$dotacionId = (int) ($db->query("SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1", [\App\Services\Pdc\MaestroInsumosService::normalizar('Sum + Inst DOTACIÓN ZONAS COMUNES')])->fetchColumn() ?: 0);
$assert($dotacionId > 0 && $svc->tipoRecursoAdmitido('MATERIAL', $dotacionId) === true, 'Los paquetes de dotación admiten materiales (excepción sembrada).');

// --- A3.3 · Puente con las duraciones legacy (lo que habilita A4) ------------------------------
// El caso que motivó el puente: «Suministro CONCRETO» no encontraba la fila «CONCRETO» porque el
// catálogo legacy guarda los nombres sin prefijo de tipo.
$concretoDur = $svc->duracionesDePaquete($concretoId);
$assert($concretoDur !== null, 'Suministro CONCRETO ya encuentra su fila de duraciones.');
$assert($concretoDur !== null && $concretoDur['diasTotales'] > 0, 'La fila trae días de proceso.');
$assert($concretoDur !== null && array_key_exists('elaboracionPliegos', $concretoDur['pasos']), 'Las duraciones vienen desglosadas por paso.');
// Un paquete nuevo, sin equivalente legacy, no inventa duraciones: devuelve null y A4 decidirá.
$assert($svc->duracionesDePaquete($pisosId) === null, 'Un paquete sin fila legacy devuelve null (no se inventan días).');

// --- A3.4 · La regla sube por la cadena de ancestros ------------------------------------------
// Caso que lo motivó: «RESANES APARTAMENTO» no dice el oficio, pero su rama sí. Igual que
// «REBANCO COCINA», que no dice «piso» y cuelga del grupo «PISOS EN ZONAS PRIVADAS».
$nuevoInsumo = static function (string $desc, string $und, string $tipoRecurso, int $itemId, float $valor) use ($db, $P1, $vid, $mid): void {
    $db->query(
        "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
         VALUES (?, ?, ?, 'X', ?, 1, 'test-a3', NOW())",
        [$desc, $desc, $und, $tipoRecurso],
    );
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, ?, 1, ?, 'pendiente')",
        [$P1, $vid, $desc, $und, $desc, $valor, $mid($desc, $und)],
    );
    $db->query(
        "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
         VALUES (?, ?, ?, ?, 'X', ?, 1, 1, 1, ?, ?, 0)",
        [$P1, $vid, $itemId, $desc, $und, $valor, $valor],
    );
};

// El insumo calla Y su actividad también: el único que nombra el oficio es el GRUPO. Es el caso que
// motivó el cambio — hasta ahora la señal se perdía porque el motor solo miraba la actividad.
$item('01.20', '01', 2, 'subcapitulo', 'CUBIERTAS Y TERRAZAS');
$item('01.20.03', '01.20', 3, 'grupo', 'IMPERMEABILIZACION DE CUBIERTAS');
$itemMudoTotal = $item('01.20.03.01', '01.20.03', 4, 'actividad', 'TRABAJOS VARIOS ZONA A', 'M2', 30);
$nuevoInsumo('M.O. CUADRILLA TIPO 7 A3', 'M', 'MANO DE OBRA', $itemMudoTotal, 900000);

// Bajo COSTO INDIRECTO: no es un contrato de obra por más que la palabra suene a oficio.
$itemAdmin = $item('02.01.01.05', '02.01.01', 4, 'actividad', 'PAPELERIA Y UTILES DE OFICINA', 'MES', 12);
$nuevoInsumo('GASTO GENERICO INDIRECTO A3', 'MES', 'SUBCONTRATO', $itemAdmin, 400000);

// Un MATERIAL con regla propia se decide por su nombre, pase lo que pase en su rama.
$nuevoInsumo('CONCRETO CON RAMA AJENA A3', 'M3', 'MATERIAL', $itemMudoTotal, 700000);

// El caso que motivó el cambio: el insumo dice «RESANES», que no es ningún oficio del catálogo, y
// quien lo identifica es el grupo del que cuelga.
$item('01.16', '01', 2, 'subcapitulo', 'ACABADOS DE MUROS');
$item('01.16.02', '01.16', 3, 'grupo', 'ESTUCO Y CAL');
$itemEstuco = $item('01.16.02.04', '01.16.02', 4, 'actividad', 'DETALLADA PARA ENTREGA', 'UN', 10);
$nuevoInsumo('RESANES APARTAMENTO A3', 'UN', 'SUBCONTRATO', $itemEstuco, 800000);

$svc->materializarActividades($P1, $vid);
$sugA = $svc->sugerencias($P1);
$byA = [];
foreach ($sugA['sugerencias'] as $s) { $byA[$s['descripcionNorm']] = $s; }

$sReb = $byA['M.O. CUADRILLA TIPO 7 A3'] ?? null;
$assert($sReb !== null, 'El insumo cuya actividad calla igual recibe propuesta.');
$assert($sReb !== null && (int) $sReb['paqueteId'] !== 0, 'La cadena de ancestros aporta un destino.');
$assert($sReb !== null && str_contains(mb_strtoupper($sReb['paqueteNombre'] ?? ''), 'IMPERMEABILIZ'), 'El oficio sale del grupo, no de la actividad ni del insumo: ' . ($sReb['paqueteNombre'] ?? 'sin propuesta'));
$assert($sReb !== null && str_contains(mb_strtolower($sReb['evidencia']), 'grupo'), 'La evidencia dice de qué nivel salió, no un genérico «actividad padre».');
$assert($sReb !== null && $sReb['confianza'] === 'media', 'Un acierto en un ancestro vale igual que en la actividad: confianza media.');

$sInd = $byA['GASTO GENERICO INDIRECTO A3'] ?? null;
$assert($sInd !== null && in_array($sInd['capa'], ['indirectos', 'reglas'], true), 'Lo que cuelga de COSTO INDIRECTO no se propone como contrato de obra (capa: ' . ($sInd['capa'] ?? 'sin propuesta') . ', evidencia: ' . mb_substr($sInd['evidencia'] ?? '', 0, 90) . ').');
$assert($sInd !== null && (int) $sInd['paqueteId'] === $indirectosId, 'Va al bucket administrativo: ' . ($sInd['paqueteNombre'] ?? ''));

$sRes = $byA['RESANES APARTAMENTO A3'] ?? null;
$assert($sRes !== null && str_contains(mb_strtoupper($sRes['paqueteNombre'] ?? ''), 'ESTUCO'), 'RESANES lo resuelve su grupo «ESTUCO Y CAL», no una bolsa de presupuesto: ' . ($sRes['paqueteNombre'] ?? 'sin propuesta'));

$sMat = $byA['CONCRETO CON RAMA AJENA A3'] ?? null;
$assert($sMat !== null && (int) $sMat['paqueteId'] === $concretoId, 'Un MATERIAL con regla propia ignora la rama y va por su descripción.');
$assert($sMat !== null && $sMat['confianza'] === 'alta', 'Y conserva la confianza alta de la descripción.');

// La búsqueda NO llega al capítulo: «COSTO DIRECTO» no puede decidir ningún oficio.
$itemHuerfano = $item('01.99.01.01', '01.99.01', 4, 'actividad', 'ITEM SIN OFICIO RECONOCIBLE A3', 'UN', 1);
$item('01.99', '01', 2, 'subcapitulo', 'SIN OFICIO RECONOCIBLE');
$item('01.99.01', '01.99', 3, 'grupo', 'TAMPOCO AQUI');
$nuevoInsumo('MO SIN RAMA UTIL A3', 'UN', 'MANO DE OBRA', $itemHuerfano, 100000);
$db->query('DELETE FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?', [$P1, $vid]);
$svc->materializarActividades($P1, $vid);
$sugB = $svc->sugerencias($P1);
$byB = [];
foreach ($sugB['sugerencias'] as $s) { $byB[$s['descripcionNorm']] = $s; }
$sHuerf = $byB['MO SIN RAMA UTIL A3'] ?? null;
$assert($sHuerf === null || $sHuerf['capa'] !== 'reglas', 'Sin oficio en toda la rama, ninguna regla lo resuelve (el capítulo no cuenta).');

// --- A3.4 · Suministro y mano de obra van a paquetes distintos --------------------------------
// Doctrina de la dirección de obra: la carpintería son dos contratos, uno de fabricación y
// suministro (IVA pleno) y otro de instalación (IVA de servicios). El motor tiene que saber cuál
// de los dos le toca a cada insumo según su tipo de recurso.
$partidos = [
    ['SUMINISTRO PUERTA MADERA P22 A3', 'UN', 'MATERIAL', 'Suministro PUERTAS EN MADERA'],
    ['M.O. INSTALACION PUERTA MADERA A3', 'UN', 'MANO DE OBRA', 'M. de O CARPINTERÍA DE MADERA'],
    ['SUMINISTRO PUERTA METALICA PM9 A3', 'UN', 'MATERIAL', 'Suministro PUERTAS METÁLICAS'],
    ['EPOXICO ESTRUCTURAL A3', 'UN', 'MATERIAL', 'Suministro ANCLAJES'],
    ['CAMPANA EXTRACTORA A3', 'UN', 'MATERIAL', 'Suministro DOTACIÓN COCINAS Y LAVADEROS'],
    ['COMISION TOPOGRAFIA A3', 'MES', 'MANO DE OBRA', 'Sum + Inst TOPOGRAFÍA'],
    ['M.O. INSTALACION TOPELLANTAS A3', 'UN', 'MANO DE OBRA', 'M. de O TOPELLANTAS'],
    ['ALQUILER BUSETA PERSONAL A3', 'MES', 'TRANSPORTE', 'Alquiler de transporte de personal'],
    // A3.5 · Fusiones de la revisión en obra. La puerta de madera es producto de catálogo: aunque
    // el presupuesto la traiga como subcontrato, se compra, no se contrata a todo costo.
    ['SUM PUERTA MADERA SUBCONTRATO A3', 'UN', 'SUBCONTRATO', 'Suministro PUERTAS EN MADERA'],
    // «Pisos y enchapes son el mismo contrato»: el enchape de muro deja de tener paquete propio.
    ['M.O. ENCHAPE CERAMICO MURO A3', 'M2', 'MANO DE OBRA', 'M. de O INSTALACIÓN DE PISOS CERÁMICOS'],
    // El foso de ascensor lo hace el mismo impermeabilizador que el resto de la obra.
    ['IMPERMEABILIZACION FOSO DE ASCENSOR A3', 'M2', 'SUBCONTRATO', 'Sum + Inst IMPERMEABILIZACIONES'],
];
foreach ($partidos as [$desc, $und, $tipo, $_]) {
    $db->query(
        "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
         VALUES (?, ?, ?, 'X', ?, 1, 'test-a3', NOW())",
        [$desc, $desc, $und, $tipo],
    );
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, 100, 1, ?, 'pendiente')",
        [$P1, $vid, $desc, $und, $desc, $mid($desc, $und)],
    );
}
$sugP = $svc->sugerencias($P1);
$byP = [];
foreach ($sugP['sugerencias'] as $s) { $byP[$s['descripcionNorm']] = $s; }
foreach ($partidos as [$desc, $und, $tipo, $destino]) {
    $s = $byP[$desc] ?? null;
    $ok = $s !== null && mb_strtoupper($s['paqueteNombre']) === mb_strtoupper($destino);
    $assert($ok, sprintf('%s (%s) → %s%s', mb_substr($desc, 0, 34), $tipo, $destino, $ok ? '' : ' [dio: ' . ($s['paqueteNombre'] ?? 'sin propuesta') . ']'));
}

// El aseo permanente de obra es un gasto recurrente de nómina, no el hito de entrega.
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, tipo_recurso, activo, creado_por, created_at)
     VALUES ('ASEO PERMANENTE DE OBRA A3', 'ASEO PERMANENTE DE OBRA A3', 'MES', 'X', 'SUBCONTRATO', 1, 'test-a3', NOW())",
);
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
     VALUES (?, ?, 'ASEO PERMANENTE DE OBRA A3', 'MES', 'ASEO PERMANENTE DE OBRA A3', 'X', 1, 100, 1, ?, 'pendiente')",
    [$P1, $vid, $mid('ASEO PERMANENTE DE OBRA A3', 'MES')],
);
$sugAseo = $svc->sugerencias($P1);
$byAseo = [];
foreach ($sugAseo['sugerencias'] as $s) { $byAseo[$s['descripcionNorm']] = $s; }
$sAseo = $byAseo['ASEO PERMANENTE DE OBRA A3'] ?? null;
$assert($sAseo !== null && (int) $sAseo['paqueteId'] === $indirectosId, 'El aseo permanente es un indirecto, no el contrato de aseo final: ' . ($sAseo['paqueteNombre'] ?? 'sin propuesta'));

// --- Ola 2 · Equipo alquilado / comprado / sin clasificar --------------------------------------
// `tiposCompatibles()` es un `match` cuyo `default` significa «no sé filtrar, no filtro». Partir el
// valor «EQUIPO» sin nombrar los nuevos ahí los mandaría al default y pasarían a ser candidatos de
// cualquier paquete, mano de obra incluida — la regresión de A3.2 en sitio nuevo, y ningún test la
// atrapaba. Se prueba a través de `tipoRecursoAdmitido()`, que es la puerta pública.
$suministroId = (int) $db->query(
    "SELECT id FROM general_paquetes_contratacion WHERE tipo_negociacion = 'suministro' AND activo = 1
       AND modalidad_contratacion = 'contrato' LIMIT 1",
)->fetchColumn();
$manoObraId = (int) $db->query(
    "SELECT id FROM general_paquetes_contratacion WHERE tipo_negociacion = 'mano_obra' AND activo = 1
       AND modalidad_contratacion = 'contrato' LIMIT 1",
)->fetchColumn();
$assert($suministroId > 0 && $manoObraId > 0, 'Hay un paquete de suministro y uno de mano de obra en el catálogo.');

$EQ_ALQ = \App\Services\Pdc\TipoRecursoEquipo::ALQUILADO;
$EQ_COM = \App\Services\Pdc\TipoRecursoEquipo::COMPRADO;
$EQ_SIN = \App\Services\Pdc\TipoRecursoEquipo::SIN_CLASIFICAR;

// Punto 4 de la condición de hecho: un equipo ALQUILADO no es candidato de un paquete de COMPRA.
// Alquilar no es comprar; si además figurara como candidato de suministro, contabilidad volvería a
// tener las dos cosas en la misma bolsa, que es el problema que este trabajo viene a resolver.
$assert($svc->tipoRecursoAdmitido($EQ_ALQ, $suministroId) === false, 'Un equipo ALQUILADO no es admisible en un paquete de suministro (no se compra lo que se alquila).');
$assert($svc->tipoRecursoAdmitido($EQ_COM, $suministroId) === true, 'Un equipo COMPRADO sí es admisible en un paquete de suministro.');

// «Sin clasificar» se comporta exactamente como el «EQUIPO» de hoy: es lo que permite usar el
// módulo con el tapón puesto.
$assert($svc->tipoRecursoAdmitido($EQ_SIN, $suministroId) === true, 'Sin clasificar se comporta como el EQUIPO de hoy: admisible en suministro.');
$assert($svc->tipoRecursoAdmitido($EQ_SIN, $manoObraId) === false, 'Sin clasificar NO cae en mano de obra: sigue filtrando, no cayó al default.');
$assert($svc->tipoRecursoAdmitido($EQ_ALQ, $manoObraId) === false, 'Un equipo alquilado tampoco cae en mano de obra.');
$assert($svc->tipoRecursoAdmitido($EQ_COM, $manoObraId) === false, 'Un equipo comprado tampoco cae en mano de obra.');

// El genérico sigue nombrado a propósito: SINCO lo emite en cada carga, y entre la carga y la
// clasificación el motor tiene que seguir filtrándolo igual que antes.
$assert($svc->tipoRecursoAdmitido('EQUIPO', $suministroId) === true, 'El genérico EQUIPO conserva su comportamiento (SINCO lo sigue emitiendo).');
$assert($svc->tipoRecursoAdmitido('EQUIPO', $manoObraId) === false, 'Y sigue sin caer en mano de obra.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
