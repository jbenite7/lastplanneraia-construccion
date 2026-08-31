<?php
declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP de `POST /api/context/weeks/create` y `POST /api/context/weeks/delete-last`
 * (T01-API-06/07, Tarea 5). Cubre SOLO las barreras de guarda (CSRF, capacidad, forma del
 * cuerpo, "solo la última semana") — nunca el camino de éxito 200/201, que requeriría DML
 * (insertar/borrar semanas_activas y programa_consolidado) prohibido como evidencia en este plan
 * (T01, restricción "no DDL/DML"). El camino de éxito ya está cubierto sin base de datos por
 * `tests/unit/Shell/WeekAdministrationServiceTest.php` (fakes) y por el navegador interceptado
 * (`tests/browser/shell-runtime-react.spec.mjs`).
 *
 * Cada caso de este archivo se detiene ANTES de que el controlador llame al repositorio real:
 * CSRF/capacidad fallan antes de leer el cuerpo; la validación 422 falla antes de resolver
 * `ProjectScope` en una operación de escritura real; "no es la última semana" (409) sí llama al
 * repositorio pero solo para un SELECT de solo lectura (`semanaMaxima`), nunca un INSERT/DELETE.
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
    $script = '<?php require "/var/www/html/vendor/autoload.php"; session_id($argv[1]); @session_start(); '
        . 'echo \App\Security\CsrfTokenManager::generate("shell_api"); session_write_close();';
    $tmp = tempnam(sys_get_temp_dir(), 'csrf_gen_');
    file_put_contents($tmp, $script);
    $token = trim((string) shell_exec('php ' . escapeshellarg($tmp) . ' ' . escapeshellarg($sessionId) . ' 2>&1'));
    @unlink($tmp);
    if (strlen($token) < 32) {
        fwrite(STDERR, "ABORT: no se pudo generar el token CSRF shell_api (salida: {$token})\n");
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
$maxSemanaReal = (int) (new SystemScopeRunner($db->dataScope()))->run(
    'test:shell-week-administration-contract:fixture',
    static fn () => $db->query("SELECT COALESCE(MAX(Semana), 0) FROM {$tSemanas} WHERE project_id = ?", [PROJECT_ID])->fetchColumn(),
);
if ($maxSemanaReal <= 0) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " no tiene semanas activas fixture\n");
    exit(2);
}
$semanaQueNoEsLaUltima = $maxSemanaReal > 1 ? $maxSemanaReal - 1 : $maxSemanaReal + 999; // nunca la máxima real

// test.R tiene lps.semana.crear y lps.semana.eliminar (RbacCatalog); test.V no tiene ninguno.
[$jarR, $sidR] = sesion('test.R');
$tokenR = csrfTokenForSession($sidR);
[$jarV, $sidV] = sesion('test.V');
$tokenV = csrfTokenForSession($sidV);

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// ------------------------------------------------------------------------------------- create ---

[$c1, $b1] = curlReq(BASE . '/api/context/weeks/create', json_encode(['startsOn' => '2026-09-07']), $jarR);
$j1 = json_decode($b1, true);
$check($c1 === 403 && ($j1['error']['code'] ?? null) === 'CSRF_INVALID', 'POST create sin CSRF -> 403 CSRF_INVALID', "HTTP {$c1}: {$b1}");

[$c2, $b2] = curlReq(BASE . '/api/context/weeks/create', json_encode(['startsOn' => '2026-09-07']), $jarV, ['X-CSRF-Token: ' . $tokenV]);
$j2 = json_decode($b2, true);
$check($c2 === 403 && ($j2['error']['code'] ?? null) === 'FORBIDDEN', 'POST create con test.V (sin capacidad) -> 403 FORBIDDEN', "HTTP {$c2}: {$b2}");

[$c3, $b3] = curlReq(BASE . '/api/context/weeks/create', json_encode(['startsOn' => 'no-es-una-fecha']), $jarR, ['X-CSRF-Token: ' . $tokenR]);
$j3 = json_decode($b3, true);
$check($c3 === 422 && ($j3['error']['code'] ?? null) === 'INVALID_STARTS_ON', 'POST create con startsOn inválido -> 422 (nunca llega al repositorio)', "HTTP {$c3}: {$b3}");

[$c4, $b4] = curlReq(BASE . '/api/context/weeks/create', json_encode([]), $jarR, ['X-CSRF-Token: ' . $tokenR]);
$j4 = json_decode($b4, true);
$check($c4 === 422, 'POST create sin startsOn -> 422', "HTTP {$c4}: {$b4}");

// --------------------------------------------------------------------------------- delete-last ---

[$c5, $b5] = curlReq(BASE . '/api/context/weeks/delete-last', json_encode(['week' => $maxSemanaReal]), $jarR);
$j5 = json_decode($b5, true);
$check($c5 === 403 && ($j5['error']['code'] ?? null) === 'CSRF_INVALID', 'POST delete-last sin CSRF -> 403 CSRF_INVALID', "HTTP {$c5}: {$b5}");

[$c6, $b6] = curlReq(BASE . '/api/context/weeks/delete-last', json_encode(['week' => $maxSemanaReal]), $jarV, ['X-CSRF-Token: ' . $tokenV]);
$j6 = json_decode($b6, true);
$check($c6 === 403 && ($j6['error']['code'] ?? null) === 'FORBIDDEN', 'POST delete-last con test.V (sin capacidad) -> 403 FORBIDDEN', "HTTP {$c6}: {$b6}");

[$c7, $b7] = curlReq(BASE . '/api/context/weeks/delete-last', json_encode(['week' => 'no-numero']), $jarR, ['X-CSRF-Token: ' . $tokenR]);
$j7 = json_decode($b7, true);
$check($c7 === 422 && ($j7['error']['code'] ?? null) === 'INVALID_WEEK', 'POST delete-last con week inválido -> 422', "HTTP {$c7}: {$b7}");

[$c8, $b8] = curlReq(BASE . '/api/context/weeks/delete-last', json_encode(['week' => $semanaQueNoEsLaUltima]), $jarR, ['X-CSRF-Token: ' . $tokenR]);
$j8 = json_decode($b8, true);
$check($c8 === 409 && ($j8['error']['code'] ?? null) === 'WEEK_NOT_LAST', 'POST delete-last con una semana que no es la máxima -> 409 WEEK_NOT_LAST (sin DML)', "HTTP {$c8}: {$b8}");

// No hay caso "delete-last exitoso" aquí a propósito: exigiría borrar la semana máxima real del
// proyecto fixture (DML), prohibido como evidencia de este plan. Cubierto sin DML por el
// navegador interceptado y por WeekAdministrationServiceTest::testEliminarUltimaSemanaEjecutaCascadaYRegistraActividad.

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);
