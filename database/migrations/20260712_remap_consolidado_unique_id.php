<?php
/**
 * Re-mapea programa_consolidado.unique_id / Consecutivo_en_Programa
 * para apuntar al nuevo programa.Consecutivo, usando la clave natural
 * (project_id, Id, Actividad, Titulo=0). Los Titulo=1 quedan NULL (sin FK).
 *
 * Esto restaura la integridad referencial requerida por las FKs
 * añadidas por 20260703_program_unique_id_refactor.php.
 *
 * Estrategia:
 *   1. Construir mapa (project_id, Id, Actividad) -> programa.Consecutivo
 *      (solo para Titulo=0 rows, donde existe el match).
 *   2. Para cada programa_consolidado (Titulo=0): unique_id = mapa[Id|Actividad].
 *   3. Para Titulo=1 y proyectos sin programa: unique_id = NULL, Consecutivo_en_Programa = NULL.
 *
 * Dry-run por defecto. --apply para ejecutar.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap env (parity with legacy-to-global migration)
$dotenv = __DIR__ . '/../../.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASS'] ?? '';

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$pdo->exec("SET time_zone = '-05:00'");

$apply = in_array('--apply', $argv ?? [], true);

/**
 * GUARDARRAÍL AÑADIDO EL 2026-07-30 — esta migración quedó obsoleta y es destructiva.
 *
 * Su premisa (línea «title rows do not FK to programa») era cierta en julio de 2026 con el esquema
 * de entonces. Hoy NO lo es: `fk_pc__programa__unique_id` referencia `programa (project_id,
 * unique_id)` —no `programa.Consecutivo`— y `programa` SÍ contiene los encabezados con su propio
 * `unique_id`. La migración confunde las dos columnas: solo `Consecutivo_en_Programa` necesitaba
 * anularse en los encabezados, y anula también `unique_id`.
 *
 * Consecuencia medida: `PlanFechasService::semanaYFrentes()` exige `unique_id IS NOT NULL` y marca
 * `esFrente = Titulo === 1`. Con los encabezados anulados, una obra se queda SIN NINGÚN FRENTE al
 * que amarrar paquetes — el desplegable «Elegir frente…» ofrece solo actividades. Le pasó a
 * `prueba-lps` (proyecto 27) el 2026-07-29.
 *
 * Dry-run sobre la base de desarrollo del 2026-07-30: `pc_t1_nulled=12136` —todos los encabezados
 * de todos los proyectos, Da Porto incluido— y su mapa de reasignación encuentra UNA entrada.
 *
 * Se aborta antes de tocar nada. `--acepto-destruir-los-frentes` existe solo para un entorno
 * legado que de verdad siga con el esquema de julio; si lo necesitas, mide antes.
 */
$excepcion = in_array('--acepto-destruir-los-frentes', $argv ?? [], true);
if (!$excepcion) {
    $encabezadosVivos = (int) $pdo->query(
        'SELECT COUNT(*) FROM programa WHERE Titulo = 1 AND unique_id IS NOT NULL'
    )->fetchColumn();
    if ($encabezadosVivos > 0) {
        fwrite(STDERR, <<<TXT

        ABORTADA: esta migración está obsoleta y borraría los frentes de obra.

        `programa` tiene {$encabezadosVivos} encabezados (Titulo=1) con `unique_id`, así que la FK
        `fk_pc__programa__unique_id` se satisface y NO hace falta anular nada en
        `programa_consolidado`. Anularlo deja a las obras sin frentes a los que amarrar paquetes
        en el Plan de Compras (PlanFechasService::semanaYFrentes exige `unique_id IS NOT NULL`).

        Si de verdad estás en un entorno con el esquema de julio de 2026, mide primero:
          SELECT COUNT(*) FROM programa WHERE Titulo = 1 AND unique_id IS NOT NULL;   -- debe dar 0
        y solo entonces vuelve a lanzarla con --acepto-destruir-los-frentes.

        TXT);
        exit(1);
    }
}

$stats = [
    'pc_rows_total' => 0,
    'pc_t0' => 0,
    'pc_t1' => 0,
    'pc_t0_mapped' => 0,
    'pc_t0_orphan' => 0,
    'pc_t1_nulled' => 0,
    'pc_already_ok' => 0,
    'orphan_examples' => [],
    'ps_rows_total' => 0,
    'ps_mapped' => 0,
    'ps_orphan' => 0,
    'ps_nulled' => 0,
    'ps_already_ok' => 0,
];

$projectIds = $pdo->query("SELECT DISTINCT project_id FROM programa_consolidado")->fetchAll(PDO::FETCH_COLUMN);
sort($projectIds);

