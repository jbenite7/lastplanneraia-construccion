<?php
// tests/test_pdc_v2_pasos_configurables.php — A4.1: pasos configurables, sobre MySQL real.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PasosContratacionService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999905; // proyecto de pruebas propio de A4.1
$svc = new PasosContratacionService($db);

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$P]);
};
$limpiar();

// ── El catálogo y la constante de código no pueden divergir ──────────────────
echo "=== catálogo global ===\n";
$cat = $svc->catalogo();
$porClave = [];
foreach ($cat as $p) { $porClave[$p['clave']] = $p; }
$assert(count($cat) >= 9, 'El catálogo tiene al menos los 9 pasos sembrados. Dio ' . count($cat));
foreach (PlanFechasService::PASOS as $i => $p) {
    $c = $porClave[$p['clave']] ?? null;
    $assert($c !== null && $c['colLegacy'] === $p['col'],
        "El catálogo y PASOS coinciden en la columna legacy de «{$p['paso']}».");
    $assert($c !== null && abs((float) $c['peso'] - PlanFechasService::PESOS_REPARTO[$i]) < 0.000001,
        "El catálogo y PESOS_REPARTO coinciden en el peso de «{$p['paso']}».");
}
// `?? 'x'` NO sirve para comprobar un null: el operador trata «existe y vale null» igual que
// «no existe», así que la comprobación se caía sola justo en el caso que quería verificar.
$assert(isset($porClave['aprobacion_cliente']) && $porClave['aprobacion_cliente']['colLegacy'] === null,
    'Aprobación del cliente no tiene columna legacy: usa días fijos.');
$assert(($porClave['licify']['diasSugeridos'] ?? 0) === 1, 'Licify sugiere 1 día, como dice el histórico.');

// ── Sin configuración: los siete de siempre ─────────────────────────────────
echo "=== proceso por defecto ===\n";
$def = $svc->deProyecto($P);
$assert(!$svc->configurado($P), 'Un proyecto sin filas no está configurado.');
$assert(count($def) === 7, 'Sin configuración devuelve los siete pasos. Dio ' . count($def));
$assert(array_column($def, 'clave') === array_column(PlanFechasService::PASOS, 'clave'),
    'Y en el mismo orden que la constante de código.');
$assert($def[0]['pasoId'] !== null, 'Cada paso por defecto resuelve su id del catálogo.');

// ── Guardar una configuración ───────────────────────────────────────────────
echo "=== guardar ===\n";
$r = $svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'],
    ['clave' => 'entrega_pliegos', 'alias' => 'Envío de pliegos'],
    ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'],
    ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'legalizacion'],
    ['clave' => 'fabricacion'],
    ['clave' => 'insumos_obra'],
], 'test-a41');
$assert(($r['ok'] ?? false) === true, 'Guardar una lista de ocho pasos.');
$cfg = $svc->deProyecto($P);
$assert(count($cfg) === 8, 'La obra ahora tiene ocho pasos. Dio ' . count($cfg));
$assert($cfg[4]['clave'] === 'aprobacion_cliente' && $cfg[4]['diasFijos'] === 15,
    'Aprobación del cliente quedó en la quinta posición con sus 15 días.');
$assert($cfg[1]['nombre'] === 'Envío de pliegos', 'El alias de la obra manda en el nombre.');
$assert($svc->configurado($P), 'Ahora sí está configurado.');

// ── Validaciones ────────────────────────────────────────────────────────────
echo "=== validaciones ===\n";
$sinDias = $svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente']], 'test-a41');
$assert(($sinDias['ok'] ?? true) === false && ($sinDias['code'] ?? '') === 'DIAS_FIJOS_REQUERIDOS',
    'Un paso sin columna legacy exige días fijos.');
$vacia = $svc->guardar($P, [], 'test-a41');
$assert(($vacia['ok'] ?? true) === false && ($vacia['code'] ?? '') === 'SIN_PASOS',
    'Una obra no puede quedarse sin ningún paso.');
$repetida = $svc->guardar($P, [['clave' => 'legalizacion'], ['clave' => 'legalizacion']], 'test-a41');
$assert(($repetida['ok'] ?? true) === false && ($repetida['code'] ?? '') === 'PASO_REPETIDO',
    'Un paso no puede aparecer dos veces.');
$inventada = $svc->guardar($P, [['clave' => 'no_existe_este_paso']], 'test-a41');
$assert(($inventada['ok'] ?? true) === false && ($inventada['code'] ?? '') === 'PASO_DESCONOCIDO',
    'Solo se aceptan claves del catálogo activo.');
