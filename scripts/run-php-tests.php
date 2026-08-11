<?php

declare(strict_types=1);

/**
 * Runner de la suite tests/test_*.php.
 *
 * Los tests de este repositorio son scripts autoejecutables sin framework. Este
 * runner los descubre, lee de cada uno la etiqueta `// @requiere: <nivel>` que
 * declara qué entorno necesita, y ejecuta los que el entorno actual puede
 * honrar.
 *
 * Uso:
 *   php scripts/run-php-tests.php --nivel=http
 *   php scripts/run-php-tests.php --nivel=puro --solo-listar
 *
 * Códigos de salida:
 *   0  todos los tests seleccionados pasaron
 *   1  algún test falló
 *   2  el runner no puede operar (falta una etiqueta, el nivel no existe,
 *      el entorno del nivel pedido no está disponible)
 */

const NIVELES = [
    'puro' => 0,
    'db' => 1,
    'http' => 2,
    'datos-proyecto' => 3,
];

/**
 * Señales de que un test comprobó algo de verdad. Un test que sale 0 sin
 * imprimir ninguna de estas es un verde sin respaldo: no demuestra nada.
 */
const SENALES_DE_COMPROBACION = ['pass', 'ok', 'comprobacion', 'comprobación', '✓', 'correcto'];

const SALIDA_OK = 0;
const SALIDA_FALLO = 1;
const SALIDA_NO_OPERABLE = 2;

/**
 * Lee las opciones de línea de comandos.
 *
 * @param list<string> $argv
 * @return array{dir: string, nivel: string, timeout: int, soloListar: bool, dbHost: ?string}
 */
function leerOpciones(array $argv): array
{
    $opciones = [
        'dir' => dirname(__DIR__) . '/tests',
        'nivel' => 'puro',
        'timeout' => 120,
        'soloListar' => false,
        'dbHost' => null,
    ];

    foreach (array_slice($argv, 1) as $argumento) {
        if ($argumento === '--solo-listar') {
            $opciones['soloListar'] = true;
            continue;
        }
        if (preg_match('/^--dir=(.+)$/', $argumento, $coincidencia) === 1) {
            $opciones['dir'] = rtrim($coincidencia[1], '/');
            continue;
        }
        if (preg_match('/^--nivel=(.+)$/', $argumento, $coincidencia) === 1) {
            $opciones['nivel'] = $coincidencia[1];
            continue;
        }
        if (preg_match('/^--timeout=(\d+)$/', $argumento, $coincidencia) === 1) {
            $opciones['timeout'] = (int) $coincidencia[1];
            continue;
        }
        if (preg_match('/^--db-host=(.+)$/', $argumento, $coincidencia) === 1) {
            $opciones['dbHost'] = $coincidencia[1];
            continue;
        }

        abortar("opción desconocida: {$argumento}");
    }

    return $opciones;
}

function abortar(string $motivo): never
{
    fwrite(STDERR, "ERROR: {$motivo}\n");
    exit(SALIDA_NO_OPERABLE);
}

/**
 * Extrae la etiqueta `// @requiere: <nivel>` de las primeras 40 líneas.
 * Devuelve null si el archivo no la declara.
 */
function leerNivelDeclarado(string $rutaTest): ?string
{
    $manejador = fopen($rutaTest, 'r');
    if ($manejador === false) {
        abortar("no se pudo leer {$rutaTest}");
    }

    $cabecera = '';
    for ($linea = 0; $linea < 40; $linea++) {
        $contenido = fgets($manejador);
        if ($contenido === false) {
            break;
        }
        $cabecera .= $contenido;
    }
    fclose($manejador);

    if (preg_match('/^\s*\/\/\s*@requiere:\s*([a-z-]+)\s*$/m', $cabecera, $coincidencia) === 1) {
        return $coincidencia[1];
    }

    return null;
}

/**
 * Descubre los tests y su nivel. Aborta si alguno no lleva etiqueta: un test
 * sin declarar es exactamente cómo nacían fuera del CI.
 *
 * @return array<string, string> ruta => nivel
 */
function descubrirTests(string $directorio): array
{
    $rutas = glob($directorio . '/test_*.php');
    if ($rutas === false || $rutas === []) {
        abortar("no se encontró ningún test en {$directorio}");
    }
    sort($rutas);

    $sinEtiqueta = [];
    $tests = [];
    foreach ($rutas as $ruta) {
        $nivel = leerNivelDeclarado($ruta);
        if ($nivel === null) {
            $sinEtiqueta[] = basename($ruta);
            continue;
        }
        if (!isset(NIVELES[$nivel])) {
            abortar(basename($ruta) . " declara un nivel que no existe: '{$nivel}'");
        }
        $tests[$ruta] = $nivel;
    }

    if ($sinEtiqueta !== []) {
        $lista = implode("\n  - ", $sinEtiqueta);
        abortar(
            count($sinEtiqueta) . " test(s) sin la etiqueta '// @requiere: <nivel>':\n  - {$lista}\n"
            . '  Niveles válidos: ' . implode(', ', array_keys(NIVELES))
        );
    }

    return $tests;
}

/**
 * Ejecuta un test como subproceso y devuelve su código de salida y su salida
 * combinada. El timeout evita que un test colgado bloquee el CI.
 *
 * @return array{codigo: int, salida: string}
 */
