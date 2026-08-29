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
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

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
$C = 999952;
$HOY = '2026-07-30';
$context = $db->dataScope();
$contextSnapshot = $context->current();
if ($contextSnapshot !== null) {
    $context->clear();
}
$runner = new SystemScopeRunner($context);

$limpiar = static function () use ($runner, $db, $A, $B, $C): void {
    $runner->run('test:pdc-torre:cleanup', static function () use ($db, $A, $B, $C): void {
        foreach ([$A, $B, $C] as $p) {
            $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_subpaquete WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$p]);
            $db->query('DELETE FROM programa WHERE project_id = ?', [$p]);
        }
        $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-b3'");
    });
};
try {
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

[$paqA, $paqB, $paqBExtra, $paqCZeta, $paqCAlfa] = $runner->run(
    'test:pdc-torre:seed',
    static function () use ($nuevoPaquete, $planPaquete, $planPaso, $db, $A, $B, $C): array {
        $paqA = $nuevoPaquete('TEST B3 999950 A');
        $paqB = $nuevoPaquete('TEST B3 999951 B');
        $paqBExtra = $nuevoPaquete('TEST B3 999951 EXTRA');
        // ZETA se inserta primero para que su id sea menor: el contrato observable exige ordenar
        // por nombre, no por el id interno del paquete.
        $paqCZeta = $nuevoPaquete('TEST B3 999952 ZETA');
        $paqCAlfa = $nuevoPaquete('TEST B3 999952 ALFA');

        $planPaquete($A, $paqA, 0);
        $planPaso($A, $paqA, 0, 1, 'Pliegos', '2026-07-29');
        $planPaso($A, $paqA, 0, 2, 'Propuestas', '2026-08-09');

        foreach ([1, 2] as $lote) {
            $db->query(
                'INSERT INTO pdc_subpaquete
                    (project_id, paquete_id, nombre, modalidad_contratacion, es_resto, orden, creado_por, updated_at)
                 VALUES (?, ?, ?, "contrato", 0, ?, "test-b3", NOW())',
                [$B, $paqB, "Lote {$lote}", $lote],
            );
            $sub = (int) $db->lastInsertId();
            $planPaquete($B, $paqB, $sub);
            $planPaso($B, $paqB, $sub, 1, 'Pliegos', '2026-07-28');
        }

        foreach ([$paqCZeta, $paqCAlfa] as $paqueteC) {
            $planPaquete($C, $paqueteC, 0);
            $planPaso($C, $paqueteC, 0, 1, 'Pliegos', '2026-07-29');
        }

        $versiones = [];
        foreach ([$A, $B, $C] as $projectId) {
            $db->query(
                "INSERT INTO pdc_presupuesto_versiones
                    (project_id, version_label, version_numero, archivo_nombre, archivo_hash,
                     total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
                 VALUES (?, ?, 1, ?, ?, 1, 4, 100, 1, 'test-b3', NOW())",
                [
                    $projectId,
                    "TEST B3 {$projectId}",
                    "test-b3-{$projectId}.xlsx",
                    hash('sha256', "test-b3-{$projectId}"),
                ],
            );
            $versiones[$projectId] = (int) $db->lastInsertId();
        }

        $insumos = [
            $A => [
                ['uno', 25.0, $paqA, false],
                ['dos', 75.0, null, false],
            ],
            $B => [
                ['uno', 10.0, $paqB, false],
                ['dos', 20.0, $paqB, false],
                ['tres', 30.0, null, true],
                ['cuatro', 40.0, null, false],
            ],
            $C => [
                ['uno', 40.0, $paqCZeta, false],
                ['dos', 60.0, $paqCAlfa, false],
            ],
        ];
        foreach ($insumos as $projectId => $rows) {
            foreach ($rows as [$suffix, $value, $packageId, $omitted]) {
                $normalized = "test b3 {$projectId} insumo {$suffix}";
                $db->query(
                    "INSERT INTO pdc_insumo_vinculos
                        (project_id, version_id, descripcion_norm, unidad, descripcion_original,
                         tipo_insumo, cantidad_total, valor_total, apariciones, estado)
                     VALUES (?, ?, ?, 'und', ?, 'MATERIAL', 1, ?, 1, 'confirmado')",
                    [$projectId, $versiones[$projectId], $normalized, strtoupper($normalized), $value],
                );
                if ($packageId === null && !$omitted) {
                    continue;
                }
                $db->query(
                    "INSERT INTO pdc_insumo_paquete
                        (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
                     VALUES (?, ?, 'und', ?, ?, 'test-b3', NOW())",
                    [$projectId, $normalized, $packageId, $omitted ? 1 : 0],
                );
            }
        }

        $schedule = [
            [$A, 51, 995001, $paqA, '2026-07-01', '2026-07-02'],
            [$B, 52, 995101, $paqB, '2026-07-03', '2026-07-04'],
            [$C, 53, 995201, $paqCZeta, '2026-07-06', '2026-07-07'],
        ];
        foreach ($schedule as [$projectId, $week, $uniqueId, $packageId, $savedDate, $currentDate]) {
            $db->query(
                'INSERT INTO semanas_activas
                    (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
                 VALUES (?, ?, ?, ?, ?)',
                [$projectId, $projectId, $week, $currentDate, $currentDate],
            );
            $db->query(
                'INSERT INTO programa
                    (project_id, Consecutivo, unique_id, Id, Actividad, Titulo, Fecha_Inicio)
                 VALUES (?, ?, ?, ?, ?, 1, ?)',
                [$projectId, $projectId, $uniqueId, "{$projectId}.1", "TEST B3 {$projectId} FRENTE", $currentDate],
            );
            $db->query(
                'INSERT INTO programa_consolidado
                    (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)',
                [$projectId, $projectId, $week, $uniqueId, $projectId, "{$projectId}.1", "TEST B3 {$projectId} FRENTE", $currentDate],
            );
            $db->query(
                "INSERT INTO pdc_paquete_frente
                    (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla, semana_origen,
                     origen, evidencia, confirmado_humano, asignado_por, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'humano', 'test-b3', 1, 'test-b3', NOW())",
                [$projectId, $packageId, $uniqueId, "TEST B3 {$projectId} FRENTE", $savedDate, $week],
            );
        }

        foreach ([
            [$B, $paqBExtra, 995102, 52, '2026-07-05'],
            [$C, $paqCAlfa, 995202, 53, '2026-07-08'],
        ] as [$projectId, $packageId, $uniqueId, $week, $savedDate]) {
            $db->query(
                "INSERT INTO pdc_paquete_frente
                    (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla, semana_origen,
                     origen, evidencia, confirmado_humano, asignado_por, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'humano', 'test-b3', 1, 'test-b3', NOW())",
                [$projectId, $packageId, $uniqueId, "TEST B3 {$projectId} AUSENTE", $savedDate, $week],
            );
        }

        return [$paqA, $paqB, $paqBExtra, $paqCZeta, $paqCAlfa];
    },
);

// --- El agregado multi-obra ------------------------------------------------------------------
$svc = new SeguimientoService($db);
$scopeAB = new MultiProjectScope([$A, $B], 'fixture-pdc-torre', 'R', 'test:test_pdc_v2_torre_control:agregado');
$scopeA = new MultiProjectScope([$A], 'fixture-pdc-torre', 'R', 'test:test_pdc_v2_torre_control:detalle-a');
$scopeB = new MultiProjectScope([$B], 'fixture-pdc-torre', 'R', 'test:test_pdc_v2_torre_control:detalle-b');
$scopeC = new MultiProjectScope([$C], 'fixture-pdc-torre', 'R', 'test:test_pdc_v2_torre_control:detalle-c');
$fixtureC = $runner->run('test:pdc-torre:oracle-c', static function () use ($db, $C): array {
    $counts = [];
    foreach ([
        'pdc_presupuesto_versiones',
        'pdc_insumo_vinculos',
        'pdc_insumo_paquete',
        'semanas_activas',
        'programa_consolidado',
        'pdc_paquete_frente',
    ] as $table) {
        $counts[$table] = (int) $db->query(
            "SELECT COUNT(*) FROM {$table} WHERE project_id = ?",
            [$C],
        )->fetchColumn();
    }
    return $counts;
});
$assert(
    $fixtureC === [
        'pdc_presupuesto_versiones' => 1,
        'pdc_insumo_vinculos'       => 2,
        'pdc_insumo_paquete'        => 2,
        'semanas_activas'           => 1,
        'programa_consolidado'      => 1,
        'pdc_paquete_frente'        => 2,
    ],
    'la obra C tiene fixture positivo demostrable en las seis entradas reales',
);
$agg = $svc->vencimientosAgregados($scopeAB, $HOY);

$assert($agg['hoy'] === $HOY, 'el agregado devuelve la fecha de corte que se le pasó');
$assert(($agg['por_obra'][$A]['conteos']['vencido'] ?? -1) === 1, 'obra A: un paso vencido');
$assert(($agg['por_obra'][$A]['conteos']['sem2'] ?? -1) === 1, 'obra A: el paso a 10 días cae en sem2');
$assert(($agg['por_obra'][$A]['destinos'] ?? -1) === 1, 'obra A: un solo destino');

// Decisión 6 del spec: el paquete partido en dos lotes cuenta DOS destinos, no uno.
$assert(($agg['por_obra'][$B]['destinos'] ?? -1) === 2, 'obra B: el paquete partido cuenta dos destinos');
$assert(($agg['por_obra'][$B]['conteos']['vencido'] ?? -1) === 2, 'obra B: dos pasos vencidos, uno por lote');

$assert(($agg['totales']['vencido'] ?? -1) === 3, 'los totales suman las dos obras');

// Punto 3 de la condición de hecho: Torre y módulo coinciden para la misma obra el mismo día.
$context->bind(new ProjectScope($A, 'fixture-pdc-torre', 'R'));
try {
    $modulo = $svc->vencimientos($A, [], $HOY);
} finally {
    $context->clear();
}
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
$detalle = $svc->detalleDestinos($scopeA, $HOY);
$assert(count($detalle) === 2, 'el detalle de la obra A trae sus dos pasos pendientes');
$assert(isset($detalle[0]['paquete'], $detalle[0]['estado']), 'cada fila del detalle trae paquete y estado');
$assert($detalle[0]['estado'] === 'vencido', 'el detalle viene ordenado por fecha: primero lo vencido');
$assert(!array_key_exists('proveedor', $detalle[0]), 'el detalle NO trae proveedor');

$detalleB = $svc->detalleDestinos($scopeB, $HOY);
$assert(($detalleB[0]['lote'] ?? null) !== null, 'el detalle de un paquete partido nombra su lote');

$detalleC = $svc->detalleDestinos($scopeC, $HOY);
$assert(
    array_column($detalleC, 'paquete') === ['TEST B3 999952 ALFA', 'TEST B3 999952 ZETA'],
    'el detalle desempata por nombre de paquete y no por id interno',
);

// Los agregados BI conservan los oráculos legacy single-project, pero nunca crean autoridad desde
// esos enteros. C queda expresamente fuera del scope y no puede aparecer en ningún mapa.
$cobertura = $svc->coberturaPorProyecto($scopeAB);
$desactualizados = $svc->paquetesDesactualizadosPorProyecto($scopeAB);
$assert(
    ($cobertura[$A] ?? null) === ['cobertura' => 50.0, 'coberturaValor' => 25.0],
    'obra A: cobertura positiva de 50.0% y 25.0% por valor',
);
$assert(
    ($cobertura[$B] ?? null) === ['cobertura' => 75.0, 'coberturaValor' => 60.0],
    'obra B: cobertura distinguible de 75.0% y 60.0% por valor',
);
$assert(($desactualizados[$A] ?? null) === 1, 'obra A: un paquete desactualizado real');
$assert(($desactualizados[$B] ?? null) === 2, 'obra B: dos paquetes desactualizados distinguibles');
$paquetesLegacy = new \App\Services\Pdc\PaquetesService($db);
foreach ([$A, $B] as $projectId) {
    $context->bind(new ProjectScope($projectId, 'fixture-pdc-torre', 'R'));
    try {
        $legacyCoverage = $paquetesLegacy->resumen($projectId) ?? ['cobertura' => 0.0, 'coberturaValor' => 0.0];
        $legacyDesactualizados = count($svc->paquetesDesactualizados($projectId));
    } finally {
        $context->clear();
    }
    $assert(
        ($cobertura[$projectId] ?? null) === [
            'cobertura' => (float) $legacyCoverage['cobertura'],
            'coberturaValor' => (float) $legacyCoverage['coberturaValor'],
        ],
        "cobertura multiproyecto conserva el resultado legacy de la obra {$projectId}",
    );
    $assert(
        ($desactualizados[$projectId] ?? -1) === $legacyDesactualizados,
        "desactualizados multiproyecto conserva el resultado legacy de la obra {$projectId}",
    );
}
$assert(!isset($cobertura[$C], $desactualizados[$C], $agg['por_obra'][$C]), 'la obra C no aparece fuera del scope A/B');

// --- El informe de la Torre ya no lee el PDC viejo -------------------------------------------
$ct = new \App\Services\ControlTowerService($db);
$brief = $ct->getBrief($scopeAB, 'pdc', '1', 'A');

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

} finally {
    if ($context->current() !== null) {
        $context->clear();
    }
    try {
        $limpiar();
    } finally {
        if ($contextSnapshot !== null) {
            $context->bind($contextSnapshot);
        }
    }
}
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
