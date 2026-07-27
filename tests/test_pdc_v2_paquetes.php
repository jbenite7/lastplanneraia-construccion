<?php
// tests/test_pdc_v2_paquetes.php — PaquetesService sobre MySQL real (proyectos 999901/999902).
// Cubre: crearPaquete (dedupe), catalogo, asignar/omitir/desasignar (un insumo un destino),
// insumosDeVersion (filtros + omitido + tipoRecurso) y resumen (cobertura = asignados+omitidos).

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

// Cleanup FK-safe por marca de test: asignaciones → paquetes → vínculos → versiones → maestro.
$limpiar = static function () use ($db, $P1, $P2): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_correcciones_motor WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a3'");
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_apu_insumos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_maestro_insumos WHERE creado_por = 'test-a3'");
};
$limpiar();

// Maestro: 1 insumo con agrupación + tipo_recurso (para verificar el passthrough en insumosDeVersion).
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, activo, creado_por, created_at)
     VALUES ('Piso ceramico 30x30', 'PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 'PISOS Y ENCHAPES', 'MATERIAL', 1, 'test-a3', NOW())",
);
$maestroCeramicoId = (int) $db->query(
    "SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'PISO CERAMICO 30X30' AND unidad = 'M2'",
)->fetchColumn();

// Fixture: versión activa + insumos únicos consolidados (vínculos) para P1.
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-A3', 1, 'test-a3.xlsx', REPEAT('a', 64), 2, 4, 1000, 1, 'test-a3', NOW())",
    [$P1],
);
$vid1 = (int) $db->lastInsertId();
$insumosP1 = [
    ['PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 100, 2500000, $maestroCeramicoId],
    ['PISO PORCELANATO 60X60', 'M2', 'MAT-ACABADOS', 50, 2400000, null],
    ['ACERO DE REFUERZO 60000PSI', 'KG', 'MAT-ACEROS', 800, 3360000, null],
    ['AYUDANTE DE OBRA', 'HC', 'MANO DE OBRA', 40, 380000, null],
];
foreach ($insumosP1 as $i) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'pendiente')",
        [$P1, $vid1, $i[0], $i[1], $i[0], $i[2], $i[3], $i[4], $i[5]],
    );
}

// Fixture para actividadesPorInsumo: 2 actividades cuyo APU usa PISO CERAMICO 30X30.
foreach ([['01.01.01.01', 'MURO EN LADRILLO'], ['01.02.03.04', 'ENCHAPE DE PISOS']] as $it) {
    $db->query(
        "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad)
         VALUES (?, ?, ?, NULL, 4, 'actividad', ?, 'M2', 100)",
        [$P1, $vid1, $it[0], $it[1]],
    );
    $itemId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO pdc_presupuesto_apu_insumos (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
         VALUES (?, ?, ?, 'PISO CERAMICO 30X30', 'MAT-ACABADOS', 'M2', 1, 1, 60, 25000, 1500000, 0)",
        [$P1, $vid1, $itemId],
    );
}

echo "=== PDC v2: paquetes de contratacion ===\n";
$svc = new PaquetesService($db);

// --- crearPaquete ---
$r = $svc->crearPaquete('TEST A3 Pisos', 'suministro', 'test-a3');
$assert($r['ok'] === true && $r['paquete']['id'] > 0 && $r['paquete']['existente'] === 0, 'Crear paquete nuevo.');
$pisosId = (int) $r['paquete']['id'];

$dup = $svc->crearPaquete('  test a3 PISOS ', 'mano_obra', 'test-a3');
$assert($dup['ok'] === true && (int) $dup['paquete']['id'] === $pisosId && $dup['paquete']['existente'] === 1, 'Duplicado por nombre_norm devuelve el existente.');
$tipo = $db->query('SELECT tipo_negociacion FROM general_paquetes_contratacion WHERE id = ?', [$pisosId])->fetchColumn();
$assert($tipo === 'suministro', 'El duplicado no pisa el tipo del existente.');

