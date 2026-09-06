<?php
// @requiere: db

/**
 * CT-7.3 (Task 4, Paso 4) — verifica que la migracion
 * database/migrations/20260827_pi_shared_constraints_gestion.sql quedo aplicada tal cual la
 * describe la spec: las 5 columnas nuevas con su tipo/nulabilidad exacta, y el enum de
 * EstadoLiberacion con exactamente esos 4 valores.
 *
 * Corregido 2026-08-26 (Important 2, revision independiente de Task 4): la version anterior
 * exigia `count($filas) === 191` y 4 conteos literales (34/5/131/21) — una foto de los datos de
 * dev el dia de la migracion. Peor: exigia que TODA fila tuviera el EstadoLiberacion que predice
 * ValorObjetivo, un invariante que esta misma feature esta disenada para romper en cuanto
 * alguien (Task 5/7) fije un estado a mano. Ese test se habria puesto en rojo con el primer uso
 * legitimo del producto, no con un bug.
 *
 * La regla de backfill solo se verifica contra las filas que EXISTIAN al aplicar la migracion —
 * un corte por CreadoEn, no las 191 en abstracto: MAX(CreadoEn) observado en esas 191 filas
 * (dev, 2026-08-26) era 2026-08-18 11:53:29. Cualquier fila creada despues de ese corte es obra
 * del producto (Task 5/7 u otra), no de esta migracion, y queda fuera de esa asercion — puede
 * traer cualquier EstadoLiberacion, incluida gestion manual que no coincida con ValorObjetivo.
 *
 * Solo lectura: ninguna sentencia de este archivo escribe en pi_shared_constraints.
 */

// Corte de CreadoEn: filas con CreadoEn <= este valor existian antes de que la migracion
// corriera (2026-08-26) y por lo tanto SI deben cumplir la regla de backfill. Filas mas nuevas
// son obra del producto, no de la migracion, y no se juzgan aqui.
const CORTE_CREADO_EN_MIGRACION = '2026-08-18 11:53:29';

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/TableResolver.php';
require_once __DIR__ . '/support/ScopeFixture.php';

$db = Database::getInstance();
$failed = 0;

function okGestion(bool $condition, string $message): void
{
    global $failed;
    echo ($condition ? '  PASS: ' : '  FAIL: ') . $message . PHP_EOL;
    if (!$condition) {
        $failed++;
    }
}

echo "=== CT-7.3 pi_shared_constraints: columnas de gestion ===" . PHP_EOL;

// ------------------------------------------------------- 1. Las 5 columnas, tipo y nulabilidad
// Los metadatos se piden por la puerta de `Database`: `information_schema` es tabla calificada
// por schema y el gate la rechaza, así que armarla aquí a mano moriría antes de comprobar nada.
$columnas = $db->columnDefinitions('pi_shared_constraints');

$porNombre = [];
foreach ($columnas as $c) {
    $porNombre[$c['COLUMN_NAME']] = $c;
}

$esperadas = [
    'ResponsableAsignado' => ['tipo' => 'varchar', 'nulo' => 'YES'],
    'FechaCompromiso' => ['tipo' => 'date', 'nulo' => 'YES'],
    'EstadoLiberacion' => ['tipo' => 'enum', 'nulo' => 'NO'],
    'AsignadoPor' => ['tipo' => 'varchar', 'nulo' => 'YES'],
    'AsignadoEn' => ['tipo' => 'datetime', 'nulo' => 'YES'],
];

foreach ($esperadas as $nombre => $spec) {
    $col = $porNombre[$nombre] ?? null;
    okGestion($col !== null, "columna {$nombre} existe");
    if ($col === null) {
        continue;
    }
    okGestion($col['DATA_TYPE'] === $spec['tipo'], "columna {$nombre} es tipo {$spec['tipo']} (real: {$col['DATA_TYPE']})");
    okGestion($col['IS_NULLABLE'] === $spec['nulo'], "columna {$nombre} nulabilidad esperada={$spec['nulo']} (real: {$col['IS_NULLABLE']})");
}

// ------------------------------------------------------------- 2. El enum, exactamente 4 valores
$tipoEnum = $porNombre['EstadoLiberacion']['COLUMN_TYPE'] ?? '';
okGestion(
    $tipoEnum === "enum('sin_gestionar','en_gestion','liberada','no_aplica')",
    "EstadoLiberacion es enum('sin_gestionar','en_gestion','liberada','no_aplica') exacto (real: {$tipoEnum})"
);

