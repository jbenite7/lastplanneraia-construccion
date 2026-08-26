<?php
// @requiere: db

/**
 * CT-7.3 (Task 4, Paso 4) — verifica que la migracion
 * database/migrations/20260827_pi_shared_constraints_gestion.sql quedo aplicada tal cual la
 * describe la spec: las 5 columnas nuevas con su tipo/nulabilidad exacta, el enum de
 * EstadoLiberacion con exactamente esos 4 valores, y el backfill correcto para TODAS las filas
 * existentes (no solo una muestra — con 191 filas, verificar el universo completo es barato y
 * es mas fuerte que una muestra).
 *
 * Solo lectura: ninguna sentencia de este archivo escribe en pi_shared_constraints.
 */

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/TableResolver.php';

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
$columnas = $db->query(
    "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_TYPE, COLUMN_DEFAULT
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pi_shared_constraints'"
)->fetchAll(PDO::FETCH_ASSOC);

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
$valorObjetivoCol = $db->query(
    "SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pi_shared_constraints' AND COLUMN_NAME = 'ValorObjetivo'"
)->fetch(PDO::FETCH_ASSOC);
okGestion(
    ($valorObjetivoCol['COLUMN_TYPE'] ?? '') === 'varchar(20)' && ($valorObjetivoCol['IS_NULLABLE'] ?? '') === 'NO',
    'ValorObjetivo conserva su tipo original varchar(20) NOT NULL'
);

// ------------------------------------------- 4. Backfill correcto para TODAS las filas (191)
echo PHP_EOL . "=== Backfill de EstadoLiberacion contra la regla CT-7.3 ===" . PHP_EOL;

$filas = $db->query('SELECT Id, project_id, ValorObjetivo, EstadoLiberacion FROM pi_shared_constraints')->fetchAll(PDO::FETCH_ASSOC);
okGestion(count($filas) === 191, 'universo de filas es 191 (no crecio ni encogio con la migracion — count real: ' . count($filas) . ')');

$discrepancias = [];
$conteoPorEstado = ['sin_gestionar' => 0, 'en_gestion' => 0, 'liberada' => 0, 'no_aplica' => 0];
foreach ($filas as $fila) {
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

okGestion(count($discrepancias) === 0, 'las 191 filas tienen el EstadoLiberacion que predice la regla de backfill');
foreach (array_slice($discrepancias, 0, 10) as $linea) {
    echo "    discrepancia: {$linea}\n";
}

// Distribucion esperada, medida por el dry-run (scripts/dry-run-constraints-gestion.php) contra
// la misma base antes de aplicar: sin_gestionar=34, en_gestion=5, liberada=131, no_aplica=21.
okGestion($conteoPorEstado['sin_gestionar'] === 34, "sin_gestionar=34 (real: {$conteoPorEstado['sin_gestionar']})");
okGestion($conteoPorEstado['en_gestion'] === 5, "en_gestion=5 (real: {$conteoPorEstado['en_gestion']})");
okGestion($conteoPorEstado['liberada'] === 131, "liberada=131 (real: {$conteoPorEstado['liberada']})");
okGestion($conteoPorEstado['no_aplica'] === 21, "no_aplica=21 (real: {$conteoPorEstado['no_aplica']})");

echo PHP_EOL;
echo $failed === 0 ? "=== CT-7.3 constraints gestion schema: OK ===\n" : "=== CT-7.3 constraints gestion schema: FAIL ===\n";
exit($failed > 0 ? 1 : 0);
