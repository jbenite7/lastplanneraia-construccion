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

$r = $svcObra->borrar($P, $REF, ['columnaInventada']);
$assert($r['ok'] === false && $r['code'] === 'COLUMNA_INVALIDA',
    'Borrar una columna fuera de la lista blanca se rechaza igual que guardarla: el vocabulario es '
    . 'el mismo en los dos sentidos.');
$assert(($svcObra->deProyecto($P)[$REF]['diasFabricacion'] ?? null) === 90,
    'Y el rechazo del borrado no tocó nada.');

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
$assert(substr_count($fuentePlan, 'DuracionesObraService($this->db))->deProyecto($projectId)') === 3,
    'calcular(), simularReprogramacion() y plan() cargan las excepciones. Si solo unas lo hacen, la '
    . 'pantalla promete fechas u orígenes distintos de los que el cálculo escribe.');

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

echo "=== una excepción donde el catálogo tiene NULL evita la duración provisional (§9) ===\n";
// El caso que la spec §6 llama «uno de los casos útiles»: la empresa no tiene número para ese paso
// y la obra sí. Si la resolución corriera después de decidir `$provisional`, el paquete quedaría
// provisional y el número de la obra no se usaría para nada.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete,
        diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos,
        diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('ZZTEST OBRA DUR NULL', 'a_todo_costo', 3, 2, 7, 4, 5, NULL, 2)",
);
$refNull = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion,
        modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('ZZTEST OBRA DUR NULL', 'zztest obra dur null', 'a_todo_costo', 'contrato', ?, 1, 'test-dur-obra-null', NOW())",
    [$refNull],
);
$paqNull = (int) $db->lastInsertId();

// Dos columnas en NULL: corregir solo una deja el paquete provisional igual.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete,
        diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos,
        diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('ZZTEST OBRA DUR NULL2', 'a_todo_costo', 3, 2, 7, 4, 5, NULL, NULL)",
);
$refNull2 = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion,
        modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('ZZTEST OBRA DUR NULL2', 'zztest obra dur null2', 'a_todo_costo', 'contrato', ?, 1, 'test-dur-obra-null', NOW())",
    [$refNull2],
);
$paqNull2 = (int) $db->lastInsertId();

$proyectarDe = static fn (int $paq, array $exc): array => $proyectar->invoke(
    $plan, $paq, '2026-12-31', $pasosSint, [], $selectCols, $exc,
);

try {
    $sinCorregir = $proyectarDe($paqNull, []);
    $assert($sinCorregir['provisional'] === true,
        'Con una columna del catálogo en NULL y sin corrección, el paquete es provisional.');

    $corregido = $proyectarDe($paqNull, [$refNull => ['diasFabricacion' => 15]]);
    $assert($corregido['provisional'] === false,
        'La obra da el número que la empresa no tiene y el paquete DEJA de ser provisional.');
    $assert($corregido['total'] === 38,
        'Y el total usa el número de la obra: 3+2+7+4+5+15+2 = 38. Dio ' . $corregido['total']);

    $parcial = $proyectarDe($paqNull2, [$refNull2 => ['diasFabricacion' => 15]]);
    $assert($parcial['provisional'] === true,
        'Con DOS columnas en NULL y una sola corregida el paquete sigue provisional: el número de la '
        . 'obra no alcanza a gobernar el cálculo, y la pantalla no debe decir que sí.');
} finally {
    $db->query('DELETE FROM general_paquetes_contratacion WHERE creado_por = ?', ['test-dur-obra-null']);
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'ZZTEST OBRA DUR NULL%'");
}

echo "=== el plan expone lo que la pantalla necesita para corregir ===\n";
$fuente = file_get_contents(__DIR__ . '/../src/Services/Pdc/PlanFechasService.php');
$assert(str_contains($fuente, 'pp.duracion_ref'),
    'La consulta del plan selecciona pp.duracion_ref.');
$assert(str_contains($fuente, "'duracionRef' => \$r['duracion_ref']"),
    'La fila del plan expone duracionRef al cliente.');
$assert(str_contains($fuente, "'origen' =>") && str_contains($fuente, "'colLegacy' =>"),
    'Cada paso del plan dice su columna legacy y si su duración es de la empresa o de la obra.');
$assert(str_contains($fuente, "'paquetesConMismaDuracion' =>"),
    'La fila del plan dice cuántos destinos de esta obra comparten su duracionRef: el aviso del '
    . 'panel promete el alcance real de la corrección, que es la fila del catálogo y no el paquete.');
$assert(str_contains($fuente, '$provisionalPorDestino'),
    'El origen «obra» exige además que la excepción se haya APLICADO de verdad: un paquete que sigue '
    . 'provisional saca sus días del reparto de la mediana, no del número que la obra escribió, y '
    . 'marcarlo «obra» sería una tercera señal contando otra historia.');

echo "=== dos obras, la corrección de una no aparece en la otra ===\n";
$OBRA_A = 999906;
$OBRA_B = 999907;
$svcDos = new DuracionesObraService($db);
$limpiarDos = static function () use ($db, $OBRA_A, $OBRA_B): void {
    $db->query('DELETE FROM pdc_proyecto_duraciones WHERE project_id IN (?, ?)', [$OBRA_A, $OBRA_B]);
};
$limpiarDos();
try {
    $svcDos->guardar($OBRA_A, 1, ['diasFabricacion' => 120], null);
    $assert($svcDos->deProyecto($OBRA_B) === [],
        'La corrección de la obra A no aparece en la obra B.');
    $assert(($svcDos->deProyecto($OBRA_A)[1]['diasFabricacion'] ?? null) === 120,
        'Y la obra A sí la conserva.');
    $svcDos->guardar($OBRA_B, 1, ['diasFabricacion' => 45], null);
    $assert(($svcDos->deProyecto($OBRA_A)[1]['diasFabricacion'] ?? null) === 120,
        'Corregir la misma fila en la obra B no toca el número de la obra A.');
    $assert(($svcDos->deProyecto($OBRA_B)[1]['diasFabricacion'] ?? null) === 45,
        'Cada obra guarda el suyo.');
} finally {
    $limpiarDos();
}

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