$bad = $svc->crearPaquete('TEST A3 X', 'tipo_falso', 'test-a3');
$assert($bad['ok'] === false && $bad['code'] === 'PAQUETE_INVALIDO', 'Tipo inválido rechazado.');
$vacio = $svc->crearPaquete('   ', 'suministro', 'test-a3');
$assert($vacio['ok'] === false && $vacio['code'] === 'PAQUETE_INVALIDO', 'Nombre vacío rechazado.');

// --- catalogo ---
$acerosCrear = $svc->crearPaquete('TEST A3 Aceros', 'a_todo_costo', 'test-a3');
$acerosId = (int) $acerosCrear['paquete']['id'];
$cat = $svc->catalogo('TEST A3');
$assert(count($cat) === 2, 'Catálogo filtrado por búsqueda: 2 paquetes.');
$assert($cat[0]['nombre'] === 'TEST A3 Aceros', 'Orden alfabético.');
$assert($cat[0]['insumosGlobal'] === 0, 'Sin asignaciones aún: insumosGlobal = 0.');
$catEsc = $svc->catalogo('TEST A3 100%');
$assert($catEsc === [], 'Comodines LIKE escapados en la búsqueda.');

// --- asignar ---
$a1 = $svc->asignar($P1, [
    ['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2'],
    ['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2'],
], $pisosId, 'test-a3');
$assert($a1['ok'] === true && $a1['asignados'] === 2, 'Asignación masiva: 2 insumos a Pisos.');

// Reasignar mueve (no duplica): el porcelanato pasa a Aceros.
$a2 = $svc->asignar($P1, [['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2']], $acerosId, 'test-a3');
$assert($a2['ok'] === true, 'Reasignación aceptada.');
$filas = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [$P1])->fetchColumn();
$assert($filas === 2, 'Un insumo, un destino: reasignar no duplica filas.');
$pq = $db->query("SELECT paquete_id FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = 'PISO PORCELANATO 60X60' AND unidad = 'M2'", [$P1])->fetchColumn();
$assert((int) $pq === $acerosId, 'El insumo quedó en el paquete nuevo.');

// Paquete inexistente → PAQUETE_INVALIDO.
$aX = $svc->asignar($P1, [['descripcionNorm' => 'AYUDANTE DE OBRA', 'unidad' => 'HC']], 99999999, 'test-a3');
$assert($aX['ok'] === false && $aX['code'] === 'PAQUETE_INVALIDO', 'Paquete inexistente rechazado.');

// Elementos malformados se descartan sin explotar (lección A2: escalares).
$aM = $svc->asignar($P1, [['descripcionNorm' => ['no' => 'array'], 'unidad' => 'M2'], 'basura'], $pisosId, 'test-a3');
$assert($aM['ok'] === true && $aM['asignados'] === 0, 'Elementos malformados descartados (0 asignados).');

// --- omitir ---
$o1 = $svc->omitir($P1, [['descripcionNorm' => 'ACERO DE REFUERZO 60000PSI', 'unidad' => 'KG']], 'test-a3');
$assert($o1['ok'] === true && $o1['omitidos'] === 1, 'Omitir 1 insumo.');
$rowOmit = $db->query("SELECT paquete_id, omitido FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = 'ACERO DE REFUERZO 60000PSI' AND unidad = 'KG'", [$P1])->fetch(PDO::FETCH_ASSOC);
$assert($rowOmit['paquete_id'] === null && (int) $rowOmit['omitido'] === 1, 'Omitido: paquete_id NULL, omitido=1 (invariante).');

// Omitir algo asignado lo MUEVE a omitido (no coexisten): el cerámico pasa de Pisos a omitido.
$svc->omitir($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], 'test-a3');
$rowCer = $db->query("SELECT paquete_id, omitido FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = 'PISO CERAMICO 30X30' AND unidad = 'M2'", [$P1])->fetch(PDO::FETCH_ASSOC);
$assert($rowCer['paquete_id'] === null && (int) $rowCer['omitido'] === 1, 'Omitir un asignado lo mueve a omitido (quita el paquete).');

