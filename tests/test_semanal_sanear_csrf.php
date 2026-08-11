<?php
declare(strict_types=1);
// @requiere: http

// `sanear` ejecuta DELETE+INSERT y debe exigir CSRF (EXPERIMENTS.md fila 25).
// Copiar sesion() y curlReq() de tests/test_csrf_modulos_api.php, con BASE y PROYECTO

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';
const DB_PREFIX = 'pdc_sandbox_e2e';

function sesion(string $usuario): string {
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    [$code] = curlReq($url, null, $jar);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

$jar = sesion('test.A');
$fallos = 0;
// La ruta de save de PS: confirmada en public/index.php line 262 -> /api/semanal/save
[$code, $body] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), ['opcion' => 'sanear', 'semana' => '1'], $jar);
if ($code !== 403) { $fallos++; echo "FALLO: sanear sin token devolvió $code (esperaba 403)\n"; }
echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