foreach ($projectIds as $projectId) {
    echo "== project $projectId ==\n";

    // Map: (Id, Actividad) -> programa.Consecutivo for Titulo=0 rows of this project
    $map = [];
    $stmt = $pdo->prepare("SELECT Consecutivo, Id, Actividad FROM programa WHERE project_id = ? AND Titulo = 0");
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['Id'] . '||' . $row['Actividad'];
        $map[$key] = (int) $row['Consecutivo'];
    }
    echo "  programa Titulo=0 mapa: " . count($map) . " entries\n";

    // Walk every consolidado row
    $stmt = $pdo->prepare("SELECT Consecutivo, Semana, Id, Actividad, Titulo, unique_id, Consecutivo_en_Programa FROM programa_consolidado WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['pc_rows_total'] += count($rows);

    $batched = [];
    foreach ($rows as $row) {
        $stats['pc_t' . (int) $row['Titulo']]++;
        $cons = (int) $row['Consecutivo'];
        $semana = (int) $row['Semana'];

        if ((int) $row['Titulo'] === 1) {
            // Set to NULL — title rows do not FK to programa
            if ($row['unique_id'] !== null || $row['Consecutivo_en_Programa'] !== null) {
                $stats['pc_t1_nulled']++;
                $batched[] = ['cons' => $cons, 'sem' => $semana, 'target' => null];
            } else {
                $stats['pc_already_ok']++;
            }
            continue;
        }

        // Titulo=0 — try to map
        $key = $row['Id'] . '||' . $row['Actividad'];
        if (!isset($map[$key])) {
            // Orphan — null the FK columns to satisfy referential integrity (NULL is allowed in nullable FKs)
            $stats['pc_t0_orphan']++;
            if (count($stats['orphan_examples']) < 10) {
                $stats['orphan_examples'][] = "project=$projectId cons=$cons sem=$semana Id={$row['Id']}";
            }
            $batched[] = ['cons' => $cons, 'sem' => $semana, 'target' => null];
            continue;
        }
        $target = $map[$key];
        if ((int) $row['unique_id'] === $target && (int) $row['Consecutivo_en_Programa'] === $target) {
            $stats['pc_already_ok']++;
            continue;
        }
        $stats['pc_t0_mapped']++;
        $batched[] = ['cons' => $cons, 'sem' => $semana, 'target' => $target];
    }

    if ($apply && !empty($batched)) {
        $upd = $pdo->prepare("UPDATE programa_consolidado SET Consecutivo_en_Programa = ?, unique_id = ? WHERE Consecutivo = ? AND project_id = ?");
        foreach ($batched as $b) {
            $upd->execute([$b['target'], $b['target'], $b['cons'], $projectId]);
        }
    }
    unset($batched);
}

echo "\n== programacion_semanal ==\n";
$psIds = $pdo->query("SELECT DISTINCT project_id FROM programacion_semanal")->fetchAll(PDO::FETCH_COLUMN);
sort($psIds);
foreach ($psIds as $projectId) {
    $map = [];
    $stmt = $pdo->prepare("SELECT Consecutivo, Id, Actividad FROM programa WHERE project_id = ?");
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['Id'] . '||' . $row['Actividad']] = (int) $row['Consecutivo'];
    }
    $stmt = $pdo->prepare("SELECT Consecutivo, Consecutivo_En_Programa, Id, Actividad, unique_id FROM programacion_semanal WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['ps_rows_total'] += count($rows);

    $batched = [];
    foreach ($rows as $row) {
        $cons = (int) $row['Consecutivo'];
        $key = $row['Id'] . '||' . $row['Actividad'];
        if (!isset($map[$key])) {
            $stats['ps_orphan']++;
            $batched[] = ['cons' => $cons, 'target' => null];
            continue;
        }
        $target = $map[$key];
        if ((int) $row['unique_id'] === $target && (int) $row['Consecutivo_En_Programa'] === $target) {
            $stats['ps_already_ok']++;
            continue;
        }
        $stats['ps_mapped']++;
        $batched[] = ['cons' => $cons, 'target' => $target];
    }
    if ($apply && !empty($batched)) {
        $upd = $pdo->prepare("UPDATE programacion_semanal SET Consecutivo_En_Programa = ?, unique_id = ? WHERE Consecutivo = ? AND project_id = ?");
        foreach ($batched as $b) {
            $upd->execute([$b['target'], $b['target'], $b['cons'], $projectId]);
        }
    }
}

// Tablas auxiliares con FK a programa: setear unique_id = NULL para huérfanos
$auxTables = [
    ['table' => 'pi_shared_constraint_links', 'fk_col' => 'ConsecutivoEnPrograma'],
    ['table' => 'auto_program_log', 'fk_col' => 'consecutivo'],
];
foreach ($auxTables as $cfg) {
    $tbl = $cfg['table'];
    $fkCol = $cfg['fk_col'];
    echo "\n== $tbl (orphan -> NULL) ==\n";
    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
    if ($cnt === 0) continue;
    // Find orphans: rows with unique_id NOT NULL that don't have a matching programa
    $orphans = $pdo->query("
        SELECT t.id, t.project_id, t.unique_id
        FROM `$tbl` t
        LEFT JOIN programa p ON p.project_id = t.project_id AND p.unique_id = t.unique_id
        WHERE t.unique_id IS NOT NULL AND p.unique_id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "  orphans: " . count($orphans) . "\n";
    if ($apply && !empty($orphans)) {
        $upd = $pdo->prepare("UPDATE `$tbl` SET unique_id = NULL WHERE id = ?");
        foreach ($orphans as $o) {
            $upd->execute([$o['id']]);
        }
    }
}

echo "\n";
echo ($apply ? 'APPLY' : 'DRY  ') . " consolidado:\n";
echo "  rows_total={$stats['pc_rows_total']} (Titulo=0:{$stats['pc_t0']}, Titulo=1:{$stats['pc_t1']})\n";
echo "  pc_t0_mapped={$stats['pc_t0_mapped']} pc_t0_orphan={$stats['pc_t0_orphan']} pc_t1_nulled={$stats['pc_t1_nulled']} already_ok={$stats['pc_already_ok']}\n";
echo "  programacion_semanal: rows_total={$stats['ps_rows_total']} ps_mapped={$stats['ps_mapped']} ps_orphan={$stats['ps_orphan']} already_ok={$stats['ps_already_ok']}\n";
if (!empty($stats['orphan_examples'])) {
    echo "  Orfandad ejemplos:\n";
    foreach ($stats['orphan_examples'] as $line) echo "    $line\n";
}
