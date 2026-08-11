<?php
// @requiere: puro


$root = dirname(__DIR__);
$scanDirs = [
    $root . '/src',
    $root . '/public',
    $root . '/views',
];

$forbiddenPatterns = [
    '/\b(?:WHERE|JOIN|ON)\b[^\n]*(?:Consecutivo_en_Programa|Consecutivo_En_Programa|consecutivo_en_programa|ConsecutivoEnPrograma)\b/i',
    '/\bprograma\s*\.\s*Consecutivo\b/i',
];

$failures = [];
foreach ($scanDirs as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (!preg_match('/\.(php|js)$/', $path)) {
            continue;
        }

        $lines = file($path);
        foreach ($lines as $lineNumber => $line) {
            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $failures[] = str_replace($root . '/', '', $path) . ':' . ($lineNumber + 1) . ' -> ' . trim($line);
                }
            }
        }
    }
}

if (!empty($failures)) {
    echo "=== Unique ID Runtime Gate: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "=== Unique ID Runtime Gate: OK ===\n";
echo "No hay filtros o joins runtime usando consecutivos legacy como llave técnica.\n";
