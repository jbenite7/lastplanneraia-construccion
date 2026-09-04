<?php
declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP de `POST /context/week` y `POST /context/clear-week` (T01-API-04/05, Tarea 5).
 * Completado en esta tarea: CSRF (antes ausente), pertenencia de la semana al proyecto activo
 * (antes solo `is_numeric`), y respuesta con el contexto canónico (`{ok:true, week:{...}}`) en
 * vez del `{success,message}` histórico.
 *
 * Solo lecturas y escritura de `$_SESSION['semana']` — nunca DDL/DML (restricción del plan T01,
 * Tarea 5): seleccionar/limpiar una semana YA activa del proyecto fixture no inserta ni borra
 * ninguna fila.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';
const PROJECT_ID = 73;

function sesion(string $usuario): array
{
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    $sid = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$sid): int {
            if (preg_match('/^Set-Cookie:\s*PHPSESSID=([^;]+)/i', $header, $m)) {
                $sid = $m[1];
            }
            return strlen($header);
        },
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!in_array($code, [200, 302], true) || $sid === null) {
        fwrite(STDERR, "ABORT: dev door cerrada para {$usuario} (HTTP {$code})\n");
        exit(2);
    }

    return [$jar, $sid];
}

function csrfTokenForSession(string $sessionId): string
{
    $diagFile = tempnam(sys_get_temp_dir(), 'csrf_diag_');
    $script = <<<'HIJO'
<?php
$diag = [];
$sid = $argv[1];
$out = $argv[2];
$anota = static function (string $linea) use (&$diag, $out): void {
    $diag[] = $linea;
    file_put_contents($out, implode("\n", $diag));
};
$anota('uid=' . (function_exists('posix_getuid') ? posix_getuid() : 'n/a')
    . ' euid=' . (function_exists('posix_geteuid') ? posix_geteuid() : 'n/a'));
$anota('output_previo_al_require ob=' . ob_get_level() . ' headers_sent=' . (headers_sent() ? 'SI' : 'no'));
require '/var/www/html/vendor/autoload.php';
$anota('tras_require ob=' . ob_get_level() . ' headers_sent=' . (headers_sent() ? 'SI' : 'no')
    . ' display_errors=' . var_export(ini_get('display_errors'), true));
$anota('save_path=' . var_export(ini_get('session.save_path'), true)
    . ' tmp=' . sys_get_temp_dir()
    . ' strict=' . ini_get('session.use_strict_mode')
    . ' handler=' . ini_get('session.save_handler'));
$archivo = sys_get_temp_dir() . '/sess_' . $sid;
$anota('archivo=' . $archivo
    . ' existe=' . (file_exists($archivo) ? 'si' : 'no')
    . ' legible=' . (is_readable($archivo) ? 'si' : 'no')
    . ' escribible=' . (is_writable($archivo) ? 'si' : 'no')
    . ' owner=' . (file_exists($archivo) ? (string) fileowner($archivo) : '-')
    . ' perms=' . (file_exists($archivo) ? decoct(fileperms($archivo) & 0777) : '-')
    . ' bytes=' . (file_exists($archivo) ? (string) filesize($archivo) : '-'));
$anota('session_id_set=' . var_export(session_id($sid), true));
$avisos = [];
set_error_handler(static function (int $no, string $str) use (&$avisos): bool {
    $avisos[] = "[{$no}] {$str}";
    return true;
});
$arranco = session_start();
restore_error_handler();
$anota('session_start_devolvio=' . var_export($arranco, true)
    . ' status=' . session_status()
    . ' id_tras_start=' . var_export(session_id(), true)
    . ' coincide=' . (session_id() === $sid ? 'si' : 'NO'));
foreach ($avisos as $aviso) {
    $anota('AVISO ' . $aviso);
}
$ultimo = error_get_last();
$anota('error_get_last=' . ($ultimo === null ? 'ninguno' : $ultimo['message']));
$anota('shell_api_en_sesion=' . (empty($_SESSION['_csrf_tokens']['shell_api'])
    ? 'no'
    : substr((string) $_SESSION['_csrf_tokens']['shell_api'], 0, 12) . '...'));
echo \App\Security\CsrfTokenManager::generate('shell_api');
session_write_close();
HIJO;
    $tmp = tempnam(sys_get_temp_dir(), 'csrf_gen_');
    file_put_contents($tmp, $script);
    $token = trim((string) shell_exec('php ' . escapeshellarg($tmp) . ' ' . escapeshellarg($sessionId) . ' ' . escapeshellarg($diagFile) . ' 2>/dev/null'));
    @unlink($tmp);
    $diag = (string) @file_get_contents($diagFile);
    @unlink($diagFile);
    if (strlen($token) < 32 || !str_contains($diag, 'coincide=si')) {
        fwrite(STDOUT, "DIAGNOSTICO DEL SUBPROCESO (ultimas lineas a proposito):\n" . $diag . "\n");
        fwrite(STDOUT, "token_len=" . strlen($token) . "\n");
        exit(2);
    }

    return $token;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?string $jsonBody, string $jar, array $extraHeaders = []): array
{
    $headers = $extraHeaders;
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ];
    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $jsonBody;
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;
    $ch = curl_init($url);
    curl_setopt_array($ch, $opts);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, $body];
}

