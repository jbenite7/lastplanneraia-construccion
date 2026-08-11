<?php
// @requiere: puro


// Contrato del guard CSRF legacy: valida $_POST['_csrf_token'] contra un formKey.
// El helper hace exit tras responder; se ejecuta cada caso en un subproceso PHP.
define('PROJECT_ROOT', dirname(__DIR__));

$php = PHP_BINARY;
$guard = PROJECT_ROOT . '/src/Legacy/rbac_guard.php';

// Runner: genera un token, arma un escenario y captura salida + exit code.
$scenario = <<<'PHP'
define('PROJECT_ROOT', %s);
require PROJECT_ROOT . '/vendor/autoload.php';
require PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$valid = \App\Security\CsrfTokenManager::generate('lps_week_admin');
$_POST['_csrf_token'] = %s === '__VALID__' ? $valid : %s;
legacy_require_csrf('lps_week_admin');
echo 'PASSED_GUARD';
PHP;

function runScenario(string $php, string $scenarioTpl, string $tokenExpr): array
{
    $code = sprintf($scenarioTpl, var_export(PROJECT_ROOT, true), $tokenExpr, $tokenExpr);
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($php . ' -r ' . escapeshellarg($code), $descriptors, $pipes);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($proc);
    return ['out' => $out, 'exit' => $exit];
}

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) { $fails++; }
};

$valid = runScenario($php, $scenario, "'__VALID__'");
$check(str_contains($valid['out'], 'PASSED_GUARD'), 'token válido pasa el guard');

$empty = runScenario($php, $scenario, "''");
$check(!str_contains($empty['out'], 'PASSED_GUARD'), 'token vacío no pasa');
$check(str_contains($empty['out'], 'Token de seguridad'), 'token vacío responde mensaje CSRF');

$wrong = runScenario($php, $scenario, "'deadbeef'");
$check(!str_contains($wrong['out'], 'PASSED_GUARD'), 'token incorrecto no pasa');

echo $fails === 0 ? "Legacy CSRF guard: PASS\n" : "Legacy CSRF guard: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
