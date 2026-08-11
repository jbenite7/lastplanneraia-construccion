<?php

/**
 * Guard: todo tipo de tabla literal que el codigo le pide a TableResolver tiene que estar en su
 * lista blanca.
 *
 * Por que existe. El 2026-08-04, al retirar el PDC v1, salio `'pdc'` de `$validTables` pero
 * quedaron tres llamadas vivas en SubcontratistasApiController. `resolveByPrefix` lanza
 * InvalidArgumentException con un tipo desconocido, asi que la pantalla de Subcontratistas dejo de
 * cargar («Error del servidor: Invalid table type: pdc») y el renombrado de un subcontratista dejo
 * de propagarse a sus dependencias.
 *
 * Lo caro del caso fue que **ningun gate lo vio**: phpstan pasa —la llamada es valida, el argumento
 * es un string— y la suite estatica del design system da 8/8 porque esto es una ruptura de tiempo
 * de ejecucion. Solo aparecio abriendo la pantalla. Este test cierra ese hueco de forma estatica.
 *
 * Cubre el caso general: retirar un tipo de la lista sin podar a sus consumidores.
 */
declare(strict_types=1);
// @requiere: puro


$root = dirname(__DIR__);

$resolverSource = file_get_contents($root . '/src/Core/TableResolver.php');
if ($resolverSource === false) {
    fwrite(STDERR, "No se pudo leer src/Core/TableResolver.php\n");
    exit(1);
}

if (!preg_match('/\$validTables\s*=\s*\[(.*?)\];/s', $resolverSource, $m)) {
    fwrite(STDERR, "No se encontro \$validTables en TableResolver\n");
    exit(1);
}
preg_match_all("/'([^']+)'/", $m[1], $valid);
$validTables = $valid[1];

if (count($validTables) === 0) {
    fwrite(STDERR, "\$validTables se leyo vacia: el guard no estaria comprobando nada\n");
    exit(1);
}

$directorios = [$root . '/src', $root . '/admin/src'];
$fallos = [];
$comprobadas = 0;

foreach ($directorios as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterador as $archivo) {
        if ($archivo->getExtension() !== 'php') {
            continue;
        }
        $ruta = $archivo->getPathname();
        if (str_ends_with($ruta, 'src/Core/TableResolver.php')) {
            continue;
        }
        $codigo = file_get_contents($ruta);
        if ($codigo === false) {
            continue;
        }

        // Solo literales: `resolve($x, 'tipo')` y `resolveByPrefix($x, 'tipo')`. Las llamadas con
        // variable no se pueden comprobar aqui y quedan fuera a proposito.
        preg_match_all(
            "/TableResolver::(?:resolve|resolveByPrefix)\s*\(\s*[^,]+,\s*'([^']+)'\s*\)/",
            $codigo,
            $usos,
            PREG_OFFSET_CAPTURE,
        );

        foreach ($usos[1] as $uso) {
            $comprobadas++;
            [$tipo, $offset] = $uso;
            if (!in_array($tipo, $validTables, true)) {
                $linea = substr_count(substr($codigo, 0, $offset), "\n") + 1;
                $relativa = str_replace($root . '/', '', $ruta);
                $fallos[] = "$relativa:$linea pide '$tipo', que no esta en \$validTables";
            }
        }
    }
}

if ($comprobadas === 0) {
    fwrite(STDERR, "No se encontro ninguna llamada literal a TableResolver: el guard quedaria mudo\n");
    exit(1);
}

if (count($fallos) > 0) {
    fwrite(STDERR, "=== TableResolver Consumers: FALLO ===\n");
    foreach ($fallos as $f) {
        fwrite(STDERR, "  - $f\n");
    }
    fwrite(STDERR, "\nO el tipo vuelve a \$validTables, o se poda al consumidor.\n");
    exit(1);
}

echo "=== TableResolver Consumers: OK ===\n";
echo "$comprobadas llamadas literales comprobadas contra " . count($validTables) . " tipos validos.\n";
