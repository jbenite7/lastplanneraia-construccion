<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_VER_A = 999901;
const PDC_VER_B = 999902;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_VER_A, PDC_VER_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

// v2: mismas actividades pero un insumo cambia de precio (contenido distinto → nueva versión).
$fixtureV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01','PRELIMINARES','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01','CAMPAMENTO','01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01','INSTALACIONES','01.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01.01','CAMPAMENTO 18M2','01.01.01','M2',18,'',102,'PI_V2','APU-001',null,null,null,null,'',''],
        ['','TEJA DE ZINC','','M2',null,'',102,'PI_V2','',1.05,1.2,19,30000,'MAT-CUBIERTAS',''],
        ['','AYUDANTE','','HC',null,'',102,'PI_V2','',8.0,0.5,null,9500,'MANO DE OBRA',''],
        ['02','ESTRUCTURA','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01','CONCRETOS','02','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01','LOSAS','02.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01.01','LOSA MACIZA E=12','02.01.01','M3',40,'',102,'PI_V2','APU-002',null,null,null,null,'',''],
        ['','CONCRETO 4000PSI','','M3',null,'',102,'PI_V2','',1.0,1.05,19,620000,'MAT-CONCRETOS',''],
        ['','SERVICIO BOMBEO','','M3',null,'',102,'PI_V2','',1.0,1.0,null,28000,'EQUIPOS',''],
    ]);
};

// v3: idéntico a fixtureV2 salvo el tipo_insumo de SERVICIO BOMBEO (EQUIPOS → EQUIPO-MAYOR).
// Solo cambia la categoría; cantidad/valor idénticos → debe considerarse contenido distinto
// (la categoría alimenta el maestro A2 y no puede perderse en el anti-duplicado).
$fixtureV2Recat = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01','PRELIMINARES','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01','CAMPAMENTO','01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01','INSTALACIONES','01.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01.01','CAMPAMENTO 18M2','01.01.01','M2',18,'',102,'PI_V2','APU-001',null,null,null,null,'',''],
        ['','TEJA DE ZINC','','M2',null,'',102,'PI_V2','',1.05,1.2,19,30000,'MAT-CUBIERTAS',''],
        ['','AYUDANTE','','HC',null,'',102,'PI_V2','',8.0,0.5,null,9500,'MANO DE OBRA',''],
        ['02','ESTRUCTURA','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01','CONCRETOS','02','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01','LOSAS','02.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01.01','LOSA MACIZA E=12','02.01.01','M3',40,'',102,'PI_V2','APU-002',null,null,null,null,'',''],
        ['','CONCRETO 4000PSI','','M3',null,'',102,'PI_V2','',1.0,1.05,19,620000,'MAT-CONCRETOS',''],
        ['','SERVICIO BOMBEO','','M3',null,'',102,'PI_V2','',1.0,1.0,null,28000,'EQUIPO-MAYOR',''],
    ]);
};