use App\Security\DataScope\SystemScope;
use App\Security\DataScope\SystemScopeRunner;

$db = Database::getInstance();
$tSemanas = TableResolver::resolve(PROJECT_ID, 'semanas_activas');
$semanaValida = (new SystemScopeRunner($db->dataScope()))->run(
    'test:shell-week-context-contract:fixture',
    static fn () => $db->query("SELECT Semana FROM {$tSemanas} WHERE project_id = ? ORDER BY Semana ASC LIMIT 1", [PROJECT_ID])->fetchColumn(),
);
if ($semanaValida === false) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " no tiene semanas activas fixture para probar select\n");
    exit(2);
}
$semanaValida = (int) $semanaValida;
$semanaInexistente = 900001; // muy por encima de cualquier semana fixture real

[$jar, $sid] = sesion('test.R');
$token = csrfTokenForSession($sid);

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// 1) Sin CSRF -> 403.
[$c1, $b1] = curlReq(BASE . '/context/week', json_encode(['semana' => $semanaValida]), $jar);
$j1 = json_decode($b1, true);
$check($c1 === 403 && ($j1['ok'] ?? null) === false, 'POST /context/week sin CSRF -> 403 {ok:false}', "HTTP {$c1}: {$b1}");

// 2) Semana con forma inválida -> 400.
[$c2, $b2] = curlReq(BASE . '/context/week', json_encode(['semana' => 'no-numero']), $jar, ['X-CSRF-Token: ' . $token]);
$j2 = json_decode($b2, true);
$check($c2 === 400 && ($j2['ok'] ?? null) === false, 'POST /context/week con semana inválida -> 400', "HTTP {$c2}: {$b2}");

// 3) Semana que no pertenece al proyecto activo -> 404 (nunca acepta autoridad del cliente).
[$c3, $b3] = curlReq(BASE . '/context/week', json_encode(['semana' => $semanaInexistente]), $jar, ['X-CSRF-Token: ' . $token]);
$j3 = json_decode($b3, true);
$check($c3 === 404 && ($j3['ok'] ?? null) === false, 'POST /context/week con semana ajena al proyecto -> 404', "HTTP {$c3}: {$b3}");

// 4) Semana válida -> 200 con contexto canónico refrescado.
[$c4, $b4] = curlReq(BASE . '/context/week', json_encode(['semana' => $semanaValida]), $jar, ['X-CSRF-Token: ' . $token]);
$j4 = json_decode($b4, true);
$check($c4 === 200 && ($j4['ok'] ?? null) === true, 'POST /context/week con semana válida -> 200 {ok:true}', "HTTP {$c4}: {$b4}");
$check(is_array($j4['week'] ?? null) && ($j4['week']['current'] ?? null) === $semanaValida, 'week.current == la semana seleccionada', 'real: ' . var_export($j4['week']['current'] ?? null, true));
$check(is_array($j4['week']['options'] ?? null), 'week.options es un arreglo');
$check(is_array($j4['week']['actions'] ?? null) && array_key_exists('create', $j4['week']['actions']) && array_key_exists('deleteLast', $j4['week']['actions']), 'week.actions trae create/deleteLast');

// 5) /api/session refleja la misma semana (bootstrap y select nunca divergen).
[$c5, $b5] = curlReq(BASE . '/api/session', null, $jar);
$j5 = json_decode($b5, true);
$check(($j5['week']['current'] ?? null) === $semanaValida, '/api/session.week.current coincide tras seleccionar', 'real: ' . var_export($j5['week']['current'] ?? null, true));

// 6) clear-week sin CSRF -> 403.
[$c6, $b6] = curlReq(BASE . '/context/clear-week', json_encode([]), $jar);
$j6 = json_decode($b6, true);
$check($c6 === 403 && ($j6['ok'] ?? null) === false, 'POST /context/clear-week sin CSRF -> 403', "HTTP {$c6}: {$b6}");

// 7) clear-week con CSRF -> 200, week:null.
[$c7, $b7] = curlReq(BASE . '/context/clear-week', json_encode([]), $jar, ['X-CSRF-Token: ' . $token]);
$j7 = json_decode($b7, true);
$check($c7 === 200 && ($j7['ok'] ?? null) === true && array_key_exists('week', $j7) && $j7['week'] === null, 'POST /context/clear-week -> 200 {ok:true, week:null}', "HTTP {$c7}: {$b7}");

// Deja la sesión de fixture como la encontró un test futuro: re-selecciona la semana original.
curlReq(BASE . '/context/week', json_encode(['semana' => $semanaValida]), $jar, ['X-CSRF-Token: ' . $token]);

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);
