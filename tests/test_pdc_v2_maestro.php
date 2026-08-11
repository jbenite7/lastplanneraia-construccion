<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_M_PROJECT_A = 999901;
const PDC_M_PROJECT_B = 999902;
const PDC_M_MARCA = 'test-a2';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_M_PROJECT_A, PDC_M_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$pid]);
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA]);

    // El e2e (pdc-v2-maestro.spec.mjs) puebla el catálogo GLOBAL general_maestro_insumos
    // con estos nombres del fixture compartido bajo creado_por='Test Admin' (no PDC_M_MARCA).
    // El cold start de este test exige que no existan, así que se borran por nombre
    // sin importar el creador. Orden FK-safe: vínculos primero (FK RESTRICT).
    $normsFixture = ['TEJA DE ZINC', 'AYUDANTE', 'CONCRETO 4000PSI', 'SERVICIO BOMBEO'];
    $marcadores = implode(',', array_fill(0, count($normsFixture), '?'));
    $db->query("DELETE FROM pdc_insumo_vinculos WHERE maestro_id IN (SELECT id FROM general_maestro_insumos WHERE descripcion_norm IN ({$marcadores}))", $normsFixture);
    $db->query("DELETE FROM general_maestro_insumos WHERE descripcion_norm IN ({$marcadores})", $normsFixture);
};
$limpiar();

// v2: mismo presupuesto pero el precio de TEJA cambia (contenido distinto → versión nueva real).
// Con el anti-duplicado de A1.7 un re-import de contenido IDÉNTICO ya no crea versión (sinCambios),
// y este test necesita una versión nueva genuina para ejercer el auto-match de un cold start
// contra un maestro ya poblado (mismos descripcion_norm/unidad, para que siga hiciendo match).
$fixtureMaestroV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',           '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01',       'CAMPAMENTO',             '01',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01',    'INSTALACIONES',          '01.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',        '01.01.01', 'M2', 18, '', 102, 'PI_TEST_1', 'APU-001', null, null, null, null, '',                        ''],
        ['',            'TEJA DE ZINC',           '',      'M2', null, '', 102, 'PI_TEST_1', '',     1.05,  1.2, 19, 26000, 'MAT-CUBIERTAS',            ''],
        ['',            'AYUDANTE',               '',      'HC', null, '', 102, 'PI_TEST_1', '',     8.0,   0.5, null, 9500, 'MANO DE OBRA',             ''],
        ['02',          'ESTRUCTURA',             '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01',       'CONCRETOS',              '02',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01',    'LOSAS',                  '02.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01.01', 'LOSA MACIZA E=12',       '02.01.01', 'M3', 40, '', 102, 'PI_TEST_1', 'APU-002', null, null, null, null, '',                        ''],
        ['',            'CONCRETO 4000PSI',       '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.05, 19, 620000, 'MAT-CONCRETOS',           ''],
        ['',            'SERVICIO BOMBEO',        '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.0, null, 28000, 'EQUIPOS',                  ''],
    ]);
};

echo "=== PDC v2: maestro de insumos (normalizar/consolidar/generar) ===\n";

// normalizar()
$assert(MaestroInsumosService::normalizar('  Teja   de Zinc  ') === 'TEJA DE ZINC', 'normalizar: mayúsculas, trim y espacios colapsados.');
$assert(MaestroInsumosService::normalizar('Ñandú Ácido') === 'NANDU ACIDO', 'normalizar: sin acentos ni Ñ.');