$assert(count($svc->deProyecto($P)) === 8, 'Ninguna validación fallida dejó la configuración a medias.');

// ── Restablecer ─────────────────────────────────────────────────────────────
echo "=== restablecer ===\n";
$svc->restablecer($P);
$assert(!$svc->configurado($P) && count($svc->deProyecto($P)) === 7,
    'Restablecer devuelve la obra al proceso por defecto.');

// ── CERO REGRESIÓN: Da Porto sin configurar no cambia ni un día ──────────────
// La foto se toma en esta misma corrida y no contra un fichero congelado: hay otras sesiones
// escribiendo en esta base, y una foto vieja probaría el estado de ayer, no la invariancia.
echo "=== cero regresión (Da Porto, proyecto 73) ===\n";
$DAPORTO = 73;
$fotoPaquetes = static fn (): array => Database::getInstance()->query(
    'SELECT paquete_id, fecha_ancla, fecha_arranque, dias_totales, duracion_provisional
     FROM pdc_plan_paquete WHERE project_id = ? ORDER BY paquete_id', [73],
)->fetchAll(PDO::FETCH_ASSOC);
$fotoPasos = static fn (): array => Database::getInstance()->query(
    'SELECT paquete_id, orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
     WHERE project_id = ? ORDER BY paquete_id, orden', [73],
)->fetchAll(PDO::FETCH_ASSOC);

$antesPaquetes = $fotoPaquetes();
$antesPasos = $fotoPasos();
$assert(count($antesPaquetes) > 0, 'La línea base de Da Porto no está vacía: ' . count($antesPaquetes) . ' paquetes.');
$assert(!$svc->configurado($DAPORTO), 'Da Porto NO tiene configuración propia de pasos.');

(new PlanFechasService($db))->calcular($DAPORTO, 'test-a41');

$assert($antesPaquetes === $fotoPaquetes(),
    'Recalcular Da Porto sin configuración deja las ' . count($antesPaquetes) . ' cabeceras idénticas.');
$assert($antesPasos === $fotoPasos(),
    'Y las ' . count($antesPasos) . ' filas de pasos idénticas: mismas fechas, mismos días, mismo orden.');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paso_id IS NULL', [$DAPORTO])->fetchColumn() === 0,
    'Todas las filas de Da Porto conservan su identidad de paso.');

// ── Fixture mínimo para la aritmética ───────────────────────────────────────
// `calcular()` solo necesita un amarre y un paquete activo con duración: ni presupuesto ni
// cronograma. Montar solo eso deja el bloque legible y sin depender del fixture grande de
// tests/test_pdc_v2_plan_fechas.php.
echo "=== aritmética con pasos configurados ===\n";
$limpiarFixture = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a41'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'TEST A41%'");
};
$limpiarFixture();

$db->query(
    'INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ("TEST A41 DURACION", "Contrato", 10, 10, 10, 10, 10, 10, 10)',
);
$duracionRef = (int) $db->query('SELECT id FROM general_dias_procesos_contratacion WHERE paqueteContratacion = "TEST A41 DURACION"')->fetchColumn();
$db->query(
    'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion,
        duracion_ref, activo, creado_por, created_at, updated_at)
     VALUES ("TEST A41 PAQUETE", "test a41 paquete", "a_todo_costo", "contrato", ?, 1, "test-a41", NOW(), NOW())',
    [$duracionRef],
);
$paqPrueba = (int) $db->query('SELECT id FROM general_paquetes_contratacion WHERE creado_por = "test-a41"')->fetchColumn();
$db->query(
    'INSERT INTO pdc_paquete_frente (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla,
        semana_origen, origen, evidencia, confirmado_humano, asignado_por, updated_at)
     VALUES (?, ?, 9001, "TEST A41 FRENTE", "2027-01-01", 1, "humano", "", 1, "test-a41", NOW())',
    [$P, $paqPrueba],
);

$svcPlan = new PlanFechasService($db);
$svcPlan->calcular($P, 'test-a41');                       // sin configurar
$base = $db->query('SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);
$assert((int) $base['dias_totales'] === 70, 'Sin configurar, los siete pasos de 10 días suman 70. Dio ' . $base['dias_totales']);

// ── Recalcular no acumula filas ─────────────────────────────────────────────
// La clave única de pdc_plan_paso es (project_id, paquete_id, paso_id), así que el upsert cae siempre
// en la misma fila. Se afirma explícitamente porque es el contrato del que cuelga todo: si `paso_id`
// llegara NULL, en un índice único NULL nunca iguala a NULL, el ON DUPLICATE KEY no encontraría nada
// y cada recálculo duplicaría los pasos en silencio.
$svcPlan->calcular($P, 'test-a41');
$filasUna = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn();
$svcPlan->calcular($P, 'test-a41');
$filasDos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn();
$assert($filasUna === 7 && $filasDos === 7,
    "Recalcular dos veces deja 7 filas, no 14: {$filasUna} → {$filasDos}");
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id IS NULL',
    [$P, $paqPrueba])->fetchColumn() === 0, 'Y ninguna fila queda sin identidad de paso.');

