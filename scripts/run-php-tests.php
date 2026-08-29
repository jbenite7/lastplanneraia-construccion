<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/php-test-lane-manifest.php';

/**
 * Runner de las pruebas PHP. Puerta única de las dos suites que conviven:
 *
 *   - los `tests/test_*.php`, scripts autoejecutables sin framework, que declaran su entorno
 *     con `// @requiere: <nivel>` en la cabecera;
 *   - los `tests/unit/*Test.php`, clases de PHPUnit, que lo declaran con `#[Group('<nivel>')]`.
 *
 * Se escriben nuevas con PHPUnit; las de scripts no se migran. Es una sola puerta a propósito: con
 * dos comandos, cada job del CI tendría que acordarse de invocar los dos, que es el defecto que
 * este runner vino a cerrar.
 *
 * Los cuatro niveles runtime, de menos a más exigente, son:
 * puro < db < http < datos-proyecto. `--nivel=X` ejecuta X y los niveles runtime inferiores.
 * `admin-db` es una lane separada y NO acumulativa: selecciona únicamente tests que crean o
 * eliminan fixtures de schema y nunca entra en db/http/datos-proyecto.
 *
 * Uso:
 *   php scripts/run-php-tests.php --nivel=http
 *   php scripts/run-php-tests.php --nivel=puro --solo-listar
 *
 * Códigos de salida:
 *   0  todo lo seleccionado pasó
 *   1  algo falló
 *   2  el runner no puede operar: falta una etiqueta o un grupo, el nivel no existe, el entorno
 *      del nivel pedido no está disponible, o falta el binario de PHPUnit habiendo tests suyos.
 *      Nunca se traduce una ausencia en un resultado verde.
 */

/**
 * Señales de que un test comprobó algo de verdad. Un test que sale 0 sin
 * imprimir ninguna de estas es un verde sin respaldo: no demuestra nada.
 */
// El detector juzgaba en un idioma distinto al que este repo usa para reportar: buscaba `pass`
// mientras siete tests de `tests/` anuncian su verde con `PASA:` en español. `str_contains('pasa:',
// 'pass')` es false, asi que un test correcto —con sus tres caminos de fallo y su `exit(1)`— se
// marcaba SOSPECHOSO. Medido el 2026-08-24: `test_causa_atribucion.php` tuvo `main` en rojo casi
// una hora, y el mensaje del propio hallazgo mandaba al sitio equivocado («dale aserciones»)
// porque las aserciones ya estaban. Los otros seis se salvaban de rebote, por imprimir ademas
// alguna otra linea con una senal reconocida: la trampa seguia armada para el siguiente.
//
// Va `pasa:` con los dos puntos, no `pasa` a secas, y la diferencia importa: a secas tambien
// casaria dentro de «no pasa», y un test que saliera 0 anunciando su propio fallo quedaria verde.
// Con los dos puntos cubre los siete casos reales y no inventa ninguno.
const SENALES_DE_COMPROBACION = ['pass', 'pasa:', 'ok', 'comprobacion', 'comprobación', '✓', 'correcto'];

const SALIDA_OK = 0;
const SALIDA_FALLO = 1;
const SALIDA_NO_OPERABLE = 2;

/**
 * Lee las opciones de línea de comandos.
 *
 * @param list<string> $argv
 * @return array{
 *   dir: string,
 *   dirUnit: string,
 *   dirUnitPorDefecto: bool,
 *   phpunit: string,
 *   nivel: string,
 *   timeout: int,
 *   soloListar: bool,
 *   dbHost: ?string
 * }
 */