// Asignar algo omitido limpia la omisión: el cerámico vuelve a Pisos.
$svc->asignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], $pisosId, 'test-a3');
$rowCer2 = $db->query("SELECT paquete_id, omitido FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = 'PISO CERAMICO 30X30' AND unidad = 'M2'", [$P1])->fetch(PDO::FETCH_ASSOC);
$assert((int) $rowCer2['paquete_id'] === $pisosId && (int) $rowCer2['omitido'] === 0, 'Asignar un omitido limpia la omisión.');

// Aislamiento por proyecto.
$filasP2 = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [$P2])->fetchColumn();
$assert($filasP2 === 0, 'Aislamiento por project_id.');

// --- desasignar ---
$d1 = $svc->desasignar($P1, [['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2']]);
$assert($d1['ok'] === true && $d1['desasignados'] === 1, 'Desasignar elimina la fila (vuelve a sin asignar).');

// Estado al llegar aquí: cerámico→Pisos (asignado); acero→omitido; porcelanato y ayudante→sin asignar.

// --- insumosDeVersion + resumen ---
$iv = $svc->insumosDeVersion($P1, 'todos');
$assert($iv !== null && count($iv['insumos']) === 4, 'insumosDeVersion: 4 únicos de la versión activa.');
$assert((int) $iv['version']['id'] === $vid1, 'Versión activa resuelta.');
$ceramico = array_values(array_filter($iv['insumos'], fn ($i) => $i['descripcionNorm'] === 'PISO CERAMICO 30X30'))[0];
$assert((int) $ceramico['paqueteId'] === $pisosId && $ceramico['paqueteNombre'] === 'TEST A3 Pisos', 'Insumo asignado trae su paquete.');
$assert($ceramico['agrupacion'] === 'PISOS Y ENCHAPES' && $ceramico['tipoRecurso'] === 'MATERIAL', 'insumosDeVersion expone agrupacion y tipoRecurso del maestro.');
$assert((int) $ceramico['omitido'] === 0, 'Cerámico no está omitido.');

$sinA = $svc->insumosDeVersion($P1, 'sin_asignar');
$assert(count($sinA['insumos']) === 2, 'Filtro sin_asignar: 2 (porcelanato, ayudante).');
$conA = $svc->insumosDeVersion($P1, 'asignados');
$assert(count($conA['insumos']) === 1, 'Filtro asignados: 1 (cerámico).');
$omit = $svc->insumosDeVersion($P1, 'omitidos');
$assert(count($omit['insumos']) === 1 && $omit['insumos'][0]['descripcionNorm'] === 'ACERO DE REFUERZO 60000PSI', 'Filtro omitidos: 1 (acero).');
$assert($svc->insumosDeVersion($P2, 'todos') === null, 'Proyecto sin presupuesto → null (NO_VERSION).');

$res = $svc->resumen($P1);
$assert($res['total'] === 4 && $res['asignados'] === 1 && $res['omitidos'] === 1, 'Resumen: 1 asignado + 1 omitido de 4.');
$assert(abs($res['cobertura'] - 50.0) < 0.01, 'Cobertura 50% (asignados + omitidos).');
$assert(count($res['porPaquete']) === 1 && $res['porPaquete'][0]['insumos'] === 1, 'porPaquete: Pisos con 1 insumo.');
$assert(abs((float) $res['porPaquete'][0]['subtotal'] - 2500000) < 0.01, 'Subtotal del paquete = valor consolidado del insumo.');

// --- actividadesPorInsumo (tooltip: qué actividades requieren cada insumo) ---
$act = $svc->actividadesPorInsumo($P1);
$assert($act !== null && isset($act['mapa']['PISO CERAMICO 30X30@@M2']), 'actividadesPorInsumo indexa por NORMA@@UNIDAD.');
$ceram = $act['mapa']['PISO CERAMICO 30X30@@M2'];
$assert($ceram['total'] === 2 && count($ceram['items']) === 2, 'PISO CERAMICO lo requieren 2 actividades.');
$codigos = array_column($ceram['items'], 'codigo');
$assert(in_array('01.01.01.01', $codigos, true) && in_array('01.02.03.04', $codigos, true), 'El tooltip trae los códigos de actividad (amarre al cronograma A4).');
$assert($svc->actividadesPorInsumo($P2) === null, 'Proyecto sin versión → null.');