// ── Con un paso nuevo, el proceso se ALARGA exactamente lo que dura el paso ──
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'entrega_pliegos'], ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'], ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'legalizacion'], ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$con = $db->query('SELECT fecha_ancla, fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);

$assert((int) $con['dias_totales'] === (int) $base['dias_totales'] + 15,
    'Agregar «Aprobación del cliente, 15 días» suma exactamente 15 al total: '
    . $base['dias_totales'] . ' → ' . $con['dias_totales']);
$assert($con['fecha_arranque'] === (new DateTimeImmutable($base['fecha_arranque']))->modify('-15 days')->format('Y-m-d'),
    'Y la fecha de arranque retrocede exactamente 15 días: ' . $base['fecha_arranque'] . ' → ' . $con['fecha_arranque']);

$filas = $db->query('SELECT orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
    WHERE project_id = ? AND paquete_id = ? ORDER BY orden', [$P, $paqPrueba])->fetchAll(PDO::FETCH_ASSOC);
$assert(count($filas) === 8, 'El paquete tiene ahora ocho filas de paso. Dio ' . count($filas));
$assert($filas[4]['paso'] === 'Aprobación del cliente' && (int) $filas[4]['dias'] === 15,
    'El paso nuevo quedó en su posición con sus días.');
$assert(end($filas)['fecha_fin'] === $con['fecha_ancla'],
    'Propiedad 3 del contrato con B1: la fecha_fin del último paso ES la fecha ancla.');
$assert(array_sum(array_column($filas, 'dias')) === (int) $con['dias_totales'],
    'Propiedad 2: la suma de los días es exactamente el intervalo completo.');
foreach ($filas as $i => $f) {
    $assert((int) (new DateTimeImmutable($f['fecha_inicio']))->diff(new DateTimeImmutable($f['fecha_fin']))->days === (int) $f['dias'],
        "Propiedad 1 en el paso {$i}: dias = fin - inicio, sin sumar ni restar uno.");
    if ($i > 0) {
        $assert($filas[$i - 1]['fecha_fin'] === $f['fecha_inicio'],
            'Fronteras [inicio, fin) intactas entre el paso ' . ($i - 1) . " y el {$i}.");
    }
}

// ── Reordenar no reasigna filas: la identidad manda ─────────────────────────
echo "=== identidad al reordenar ===\n";
$idAprobacion = (int) $db->query('SELECT id FROM general_pasos_contratacion WHERE clave = "aprobacion_cliente"')->fetchColumn();
$diasAntes = (int) $db->query('SELECT dias FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
    [$P, $paqPrueba, $idAprobacion])->fetchColumn();
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'entrega_pliegos'], ['clave' => 'recibo_propuestas'], ['clave' => 'cuadros_comparativos'],
    ['clave' => 'legalizacion'], ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$fila = $db->query('SELECT orden, paso, dias FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
    [$P, $paqPrueba, $idAprobacion])->fetch(PDO::FETCH_ASSOC);
$assert($fila !== false && (int) $fila['orden'] === 1 && (int) $fila['dias'] === $diasAntes,
    'Mover el paso de la posición 4 a la 1 mueve SU fila, no reescribe la del vecino.');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 8, 'Reordenar no duplica ni pierde filas.');

// ── Acortar el proceso borra los sobrantes por identidad, no por posición ───
$svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion'], ['clave' => 'insumos_obra']], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 3, 'Bajar a tres pasos deja exactamente tres filas.');

// ── Más de siete pasos ──────────────────────────────────────────────────────
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'licify', 'diasFijos' => 1], ['clave' => 'entrega_pliegos'],
    ['clave' => 'recibo_propuestas'], ['clave' => 'cuadros_comparativos'],
    ['clave' => 'aprobacion_cliente', 'diasFijos' => 15], ['clave' => 'legalizacion'],
    ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 9, 'Nueve pasos: nada asume siete.');
