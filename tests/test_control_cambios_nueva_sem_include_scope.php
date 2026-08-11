<?php
// @requiere: puro


// Contrato de ámbito del legacy `modificar_sem_estado.php`, que es include-scoped: no recibe
// argumentos, sino que lee `$dbName` y `$semana` del ámbito de quien lo incluye.
//
// `nueva_semana.php` cumple ese contrato (asigna ambas justo antes de su `include` final), pero
// `ControlCambiosApiController::nueva_sem()` lo requiere UNA SEGUNDA VEZ sin definir nada. En el
// camino feliz ese segundo `require_once` es un no-op porque el archivo ya se incluyó; en cuanto
// `nueva_semana.php` sale antes de su `include` —hay tres salidas así— deja de serlo y el script
// corre con las dos variables indefinidas.
//
// Este test fija: (1) qué ocurre de verdad en ese ámbito, (2) que el controlador no vuelva a
// requerirlo, y (3) que el llamador legítimo siga estableciendo el contrato.

define('PROJECT_ROOT', dirname(__DIR__));

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $fails++;
    }
};

// --- 1. Reproducción: el ámbito exacto que ofrece nueva_sem() tras una salida temprana ---------
// Sólo se define lo que nueva_sem() define de verdad: `$f_inicio_sem`. `$dbInstance` lo habría
// dejado `nueva_semana.php` al ejecutarse en el mismo ámbito. `$dbName` y `$semana`, no.
// El fallo salta en la línea 20, antes del `try` del script y antes de cualquier consulta, así
// que la reproducción no escribe en ninguna tabla.
$escenario = <<<'PHP'
define('PROJECT_ROOT', %s);
require PROJECT_ROOT . '/vendor/autoload.php';
$dbInstance = new stdClass();
$f_inicio_sem = '2026-01-01';
require PROJECT_ROOT . '/src/Legacy/modificar_sem_estado.php';
echo 'COMPLETO_SIN_ERROR';
PHP;

$code = sprintf($escenario, var_export(PROJECT_ROOT, true));
$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open(PHP_BINARY . ' -r ' . escapeshellarg($code), $descriptors, $pipes);
$salida = stream_get_contents($pipes[1]);
$errores = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($proc);
$todo = $salida . $errores;

$check(
    !str_contains($salida, 'COMPLETO_SIN_ERROR'),
    'sin $dbName/$semana el legacy NO completa (no degrada en silencio)',
);
$check(
    str_contains($todo, 'TypeError') && str_contains($todo, 'resolveByPrefix'),
    'el fallo es un TypeError en TableResolver::resolveByPrefix (null a un parámetro string)',
);
$check($exit !== 0, 'la ejecución en ese ámbito termina con código distinto de cero');

// --- 2. Regresión: el controlador no puede requerir un script cuyo contrato no establece -------
// `nueva_semana.php` ya lo incluye en el punto correcto y con las variables puestas. El segundo
// require no aporta nada cuando todo va bien y rompe la respuesta cuando algo va mal.
$controlador = file_get_contents(PROJECT_ROOT . '/src/Controllers/Api/ControlCambiosApiController.php');
$check(
    !preg_match('/require(_once)?\s+PROJECT_ROOT\s*\.\s*[\'"][^\'"]*modificar_sem_estado\.php/', $controlador),
    'ControlCambiosApiController no requiere modificar_sem_estado.php directamente',
);
$check(
    (bool) preg_match('/require(_once)?\s+PROJECT_ROOT\s*\.\s*[\'"][^\'"]*nueva_semana\.php/', $controlador),
    'ControlCambiosApiController sigue delegando en nueva_semana.php',
);

// --- 3. El llamador legítimo mantiene el contrato ----------------------------------------------
$nuevaSemana = file_get_contents(PROJECT_ROOT . '/src/Legacy/nueva_semana.php');
$posInclude = strpos($nuevaSemana, 'modificar_sem_estado.php');
$antesDelInclude = $posInclude === false ? '' : substr($nuevaSemana, 0, $posInclude);
$check($posInclude !== false, 'nueva_semana.php sigue incluyendo modificar_sem_estado.php');
$check(
    str_contains($antesDelInclude, '$semana = $semana_crear;'),
    'nueva_semana.php asigna $semana antes de incluirlo',
);
$check(
    str_contains($antesDelInclude, '$dbName = $db;'),
    'nueva_semana.php asigna $dbName antes de incluirlo',
);

echo $fails === 0
    ? "Ámbito de include en nueva_sem: PASS\n"
    : "Ámbito de include en nueva_sem: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
