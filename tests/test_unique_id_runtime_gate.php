<?php
// @requiere: puro

// Excepciones documentadas (mismo patrón que docs/design-system/exceptions.json): un hallazgo
// real y medido, con responsable, razón y fecha de vencimiento -- no un ignore silencioso. Una
// excepción vencida deja de contar y el hallazgo vuelve a fallar el gate.
$exceptionsPath = __DIR__ . '/unique-id-runtime-gate-exceptions.json';
$exceptions = [];
if (is_file($exceptionsPath)) {
    $decoded = json_decode((string) file_get_contents($exceptionsPath), true);
    $today = date('Y-m-d');
    foreach ($decoded['exceptions'] ?? [] as $entry) {
        if (($entry['expiresAt'] ?? '') < $today) {
            continue;
        }
        $exceptions[$entry['file'] . ':' . $entry['line']] = $entry;
    }
}

$root = dirname(__DIR__);
$scanDirs = [
    $root . '/src',
    $root . '/public',
    $root . '/views',
    $root . '/frontend/src',
];

// Directorios de salida compilada: bundles minificados sin saltos de linea reales, donde el
// patron "WHERE|JOIN|ON ... Consecutivo en la misma linea" produce falsos positivos estructurales
// (texto interno de librerias de terceros como Zod cae a metros de distancia de un ON o WHERE
// sueltos). El codigo fuente legible que los genera vive en frontend/src, que si se escanea.
$excludedDirs = [
    $root . '/public/app/assets',
    $root . '/public/pdc-app/assets',
    $root . '/public/ct-app/assets',
];

$forbiddenPatterns = [
    '/\b(?:WHERE|JOIN|ON)\b[^\n]*(?:Consecutivo_en_Programa|Consecutivo_En_Programa|consecutivo_en_programa|ConsecutivoEnPrograma)\b/i',
    '/\bprograma\s*\.\s*Consecutivo\b/i',
];

$failures = [];
$waived = [];
foreach ($scanDirs as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (!preg_match('/\.(php|js|ts|tsx)$/', $path)) {
            continue;
        }

        foreach ($excludedDirs as $excludedDir) {
            if (str_starts_with($path, $excludedDir . '/')) {
                continue 2;
            }
        }

        $lines = file($path);
        foreach ($lines as $lineNumber => $line) {
            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $relative = str_replace($root . '/', '', $path);
                    $lineKey = $relative . ':' . ($lineNumber + 1);
                    if (isset($exceptions[$lineKey])) {
                        $waived[] = $lineKey . ' -> ' . trim($line);
                        continue;
                    }
                    $failures[] = $lineKey . ' -> ' . trim($line);
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
if (!empty($waived)) {
    echo "Excepciones documentadas vigentes (tests/unique-id-runtime-gate-exceptions.json):\n";
    foreach ($waived as $entry) {
        echo " - {$entry}\n";
    }
}
