<?php
// tests/test_pdc_v2_pasos_copiar.php — A4.1 · diferido nº 2: copiar la configuración de pasos de una
// obra a otra. Copia explícita y PUNTUAL, no un vínculo vivo. Sobre MySQL real.
declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PasosContratacionService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$A = 999960;   // obra origen, configurada
$B = 999961;   // obra destino
$C = 999962;   // obra sin configurar

$limpiar = static function () use ($db, $A, $B, $C): void {
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id IN (?, ?, ?)', [$A, $B, $C]);
    $db->query('DELETE FROM project_members WHERE project_id IN (?, ?, ?)', [$A, $B, $C]);
    $db->query('DELETE FROM general_proyectos_procesos WHERE Id IN (?, ?, ?)', [$A, $B, $C]);
    $db->query("DELETE FROM general_usuarios WHERE usuario LIKE 'zztest-copia-%'");
};
$limpiar();

// ── Fixture: tres obras y dos usuarios con visibilidad distinta ──────────────
foreach ([[$A, 'ZZTEST OBRA A'], [$B, 'ZZTEST OBRA B'], [$C, 'ZZTEST OBRA C']] as [$id, $nombre]) {
    $db->query(
        "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso)
         VALUES (?, ?, 'zztest_copia', 'Construccion', 1, 1)",
        [$id, $nombre],
    );
}
$crearUsuario = static function (string $usuario, array $proyectos) use ($db): int {
    $db->query(
        "INSERT INTO general_usuarios (usuario, nombre, email, cargo, activo, password)
         VALUES (?, ?, ?, 'Director de Obra', 1, '')",
        [$usuario, strtoupper($usuario), $usuario . '@zztest.local'],
    );
    $id = (int) $db->lastInsertId();
    foreach ($proyectos as $p) {
        $db->query("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'D')", [$p, $id]);
    }
    return $id;
};
$conAmbas = $crearUsuario('zztest-copia-ambas', [$A, $B, $C]);
$soloDestino = $crearUsuario('zztest-copia-solo-b', [$B]);

$svc = new PasosContratacionService($db);

echo "=== A4.1 · copiar la configuración de pasos entre obras ===\n";

// El origen se configura con cinco pasos; el último con alias propio.
$svc->guardar($A, [
    ['clave' => 'elaboracion_pliegos'],
    ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'],
    ['clave' => 'legalizacion'],
    ['clave' => 'insumos_obra', 'alias' => 'Llegada a obra'],
], 'test-copia');

// ── Vista previa: se ve QUÉ se va a copiar antes de copiarlo ─────────────────
$prev = $svc->previsualizarCopia($A);
$assert(count($prev['pasos']) === 5, 'La vista previa muestra los cinco pasos del origen: ' . count($prev['pasos']));
$assert($prev['pasos'][0]['clave'] === 'elaboracion_pliegos', 'Y en el orden del origen.');
$assert($prev['pasos'][4]['alias'] === 'Llegada a obra', 'La vista previa muestra el alias, no solo la clave.');
$assert($prev['incompleta'] === false, 'Una configuración completa no se marca como incompleta.');

// ── Cero regresión: el destino, sin configurar, tiene los siete de siempre ───
$assert(count($svc->deProyecto($B)) === 7, 'Antes de copiar, el destino tiene los siete por defecto.');
$assert($svc->configurado($B) === false, 'Y no figura como configurado.');

// ── Copiar ──────────────────────────────────────────────────────────────────
$r = $svc->copiarDesde($A, $B, 'test-copia');
$assert(($r['ok'] ?? false) === true && ($r['pasos'] ?? 0) === 5, 'Se copiaron cinco pasos: ' . json_encode($r));

$pa = $svc->deProyecto($A);
$pb = $svc->deProyecto($B);
$assert(array_column($pa, 'clave') === array_column($pb, 'clave'), 'B queda con la misma lista y el mismo orden que A.');
$assert(array_column($pa, 'nombre') === array_column($pb, 'nombre'), 'Y con los mismos alias.');

// ── Copia puntual, NO vínculo vivo ──────────────────────────────────────────
$svc->guardar($B, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion']], 'test-copia');
$assert(count($svc->deProyecto($B)) === 2, 'B se puede editar después de copiar: ' . count($svc->deProyecto($B)));
$assert(count($svc->deProyecto($A)) === 5, 'Y editar B no cambia A: ' . count($svc->deProyecto($A)));

// ── Un origen sin configurar no se copia: copiaría «los siete» disfrazados ───
$r2 = $svc->copiarDesde($C, $B, 'test-copia');
$assert(($r2['ok'] ?? true) === false && ($r2['code'] ?? '') === 'ORIGEN_SIN_CONFIGURAR',
    'Copiar de una obra sin configuración propia se rechaza: ' . json_encode($r2));

$r3 = $svc->copiarDesde($A, $A, 'test-copia');
$assert(($r3['code'] ?? '') === 'ORIGEN_ES_DESTINO', 'Una obra no se copia a sí misma: ' . json_encode($r3));

// ── Orígenes disponibles: solo obras que el usuario ve Y están configuradas ──
$or = $svc->origenesDisponibles($B, $conAmbas);
$claves = array_column($or, 'projectId');
$assert(in_array($A, $claves, true), 'A es un origen disponible para quien lo ve.');
$assert(!in_array($B, $claves, true), 'La obra actual nunca se ofrece como origen.');
$assert(!in_array($C, $claves, true), 'Una obra sin configurar no se ofrece como origen.');
$filaA = null;
foreach ($or as $o) {
    if ($o['projectId'] === $A) { $filaA = $o; }
}
$assert($filaA !== null && $filaA['nombre'] === 'ZZTEST OBRA A' && $filaA['pasos'] === 5,
    'Cada origen dice su nombre y cuántos pasos trae: ' . json_encode($filaA));

$assert($svc->origenesDisponibles($B, $soloDestino) === [],
    'Quien no es miembro de A no lo ve como origen: ' . json_encode($svc->origenesDisponibles($B, $soloDestino)));

// ── Una configuración a medias se marca, para que la copia no la herede a ciegas ──
$pasoSinCatalogo = $db->query(
    "SELECT clave FROM general_pasos_contratacion WHERE activo = 1 AND col_legacy IS NULL LIMIT 1",
)->fetchColumn();
if ($pasoSinCatalogo !== false) {
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$A]);
    $svc->guardar($A, [
        ['clave' => 'elaboracion_pliegos'],
        ['clave' => (string) $pasoSinCatalogo, 'diasFijos' => 4],
    ], 'test-copia');
    // Se vacía a mano el número: `guardar()` no deja llegar aquí, pero una fila vieja sí puede estar así.
    $db->query(
        'UPDATE pdc_proyecto_pasos p JOIN general_pasos_contratacion c ON c.id = p.paso_id
            SET p.dias_fijos = NULL WHERE p.project_id = ? AND c.clave = ?',
        [$A, $pasoSinCatalogo],
    );
    $assert($svc->previsualizarCopia($A)['incompleta'] === true,
        'Un paso sin duración en el origen marca la copia como incompleta.');
} else {
    echo "SKIP: no hay ningún paso del catálogo sin respaldo legacy; no se prueba «incompleta».\n";
}

$limpiar();

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
