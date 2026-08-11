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

// Pedir un nivel db sin base de datos alcanzable aborta con 2, no da verde.
// Es el guardarrail que nace de lo medido el 2026-08-10: 26 tests de la suite
// salen 0 cuando no hay base de datos, porque capturan el fallo de conexion.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--nivel=db',
    '--db-host=host.invalido.imposible',
]);
verificar('sin base de datos, el nivel db aborta con 2', $r['codigo'] === 2);
verificar('el error explica que falta la base', stripos($r['salida'], 'base de datos') !== false);
verificar('la ausencia de entorno no se reporta como verde', stripos($r['salida'], 'OK:') === false);

// Un test que sale 0 sin decir nada se reporta como sospechoso y no da verde global.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/mudo', '--nivel=puro']);
verificar('un verde sin respaldo se marca sospechoso', str_contains($r['salida'], 'SOSPECHOSO'));
verificar('un verde sin respaldo no deja el runner en 0', $r['codigo'] !== 0);

// Un test que se salta solo sale 0, pero no ha comprobado nada: contarlo entre
// los que pasaron infla la cobertura que el CI dice tener.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/salta', '--nivel=puro']);
verificar('un test que se salta solo se cuenta aparte', str_contains($r['salida'], 'se saltaron solos'));
verificar('un test que se salta solo no se cuenta como que paso', str_contains($r['salida'], '0 pasaron'));

// --- PHPUnit: los mismos dos guardarraíles, trasladados -----------------------------------------

// Una clase PHPUnit sin grupo de nivel rompe el runner, igual que un script sin @requiere.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-sin-grupo',
    '--nivel=puro',
]);
verificar('una clase PHPUnit sin grupo devuelve 2', $r['codigo'] === 2);
verificar('el error nombra la clase sin grupo', str_contains($r['salida'], 'SinGrupoTest.php'));

// Un test PHPUnit verde no altera el resultado; uno rojo hace fallar al runner.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-verde',
    '--nivel=puro',
]);
verificar('con PHPUnit en verde el runner sale 0', $r['codigo'] === 0);
verificar('el resumen cuenta los tests PHPUnit', stripos($r['salida'], 'phpunit') !== false);

$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-rojo',
    '--nivel=puro',
]);
verificar('un test PHPUnit rojo hace fallar al runner', $r['codigo'] === 1);

// Sin el binario de PHPUnit y con tests suyos en el nivel, el runner aborta: no da verde.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-verde',
    '--nivel=puro',
    '--phpunit=/ruta/que/no/existe/phpunit',
]);
verificar('sin el binario de PHPUnit el runner aborta con 2', $r['codigo'] === 2);
verificar('el error explica que falta PHPUnit', stripos($r['salida'], 'phpunit') !== false);
verificar('la ausencia de PHPUnit no se reporta como verde', stripos($r['salida'], 'OK:') === false);

// Pero si el nivel no selecciona ningún test PHPUnit, su ausencia da igual.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-vacio',
    '--nivel=puro',
    '--phpunit=/ruta/que/no/existe/phpunit',
]);
verificar('sin tests PHPUnit seleccionados, su ausencia no estorba', $r['codigo'] === 0);

// --- El runner es ACUMULATIVO: pedir un nivel ejecuta también los de debajo ----------------------
//
// Esta es la propiedad de la que depende la aserción de acumulatividad de
// tests/design-system/visual-ci-contract.test.mjs, que da por bueno que si el CI invoca
// `--nivel=http` entonces una prueba de nivel `db` se ejecuta. Hasta el 2026-08-11 nada la
// comprobaba de frente: cambiando el `<=` del runner por `===` el contrato seguía en verde y
// test_global_table_safety dejaba de correr en el CI. Lo destapó la sesión coordinadora mutando el
// supuesto en vez de las entradas.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/dos-niveles', '--nivel=db', '--solo-listar']);
verificar('pedir db ejecuta el test de nivel db', str_contains($r['salida'], '[ejecuta] test_b_db.php'));
verificar('pedir db ejecuta TAMBIEN el de nivel puro', str_contains($r['salida'], '[ejecuta] test_a_puro.php'));

$r = correrRunner($runner, ['--dir=' . $fixtures . '/dos-niveles', '--nivel=puro', '--solo-listar']);
verificar('pedir puro ejecuta el de nivel puro', str_contains($r['salida'], '[ejecuta] test_a_puro.php'));
verificar('pedir puro NO ejecuta el de nivel db', str_contains($r['salida'], '[omite]   test_b_db.php'));

echo "\n";
if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}
echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
