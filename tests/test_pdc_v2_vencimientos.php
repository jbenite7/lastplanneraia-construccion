<?php
/**
 * Gate de la fase B2 (primera mitad): vencimientos y semaforo del plan.
 *
 * Corre contra DAPORTO (project_id = 73) y NO escribe nada: este frente solo lee. Autoejecutable:
 * imprime PASS:/FAIL: y sale con 0/1. No hay PHPUnit en este repo.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

const P = 73;

$db = Database::getInstance();
$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    echo ($cond ? 'PASS: ' : 'FAIL: '), $msg, "\n";
    if (!$cond) {
        $fallos++;
    }
};

$svc = new SeguimientoService($db);

// --- La regla, sin base de datos ---
$hoy = '2026-07-29';
$c = static fn (?string $f): string => SeguimientoService::clasificarVencimiento($f, $hoy)['estado'];

$assert($c('2026-07-28') === 'vencido', 'Ayer esta vencido. Dio ' . $c('2026-07-28'));
$assert($c('2026-07-29') === 'sem1', 'Hoy mismo cuenta como «vence en 1 semana», no como vencido. Dio ' . $c('2026-07-29'));
$assert($c('2026-08-04') === 'sem1', 'Seis dias adelante sigue en la primera semana. Dio ' . $c('2026-08-04'));
$assert($c('2026-08-05') === 'sem2', 'A los siete dias exactos empieza la segunda semana. Dio ' . $c('2026-08-05'));
$assert($c('2026-08-12') === 'sem3', 'A los catorce dias exactos empieza la tercera semana. Dio ' . $c('2026-08-12'));
$assert($c('2026-08-19') === 'sem6', 'A los veintiun dias exactos empieza el corte de seis semanas. Dio ' . $c('2026-08-19'));
$assert($c('2026-09-08') === 'sem6', 'El dia 41 todavia es del corte de seis semanas. Dio ' . $c('2026-09-08'));
$assert($c('2026-09-09') === 'adelante', 'A los 42 dias exactos ya es «mas adelante». Dio ' . $c('2026-09-09'));
$assert($c(null) === 'sin_fecha', 'Un paso sin fecha programada no se inventa un corte: es «sin fecha». Dio ' . $c(null));

$d = SeguimientoService::clasificarVencimiento('2026-07-20', $hoy);
$assert($d['diasDesfase'] === 9, 'El desfase de lo vencido son dias positivos de retraso. Dio ' . var_export($d['diasDesfase'], true));
$assert(SeguimientoService::clasificarVencimiento('2026-08-04', $hoy)['diasDesfase'] === null,
    'Lo que aun no vence no tiene desfase: null, no cero.');

// --- El tablero contra Da Porto ---
$v = $svc->vencimientos(P);

$assert($v['hoy'] === (new DateTimeImmutable('today'))->format('Y-m-d'),
    'La fecha de hoy la pone el servidor y viaja en la respuesta. Dio ' . $v['hoy']);

$pendientes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND fecha_real IS NULL', [P],
)->fetchColumn();
$assert(array_sum($v['conteos']) === $pendientes,
    'Los conteos por corte suman EXACTAMENTE los pasos pendientes del proyecto. Sumaron '
    . array_sum($v['conteos']) . ' y hay ' . $pendientes);
$assert($v['totalPendientes'] === $pendientes,
    'totalPendientes es ese mismo numero. Dio ' . $v['totalPendientes']);

// «Mas adelante» se cuenta pero no se lista: las filas son todo menos ese corte.
$listables = $pendientes - $v['conteos']['adelante'];
$assert(count($v['filas']) === $listables,
    'Se listan todos los cortes menos «mas adelante». Listo ' . count($v['filas']) . ' de ' . $listables);
$assert(array_filter($v['filas'], static fn (array $f): bool => $f['estado'] === 'adelante') === [],
    'Ninguna fila listada es del corte «mas adelante».');

// Ningun paso cumplido se cuela.
$cumplido = $db->query(
    'SELECT paquete_id, paso_id FROM pdc_plan_paso WHERE project_id = ? AND fecha_real IS NOT NULL LIMIT 1', [P],
)->fetch(PDO::FETCH_ASSOC);
if ($cumplido !== false) {
    $colado = array_filter(
        $v['filas'],
        static fn (array $f): bool => $f['paqueteId'] === (int) $cumplido['paquete_id']
            && $f['pasoId'] === (int) $cumplido['paso_id'],
    );
    $assert($colado === [], 'Un paso con fecha real no aparece en el tablero.');
}

// Cada fila lleva su paquete, su paso y su clasificacion, y la clasificacion es la de la regla.
$assert($v['filas'] !== [], 'Da Porto tiene pasos pendientes que listar.');
$desalineadas = 0;
foreach ($v['filas'] as $f) {
    $esperado = SeguimientoService::clasificarVencimiento($f['fechaFin'], $v['hoy']);
    if ($f['estado'] !== $esperado['estado'] || $f['diasDesfase'] !== $esperado['diasDesfase']) {
        $desalineadas++;
    }
}
$assert($desalineadas === 0,
    'Cada fila del tablero se clasifica con clasificarVencimiento(). Desalineadas: ' . $desalineadas);

// Filtro por paso: solo quedan las filas de ese paso.
$clave = $v['filas'][0]['clave'];
$soloPaso = $svc->vencimientos(P, ['pasoClave' => $clave]);
$assert(
    $soloPaso['filas'] !== []
    && array_filter($soloPaso['filas'], static fn (array $f): bool => $f['clave'] !== $clave) === [],
    'Filtrar por un paso concreto deja solo las filas de ese paso.',
);
$assert(array_sum($soloPaso['conteos']) <= $pendientes,
    'Los conteos del filtro cuentan lo filtrado, no el proyecto entero.');
$assert($soloPaso['pasos'] === $v['pasos'],
    'El catalogo del desplegable no se encoge al filtrar: si no, elegir un paso dejaria sin vuelta atras.');

// Filtro por responsable.
$conDueno = null;
foreach ($v['filas'] as $f) {
    if ($f['responsableUserId'] !== null) {
        $conDueno = $f['responsableUserId'];
        break;
    }
}
if ($conDueno !== null) {
    $mios = $svc->vencimientos(P, ['responsableUserId' => $conDueno]);
    $assert(
        $mios['filas'] !== []
        && array_filter($mios['filas'], static fn (array $f): bool => $f['responsableUserId'] !== $conDueno) === [],
        'Filtrar por responsable deja solo las filas de esa persona.',
    );
} else {
    echo "  (Da Porto no tiene ningun paquete con responsable: el filtro por persona queda sin probar con datos)\n";
}
$sinDueno = $svc->vencimientos(P, ['soloSinResponsable' => true]);
$assert(
    array_filter($sinDueno['filas'], static fn (array $f): bool => $f['responsableUserId'] !== null) === [],
    '«Sin responsable» deja solo las filas que no tienen dueño.',
);

// Lo que el tablero NO esta mirando, dicho en numeros.
$assert(isset($v['sinFechas']['paquetes'], $v['sinFechas']['sinFrente'], $v['sinFechas']['sinCalcular']),
    'El tablero declara cuantos paquetes no esta mirando, y por que.');
$assert($v['sinFechas']['paquetes'] === $v['sinFechas']['sinFrente'] + $v['sinFechas']['sinCalcular'],
    'El total sin fecha es la suma de sus dos motivos. Dio ' . json_encode($v['sinFechas']));
// Da Porto tiene un paquete `no_contratable` (Imprevistos y provisiones, id 205): no se le compra a
// nadie, nunca va a tener fecha, y contarlo como «no mirado» seria una alarma que no se puede apagar.
$assert($v['sinFechas']['paquetes'] === 0,
    'Los cuatro paquetes de Da Porto: tres con plan y uno no contratable. Ninguno cuenta como sin fecha. Dio '
    . $v['sinFechas']['paquetes']);

// El catalogo de pasos para poblar el filtro sale de lo que hay, sin inventar opciones.
$assert($v['pasos'] !== [] && isset($v['pasos'][0]['clave'], $v['pasos'][0]['paso']),
    'La respuesta trae los pasos que de verdad aparecen, para el desplegable del filtro.');

// --- El semaforo del plan y el tablero no pueden divergir ---
$plan = (new \App\Services\Pdc\PlanFechasService($db))->plan(P);
$assert($plan !== [], 'Da Porto tiene plan calculado que comparar.');

// Indice del tablero por (paquete, orden): es la pareja que identifica un paso en las dos vistas.
$delTablero = [];
foreach ($v['filas'] as $f) {
    $delTablero[$f['paqueteId'] . ':' . $f['orden']] = $f['estado'];
}
$comparados = 0;
$divergen = [];
foreach ($plan as $fila) {
    foreach ($fila['pasos'] as $p) {
        if (!array_key_exists('vencimiento', $p) || !array_key_exists('fechaReal', $p)) {
            $divergen[] = 'el paso ' . $p['orden'] . ' de ' . $fila['paqueteId'] . ' no trae vencimiento/fechaReal';
            continue;
        }
        $ref = $fila['paqueteId'] . ':' . $p['orden'];
        if ($p['fechaReal'] !== null) {
            if ($p['vencimiento'] !== 'cumplido') {
                $divergen[] = $ref . ' esta cumplido y no dice «cumplido»';
            }
            continue;
        }
        if (isset($delTablero[$ref])) {
            $comparados++;
            if ($delTablero[$ref] !== $p['vencimiento']) {
                $divergen[] = $ref . ': tablero dice ' . $delTablero[$ref] . ' y el plan ' . $p['vencimiento'];
            }
        } elseif ($p['vencimiento'] !== 'adelante') {
            // No esta en el tablero: la unica razon legitima es que sea del corte «mas adelante».
            $divergen[] = $ref . ' no esta en el tablero y no es «adelante», sino ' . $p['vencimiento'];
        }
    }
}
$assert($comparados > 0, 'Hay pasos pendientes comparables entre el plan y el tablero. Comparados: ' . $comparados);
$assert($divergen === [],
    'El semaforo del plan coincide paso a paso con el corte del tablero. Divergencias: ' . implode(' | ', $divergen));

// --- Pendiente 2 del cierre: un paquete sin duracion_ref SI recibe fechas ---
// Es lo que hace que no haga falta escribir nada en el maestro global. Da Porto lo demuestra con el
// paquete 191 («Sum + Inst RED ELECTRICA»): duracion_ref NULL y, aun asi, plan calculado.
$prov = $db->query(
    'SELECT pp.paquete_id, pp.duracion_provisional, pp.dias_totales
       FROM pdc_plan_paquete pp
       JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
      WHERE pp.project_id = ? AND p.duracion_ref IS NULL AND pp.fecha_arranque IS NOT NULL',
    [P],
)->fetchAll(PDO::FETCH_ASSOC);
$assert($prov !== [],
    'Hay al menos un paquete sin duracion_ref con plan calculado: el camino estadistico de A4 funciona.');
foreach ($prov as $r) {
    $assert((int) $r['duracion_provisional'] === 1 && (int) $r['dias_totales'] > 0,
        'El paquete ' . $r['paquete_id'] . ' quedo marcado como provisional y con dias > 0. Dio '
        . json_encode($r));
}

// --- La ruta y su RBAC ---
$rutas = (string) file_get_contents(__DIR__ . '/../public/index.php');
$assert(str_contains($rutas, "\$router->get('/plan-compras/api/seguimiento/vencimientos'"),
    'La ruta GET de vencimientos esta registrada.');
$assert(
    strpos($rutas, "/plan-compras/api/seguimiento/vencimientos'") < strpos($rutas, "\$router->get('/plan-compras/api/seguimiento',"),
    'La ruta sufijada va antes que la desnuda, como el resto del bloque.',
);

$ctrl = (string) file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasSeguimientoController.php');
$assert(preg_match('/function vencimientos\(\): void\s*\{\s*\$projectId = \$this->guardLectura\(\)/', $ctrl) === 1,
    'El endpoint entra por el guard de LECTURA: el tablero solo lee, y exigirle el permiso de escritura '
    . 'dejaria sin ver sus vencimientos a quien solo consulta.');

// Un rol permitido y uno denegado, medidos contra la tabla que decide de verdad.
$permitidos = $db->query(
    'SELECT role_code FROM rbac_role_permissions WHERE permission_key = ? AND allowed = 1 ORDER BY role_code',
    ['lps.paquetes_contratacion.ver'],
)->fetchAll(PDO::FETCH_COLUMN);
$todos = $db->query('SELECT code FROM rbac_roles WHERE status = 1 ORDER BY code')->fetchAll(PDO::FETCH_COLUMN);
$denegados = array_values(array_diff($todos, $permitidos));
$assert($permitidos !== [], 'Hay al menos un rol que SI puede leer el tablero: ' . implode(', ', $permitidos));
$assert($denegados !== [], 'Y al menos uno que NO: ' . implode(', ', $denegados));

echo $fallos === 0 ? "\nOK\n" : "\n{$fallos} FALLOS\n";
exit($fallos === 0 ? 0 : 1);
