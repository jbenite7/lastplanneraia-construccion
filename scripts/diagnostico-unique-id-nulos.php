<?php
/**
 * DIAGNOSTICO (solo lectura) — actividades con `unique_id` NULL.
 *
 * Motivo: el 2026-08-12 se midio en `prueba-lps` (proyecto 73, Da Porto) que 98 de 273 filas de
 * `programa_consolidado` de la semana activa tienen `unique_id` y `Consecutivo_en_Programa` en
 * NULL. Sin ese identificador, Programacion Intermedia rechaza toda edicion con
 * «Id de actividad invalido» (public/js/modules/programacion_intermedia/hot.js:2988 y
 * src/Legacy/guardar_programacion_intermedia.php:172).
 *
 * Sospecha principal: la migracion obsoleta `database/migrations/20260712_remap_consolidado_unique_id.php`,
 * que anula `unique_id` en los encabezados y en las filas que no logra emparejar contra `programa`.
 *
 * Este script NO escribe nada: no tiene `--apply`, no abre transacciones y solo ejecuta SELECT /
 * SHOW. Sirve para dimensionar el dano y para saber cuantas filas se podrian recuperar y por que
 * regla, antes de decidir cualquier reparacion (que exige gate, respaldo y plan de restauracion
 * segun AGENTS.md).
 *
 * Uso:
 *   php scripts/diagnostico-unique-id-nulos.php                # todos los proyectos
 *   php scripts/diagnostico-unique-id-nulos.php --project=73   # solo un proyecto
 *   php scripts/diagnostico-unique-id-nulos.php --ejemplos=20  # cuantas filas de muestra imprimir
 *
 * Lee la conexion del `.env` del repositorio donde se ejecute (en el servidor de pruebas, el suyo).
 */

$repoRoot = dirname(__DIR__);