$maestro = new MaestroInsumosService($db);
$importSvc = new PresupuestoImportService($db, new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-m-store-' . getmypid()), new PresupuestoExcelParser());

// Sin versión → null.
$assert($maestro->generarVinculos(PDC_M_PROJECT_A) === null, 'Sin versión activa → null.');

// Importar fixture (4 filas APU; TEJA/AYUDANTE/CONCRETO/BOMBEO — todos distintos → 4 únicos).
$tmp = sys_get_temp_dir() . '/pdc_m_v1.xlsx';
pdcFixturePresupuestoValido($tmp);
$p = $importSvc->previewDesdeArchivo($tmp, 'v1.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$c = $importSvc->confirmar($p['importToken'], PDC_M_PROJECT_A);

// Cold start: maestro vacío → todo pendiente.
$g1 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g1['total'] === 4 && $g1['pendientes'] === 4 && $g1['auto'] === 0, 'Cold start: 4 únicos, todos pendientes.');

// Idempotencia: regenerar no duplica ni cambia estados.
$g2 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g2['total'] === 4 && $g2['pendientes'] === 4, 'Regenerar es idempotente.');

// Consolidación real: mismo insumo en 2 actividades suma cantidades/valores.
// (El fixture SinIdApu de A1-T3 tiene un insumo; usamos un import a B con el fixture válido
// y verificamos los agregados del vínculo de TEJA en A.)
$v = $maestro->vinculos(PDC_M_PROJECT_A);
$teja = array_values(array_filter($v['vinculos'], fn ($x) => $x['descripcionNorm'] === 'TEJA DE ZINC'))[0];
$assert(abs($teja['cantidadTotal'] - 22.68) < 0.001 && abs($teja['valorTotal'] - 567000.0) < 0.01 && $teja['apariciones'] === 1, 'Consolidado de TEJA correcto.');
$assert($v['resumen']['cobertura'] === 0.0, 'Cobertura 0% en cold start.');
$assert($v['vinculos'][0]['estado'] === 'pendiente', 'Orden: pendientes primero.');

// Aislamiento: B sin vínculos.
$assert($maestro->generarVinculos(PDC_M_PROJECT_B) === null, 'B sin versión → null.');

echo "=== PDC v2: maestro — acciones de la cola ===\n";

// Re-preparar: importar y generar de nuevo (la limpieza de arriba borró todo).
$tmpB = sys_get_temp_dir() . '/pdc_m_v1b.xlsx';
pdcFixturePresupuestoValido($tmpB);
$pB = $importSvc->previewDesdeArchivo($tmpB, 'v1b.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$cB = $importSvc->confirmar($pB['importToken'], PDC_M_PROJECT_A);
$maestro->generarVinculos(PDC_M_PROJECT_A);
$v = $maestro->vinculos(PDC_M_PROJECT_A);
$ids = array_column($v['vinculos'], 'id');

// Cold start masivo: crear TODOS los pendientes en el maestro.
$r = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, $ids, PDC_M_MARCA);
$assert($r['ok'] === true && $r['creados'] === 4 && $r['vinculados'] === 4, 'Creación masiva: 4 creados y vinculados.');
$v2 = $maestro->vinculos(PDC_M_PROJECT_A);
$assert($v2['resumen']['pendientes'] === 0 && $v2['resumen']['cobertura'] === 100.0, 'Cobertura 100% tras el masivo.');
$assert($v2['vinculos'][0]['estado'] !== 'pendiente' && $v2['vinculos'][0]['maestroDescripcion'] !== null, 'Vínculos con maestro asignado.');

// Segundo import (contenido distinto → versión nueva real) → auto-match 100% sin intervención.
$tmp2 = sys_get_temp_dir() . '/pdc_m_v2.xlsx';
$fixtureMaestroV2($tmp2);
$p2 = $importSvc->previewDesdeArchivo($tmp2, 'v2.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$c2 = $importSvc->confirmar($p2['importToken'], PDC_M_PROJECT_A);
$g = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g['total'] === 4 && $g['auto'] === 4 && $g['pendientes'] === 0, 'Re-import: auto-match 100% contra el maestro poblado.');

// Idempotencia del masivo: repetir con los mismos ids no duplica el catálogo.
$antes = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA])->fetchColumn();
$maestro->crearDesdePendientes(PDC_M_PROJECT_A, $ids, PDC_M_MARCA);
$despues = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA])->fetchColumn();
$assert($antes === $despues && $antes === 4, 'El masivo repetido no duplica insumos del maestro.');

