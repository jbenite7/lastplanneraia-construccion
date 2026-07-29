<?php
// tests/test_pdc_reset_service.php — PdcResetService sobre MySQL real (proyectos 999911/999912).
// Cubre: expansión de etapas aguas abajo, borrado selectivo que respeta lo de aguas arriba,
// cascada desde pdc_presupuesto_versiones, respaldo .sql, y las dos invariantes que más importan:
// los catálogos globales no se tocan y otro proyecto no pierde filas.

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PdcResetService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$servicio = new PdcResetService($db);
$P1 = 999911; $P2 = 999912;
$MARCA = 'test-reset';

$contarTabla = static fn (string $tabla, int $pid): int => (int) $db->query(
    "SELECT COUNT(*) FROM `{$tabla}` WHERE project_id = ?",
    [$pid],
)->fetchColumn();

// Cleanup FK-safe: dependientes → versiones → catálogos marcados.
$limpiar = static function () use ($db, $P1, $P2, $MARCA): void {
    foreach ([
        'pdc_plan_paso', 'pdc_plan_paquete', 'pdc_proyecto_pasos',
        'pdc_insumo_paquete', 'pdc_paquete_frente', 'pdc_rama_frente',
        'pdc_correcciones_motor', 'pdc_correcciones_frente',
        'pdc_insumo_actividades', 'pdc_insumo_vinculos',
        'pdc_presupuesto_apu_insumos', 'pdc_presupuesto_items', 'pdc_presupuesto_versiones',
    ] as $tabla) {
        $db->query("DELETE FROM `{$tabla}` WHERE project_id IN (?, ?)", [$P1, $P2]);
    }
    $db->query('DELETE FROM general_paquetes_contratacion WHERE creado_por = ?', [$MARCA]);
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [$MARCA]);
};
$limpiar();

// --- Semilla -----------------------------------------------------------------------------------
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
     VALUES ('Cemento gris reset', 'CEMENTO GRIS RESET', 'KG', 'MAT', 1, ?, NOW())",
    [$MARCA],
);
$maestroId = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, activo, creado_por, created_at)
     VALUES ('Paquete reset', 'PAQUETE RESET', 1, ?, NOW())",
    [$MARCA],
);
$paqueteId = (int) $db->lastInsertId();

$pasoId = (int) $db->query('SELECT id FROM general_pasos_contratacion ORDER BY orden_default LIMIT 1')->fetchColumn();

/** Siembra un PDC completo (presupuesto → vínculos → paquetes → plan) para un proyecto. */
$sembrar = static function (int $pid) use ($db, $maestroId, $paqueteId, $pasoId): void {
    $db->query(
        "INSERT INTO pdc_presupuesto_versiones
            (project_id, version_label, version_numero, archivo_nombre, archivo_hash, activa, importado_por, created_at)
         VALUES (?, 'v-reset', 1, 'reset.xlsx', ?, 1, 'test-reset', NOW())",
        [$pid, str_repeat((string) ($pid % 10), 64)],
    );
    $versionId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, nivel, tipo_fila, descripcion)
         VALUES (?, ?, '1', 1, 'actividad', 'Actividad reset')",
        [$pid, $versionId],
    );
    $itemId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO pdc_presupuesto_apu_insumos
            (project_id, version_id, item_id, descripcion, unidad, cantidad_total, valor_total)
         VALUES (?, ?, ?, 'Cemento gris reset', 'KG', 1, 100)",
        [$pid, $versionId, $itemId],
    );

    $db->query(
        "INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_norm, unidad, descripcion_original, maestro_id, estado)
         VALUES (?, ?, 'CEMENTO GRIS RESET', 'KG', 'Cemento gris reset', ?, 'confirmado')",
        [$pid, $versionId, $maestroId],
    );

    $db->query(
        "INSERT INTO pdc_insumo_actividades
            (project_id, version_id, descripcion_norm, unidad, item_id, codigo, actividad)
         VALUES (?, ?, 'CEMENTO GRIS RESET', 'KG', ?, '1', 'Actividad reset')",
        [$pid, $versionId, $itemId],
    );

    $db->query(
        "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, origen, updated_at)
         VALUES (?, 'CEMENTO GRIS RESET', 'KG', ?, 'humano', NOW())",
        [$pid, $paqueteId],
    );

    $db->query(
        "INSERT INTO pdc_plan_paquete (project_id, paquete_id, updated_at) VALUES (?, ?, NOW())",
        [$pid, $paqueteId],
    );

    $db->query(
        "INSERT INTO pdc_plan_paso (project_id, paquete_id, paso, orden, dias, paso_id)
         VALUES (?, ?, 'reset', 10, 5, ?)",
        [$pid, $paqueteId, $pasoId],
    );
};

$sembrar($P1);
$sembrar($P2);

$maestroAntes = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos')->fetchColumn();
$paquetesAntes = (int) $db->query('SELECT COUNT(*) FROM general_paquetes_contratacion')->fetchColumn();

