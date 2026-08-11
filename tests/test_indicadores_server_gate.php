<?php
declare(strict_types=1);
// @requiere: datos-proyecto

// /indicadores ocultaba el informe de Power BI solo en el navegador (EXPERIMENTS.md fila 49):
// el 403 y la ausencia de la URL en el HTML deben resolverse en el servidor.
// Permitido: V (Visualizador ve informes). Denegado: C (Subcontratista, rol restringido
// ya sembrado en database/seeds/dev_test_users.php y habilitado en DEV_DOOR_USERS local).

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';

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

$fallos = 0;
$jarV = sesion('test.V');
[$codeV, $htmlV] = curlReq(BASE . '/indicadores', null, $jarV);
if ($codeV !== 200) { $fallos++; echo "FALLO: V no ve /indicadores ($codeV)\n"; }

$jarC = sesion('test.C');
[$codeC, $htmlC] = curlReq(BASE . '/indicadores', null, $jarC);
if ($codeC !== 403) { $fallos++; echo "FALLO: rol restringido recibió $codeC (esperaba 403)\n"; }
if (str_contains($htmlC, 'POWER_BI_REPORT_URL') || str_contains($htmlC, 'app.powerbi.com')) {
    $fallos++; echo "FALLO: la URL del informe viaja en el HTML del rol restringido\n";
}

echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
