<?php
// tests/test_pdc_v2_paquetes_motor.php — motor de sugerencias (3 capas + candidatos por tipo_recurso) sobre MySQL real.
// Escenario: P2 (999902) tiene historial de asignaciones; P1 (999901) recibe sugerencias.

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
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_maestro_insumos WHERE creado_por = 'test-a3'");
};
$limpiar();

echo "=== PDC v2: motor de sugerencias (3 capas + candidatos) ===\n";
$svc = new PaquetesService($db);

// Paquetes globales.
$pisosId = (int) $svc->crearPaquete('TEST A3 Pisos', 'suministro', 'test-a3')['paquete']['id'];
$acabadosId = (int) $svc->crearPaquete('TEST A3 Acabados', 'a_todo_costo', 'test-a3')['paquete']['id'];

// Historial en P2 (la "memoria" del motor): dos asignaciones a Pisos, una a Acabados.
$db->query(
    "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at) VALUES
     (?, 'PISO CERAMICO 30X30', 'M2', ?, 0, 'test-a3', NOW()),
     (?, 'PISO GRES 40X40', 'M2', ?, 0, 'test-a3', NOW()),
     (?, 'ESTUCO PLASTICO', 'M2', ?, 0, 'test-a3', NOW())",
    [$P2, $pisosId, $P2, $pisosId, $P2, $acabadosId],
);

// Maestro: agrupación + tipo_recurso para la capa 3 y el filtro de candidatos.
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, activo, creado_por, created_at) VALUES
     ('Pintura vinilo tipo 1', 'PINTURA VINILO TIPO 1', 'GL', 'MAT-ACABADOS', 'TEST-A3-ACABADOS', 'MATERIAL', 1, 'test-a3', NOW()),
     ('Estuco plastico', 'ESTUCO PLASTICO', 'M2', 'MAT-ACABADOS', 'TEST-A3-ACABADOS', 'MATERIAL', 1, 'test-a3', NOW()),
     ('Piso porcelanato 60x60', 'PISO PORCELANATO 60X60', 'M2', 'MAT-ACABADOS', 'PISOS Y ENCHAPES', 'MATERIAL', 1, 'test-a3', NOW()),
     ('Ayudante enchapes', 'AYUDANTE ENCHAPES PISO', 'HC', 'MANO DE OBRA', 'PISOS Y ENCHAPES', 'MANO DE OBRA', 1, 'test-a3', NOW())",
);
$mPintura = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'PINTURA VINILO TIPO 1' AND unidad = 'GL'")->fetchColumn();
$mPorce = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'PISO PORCELANATO 60X60' AND unidad = 'M2'")->fetchColumn();
$mAyud = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'AYUDANTE ENCHAPES PISO' AND unidad = 'HC'")->fetchColumn();

// Versión activa de P1 con insumos sin asignar.
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-MOTOR', 1, 'motor.xlsx', REPEAT('c', 64), 1, 5, 100, 1, 'test-a3', NOW())",
    [$P1],
);
$vid = (int) $db->lastInsertId();
$fixtures = [
    ['PISO CERAMICO 30X30', 'M2', null, 900],        // capa exacta → Pisos
    ['PISO PORCELANATO 60X60', 'M2', $mPorce, 800],  // capa tokens ("PISO") → Pisos ; candidato material de Pisos
    ['PINTURA VINILO TIPO 1', 'GL', $mPintura, 700], // capa agrupación TEST-A3-ACABADOS → Acabados
    ['AYUDANTE ENCHAPES PISO', 'HC', $mAyud, 600],   // tokens comparte "PISO" pero es MANO DE OBRA
    ['ZZZZ INSUMO INEDITO', 'UN', null, 500],        // sin sugerencia
];
foreach ($fixtures as $f) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, ?, 1, ?, 'pendiente')",
        [$P1, $vid, $f[0], $f[1], $f[0], $f[3], $f[2]],
    );
}

$r = $svc->sugerencias($P1);
$assert($r !== null, 'Con versión activa hay respuesta.');
$porNorm = [];
foreach ($r['sugerencias'] as $s) { $porNorm[$s['descripcionNorm']] = $s; }