$dotenv = $repoRoot . '/.env';
if (is_file($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASS'] ?? '';

if ($dbName === '') {
    fwrite(STDERR, "No hay DB_NAME en el .env de {$repoRoot}. Abortado.\n");
    exit(1);
}

$argvList = $argv ?? [];
$soloProyecto = null;
$maxEjemplos = 10;
foreach ($argvList as $arg) {
    if (preg_match('/^--project=(\d+)$/', $arg, $m)) {
        $soloProyecto = (int) $m[1];
    }
    if (preg_match('/^--ejemplos=(\d+)$/', $arg, $m)) {
        $maxEjemplos = (int) $m[1];
    }
}

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

/** Barrera dura: esta sesion no puede escribir aunque alguien edite el script por descuido. */
$pdo->exec('SET SESSION TRANSACTION READ ONLY');

echo "DIAGNOSTICO unique_id NULL — base `{$dbName}` en {$dbHost}:{$dbPort}\n";
echo "Solo lectura: la sesion esta en TRANSACTION READ ONLY.\n";
echo str_repeat('=', 78) . "\n\n";

// ---------------------------------------------------------------- 1. Triggers
echo "1) Triggers que mantienen unique_id = Consecutivo\n";
$triggers = $pdo->query("SHOW TRIGGERS")->fetchAll();
$esperados = [
    'trg_programa_unique_id_INSERT',
    'trg_programa_unique_id_UPDATE',
    'trg_programa_consolidado_unique_id_INSERT',
    'trg_programa_consolidado_unique_id_UPDATE',
    'trg_programacion_semanal_unique_id_INSERT',
    'trg_programacion_semanal_unique_id_UPDATE',
];
$presentes = array_column($triggers, 'Trigger');
foreach ($esperados as $t) {
    $ok = in_array($t, $presentes, true);
    echo '   ' . ($ok ? '[OK]     ' : '[FALTA]  ') . $t . "\n";
}
$otros = array_values(array_filter($presentes, static fn ($t) => strpos($t, 'unique_id') !== false && !in_array($t, $esperados, true)));
if ($otros) {
    echo '   otros con unique_id: ' . implode(', ', $otros) . "\n";
}
echo "\n";

// ------------------------------------------------------- 2. Alcance por tabla
$filtroProyecto = $soloProyecto !== null ? ' WHERE project_id = ' . $soloProyecto : '';
$filtroProyectoAnd = $soloProyecto !== null ? ' AND project_id = ' . $soloProyecto : '';

echo "2) Filas con unique_id NULL, por tabla\n";
$tablas = [
    'programa' => 'SELECT COUNT(*) total, SUM(unique_id IS NULL) nulos FROM programa' . $filtroProyecto,
    'programa_consolidado' => 'SELECT COUNT(*) total, SUM(unique_id IS NULL) nulos FROM programa_consolidado' . $filtroProyecto,
    'programacion_semanal' => 'SELECT COUNT(*) total, SUM(unique_id IS NULL) nulos FROM programacion_semanal' . $filtroProyecto,
];
foreach ($tablas as $tabla => $sql) {
    $r = $pdo->query($sql)->fetch();
    $total = (int) $r['total'];
    $nulos = (int) $r['nulos'];
    $pct = $total > 0 ? round($nulos * 100 / $total, 1) : 0;
    printf("   %-22s total=%-7d nulos=%-7d (%s%%)\n", $tabla, $total, $nulos, $pct);
}
echo "\n";

// ------------------------------ 3. Detalle por proyecto y semana (consolidado)
echo "3) programa_consolidado — nulos por proyecto y semana (solo donde hay nulos)\n";
$sql = "SELECT pc.project_id,
               p.Proyecto_Proceso AS proyecto,
               pc.Semana,
               COUNT(*) AS total,
               SUM(pc.unique_id IS NULL) AS nulos,
               SUM(pc.unique_id IS NULL AND pc.Titulo = 0) AS nulos_actividad,
               SUM(pc.unique_id IS NULL AND pc.Titulo = 1) AS nulos_encabezado
        FROM programa_consolidado pc
        LEFT JOIN general_proyectos_procesos p ON p.ID = pc.project_id
        WHERE 1=1 {$filtroProyectoAnd}
        GROUP BY pc.project_id, proyecto, pc.Semana
        HAVING nulos > 0
        ORDER BY pc.project_id, pc.Semana";
$filas = $pdo->query($sql)->fetchAll();
if (!$filas) {
    echo "   (ninguna)\n";
} else {
    printf("   %-6s %-24s %-7s %-7s %-7s %-10s %-11s\n", 'proy', 'nombre', 'semana', 'total', 'nulos', 'actividad', 'encabezado');
    foreach ($filas as $f) {
        printf(
            "   %-6d %-24s %-7d %-7d %-7d %-10d %-11d\n",
            (int) $f['project_id'],
            mb_substr((string) ($f['proyecto'] ?? '?'), 0, 24),
            (int) $f['Semana'],
            (int) $f['total'],
            (int) $f['nulos'],
            (int) $f['nulos_actividad'],
            (int) $f['nulos_encabezado'],
        );
    }
}
echo "\n";

// --------------------------- 4. Cuantos nulos se podrian recuperar, y con que regla
echo "4) Recuperabilidad de los nulos de programa_consolidado\n";
echo "   Se prueba, en este orden, contra las filas de `programa` del mismo proyecto:\n";
echo "     A) Id + Actividad identicos y unico   B) Id identico y unico   C) sin candidato o ambiguo\n\n";

$proyectosConNulos = $pdo->query(
    'SELECT DISTINCT project_id FROM programa_consolidado WHERE unique_id IS NULL' . $filtroProyectoAnd . ' ORDER BY project_id'
)->fetchAll(PDO::FETCH_COLUMN);

$global = ['a' => 0, 'b' => 0, 'c' => 0, 'total' => 0];
$ejemplos = [];

foreach ($proyectosConNulos as $projectId) {
    $projectId = (int) $projectId;

    $porIdActividad = [];
    $porId = [];
    $stmt = $pdo->prepare('SELECT unique_id, Id, Actividad FROM programa WHERE project_id = ? AND unique_id IS NOT NULL');
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll() as $row) {
        $claveDoble = (string) $row['Id'] . '||' . (string) $row['Actividad'];
        $porIdActividad[$claveDoble][] = (int) $row['unique_id'];
        $porId[(string) $row['Id']][] = (int) $row['unique_id'];
    }

    $stmt = $pdo->prepare('SELECT row_id, Semana, Titulo, Id, Actividad FROM programa_consolidado WHERE project_id = ? AND unique_id IS NULL');
    $stmt->execute([$projectId]);
    $nulos = $stmt->fetchAll();

    $conteo = ['a' => 0, 'b' => 0, 'c' => 0];
    foreach ($nulos as $fila) {
        $claveDoble = (string) $fila['Id'] . '||' . (string) $fila['Actividad'];
        $claveId = (string) $fila['Id'];

        if (isset($porIdActividad[$claveDoble]) && count(array_unique($porIdActividad[$claveDoble])) === 1) {
            $conteo['a']++;
            continue;
        }
        if (isset($porId[$claveId]) && count(array_unique($porId[$claveId])) === 1) {
            $conteo['b']++;
            continue;
        }
        $conteo['c']++;
        if (count($ejemplos) < $maxEjemplos) {
            $candidatos = isset($porId[$claveId]) ? count(array_unique($porId[$claveId])) : 0;
            $ejemplos[] = sprintf(
                'proy=%d semana=%s row_id=%s Titulo=%s Id=%s candidatos_por_Id=%d',
                $projectId,
                $fila['Semana'],
                $fila['row_id'],
                $fila['Titulo'],
                $claveId,
                $candidatos,
            );
        }
    }

    printf(
        "   proyecto %-5d nulos=%-6d A=%-6d B=%-6d C(irrecuperables)=%-6d\n",
        $projectId,
        count($nulos),
        $conteo['a'],
        $conteo['b'],
        $conteo['c'],
    );

    $global['a'] += $conteo['a'];
    $global['b'] += $conteo['b'];
    $global['c'] += $conteo['c'];
    $global['total'] += count($nulos);
}

if (!$proyectosConNulos) {
    echo "   (ningun proyecto con nulos)\n";
}
echo "\n";

if ($ejemplos) {
    echo "   Ejemplos de filas sin candidato claro (grupo C):\n";
    foreach ($ejemplos as $linea) {
        echo "     {$linea}\n";
    }
    echo "\n";
}

// ------------------------------------------------------------- 5. Conclusion
echo "5) Resumen\n";
printf("   nulos en programa_consolidado: %d\n", $global['total']);
printf("   recuperables por Id+Actividad (A): %d\n", $global['a']);
printf("   recuperables solo por Id (B):      %d\n", $global['b']);
printf("   sin candidato o ambiguos (C):      %d\n", $global['c']);
echo "\n";
echo "   Nada de esto se ha escrito. La reparacion es un paso aparte y necesita\n";
echo "   autorizacion explicita, respaldo verificable y plan de restauracion.\n";
