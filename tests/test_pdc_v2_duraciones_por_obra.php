<?php
// tests/test_pdc_v2_duraciones_por_obra.php — duraciones por obra: tabla, servicio y resolución.
declare(strict_types=1);
// @requiere: datos-proyecto

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\DuracionesObraService;
use App\Services\Pdc\PasosContratacionService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();

echo "=== la tabla existe con su clave única ===\n";
$tabla = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    ['pdc_proyecto_duraciones'],
)->fetchColumn();
$assert($tabla === 1, 'Existe la tabla pdc_proyecto_duraciones.');

$unica = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? AND NON_UNIQUE = 0',
    ['pdc_proyecto_duraciones', 'uq_ppd_obra_ref_col'],
)->fetchColumn();
$assert($unica === 3, 'La clave única cubre las tres columnas (project_id, duracion_ref, columna). Dio ' . $unica);

echo "=== el servicio guarda, lee y borra ===\n";
$P = 999906;
$REF = 1;
$svcObra = new DuracionesObraService($db);
$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_duraciones WHERE project_id = ?', [$P]);
};
$limpiar();

$assert($svcObra->deProyecto($P) === [], 'Una obra sin correcciones devuelve un mapa vacío.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 120], null);
$assert($r['ok'] === true, 'Guardar una corrección válida responde ok.');
$assert($svcObra->deProyecto($P) === [$REF => ['diasFabricacion' => 120]],
    'La corrección se lee indexada por duracionRef y columna.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 90], null);
$assert($r['ok'] === true && $svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Guardar dos veces la misma columna actualiza en vez de duplicar.');

$r = $svcObra->guardar($P, $REF, ['columnaInventada' => 5], null);
$assert($r['ok'] === false && $r['code'] === 'COLUMNA_INVALIDA',
    'Una columna fuera de la lista blanca se rechaza.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => -1], null);
$assert($r['ok'] === false && $r['code'] === 'DIAS_INVALIDOS',
    'Un número de días negativo se rechaza.');

$assert($svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Un rechazo no deja el dato a medias: sigue valiendo 90.');

$r = $svcObra->borrar($P, $REF, ['diasFabricacion']);
$assert($r['ok'] === true && $svcObra->deProyecto($P) === [],
    'Borrar la corrección devuelve la obra al catálogo de la empresa.');

$limpiar();

echo "=== la obra manda sobre la empresa ===\n";
$refl = new \ReflectionMethod(PlanFechasService::class, 'proyectar');
$assert($refl->getNumberOfParameters() === 7,
    'proyectar() acepta las excepciones. Tiene ' . $refl->getNumberOfParameters() . ' parámetros.');
$assert($refl->getParameters()[5]->getName() === 'excepciones',
    'El sexto parámetro de proyectar() se llama $excepciones.');

$fuentePlan = file_get_contents(__DIR__ . '/../src/Services/Pdc/PlanFechasService.php');
$assert(substr_count($fuentePlan, 'DuracionesObraService($this->db))->deProyecto($projectId)') === 2,
    'calcular() y simularReprogramacion() cargan las excepciones. Si solo una lo hace, la '
    . 'simulación promete fechas distintas de las que el cálculo escribe.');

// La resolución en sí es aritmética sobre dos arrays y se prueba sin base.
$catalogo = ['diasFabricacion' => 180, 'diasLegalizacionContrato' => 30];
$resolver = static function (array $paq, array $exc): array {
    foreach ($exc as $col => $d) { if (array_key_exists($col, $paq)) { $paq[$col] = $d; } }
    return $paq;
};
$assert($resolver($catalogo, [])['diasFabricacion'] === 180,
    'Sin excepción manda el número de la empresa.');
$assert($resolver($catalogo, ['diasFabricacion' => 120])['diasFabricacion'] === 120,
    'Con excepción manda el número de la obra.');
$assert($resolver($catalogo, ['diasFabricacion' => 120])['diasLegalizacionContrato'] === 30,
    'La excepción de un paso no toca los demás pasos.');

echo "=== la misma fila del catálogo, dos obras, resultados distintos ===\n";
$cols = PasosContratacionService::columnasLegacy();
$selectCols = ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, $cols));
$pasosSint = [];
foreach (PlanFechasService::PASOS as $i => $p) {
    $pasosSint[] = [
        'pasoId' => $i + 1, 'clave' => $p['clave'], 'nombre' => $p['paso'],
        'colLegacy' => $p['col'], 'diasFijos' => null, 'peso' => null,
    ];
}

// Una fila del catálogo y un paquete que la usa. Las DOS obras comparten ambos: si el aislamiento
// fallara, la corrección de una se vería en la otra. Los siete números suman 33.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete,
        diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos,
        diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('ZZTEST OBRA DUR', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)",
);
$refSint = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion,
        modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('ZZTEST OBRA DUR', 'zztest obra dur', 'a_todo_costo', 'contrato', ?, 1, 'test-dur-obra', NOW())",
    [$refSint],
);
$paqSint = (int) $db->lastInsertId();

// `proyectar()` es privado a propósito: lo que se prueba es su contrato, no su visibilidad.
$proyectar = new \ReflectionMethod(PlanFechasService::class, 'proyectar');
$proyectar->setAccessible(true);
$plan = new PlanFechasService($db);
$correr = static fn (array $exc): int => (int) $proyectar->invoke(
    $plan, $paqSint, '2026-12-31', $pasosSint, [], $selectCols, $exc,
)['total'];

try {
    $sinNada = $correr([]);
    $assert($sinNada === 33, 'Sin correcciones el proceso dura lo del catálogo: 33 días. Dio ' . $sinNada);

    $obraQueCorrige = $correr([$refSint => ['diasFabricacion' => 15]]);
    $assert($obraQueCorrige === 38,
        'La obra que corrigió Fabricación de 10 a 15 pasa a 38 días. Dio ' . $obraQueCorrige);

    $obraQueNoCorrige = $correr([]);
    $assert($obraQueNoCorrige === 33,
        'Y la otra obra, con el MISMO paquete y la MISMA fila del catálogo, sigue en 33. Dio '
        . $obraQueNoCorrige);

    $otraFila = $correr([$refSint + 100000 => ['diasFabricacion' => 15]]);
    $assert($otraFila === 33,
        'Una corrección sobre otra fila del catálogo no afecta a este paquete. Dio ' . $otraFila);
} finally {
    $db->query('DELETE FROM general_paquetes_contratacion WHERE creado_por = ?', ['test-dur-obra']);
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ZZTEST OBRA DUR'");
}

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