// Sugerencias: buscar para un pendiente artificial con texto similar.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], 'TEJA ZINC CALIBRE 34', 'M2', 'Teja Zinc calibre 34', 'MAT', ],
);
$pendienteId = (int) $db->lastInsertId();
$sug = $maestro->sugerencias(PDC_M_PROJECT_A, $pendienteId);
$assert(count($sug) >= 1 && str_contains($sug[0]['descripcion'], 'TEJA'), 'Sugerencias por tokens encuentran TEJA DE ZINC.');

// Vincular manual a una sugerencia.
$rv = $maestro->vincular(PDC_M_PROJECT_A, $pendienteId, $sug[0]['id']);
$assert($rv['ok'] === true, 'Vinculación manual confirma.');
$assert($maestro->vincular(PDC_M_PROJECT_A, 999999999, $sug[0]['id'])['code'] === 'VINCULO_INVALIDO', 'Vínculo inexistente rechazado.');

// Crear manual duplicado → MAESTRO_DUPLICADO.
$dup = $maestro->crearManual(PDC_M_PROJECT_A, 'Teja de Zinc', 'M2', 'MAT', PDC_M_MARCA);
$assert($dup['ok'] === false && $dup['code'] === 'MAESTRO_DUPLICADO', 'Crear manual duplicado se rechaza.');

// Catálogo con búsqueda.
$cat = $maestro->catalogo('teja');
$assert(count($cat) >= 1 && str_contains($cat[0]['descripcion'], 'TEJA'), 'Catálogo filtra por búsqueda normalizada.');

echo "=== PDC v2: maestro — follow-ups A2 (1062 / colisión de prefijo) ===\n";

// La unique key es (descripcion_norm(191), unidad): dos normas que comparten los
// primeros 191 chars chocan en el INSERT aunque el igual estricto no las encuentre.
$prefijo191 = str_repeat('X', 191);
$mL = $maestro->crearManual(PDC_M_PROJECT_A, $prefijo191 . 'AAA', 'UN', 'MAT', PDC_M_MARCA);
$assert($mL['ok'] === true, 'Prefijo: primer insumo largo se crea.');

// crearManual: el pre-check (igualdad completa) no lo ve → INSERT 1062 → MAESTRO_DUPLICADO, no 500.
$mL2 = $maestro->crearManual(PDC_M_PROJECT_A, $prefijo191 . 'CCC', 'UN', 'MAT', PDC_M_MARCA);
$assert($mL2['ok'] === false && ($mL2['code'] ?? '') === 'MAESTRO_DUPLICADO', 'crearManual captura 1062 como MAESTRO_DUPLICADO.');

// crearDesdePendientes: vincula al existente en vez de abortar el lote con excepción.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], $prefijo191 . 'BBB', 'UN', $prefijo191 . 'BBB', 'MAT'],
);
$vinculoLargo = (int) $db->lastInsertId();
$rL = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, [$vinculoLargo], PDC_M_MARCA);
$assert($rL['ok'] === true && $rL['creados'] === 0 && $rL['vinculados'] === 1, 'Colisión de prefijo: vincula al existente sin crear ni abortar.');

echo "=== PDC v2: maestro — follow-ups A2 (escape de comodines LIKE) ===\n";

$maestro->crearManual(PDC_M_PROJECT_A, 'Viga C_10', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Viga C 10', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Malla 100%', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Malla 100337', 'UN', 'MAT', PDC_M_MARCA);

$descs = array_column($maestro->catalogo('C_10'), 'descripcion');
$assert(in_array('Viga C_10', $descs, true) && !in_array('Viga C 10', $descs, true), 'Catálogo: _ se busca literal, no como comodín.');
$descs = array_column($maestro->catalogo('100%'), 'descripcion');
$assert(in_array('Malla 100%', $descs, true) && !in_array('Malla 100337', $descs, true), 'Catálogo: % se busca literal, no como comodín.');

// Sugerencias: el token C_10 solo debe puntuar el match literal.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], 'VIGA C_10 REFORZADA', 'UN', 'Viga C_10 reforzada', 'MAT'],
);
$sugL = $maestro->sugerencias(PDC_M_PROJECT_A, (int) $db->lastInsertId());
$assert($sugL !== [] && $sugL[0]['descripcion'] === 'Viga C_10', 'Sugerencias: tokens con _ puntúan solo el literal.');