// ---------------------------------------------------- 3. ValorObjetivo intacto (la migracion
// solo LEE esa columna para calcular EstadoLiberacion; nunca debio escribirla)
$valorObjetivoCol = null;
foreach ($db->columnDefinitions('pi_shared_constraints') as $definicion) {
    if ($definicion['COLUMN_NAME'] === 'ValorObjetivo') {
        $valorObjetivoCol = $definicion;
        break;
    }
}
okGestion(
    ($valorObjetivoCol['COLUMN_TYPE'] ?? '') === 'varchar(20)' && ($valorObjetivoCol['IS_NULLABLE'] ?? '') === 'NO',
    'ValorObjetivo conserva su tipo original varchar(20) NOT NULL'
);

// ------------------- 4. Backfill correcto SOLO para las filas que existian al migrar (Important 2)
echo PHP_EOL . "=== Backfill de EstadoLiberacion contra la regla CT-7.3 (filas pre-migracion) ===" . PHP_EOL;

// Esta lectura cruza obras a propósito y no puede no hacerlo: lo que se juzga es si el backfill
// de una migración fue correcto en TODA la base, no en una obra. Va como mantenimiento, con su
// razón escrita; acotarla a un proyecto convertiría el control en una muestra y dejaría sin
// comprobar justo las filas donde la migración pudo equivocarse.
$todasLasFilas = ScopeFixture::comoSistema(
    $db,
    'test:constraints-gestion-schema:backfill-de-migracion',
    static fn () => $db->query(
        'SELECT Id, project_id, ValorObjetivo, EstadoLiberacion, CreadoEn FROM pi_shared_constraints'
    )->fetchAll(PDO::FETCH_ASSOC),
);

$filasPreMigracion = array_values(array_filter(
    $todasLasFilas,
    static fn (array $f): bool => $f['CreadoEn'] <= CORTE_CREADO_EN_MIGRACION
));
$filasPosteriores = count($todasLasFilas) - count($filasPreMigracion);

echo "  filas totales hoy: " . count($todasLasFilas) . " (pre-migracion: " . count($filasPreMigracion) . ", posteriores al corte: {$filasPosteriores})\n";
okGestion(
    count($filasPreMigracion) > 0,
    'hay al menos una fila pre-migracion contra la que verificar la regla de backfill'
);

$discrepancias = [];
$conteoPorEstado = ['sin_gestionar' => 0, 'en_gestion' => 0, 'liberada' => 0, 'no_aplica' => 0];
foreach ($filasPreMigracion as $fila) {
    $crudo = trim((string) $fila['ValorObjetivo']);
    if (!is_numeric($crudo)) {
        $esperado = 'no_aplica';
    } else {
        $valor = (float) $crudo;
        if ($valor === 0.0) {
            $esperado = 'sin_gestionar';
        } elseif ($valor >= 1.0) {
            $esperado = 'liberada';
        } else {
            $esperado = 'en_gestion';
        }
    }
    $conteoPorEstado[$fila['EstadoLiberacion']] = ($conteoPorEstado[$fila['EstadoLiberacion']] ?? 0) + 1;
    if ($fila['EstadoLiberacion'] !== $esperado) {
        $discrepancias[] = sprintf(
            'project_id=%s Id=%s ValorObjetivo=%s esperado=%s real=%s',
            $fila['project_id'],
            $fila['Id'],
            $crudo,
            $esperado,
            $fila['EstadoLiberacion']
        );
    }
}

okGestion(
    count($discrepancias) === 0,
    'las filas pre-migracion (' . count($filasPreMigracion) . ') tienen el EstadoLiberacion que predice la regla de backfill'
);
foreach (array_slice($discrepancias, 0, 10) as $linea) {
    echo "    discrepancia: {$linea}\n";
}

// Informativo, no asercion: distribucion actual entre las filas pre-migracion. Puede diferir de
// lo que midio el dry-run si alguien ya fijo un EstadoLiberacion a mano sobre una fila vieja
// (legitimo — D33/D30) sin que eso sea un fallo de esta migracion.
echo '  distribucion (pre-migracion): ' . implode(', ', array_map(
    static fn ($estado, $n) => "{$estado}={$n}",
    array_keys($conteoPorEstado),
    array_values($conteoPorEstado)
)) . "\n";

echo PHP_EOL;
echo $failed === 0 ? "=== CT-7.3 constraints gestion schema: OK ===\n" : "=== CT-7.3 constraints gestion schema: FAIL ===\n";
exit($failed > 0 ? 1 : 0);