echo "=== PDC v2: versionamiento inteligente ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-ver-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// --- Hash de contenido: estable ante reordenamiento de filas ---
$items = [
    ['codigo' => '02', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
    ['codigo' => '01', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
];
$itemsRev = array_reverse($items);
$insumos = [
    ['codigo_actividad' => '01.01.01.01', 'descripcion' => 'Teja de Zinc', 'unidad' => 'M2', 'cantidad_total' => 21.6, 'valor_total' => 540000],
    ['codigo_actividad' => '02.01.01.01', 'descripcion' => 'Concreto 4000PSI', 'unidad' => 'M3', 'cantidad_total' => 42, 'valor_total' => 26040000],
];
$insumosRev = array_reverse($insumos);
$assert($service->hashContenido($items, $insumos) === $service->hashContenido($itemsRev, $insumosRev), 'hashContenido estable ante reordenamiento.');
$assert($service->hashContenido($items, $insumos) !== $service->hashContenido($items, array_slice($insumos, 0, 1)), 'hashContenido distingue contenidos distintos.');
// Recategorizar un insumo (mismo valor/cantidad, distinto tipo_insumo) debe dar hash distinto:
// tipo_insumo alimenta el maestro A2 y no puede perderse en el anti-duplicado por contenido.
$insumoBase = ['codigo_actividad' => '01.01.01.01', 'descripcion' => 'Teja de Zinc', 'unidad' => 'M2', 'cantidad_total' => 21.6, 'valor_total' => 540000];
$insumoMat = [$insumoBase + ['tipo_insumo' => 'MAT-CUBIERTAS']];
$insumoEqp = [$insumoBase + ['tipo_insumo' => 'EQUIPOS']];
$assert($service->hashContenido($items, $insumoMat) !== $service->hashContenido($items, $insumoEqp), 'hashContenido distingue por tipo_insumo (recategorización).');

// --- Primer cargue → Versión 1 ---
$v1 = sys_get_temp_dir() . '/pdc_ver_v1.xlsx';
pdcFixturePresupuestoValido($v1);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_VER_A, 'tester');
$assert($p1['sinCambios'] === false && $p1['versionActiva'] === null, 'Primer preview: sin activa, sin "sin cambios".');
$c1 = $service->confirmar($p1['importToken'], PDC_VER_A);
$assert($c1['ok'] === true && $c1['versionNumero'] === 1 && $c1['versionIdAnterior'] === null && $c1['sinCambios'] === false, 'Confirmar 1 → Versión 1, sin anterior.');

// --- Re-cargue idéntico → sin cambios, NO crea versión ---
$v1b = sys_get_temp_dir() . '/pdc_ver_v1b.xlsx';
pdcFixturePresupuestoValido($v1b);
$p2 = $service->previewDesdeArchivo($v1b, 'v1b.xlsx', PDC_VER_A, 'tester');
$assert($p2['sinCambios'] === true && (int) $p2['versionActiva']['numero'] === 1, 'Preview idéntico avisa "sin cambios" (Versión 1 activa).');
$c2 = $service->confirmar($p2['importToken'], PDC_VER_A);
$assert($c2['ok'] === true && $c2['sinCambios'] === true && $c2['versionId'] === $c1['versionId'], 'Confirmar idéntico NO crea versión (retorna la activa).');
$total = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_VER_A])->fetchColumn();
$assert($total === 1, 'Sigue habiendo 1 sola versión tras el re-cargue idéntico.');

// --- Cargue con contenido distinto → Versión 2, con anterior ---
$v2 = sys_get_temp_dir() . '/pdc_ver_v2.xlsx';
$fixtureV2($v2);
$p3 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_VER_A, 'tester');
$assert($p3['sinCambios'] === false, 'Preview con contenido distinto: no "sin cambios".');
$c3 = $service->confirmar($p3['importToken'], PDC_VER_A);
$assert($c3['ok'] === true && $c3['versionNumero'] === 2 && $c3['versionIdAnterior'] === $c1['versionId'] && $c3['sinCambios'] === false, 'Confirmar distinto → Versión 2 con versionIdAnterior = V1.');

// --- Re-cargue que cambia SOLO el tipo_insumo de un insumo → Versión 3 ---
$v3 = sys_get_temp_dir() . '/pdc_ver_v3.xlsx';
$fixtureV2Recat($v3);
$p4 = $service->previewDesdeArchivo($v3, 'v3.xlsx', PDC_VER_A, 'tester');
$assert($p4['sinCambios'] === false, 'Preview con solo tipo_insumo distinto: no "sin cambios".');
$c4 = $service->confirmar($p4['importToken'], PDC_VER_A);
$assert($c4['ok'] === true && $c4['versionNumero'] === 3 && $c4['versionIdAnterior'] === $c3['versionId'] && $c4['sinCambios'] === false, 'Recategorizar un insumo (solo tipo_insumo) → Versión 3 con versionIdAnterior = V2.');