// --- Herencia en re-import ---
$db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ?', [$P1]);
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-A3-2', 2, 'test-a3b.xlsx', REPEAT('b', 64), 1, 2, 500, 1, 'test-a3', NOW())",
    [$P1],
);
$vid2 = (int) $db->lastInsertId();
foreach ([['PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 60, 1500000], ['INSUMO NUEVO A3', 'UN', 'OTROS', 5, 50000]] as $i) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'pendiente')",
        [$P1, $vid2, $i[0], $i[1], $i[0], $i[2], $i[3], $i[4]],
    );
}
$iv2 = $svc->insumosDeVersion($P1, 'todos');
$assert(count($iv2['insumos']) === 2, 'Nueva versión: 2 insumos.');
$cer2 = array_values(array_filter($iv2['insumos'], fn ($i) => $i['descripcionNorm'] === 'PISO CERAMICO 30X30'))[0];
$assert((int) $cer2['paqueteId'] === $pisosId, 'HERENCIA: el insumo reaparecido conserva su paquete en el re-import.');
$nuevo = array_values(array_filter($iv2['insumos'], fn ($i) => $i['descripcionNorm'] === 'INSUMO NUEVO A3'))[0];
$assert($nuevo['paqueteId'] === null && (int) $nuevo['omitido'] === 0, 'Insumo nuevo queda sin asignar.');

// --- A3.3 · Procedencia de la asignación -----------------------------------------------------
// Al aplicar el sembrado se perdía de qué capa salió cada fila: sin eso no se puede auditar el
// motor ni medir su acierto. Ahora la asignación guarda origen, confianza y evidencia.
$svc->desasignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']]);
$svc->asignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], $pisosId, 'test-a3', [
    'origen' => 'reglas',
    'confianza' => 'alta',
    'evidencia' => 'Regla «CERAMIC» sobre la descripción del insumo.',
]);
$proc = $db->query(
    'SELECT origen, confianza, evidencia, confirmado_humano FROM pdc_insumo_paquete
     WHERE project_id = ? AND descripcion_norm = ? AND unidad = ?',
    [$P1, 'PISO CERAMICO 30X30', 'M2'],
)->fetch(\PDO::FETCH_ASSOC);
$assert($proc !== false && $proc['origen'] === 'reglas', 'La asignación guarda la capa que la produjo.');
$assert($proc['confianza'] === 'alta', 'La asignación guarda la confianza.');
$assert(str_contains((string) $proc['evidencia'], 'CERAMIC'), 'La asignación guarda la evidencia legible.');
$assert((int) $proc['confirmado_humano'] === 0, 'Lo que viene del motor NO nace confirmado por un humano.');

// Sin procedencia explícita, la asignación es un acto humano: es lo que hace la grilla y el asistente.
$svc->asignar($P1, [['descripcionNorm' => 'INSUMO NUEVO A3', 'unidad' => 'UN']], $pisosId, 'test-a3');
$manual = $db->query(
    'SELECT origen, confianza, confirmado_humano FROM pdc_insumo_paquete
     WHERE project_id = ? AND descripcion_norm = ? AND unidad = ?',
    [$P1, 'INSUMO NUEVO A3', 'UN'],
)->fetch(\PDO::FETCH_ASSOC);
$assert($manual['origen'] === 'humano' && (int) $manual['confirmado_humano'] === 1, 'Asignar sin procedencia = decisión humana confirmada.');
$assert($manual['confianza'] === null, 'Una decisión humana no lleva confianza: no es una apuesta.');