// --- expandir() --------------------------------------------------------------------------------
$assert($servicio->expandir(['plan']) === ['plan'], 'expandir(plan) no arrastra nada');
$assert($servicio->expandir(['paquetes']) === ['plan', 'paquetes'], 'expandir(paquetes) arrastra plan');
$assert(
    $servicio->expandir(['presupuesto']) === ['plan', 'paquetes', 'vinculos', 'presupuesto'],
    'expandir(presupuesto) arrastra las cuatro etapas',
);
$assert(
    $servicio->expandir(['presupuesto', 'plan']) === ['plan', 'paquetes', 'vinculos', 'presupuesto'],
    'expandir() se queda con la etapa más profunda',
);

$rechazada = false;
try { $servicio->expandir(['inventada']); } catch (InvalidArgumentException) { $rechazada = true; }
$assert($rechazada, 'expandir() rechaza una etapa desconocida');

$rechazadaVacia = false;
try { $servicio->expandir([]); } catch (InvalidArgumentException) { $rechazadaVacia = true; }
$assert($rechazadaVacia, 'expandir() rechaza una selección vacía');

// --- contar() ----------------------------------------------------------------------------------
$conteos = $servicio->contar($P1);
$assert($conteos['etapas']['plan']['total'] === 2, 'contar() ve 2 filas en la etapa plan');
$assert($conteos['etapas']['presupuesto']['total'] === 1, 'contar() ve 1 versión de presupuesto');
$assert($conteos['cascada']['pdc_presupuesto_items'] === 1, 'contar() reporta los items en cascada');
$assert($conteos['catalogos']['general_maestro_insumos'] === $maestroAntes, 'contar() reporta el maestro');

// --- respaldar() -------------------------------------------------------------------------------
$dirTmp = sys_get_temp_dir() . '/pdc-reset-test-' . getmypid();
$ruta = $servicio->respaldar($P1, ['presupuesto'], $dirTmp);
$sqlRespaldo = (string) file_get_contents($ruta);
$assert(is_file($ruta) && filesize($ruta) > 0, 'respaldar() escribe un .sql no vacío');
$assert(
    str_contains($sqlRespaldo, 'INSERT INTO `pdc_presupuesto_versiones`')
    && str_contains($sqlRespaldo, 'INSERT INTO `pdc_presupuesto_items`')
    && str_contains($sqlRespaldo, 'INSERT INTO `pdc_plan_paso`'),
    'el respaldo incluye las tablas seleccionadas y las de cascada',
);

// --- limpiar() solo el plan --------------------------------------------------------------------
$servicio->limpiar($P1, ['plan']);
$assert($contarTabla('pdc_plan_paso', $P1) === 0, 'limpiar(plan) borra pdc_plan_paso');
$assert($contarTabla('pdc_plan_paquete', $P1) === 0, 'limpiar(plan) borra pdc_plan_paquete');
$assert($contarTabla('pdc_insumo_paquete', $P1) === 1, 'limpiar(plan) NO toca los paquetes (aguas arriba)');
$assert($contarTabla('pdc_presupuesto_versiones', $P1) === 1, 'limpiar(plan) NO toca el presupuesto');

// --- limpiar() paquetes ------------------------------------------------------------------------
$servicio->limpiar($P1, ['paquetes']);
$assert($contarTabla('pdc_insumo_paquete', $P1) === 0, 'limpiar(paquetes) borra las asignaciones');
$assert($contarTabla('pdc_insumo_vinculos', $P1) === 1, 'limpiar(paquetes) NO toca los vínculos');

// --- limpiar() presupuesto y su cascada ----------------------------------------------------------
$resultado = $servicio->limpiar($P1, ['presupuesto']);
$assert($contarTabla('pdc_presupuesto_versiones', $P1) === 0, 'limpiar(presupuesto) borra las versiones');
$assert($contarTabla('pdc_presupuesto_items', $P1) === 0, 'los items caen en cascada');
$assert($contarTabla('pdc_presupuesto_apu_insumos', $P1) === 0, 'los insumos de APU caen en cascada');
$assert($contarTabla('pdc_insumo_vinculos', $P1) === 0, 'los vínculos caen en cascada');
$assert($contarTabla('pdc_insumo_actividades', $P1) === 0, 'las actividades del insumo se borran');
$assert($resultado['conteos']['total'] === 0, 'el resultado reporta el proyecto en cero');

// --- Invariantes ---------------------------------------------------------------------------------
$maestroDespues = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos')->fetchColumn();
$paquetesDespues = (int) $db->query('SELECT COUNT(*) FROM general_paquetes_contratacion')->fetchColumn();
$assert($maestroDespues === $maestroAntes, 'general_maestro_insumos queda intacto');
$assert($paquetesDespues === $paquetesAntes, 'general_paquetes_contratacion queda intacto');

$assert($contarTabla('pdc_presupuesto_versiones', $P2) === 1, 'el otro proyecto conserva su presupuesto');
$assert($contarTabla('pdc_plan_paso', $P2) === 1, 'el otro proyecto conserva su plan');
$assert($contarTabla('pdc_insumo_paquete', $P2) === 1, 'el otro proyecto conserva sus paquetes');

// --- Limpieza del test ---------------------------------------------------------------------------
$limpiar();
foreach (glob($dirTmp . '/*.sql') ?: [] as $archivo) { unlink($archivo); }
@rmdir($dirTmp);

if ($failures !== []) {
    fwrite(STDERR, "\n" . count($failures) . " fallo(s).\n");
    exit(1);
}
fwrite(STDOUT, "\nTodo verde.\n");