$assert((int) $db->query('SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 86, 'Los 70 del catálogo + 1 de Licify + 15 de aprobación = 86.');

// ── Un paso sin identidad no se escribe a medias: se para en seco ───────────
// El agujero que cubre esto: si el catálogo no estuviera sembrado, todos los pasos llegarían con
// `pasoId` NULL, `$idsVigentes` quedaría vacío y el DELETE de sobrantes —que sin ids no lleva
// condición— borraría las filas recién insertadas. El paquete acabaría con una cabecera que dice
// «85 días» y CERO pasos, sin que nada fallara. Antes de escribir, se exige identidad.
echo "=== identidad obligatoria ===\n";
$sinIdentidad = [
    ['pasoId' => 1, 'clave' => 'elaboracion_pliegos', 'nombre' => 'x', 'colLegacy' => 'diasElaboracionPliegos', 'diasFijos' => null, 'peso' => 0.1],
    ['pasoId' => null, 'clave' => 'fabricacion', 'nombre' => 'y', 'colLegacy' => 'diasFabricacion', 'diasFijos' => null, 'peso' => 0.2],
];
$lanzo = false;
$mensaje = '';
try {
    PlanFechasService::exigirIdentidad($sinIdentidad);
} catch (RuntimeException $e) {
    $lanzo = true;
    $mensaje = $e->getMessage();
}
$assert($lanzo, 'Un paso sin identidad detiene el cálculo en vez de escribir un plan a medias.');
$assert(str_contains($mensaje, 'fabricacion'), 'El error nombra el paso culpable: ' . $mensaje);
$assert(str_contains($mensaje, '20260728_pdc_v2_pasos_configurables'),
    'Y dice qué migración lo arregla: ' . $mensaje);

$todosConId = [
    ['pasoId' => 1, 'clave' => 'elaboracion_pliegos', 'nombre' => 'x', 'colLegacy' => 'diasElaboracionPliegos', 'diasFijos' => null, 'peso' => 0.1],
];
PlanFechasService::exigirIdentidad($todosConId);
$assert(true, 'Con todos los pasos identificados, no estorba.');

// ── Reparto de la mediana con días fijos aparte ─────────────────────────────
echo "=== reparto ===\n";
$pesos = [0.5, 0.0, 0.5];
$r = PlanFechasService::repartirMediana(10, $pesos);
$assert($r === [5, 0, 5], 'Un paso de peso cero no recibe días del reparto: [' . implode(', ', $r) . ']');
$assert(array_sum(PlanFechasService::repartirMediana(0, $pesos)) === 0, 'Repartir cero da cero.');
$assert(PlanFechasService::repartirMediana(10, [0.0, 0.0]) === [0, 0], 'Sin ningún peso, no hay reparto ni división por cero.');
$assert(PlanFechasService::repartirMediana(90) === PlanFechasService::repartirMediana(90, PlanFechasService::PESOS_REPARTO),
    'Sin pesos explícitos sigue usando PESOS_REPARTO: los llamadores viejos no cambian de resultado.');

// ── Provisional: días fijos aparte, y el tope cuando se pasan de la mediana ──
$db->query('UPDATE general_paquetes_contratacion SET duracion_ref = NULL WHERE id = ?', [$paqPrueba]);
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'fabricacion'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$prov = $db->query('SELECT dias_totales, duracion_provisional FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);
$diasProv = $db->query('SELECT paso, dias FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? ORDER BY orden',
    [$P, $paqPrueba])->fetchAll(PDO::FETCH_ASSOC);
$assert((int) $prov['duracion_provisional'] === 1, 'Sin duracion_ref el paquete queda marcado como provisional.');
$assert(array_sum(array_column($diasProv, 'dias')) === (int) $prov['dias_totales'],
    'Los días de los pasos suman el total, también en el reparto provisional.');
$fijoProv = 0;
foreach ($diasProv as $d) { if ($d['paso'] === 'Aprobación del cliente') { $fijoProv = (int) $d['dias']; } }
$assert($fijoProv === 15, 'El paso de días fijos conserva sus 15 días dentro del reparto de la mediana.');
$assert((int) $prov['dias_totales'] >= 15, 'El total nunca queda por debajo de la suma de los días fijos.');

// Días fijos mayores que la mediana: el resto se topa en cero y el total es la suma de los fijos.
$svc->guardar($P, [['clave' => 'aprobacion_cliente', 'diasFijos' => 9999], ['clave' => 'fabricacion']], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$tope = $db->query('SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);
$negativos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND dias < 0',
    [$P, $paqPrueba])->fetchColumn();
$assert((int) $tope['dias_totales'] === 9999, 'Con los fijos por encima de la mediana, el total es la suma de los fijos.');
$assert($negativos === 0, 'Y ningún paso queda con días negativos.');

$limpiarFixture();
$limpiar();
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