function leerOpciones(array $argv): array
{
    $opciones = [
        'dir' => dirname(__DIR__) . '/tests',
        'dirUnit' => dirname(__DIR__) . '/tests/unit',
        'dirUnitPorDefecto' => true,
        'phpunit' => dirname(__DIR__) . '/vendor/bin/phpunit',
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
        if (preg_match('/^--dir-unit=(.+)$/', $argumento, $coincidencia) === 1) {
            $opciones['dirUnit'] = rtrim($coincidencia[1], '/');
            $opciones['dirUnitPorDefecto'] = false;
            continue;
        }
        if (preg_match('/^--phpunit=(.+)$/', $argumento, $coincidencia) === 1) {
            $opciones['phpunit'] = $coincidencia[1];
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
        if (!array_key_exists($nivel, PhpTestLaneManifest::levels())) {
            abortar(basename($ruta) . " declara un nivel que no existe: '{$nivel}'");
        }
        $tests[$ruta] = $nivel;
    }

    if ($sinEtiqueta !== []) {
        $lista = implode("\n  - ", $sinEtiqueta);
        abortar(
            count($sinEtiqueta) . " test(s) sin la etiqueta '// @requiere: <nivel>':\n  - {$lista}\n"
            . '  Niveles válidos: ' . implode(', ', array_keys(PhpTestLaneManifest::levels()))
        );
    }

    return $tests;
}

/**
 * Descubre las clases de test de PHPUnit y su nivel.
 *
 * Mismo trato que los scripts: el nivel se declara y sin declaración no se corre. Aquí se lee del
 * atributo `#[Group('<nivel>')]` de la clase en vez de un comentario, pero la garantía es la misma
 * —un test nuevo no puede quedar fuera del CI en silencio—, y se comprueba escaneando el archivo
 * para no depender de la API de PHPUnit.
 *
 * El nivel se declara en la CLASE, no en el método: un caso que necesite otro entorno va a otra
 * clase. Es la regla simple que basta hoy.
 *
 * @return array<string, string> ruta => nivel
 */
function descubrirTestsUnitarios(string $directorio): array
{
    if (!is_dir($directorio)) {
        return [];
    }

    $rutas = glob($directorio . '/*Test.php');
    if ($rutas === false || $rutas === []) {
        return [];
    }
    sort($rutas);

    $sinGrupo = [];
    $clases = [];
    foreach ($rutas as $ruta) {
        $cabecera = (string) file_get_contents($ruta);

        if (preg_match('/#\[Group\(\s*[\'"]([a-z-]+)[\'"]\s*\)\]/', $cabecera, $coincidencia) !== 1) {
            $sinGrupo[] = basename($ruta);
            continue;
        }
        if (!array_key_exists($coincidencia[1], PhpTestLaneManifest::levels())) {
            abortar(
                basename($ruta) . " declara un grupo que no es un nivel: '{$coincidencia[1]}'.\n"
                . '  Niveles válidos: ' . implode(', ', array_keys(PhpTestLaneManifest::levels()))
            );
        }
        $clases[$ruta] = $coincidencia[1];
    }

    if ($sinGrupo !== []) {
        $lista = implode("\n  - ", $sinGrupo);
        abortar(
            count($sinGrupo) . " clase(s) de PHPUnit sin el atributo #[Group('<nivel>')]:\n  - {$lista}\n"
            . '  Niveles válidos: ' . implode(', ', array_keys(PhpTestLaneManifest::levels())) . "\n"
            . '  Sin declarar su entorno, un test nuevo queda fuera del CI sin que nadie se entere.'
        );
    }

    return $clases;
}

/**
 * Comprueba que el entorno que exige un nivel está disponible.
 *
 * Existe por algo medido, no por prudencia teórica: el 2026-08-10 se comprobó
 * que 26 tests de la suite salen 0 cuando no hay base de datos, porque capturan
 * el fallo de conexión y terminan bien. Ejecutarlos sin base daría 26 verdes
 * que no comprobaron nada. Por eso la ausencia de entorno es un error del
 * runner y nunca un resultado verde.
 *
 * @return string|null null si el entorno está; si no, qué falta
 */
function entornoDisponible(string $nivel, ?string $anfitrionDeBase): ?string
{
    if ($nivel === 'puro') {
        return null;
    }
    if ($nivel === 'admin-db' && getenv('LPS_ADMIN_DB_LANE') !== '1') {
        return "falta LPS_ADMIN_DB_LANE=1 para habilitar la lane administrativa explícita";
    }

    $anfitrion = $anfitrionDeBase ?? (getenv('DB_HOST') ?: 'db');
    $puerto = getenv('DB_PORT') ?: '3306';
    $base = (string) getenv('DB_NAME');

    try {
        new PDO(
            "mysql:host={$anfitrion};port={$puerto};dbname={$base}",
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Throwable $error) {
        return 'no hay base de datos alcanzable: ' . $error->getMessage();
    }

    if ($nivel === 'db' || $nivel === 'admin-db') {
        return null;
    }

    $contexto = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $respuesta = @file_get_contents('http://127.0.0.1/login', false, $contexto);
    if ($respuesta === false) {
        return 'la aplicacion no responde por HTTP en 127.0.0.1';
    }

    return null;
}

/**
 * Ejecuta un test como subproceso y devuelve su código de salida y su salida
 * combinada. El timeout evita que un test colgado bloquee el CI.
 *
 * @return array{codigo: int, salida: string}
 */
function ejecutarTest(string $rutaTest, int $segundosDeEspera): array
{
    // Ruta absoluta y directorio de trabajo fijo en la raíz del repositorio: los
    // tests se escribieron para correrse desde ahí (`php tests/test_x.php`), y
    // varios resuelven rutas relativas a ese punto.
    $rutaAbsoluta = realpath($rutaTest);
    if ($rutaAbsoluta === false) {
        return ['codigo' => SALIDA_NO_OPERABLE, 'salida' => "no se encontró {$rutaTest}"];
    }

    return ejecutarProceso([PHP_BINARY, $rutaAbsoluta], $segundosDeEspera);
}

/**
 * Lanza un subproceso y devuelve su código de salida y su salida combinada.
 *
 * Siempre desde la raíz del repositorio, y leyendo el código de salida del proceso, nunca de una
 * tubería — ver `memoria/trampas/el-codigo-de-salida-se-pierde-en-la-tuberia.md`.
 *
 * @param list<string> $argumentos
 * @return array{codigo: int, salida: string}
 */
function ejecutarProceso(array $argumentos, int $segundosDeEspera): array
{
    $descriptores = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proceso = proc_open(
        $argumentos,
        $descriptores,
        $tuberias,
        dirname(__DIR__)
    );

    if (!is_resource($proceso)) {
        return ['codigo' => SALIDA_NO_OPERABLE, 'salida' => 'no se pudo lanzar el proceso'];
    }

    stream_set_blocking($tuberias[1], false);
    stream_set_blocking($tuberias[2], false);

    $salida = '';
    $limite = microtime(true) + $segundosDeEspera;
    $codigo = SALIDA_FALLO;

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

    return ['codigo' => $codigo, 'salida' => $salida];
}

/**
 * Varios tests de la suite se saltan solos cuando la base no trae los datos que
 * esperan («SKIP: el proyecto 73 no está sembrado…») y salen 0. Contarlos entre
 * los que pasaron infla la cobertura que el CI dice tener, así que se cuentan
 * aparte. Un test que se salta siempre en CI está mal etiquetado: su sitio es
 * el nivel 'datos-proyecto'.
 */
function seSaltoSolo(int $codigo, string $salida): bool
{
    if ($codigo !== SALIDA_OK || preg_match('/^\s*SKIP\b/mi', $salida) !== 1) {
        return false;
    }

    // Un test puede saltarse una parte y comprobar el resto: eso es un pase, no
    // un salto. Sólo cuenta como salto si no comprobó nada en absoluto.
    return !tieneSenalDeComprobacion($salida);
}

function tieneSenalDeComprobacion(string $salida): bool
{
    $enMinusculas = mb_strtolower($salida);
    foreach (SENALES_DE_COMPROBACION as $senal) {
        if (str_contains($enMinusculas, $senal)) {
            return true;
        }
    }

    return false;
}

/**
 * Un test que sale 0 sin decir nada que respalde ese verde no ha demostrado
 * nada. Se reporta aparte en vez de contarlo como éxito.
 */
function esVerdeSinRespaldo(int $codigo, string $salida): bool
{
    return $codigo === SALIDA_OK && !tieneSenalDeComprobacion($salida);
}

// ---------------------------------------------------------------------------

$opciones = leerOpciones($argv);

if (!array_key_exists($opciones['nivel'], PhpTestLaneManifest::levels())) {
    abortar(
        "el nivel '{$opciones['nivel']}' no existe. Válidos: " . implode(', ', array_keys(PhpTestLaneManifest::levels()))
    );
}

$tests = descubrirTests($opciones['dir']);
$unitarios = descubrirTestsUnitarios($opciones['dirUnit']);

$seleccionados = [];
$omitidos = [];
foreach ($tests as $ruta => $nivel) {
    if (PhpTestLaneManifest::select($opciones['nivel'], $nivel)) {
        $seleccionados[$ruta] = $nivel;
        continue;
    }
    $omitidos[$ruta] = $nivel;
}

echo "Runner de tests PHP — nivel '{$opciones['nivel']}'\n";
echo '  ' . count($tests) . ' test(s) descubiertos, ' . count($seleccionados)
    . ' seleccionados, ' . count($omitidos) . " omitidos por nivel\n\n";

if ($opciones['soloListar']) {
    // Marca cada test con lo que haría el nivel pedido, no solo el nivel que declara. Así se puede
    // comprobar de frente que el runner es ACUMULATIVO —que pedir 'db' ejecuta también los 'puro'—
    // sin necesitar el entorno de ese nivel. Esa propiedad la da por buena la aserción de
    // tests/design-system/visual-ci-contract.test.mjs, y hasta el 2026-08-11 nada la comprobaba:
    // cambiando el '<=' de la selección por '===' el contrato seguía verde mientras
    // test_global_table_safety dejaba de correr en el CI.
    foreach (array_keys(PhpTestLaneManifest::levels()) as $nivel) {
        $delNivel = array_keys($tests, $nivel, true);
        if ($delNivel === []) {
            continue;
        }
        echo '  ' . $nivel . ' (' . count($delNivel) . "):\n";
        foreach ($delNivel as $ruta) {
            $marca = isset($seleccionados[$ruta]) ? '[ejecuta]' : '[omite]  ';
            echo '    ' . $marca . ' ' . basename($ruta) . "\n";
        }
    }

    foreach ($unitarios as $ruta => $nivel) {
        $marca = PhpTestLaneManifest::select($opciones['nivel'], $nivel) ? '[ejecuta]' : '[omite]  ';
        echo '    ' . $marca . ' ' . basename($ruta) . " (PHPUnit, {$nivel})\n";
    }

    exit(SALIDA_OK);
}

if ($seleccionados === []) {
    echo "No hay nada que ejecutar en este nivel.\n";
    exit(SALIDA_OK);
}

// Se comprueba el nivel PEDIDO, no el de los tests seleccionados: si alguien
// pide 'db' y no hay base, la respuesta honesta es abortar, no correr los
// 'puro' y devolver un verde que nadie pidió.
$loQueFalta = entornoDisponible($opciones['nivel'], $opciones['dbHost']);
if ($loQueFalta !== null) {
    abortar(
        "el entorno del nivel '{$opciones['nivel']}' no está disponible: {$loQueFalta}\n"
        . '  No se ejecutó ningún test. La ausencia de entorno no es un resultado verde.'
    );
}

$pasaron = [];
$fallaron = [];
$sospechosos = [];
$saltados = [];

foreach ($seleccionados as $ruta => $nivel) {
    $nombre = basename($ruta);
    $resultado = ejecutarTest($ruta, $opciones['timeout']);

    if ($resultado['codigo'] !== SALIDA_OK) {
        $fallaron[$nombre] = $resultado;
        echo "  FALLA   {$nombre} (rc={$resultado['codigo']}, {$nivel})\n";
        continue;
    }

    if (seSaltoSolo($resultado['codigo'], $resultado['salida'])) {
        $saltados[$nombre] = $resultado;
        echo "  se salta  {$nombre} (no comprobó nada, {$nivel})\n";
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

// --- PHPUnit -----------------------------------------------------------------------------------
// Los tests nuevos se escriben con PHPUnit y viven en tests/unit/. El runner sigue siendo la puerta
// única: si fueran dos comandos, cada job del CI tendría que acordarse de invocar los dos, que es
// exactamente el defecto que este runner vino a cerrar.
$unitariosSeleccionados = [];
foreach ($unitarios as $ruta => $nivel) {
    if (PhpTestLaneManifest::select($opciones['nivel'], $nivel)) {
        $unitariosSeleccionados[$ruta] = $nivel;
    }
}

$resultadoPhpunit = null;
if ($unitariosSeleccionados !== []) {
    if (!is_file($opciones['phpunit']) || !is_executable($opciones['phpunit'])) {
        abortar(
            "el nivel '{$opciones['nivel']}' incluye " . count($unitariosSeleccionados)
            . " test(s) de PHPUnit, pero no hay binario ejecutable en {$opciones['phpunit']}.\n"
            . "  Instálalo con 'composer install' incluyendo las dependencias de desarrollo.\n"
            . '  No ejecutarlos no es un resultado verde.'
        );
    }

    // PHPUnit 12 no trata "--group=a,b" como un OR entre grupos (visto el 2026-08-19: con
    // TableResolverTest.php como primera clase no-'puro', el runner pasó a pedir dos grupos a la
    // vez y PHPUnit devolvió "No tests executed!" en vez de ejecutar ninguno). Cada grupo va en su
    // propia bandera `--group=X`, que es la forma que sí filtra por OR.
    $listaDeGrupos = array_values(array_unique($unitariosSeleccionados));
    $grupos = implode(',', $listaDeGrupos);
    $argumentos = [$opciones['phpunit']];
    foreach ($listaDeGrupos as $grupo) {
        $argumentos[] = '--group=' . $grupo;
    }
    if (!$opciones['dirUnitPorDefecto']) {
        // Con un directorio explícito no vale phpunit.xml, que apunta a tests/unit.
        array_push($argumentos, '--no-configuration', '--bootstrap', dirname(__DIR__) . '/vendor/autoload.php', $opciones['dirUnit']);
    }

    echo "\n";
    echo '  PHPUnit: ' . count($unitariosSeleccionados) . ' clase(s) en los grupos ' . $grupos . "\n";
    $resultadoPhpunit = ejecutarProceso($argumentos, $opciones['timeout']);
    foreach (explode("\n", trim($resultadoPhpunit['salida'])) as $linea) {
        if (trim($linea) !== '') {
            echo '    ' . $linea . "\n";
        }
    }
}

echo "\n";
echo '=== ' . count($seleccionados) . ' corridos: ' . count($pasaron) . ' pasaron, '
    . count($fallaron) . ' fallaron, ' . count($sospechosos) . ' sospechosos, '
    . count($saltados) . ' se saltaron solos, '
    . count($omitidos) . " omitidos por nivel ===\n";

if ($resultadoPhpunit !== null) {
    echo '=== PHPUnit: ' . count($unitariosSeleccionados) . ' clase(s), '
        . ($resultadoPhpunit['codigo'] === SALIDA_OK ? 'en verde' : 'CON FALLOS')
        . " (rc={$resultadoPhpunit['codigo']}) ===\n";
} elseif ($unitarios !== []) {
    echo '=== PHPUnit: ninguna de sus ' . count($unitarios)
        . " clase(s) entra en este nivel ===\n";
}

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

if ($saltados !== []) {
    echo "\nSE SALTARON SOLOS — salieron 0 sin comprobar nada porque les faltaban datos:\n";
    foreach (array_keys($saltados) as $nombre) {
        echo "  - {$nombre}\n";
    }
    echo "  Si se saltan siempre en CI, su nivel es 'datos-proyecto'.\n";
}

if ($sospechosos !== []) {
    echo "\nSOSPECHOSOS — salieron 0 sin imprimir nada que respalde el verde:\n";
    foreach (array_keys($sospechosos) as $nombre) {
        echo "  - {$nombre}\n";
    }
    echo "  Un test que no puede fallar no está comprobando nada. Dale aserciones.\n";
}

$phpunitFallo = $resultadoPhpunit !== null && $resultadoPhpunit['codigo'] !== SALIDA_OK;

exit($fallaron === [] && $sospechosos === [] && !$phpunitFallo ? SALIDA_OK : SALIDA_FALLO);