// --- versiones() incluye versionNumero; aislamiento por proyecto ---
$vers = $service->versiones(PDC_VER_A);
$assert(isset($vers[0]['versionNumero']) && $vers[0]['versionNumero'] === 3, 'versiones() trae versionNumero (la más reciente = 3).');
$assert($service->versiones(PDC_VER_B) === [], 'Aislamiento: proyecto B sin versiones.');

// --- Elegir a mano la versión oficial (f15-f19 de la revisión de UX) ---
// Hasta ahora la marca «Activa» se la llevaba siempre la última importación, sin forma de volver
// a una anterior: si alguien cargaba un presupuesto equivocado, el proyecto entero quedaba
// colgando de él.
$idV1 = $c1['versionId'];
$idV3 = $c4['versionId'];

$r = $service->activar(PDC_VER_A, $idV1);
$assert(($r['ok'] ?? false) === true, 'Activar: responde ok. Dio ' . var_export($r, true));

$activas = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_VER_A])->fetchColumn();
$assert($activas === 1, 'Activar: queda exactamente una versión activa. Dio ' . $activas);

$cual = (int) $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_VER_A])->fetchColumn();
$assert($cual === $idV1, 'Activar: la activa es la que se eligió, no la última cargada. Dio ' . $cual);

// f17: volver a una versión NUEVA sigue siendo posible; esto no es un camino de una sola dirección.
$service->activar(PDC_VER_A, $idV3);
$cual = (int) $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_VER_A])->fetchColumn();
$assert($cual === $idV3, 'Activar: se puede volver a una versión más reciente. Dio ' . $cual);

// Activar la que ya está activa es un no-op, no un error ni una violación del índice único.
$rMismo = $service->activar(PDC_VER_A, $idV3);
$assert(($rMismo['ok'] ?? false) === true, 'Activar la que ya estaba activa no rompe. Dio ' . var_export($rMismo, true));

// Una versión de OTRO proyecto no se puede activar aquí.
$rAjena = $service->activar(PDC_VER_B, $idV1);
$assert(($rAjena['ok'] ?? true) === false && ($rAjena['code'] ?? '') === 'VERSION_INVALIDA',
    'Activar: una versión de otro proyecto se rechaza. Dio ' . var_export($rAjena, true));

// f16: el aviso cuenta lo ÚNICO que está atado a una versión concreta — los vínculos del maestro.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1000, 1), (?, ?, ?, ?, ?, ?, 1, 2000, 1)',
    [PDC_VER_A, $idV3, 'TEJA DE ZINC', 'M2', 'TEJA DE ZINC', 'MAT-CUBIERTAS',
     PDC_VER_A, $idV3, 'AYUDANTE', 'HC', 'AYUDANTE', 'MANO DE OBRA'],
);
$impacto = $service->impactoDeCambiarVersion(PDC_VER_A);
$assert(($impacto['vinculosAfectados'] ?? -1) === 2,
    'Impacto: cuenta los vínculos del maestro hechos sobre la versión que se abandona. Dio ' . var_export($impacto['vinculosAfectados'] ?? null, true));
$assert(($impacto['versionActual']['id'] ?? null) === $idV3,
    'Impacto: dice de qué versión se está saliendo. Dio ' . var_export($impacto['versionActual'] ?? null, true));

// Un proyecto sin versión activa no rompe el aviso: informa cero y sin versión.
$impactoVacio = $service->impactoDeCambiarVersion(PDC_VER_B);
// Ojo con `??` aquí: null es justo lo que se espera, y `?? 'x'` lo convertiría en 'x'.
$assert(($impactoVacio['vinculosAfectados'] ?? -1) === 0 && $impactoVacio['versionActual'] === null,
    'Impacto: sin versión activa informa cero, no revienta. Dio ' . var_export($impactoVacio, true));

$db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [PDC_VER_A]);

foreach ([$v1, $v1b, $v2, $v3] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