$s1 = $porNorm['PISO CERAMICO 30X30'] ?? null;
$assert($s1 !== null && $s1['capa'] === 'exacta' && $s1['confianza'] === 'alta' && (int) $s1['paqueteId'] === $pisosId, 'Capa 1 exacta: mismo insumo en P2 → Pisos (alta).');
$assert($s1 !== null && str_contains($s1['evidencia'], '1'), 'Evidencia exacta menciona el nº de proyectos.');

$s2 = $porNorm['PISO PORCELANATO 60X60'] ?? null;
$assert($s2 !== null && $s2['capa'] === 'tokens' && $s2['confianza'] === 'media' && (int) $s2['paqueteId'] === $pisosId, 'Capa 2 tokens: "PISO" coincide → Pisos (media).');

$s3 = $porNorm['PINTURA VINILO TIPO 1'] ?? null;
$assert($s3 !== null && $s3['capa'] === 'agrupacion' && $s3['confianza'] === 'baja' && (int) $s3['paqueteId'] === $acabadosId, 'Capa 3 agrupación: TEST-A3-ACABADOS → Acabados (baja).');

$assert(!isset($porNorm['ZZZZ INSUMO INEDITO']), 'Insumo inédito: sin sugerencia (no se inventa).');

// La capa exacta NO usa el propio proyecto: asignar en P1 y pedir de nuevo no debe auto-sugerirse.
$svc->asignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], $pisosId, 'test-a3');
$r2 = $svc->sugerencias($P1);
$normsR2 = array_column($r2['sugerencias'], 'descripcionNorm');
$assert(!in_array('PISO CERAMICO 30X30', $normsR2, true), 'Insumo ya asignado sale de las sugerencias.');

// --- candidatosParaPaquete (4ª señal: filtro por tipo_recurso) ---
// Pisos ya tiene PISO CERAMICO/GRES (P2) + CERAMICO (P1). Candidatos sin asignar de P1 similares a Pisos:
//   PISO PORCELANATO (MATERIAL) y AYUDANTE ENCHAPES PISO (MANO DE OBRA) comparten token "PISO"/agrupación.
$cand = $svc->candidatosParaPaquete($P1, $pisosId);
$candNorms = array_column($cand['candidatos'], 'descripcionNorm');
$assert(in_array('PISO PORCELANATO 60X60', $candNorms, true), 'Candidato por similitud al paquete Pisos.');
$assert(!in_array('ZZZZ INSUMO INEDITO', $candNorms, true), 'El inédito no es candidato.');

// Filtrando por tipo_recurso MATERIAL: el ayudante (MANO DE OBRA) queda fuera.
$candMat = $svc->candidatosParaPaquete($P1, $pisosId, 'MATERIAL');
$candMatNorms = array_column($candMat['candidatos'], 'descripcionNorm');
$assert(in_array('PISO PORCELANATO 60X60', $candMatNorms, true), 'Filtro MATERIAL incluye el porcelanato.');
$assert(!in_array('AYUDANTE ENCHAPES PISO', $candMatNorms, true), 'Filtro MATERIAL excluye la mano de obra.');

// Filtrando por MANO DE OBRA: incluye el ayudante, excluye el porcelanato.
$candMo = $svc->candidatosParaPaquete($P1, $pisosId, 'MANO DE OBRA');
$candMoNorms = array_column($candMo['candidatos'], 'descripcionNorm');
$assert(in_array('AYUDANTE ENCHAPES PISO', $candMoNorms, true), 'Filtro MANO DE OBRA incluye el ayudante.');
$assert(!in_array('PISO PORCELANATO 60X60', $candMoNorms, true), 'Filtro MANO DE OBRA excluye el material.');

// Comodines en tokens no rompen (insumo con % y _ en la descripción).
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, 'SOLUCION 50%_ ESPECIAL', 'LT', 'Solucion 50%_ especial', 'X', 1, 10, 1, 'pendiente')",
    [$P1, $vid],
);
$r3 = $svc->sugerencias($P1); // no debe lanzar ni sugerir por el comodín
$assert($r3 !== null, 'Tokens con % y _ escapados: la consulta no explota.');

$assert($svc->sugerencias($P2) === null, 'P2 sin presupuesto → null.');
$assert($svc->candidatosParaPaquete($P2, $pisosId) === null, 'candidatos sin versión → null.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
