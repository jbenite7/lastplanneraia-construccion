<?php
// tests/test_pdc_v2_torre_control.php — Fase B3 sobre MySQL real (proyectos 999950 y 999951).
//
// Prueba la condición de hecho del spec `2026-07-29-b3-torre-control-pdc-design.md`:
// el agregado multi-obra, que sus números coincidan con los de la pestaña del módulo para la
// misma obra y el mismo día, y que la unidad de conteo sea el destino (paquete + lote).
//
// La aserción que más importa es la de coincidencia: si el agregado y vencimientos() dejan de
// clasificar igual, tiene que fallar aquí y no en la pantalla de un gerente.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) {
        fwrite(STDOUT, "PASS: {$m}\n");
        return;
    }
    $failures[] = $m;
    fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$A = 999950;
$B = 999951;
$HOY = '2026-07-30';

$limpiar = static function () use ($db, $A, $B): void {
    foreach ([$A, $B] as $p) {
        $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$p]);
        $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$p]);
        $db->query('DELETE FROM pdc_subpaquete WHERE project_id = ?', [$p]);
    }
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-b3'");
};
$limpiar();

// --- Fixture ---------------------------------------------------------------------------------
// Obra A: un paquete sin partir, dos pasos pendientes (uno vencido ayer, uno a 10 días).
// Obra B: un paquete partido en dos lotes, un paso vencido en cada uno.
$nuevoPaquete = static function (string $nombre) use ($db): int {
    $db->query(
        "INSERT INTO general_paquetes_contratacion
            (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, admite_materiales, activo, creado_por, created_at)
         VALUES (?, ?, 'suministro', 'contrato', 1, 1, 'test-b3', NOW())",
        [$nombre, strtolower($nombre)],
    );

    return (int) $db->lastInsertId();
};

$planPaquete = static function (int $p, int $paq, int $sub) use ($db): void {
    $db->query(
        'INSERT INTO pdc_plan_paquete
            (project_id, paquete_id, subpaquete_id, duracion_provisional, responsable_user_id,
             responsable_asignado_por, calculado_por, updated_at)
         VALUES (?, ?, ?, 0, NULL, "", "test-b3", NOW())',
        [$p, $paq, $sub],
    );
};

$planPaso = static function (int $p, int $paq, int $sub, int $orden, string $paso, ?string $fin) use ($db): void {
    $db->query(
        'INSERT INTO pdc_plan_paso
            (project_id, paquete_id, subpaquete_id, orden, paso, dias, fecha_fin, fecha_real, registrado_por)
         VALUES (?, ?, ?, ?, ?, 1, ?, NULL, "test-b3")',
        [$p, $paq, $sub, $orden, $paso, $fin],
    );
};

$paqA = $nuevoPaquete('TEST B3 A');
$paqB = $nuevoPaquete('TEST B3 B');

$planPaquete($A, $paqA, 0);
$planPaso($A, $paqA, 0, 1, 'Pliegos', '2026-07-29');     // vencido
$planPaso($A, $paqA, 0, 2, 'Propuestas', '2026-08-09');  // a 10 días → sem2

foreach ([1, 2] as $lote) {
    $db->query(
        'INSERT INTO pdc_subpaquete
            (project_id, paquete_id, nombre, modalidad_contratacion, es_resto, orden, creado_por, updated_at)
         VALUES (?, ?, ?, "contrato", 0, ?, "test-b3", NOW())',
        [$B, $paqB, "Lote {$lote}", $lote],
    );
    $sub = (int) $db->lastInsertId();
    $planPaquete($B, $paqB, $sub);
    $planPaso($B, $paqB, $sub, 1, 'Pliegos', '2026-07-28'); // vencido
}

// --- El agregado multi-obra ------------------------------------------------------------------
$svc = new SeguimientoService($db);
$agg = $svc->vencimientosAgregados([$A, $B], $HOY);

$assert($agg['hoy'] === $HOY, 'el agregado devuelve la fecha de corte que se le pasó');
$assert(($agg['por_obra'][$A]['conteos']['vencido'] ?? -1) === 1, 'obra A: un paso vencido');
$assert(($agg['por_obra'][$A]['conteos']['sem2'] ?? -1) === 1, 'obra A: el paso a 10 días cae en sem2');
$assert(($agg['por_obra'][$A]['destinos'] ?? -1) === 1, 'obra A: un solo destino');

// Decisión 6 del spec: el paquete partido en dos lotes cuenta DOS destinos, no uno.
$assert(($agg['por_obra'][$B]['destinos'] ?? -1) === 2, 'obra B: el paquete partido cuenta dos destinos');
$assert(($agg['por_obra'][$B]['conteos']['vencido'] ?? -1) === 2, 'obra B: dos pasos vencidos, uno por lote');

$assert(($agg['totales']['vencido'] ?? -1) === 3, 'los totales suman las dos obras');

