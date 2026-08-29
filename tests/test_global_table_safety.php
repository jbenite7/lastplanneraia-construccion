<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$roots = [
    __DIR__ . '/../src',
    __DIR__ . '/../admin/src',
    __DIR__ . '/../public',
];

$globalTables = Database::globalTableNames();
$tablePattern = implode('|', array_map(static fn($table) => preg_quote($table, '/'), $globalTables));
$failures = [];

function phpFiles(array $roots): Generator
{
    foreach ($roots as $root) {
        if (!is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (!str_ends_with($path, '.php')) {
                continue;
            }

            if (str_contains($path, '/vendor/')) {
                continue;
            }

            yield $path;
        }
    }
}

function relativePath(string $path): string
{
    $root = realpath(dirname(__DIR__));
    $realPath = realpath($path) ?: $path;
    return $root ? str_replace($root . '/', '', $realPath) : $path;
}

foreach (phpFiles($roots) as $path) {
    $content = file_get_contents($path);
    if ($content === false) {
        $failures[] = relativePath($path) . ': no se pudo leer el archivo';
        continue;
    }

    $lines = preg_split('/\R/', $content);
    foreach ($lines as $index => $line) {
        $lineNumber = $index + 1;

        if (preg_match('/TRUNCATE\s+TABLE\s+`?(?:\{\$(?:dbPrefix|prefix)\}_)?(' . $tablePattern . ')\b/i', $line, $match)) {
            $failures[] = relativePath($path) . ":{$lineNumber}: TRUNCATE no permitido sobre tabla global {$match[1]}";
        }
    }

    if (preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?\{\$(?:dbPrefix|prefix)\}_(' . $tablePattern . ')\b/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as [$table, $offset]) {
            $prefix = substr($content, max(0, $offset - 1600), 1600);
            $isAdminRollbackTemplate = str_ends_with($path, '/admin/src/Models/Project.php');
            $isGuarded = str_contains($prefix, 'isUsingGlobalTables()');

            if (!$isAdminRollbackTemplate && !$isGuarded) {
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
                $failures[] = relativePath($path) . ":{$lineNumber}: CREATE runtime sin guard global para {$table}";
            }
        }
    }
}

if ($failures !== []) {
    echo "=== Global Table Safety: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

$db = Database::getInstance();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['db'] = 'da_porto';
$_SESSION['project_id'] = 73;
$db->dataScope()->clear();
$db->dataScope()->bind(new \App\Security\DataScope\ProjectScope(73, 'test.A', 'A'));

try {
    $db->query(
        "SELECT a.Id,
                (SELECT pc.Actividad
                 FROM da_porto_programa_consolidado pc
                 WHERE pc.Semana = a.Semana
                 LIMIT 1) AS nombreActividadInicio
         FROM da_porto_cic a
         WHERE a.Semana = ?
         LIMIT 1",
        [1],
    );

    echo "=== Global Table Safety: FAIL ===\n";
    echo " - Consulta prefijada compleja sin project_id no falló cerrada.\n";
    exit(1);
} catch (RuntimeException $e) {
    if (!str_contains($e->getMessage(), 'project_id')) {
        echo "=== Global Table Safety: FAIL ===\n";
        echo " - Error inesperado en guard de scope: " . $e->getMessage() . "\n";
        exit(1);
    }
}

$db->query(
    "SELECT a.Id,
            (SELECT pc.Actividad
             FROM da_porto_programa_consolidado pc
             WHERE pc.project_id = a.project_id
               AND pc.Semana = a.Semana
             LIMIT 1) AS nombreActividadInicio
     FROM da_porto_cic a
     WHERE a.project_id = ? AND a.Semana = ?
     LIMIT 1",
    [73, 1],
);

try {
    $db->beginTransaction();
    $db->query("INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)", [
        73,
        9999,
        1,
        'comprometer',
        'E2E rewrite source',
    ]);
    $db->query("INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
        SELECT project_id, semana, consecutivo + 1, accion, 'E2E rewrite copy'
        FROM auto_program_log
        WHERE project_id = ? AND detalle = ?", [73, 'E2E rewrite source']);

    $copyCount = (int) $db->query(
        "SELECT COUNT(*) FROM auto_program_log WHERE project_id = ? AND semana = ? AND detalle = ?",
        [73, 9999, 'E2E rewrite copy']
    )->fetchColumn();

    $db->dataScope()->clear();
    $db->dataScope()->bind(\App\Security\DataScope\SystemScope::forMaintenance('global safety cross-project verification'));
    $crossProjectCount = (int) $db->query(
        "SELECT COUNT(*) FROM auto_program_log WHERE project_id <> ? AND semana = ? AND detalle LIKE ?",
        [73, 9999, 'E2E rewrite%']
    )->fetchColumn();

    if ($copyCount !== 1 || $crossProjectCount !== 0) {
        throw new RuntimeException("INSERT SELECT global produjo copyCount={$copyCount}, crossProjectCount={$crossProjectCount}");
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo "=== Global Table Safety: FAIL ===\n";
    echo " - Validación dinámica INSERT SELECT: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->dataScope()->clear();
}

echo "=== Global Table Safety: OK ===\n";
echo "No hay TRUNCATE ni CREATE runtime inseguro sobre tablas globales.\n";
echo "INSERT SELECT global conserva project_id e IDs por proyecto.\n";