function ejecutarTest(string $rutaTest, int $segundosDeEspera): array
{
    $descriptores = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proceso = proc_open(
        [PHP_BINARY, $rutaTest],
        $descriptores,
        $tuberias,
        dirname($rutaTest, 2)
    );

    if (!is_resource($proceso)) {
        return ['codigo' => SALIDA_NO_OPERABLE, 'salida' => 'no se pudo lanzar el proceso'];
    }

    stream_set_blocking($tuberias[1], false);
    stream_set_blocking($tuberias[2], false);

    $salida = '';
    $limite = microtime(true) + $segundosDeEspera;
    $codigo = null;

    while (true) {
        $salida .= (string) stream_get_contents($tuberias[1]);
        $salida .= (string) stream_get_contents($tuberias[2]);

        $estado = proc_get_status($proceso);
        if (!$estado['running']) {
            $codigo = $estado['exitcode'];
            break;
        }
        if (microtime(true) > $limite) {
            proc_terminate($proceso, 9);
            $salida .= "\n  [el runner lo cortó tras {$segundosDeEspera}s]";
            $codigo = SALIDA_FALLO;
            break;
        }
        usleep(20000);
    }

    $salida .= (string) stream_get_contents($tuberias[1]);
    $salida .= (string) stream_get_contents($tuberias[2]);

    fclose($tuberias[1]);
    fclose($tuberias[2]);
    proc_close($proceso);

    return ['codigo' => $codigo ?? SALIDA_FALLO, 'salida' => $salida];
}

/**
 * Un test que sale 0 sin decir nada que respalde ese verde no ha demostrado
 * nada. Se reporta aparte en vez de contarlo como éxito.
 */
function esVerdeSinRespaldo(int $codigo, string $salida): bool
{
    if ($codigo !== SALIDA_OK) {
        return false;
    }

    $enMinusculas = mb_strtolower($salida);
    foreach (SENALES_DE_COMPROBACION as $senal) {
        if (str_contains($enMinusculas, $senal)) {
            return false;
        }
    }

    return true;
}

// ---------------------------------------------------------------------------

$opciones = leerOpciones($argv);

if (!isset(NIVELES[$opciones['nivel']])) {
    abortar(
        "el nivel '{$opciones['nivel']}' no existe. Válidos: " . implode(', ', array_keys(NIVELES))
    );
}

$pesoPedido = NIVELES[$opciones['nivel']];
$tests = descubrirTests($opciones['dir']);

$seleccionados = [];
$omitidos = [];
foreach ($tests as $ruta => $nivel) {
    if (NIVELES[$nivel] <= $pesoPedido) {
        $seleccionados[$ruta] = $nivel;
        continue;
    }
    $omitidos[$ruta] = $nivel;
}

echo "Runner de tests PHP — nivel '{$opciones['nivel']}'\n";
echo '  ' . count($tests) . ' test(s) descubiertos, ' . count($seleccionados)
    . ' seleccionados, ' . count($omitidos) . " omitidos por nivel\n\n";

if ($opciones['soloListar']) {
    foreach (array_keys(NIVELES) as $nivel) {
        $delNivel = array_keys($tests, $nivel, true);
        if ($delNivel === []) {
            continue;
        }
        echo '  ' . $nivel . ' (' . count($delNivel) . "):\n";
        foreach ($delNivel as $ruta) {
            echo '    ' . basename($ruta) . "\n";
        }
    }
    exit(SALIDA_OK);
}

if ($seleccionados === []) {
    echo "No hay nada que ejecutar en este nivel.\n";
    exit(SALIDA_OK);
}

$pasaron = [];
$fallaron = [];
$sospechosos = [];

foreach ($seleccionados as $ruta => $nivel) {
    $nombre = basename($ruta);
    $resultado = ejecutarTest($ruta, $opciones['timeout']);

    if ($resultado['codigo'] !== SALIDA_OK) {
        $fallaron[$nombre] = $resultado;
        echo "  FALLA   {$nombre} (rc={$resultado['codigo']}, {$nivel})\n";
        continue;
    }

    if (esVerdeSinRespaldo($resultado['codigo'], $resultado['salida'])) {
        $sospechosos[$nombre] = $resultado;
        echo "  SOSPECHOSO  {$nombre} (salió 0 sin comprobar nada, {$nivel})\n";
        continue;
    }

    $pasaron[] = $nombre;
    echo "  pasa    {$nombre} ({$nivel})\n";
}

echo "\n";
echo '=== ' . count($seleccionados) . ' corridos: ' . count($pasaron) . ' pasaron, '
    . count($fallaron) . ' fallaron, ' . count($sospechosos) . ' sospechosos, '
    . count($omitidos) . " omitidos por nivel ===\n";

if ($fallaron !== []) {
    echo "\nDetalle de los que fallaron:\n";
    foreach ($fallaron as $nombre => $resultado) {
        echo "\n--- {$nombre} (rc={$resultado['codigo']}) ---\n";
        $lineas = explode("\n", trim($resultado['salida']));
        foreach (array_slice($lineas, -15) as $linea) {
            echo "  {$linea}\n";
        }
    }
}

if ($sospechosos !== []) {
    echo "\nSOSPECHOSOS — salieron 0 sin imprimir nada que respalde el verde:\n";
    foreach (array_keys($sospechosos) as $nombre) {
        echo "  - {$nombre}\n";
    }
    echo "  Un test que no puede fallar no está comprobando nada. Dale aserciones.\n";
}

exit($fallaron === [] && $sospechosos === [] ? SALIDA_OK : SALIDA_FALLO);