// Aceptar una sugerencia tal cual es un ACIERTO del motor, no una decisión desde cero: la fila
// conserva la capa que la propuso y además queda confirmada (el humano la revisó y la avaló).
$svc->desasignar($P1, [['descripcionNorm' => 'ACERO DE REFUERZO 60000PSI', 'unidad' => 'KG']]);
$svc->asignar($P1, [['descripcionNorm' => 'ACERO DE REFUERZO 60000PSI', 'unidad' => 'KG']], $pisosId, 'test-a3', [
    'origen' => 'tokens', 'confianza' => 'media', 'evidencia' => 'Tokens comunes con el paquete.', 'confirmado' => true,
]);
$aceptada = $db->query(
    'SELECT origen, confirmado_humano FROM pdc_insumo_paquete
     WHERE project_id = ? AND descripcion_norm = ? AND unidad = ?',
    [$P1, 'ACERO DE REFUERZO 60000PSI', 'KG'],
)->fetch(\PDO::FETCH_ASSOC);
$assert($aceptada['origen'] === 'tokens' && (int) $aceptada['confirmado_humano'] === 1, 'Sugerencia aceptada: conserva la capa Y queda confirmada.');

// --- A3.3 · Correcciones del motor (alimentan la tasa de acierto) -----------------------------
// Cambiar a mano un destino que propuso el motor es la señal más valiosa que tenemos: se registra.
$otroId = $svc->crearPaquete('TEST A3 Otro Destino', 'suministro', 'test-a3')['paquete']['id'];
$svc->asignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], (int) $otroId, 'test-a3');
$corr = $db->query(
    'SELECT paquete_sugerido, paquete_elegido, capa_sugerida FROM pdc_correcciones_motor
     WHERE project_id = ? AND descripcion_norm = ?',
    [$P1, 'PISO CERAMICO 30X30'],
)->fetch(\PDO::FETCH_ASSOC);
$assert($corr !== false, 'Corregir a mano un destino del motor deja registro.');
$assert((int) $corr['paquete_sugerido'] === $pisosId && (int) $corr['paquete_elegido'] === (int) $otroId, 'La corrección guarda de dónde a dónde se movió.');
$assert($corr['capa_sugerida'] === 'reglas', 'La corrección recuerda qué capa se equivocó.');

$corrHumana = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_correcciones_motor WHERE project_id = ? AND descripcion_norm = ?',
    [$P1, 'INSUMO NUEVO A3'],
)->fetchColumn();
$assert($corrHumana === 0, 'Reasignar algo que ya era humano no cuenta como error del motor.');

// --- A3.3 · Tres indicadores en el resumen ----------------------------------------------------
// Un solo número miente: 82% por conteo y 98% por valor son la misma base. Y ninguno dice si el
// motor acierta — eso lo dicen las correcciones.
$res3 = $svc->resumen($P1);
$assert(isset($res3['coberturaValor']), 'El resumen trae cobertura por valor, no solo por conteo.');
$assert($res3['coberturaValor'] >= 0 && $res3['coberturaValor'] <= 100, 'La cobertura por valor es un porcentaje.');
$assert(isset($res3['acierto']), 'El resumen trae la tasa de acierto del motor.');
$assert(array_key_exists('sugerenciasAplicadas', $res3['acierto']) && array_key_exists('correcciones', $res3['acierto']), 'La tasa de acierto expone su base de cálculo.');
$assert((int) $res3['acierto']['correcciones'] === 1, 'La corrección del cerámico cuenta contra el motor.');
$assert((int) $res3['acierto']['sugerenciasAplicadas'] === 2, 'La base son las decisiones del motor: la aceptada + la corregida.');
$assert(abs((float) $res3['acierto']['tasa'] - 50.0) < 0.01, 'Una aceptada y una corregida = 50% de acierto.');
$assert($res3['acierto']['tasa'] === null || ($res3['acierto']['tasa'] >= 0 && $res3['acierto']['tasa'] <= 100), 'La tasa es null (sin datos) o un porcentaje.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