// Punto 3 de la condición de hecho: Torre y módulo coinciden para la misma obra el mismo día.
$modulo = $svc->vencimientos($A, [], $HOY);
$assert(
    $modulo['conteos'] === $agg['por_obra'][$A]['conteos'],
    'los conteos de la Torre coinciden exactamente con los de la pestaña del módulo',
);

// --- Avance por paso y carga por responsable ---------------------------------------------------
$assert(($agg['por_paso']['Pliegos']['pendientes'] ?? -1) === 3, 'avance por paso: tres pasos «Pliegos» pendientes entre las dos obras');
$assert(($agg['por_paso']['Pliegos']['vencidos'] ?? -1) === 3, 'avance por paso: los tres están vencidos');
$assert(($agg['por_paso']['Propuestas']['vencidos'] ?? -1) === 0, 'avance por paso: «Propuestas» no está vencido');
$assert(isset($agg['por_responsable'][0]), 'carga por responsable: los pasos sin responsable se agrupan aparte');
$assert(($agg['por_responsable'][0]['pendientes'] ?? -1) === 4, 'carga por responsable: los cuatro pasos del fixture no tienen responsable');

// --- El drill-down al paquete ------------------------------------------------------------------
$detalle = $svc->detalleDestinos([$A], $HOY);
$assert(count($detalle) === 2, 'el detalle de la obra A trae sus dos pasos pendientes');
$assert(isset($detalle[0]['paquete'], $detalle[0]['estado']), 'cada fila del detalle trae paquete y estado');
$assert($detalle[0]['estado'] === 'vencido', 'el detalle viene ordenado por fecha: primero lo vencido');
$assert(!array_key_exists('proveedor', $detalle[0]), 'el detalle NO trae proveedor');

$detalleB = $svc->detalleDestinos([$B], $HOY);
$assert(($detalleB[0]['lote'] ?? null) !== null, 'el detalle de un paquete partido nombra su lote');

// --- El informe de la Torre ya no lee el PDC viejo -------------------------------------------
$ct = new \App\Services\ControlTowerService($db);
$brief = $ct->getBrief('pdc', [$A, $B], '1', 'A');

$assert($brief['respuesta'] === 'BIEN', 'el brief responde BIEN');
$assert(count($brief['scorecard']) > 0, 'el scorecard trae indicadores');
$assert($brief['raw_row_count'] === 2, 'el brief trae una fila por obra');

$json = json_encode($brief, JSON_UNESCAPED_UNICODE);
$assert(stripos($json, 'subcontratoPaquete') === false, 'el brief ya no expone columnas del PDC viejo');
$assert(stripos($json, 'bi_pdc_general') === false, 'el lineage ya no apunta a la tabla del PDC viejo');

// Punto 5 de la condición de hecho: el proveedor no sale de la Torre.
foreach (['proveedor', 'subcontratista'] as $prohibido) {
    $assert(stripos($json, $prohibido) === false, "el brief no expone «{$prohibido}»");
}

// --- El scorecard responde las preguntas del comité -------------------------------------------
$nombresKpi = array_map(static fn($k) => (string) ($k['kpi'] ?? $k['name'] ?? ''), $brief['scorecard']);
$hay = static fn(string $frag): bool => $nombresKpi !== array_filter(
    $nombresKpi,
    static fn($n) => stripos($n, $frag) === false,
);

$assert($hay('Cobertura'), 'el scorecard trae cobertura');
$assert($hay('valor'), 'la cobertura por valor aparece junto a la de conteo');
$assert($hay('Vencid'), 'el scorecard trae vencidos');
$assert($hay('sin mirar'), 'el scorecard dice cuántos paquetes no está mirando');

$assert(($brief['pdc_breakdown']['por_paso']['Pliegos']['pendientes'] ?? -1) === 3, 'el brief expone el avance por paso');
$assert(
    ($brief['pdc_breakdown']['por_responsable'][0]['nombre'] ?? '') === 'Sin responsable',
    'el brief expone la carga por responsable, con lo no asignado a la vista',
);
$assert(is_array($brief['pdc_items'] ?? null), 'el brief lleva las filas por obra para el rótulo de fecha');
$assert(($brief['pdc_items'][0]['hoy'] ?? '') !== '', 'las filas traen la fecha de corte del servidor');
// El lineage es una LISTA de métricas, no un mapa: el plan asumió la forma equivocada.
$assert(
    ($brief['lineage'][0]['grain'] ?? '') === 'project_id + paquete_id + subpaquete_id (destino)',
    'el lineage declara el grano por destino',
);
$assert(
    stripos(json_encode($brief['lineage'], JSON_UNESCAPED_UNICODE), 'listo_para_iniciar') === false,
    'el lineage ya no describe el campo del PDC viejo',
);

$limpiar();
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
