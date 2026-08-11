<?php

declare(strict_types=1);

// @requiere: puro

/**
 * Verifica el contrato de scripts/run-php-tests.php: descubrimiento, etiqueta
 * obligatoria y código de salida.
 *
 * Corre contra los fixtures de tests/fixtures/runner/, nunca contra la suite
 * real: así puede comprobar el caso «test sin etiqueta» sin ensuciar tests/.
 */

$total = 0;
$fallos = 0;

function verificar(string $descripcion, bool $condicion): void
{
    global $total, $fallos;
    $total++;
    if ($condicion) {
        echo "  PASS: {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FAIL: {$descripcion}\n";
}

$runner = __DIR__ . '/../scripts/run-php-tests.php';
$fixtures = __DIR__ . '/fixtures/runner';

function correrRunner(string $runner, array $args): array
{
    $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner);
    foreach ($args as $arg) {
        $comando .= ' ' . escapeshellarg($arg);
    }
    $salida = [];
    $codigo = 0;
    exec($comando . ' 2>&1', $salida, $codigo);

    return ['codigo' => $codigo, 'salida' => implode("\n", $salida)];
}

// Un directorio con un test etiquetado y verde sale 0.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/con-etiqueta', '--nivel=puro']);
verificar('un test etiquetado y verde devuelve 0', $r['codigo'] === 0);
verificar('el resumen dice cuantos corrieron', str_contains($r['salida'], '1'));

// Un test sin etiqueta rompe el runner con codigo 2.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/sin-etiqueta', '--nivel=puro']);
verificar('un test sin etiqueta devuelve 2', $r['codigo'] === 2);
verificar('el error nombra el archivo sin etiqueta', str_contains($r['salida'], 'test_sin.php'));

// Un nivel inventado se rechaza.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/con-etiqueta', '--nivel=inventado']);
verificar('un nivel invalido devuelve 2', $r['codigo'] === 2);

echo "\n";
if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}
echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