echo "=== PDC v2: maestro — follow-ups A2 (retiro y reactivación) ===\n";

// Estado al llegar aquí: TEJA DE ZINC tiene un vínculo auto (re-import) y uno
// confirmado (TEJA ZINC CALIBRE 34, vinculado a mano más arriba).
$midTeja = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'TEJA DE ZINC' AND unidad = 'M2'")->fetchColumn();
$rd = $maestro->desactivar($midTeja, PDC_M_MARCA);
$assert($rd['ok'] === true && $rd['revertidos'] === 1, 'Retiro revierte exactamente el vínculo auto.');
$auto = $db->query("SELECT estado, maestro_id FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A, $g['versionId']])->fetch(\PDO::FETCH_ASSOC);
$assert($auto['estado'] === 'pendiente' && $auto['maestro_id'] === null, 'Vínculo auto vuelve a pendiente sin maestro.');
$conf = $db->query("SELECT estado, maestro_id FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ? AND descripcion_norm = 'TEJA ZINC CALIBRE 34'", [PDC_M_PROJECT_A, $g['versionId']])->fetch(\PDO::FETCH_ASSOC);
$assert($conf['estado'] === 'confirmado' && (int) $conf['maestro_id'] === $midTeja, 'Vínculo confirmado (decisión humana) se conserva.');

// Auditoría y visibilidad en catálogo.
$aud = $db->query('SELECT activo, actualizado_por, updated_at FROM general_maestro_insumos WHERE id = ?', [$midTeja])->fetch(\PDO::FETCH_ASSOC);
$assert((int) $aud['activo'] === 0 && $aud['actualizado_por'] === PDC_M_MARCA && $aud['updated_at'] !== null, 'Retiro audita actualizado_por y updated_at.');
$assert(array_column($maestro->catalogo('teja de zinc'), 'id') === [], 'Catálogo por defecto oculta retirados.');
$idsInactivos = array_column($maestro->catalogo('teja de zinc', true), 'id');
$assert(in_array($midTeja, $idsInactivos, true), 'Catálogo con incluirInactivos muestra retirados.');

// Guardas.
$assert($maestro->desactivar($midTeja, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Retirar dos veces se rechaza.');
$assert($maestro->desactivar(999999999, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Retirar inexistente se rechaza.');

// Reactivar + regenerar repone el auto-match.
$assert($maestro->reactivar($midTeja, PDC_M_MARCA)['ok'] === true, 'Reactivación OK.');
$assert($maestro->reactivar($midTeja, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Reactivar un activo se rechaza.');
$maestro->generarVinculos(PDC_M_PROJECT_A);
$autoR = $db->query("SELECT estado FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A, $g['versionId']])->fetchColumn();
$assert($autoR === 'auto', 'Tras reactivar, regenerar repone el auto-match.');

// crearDesdePendientes reactiva un maestro retirado en vez de duplicar o fallar.
$maestro->desactivar($midTeja, PDC_M_MARCA);
$vidTeja = (int) $db->query("SELECT id FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A, $g['versionId']])->fetchColumn();
$rReact = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, [$vidTeja], PDC_M_MARCA);
$actTeja = (int) $db->query('SELECT activo FROM general_maestro_insumos WHERE id = ?', [$midTeja])->fetchColumn();
$assert($rReact['creados'] === 0 && $rReact['vinculados'] === 1 && $actTeja === 1, 'El masivo reactiva un maestro retirado y vincula.');

foreach ([$tmpB, $tmp2] as $f) { @unlink($f); }

@unlink($tmp);
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
