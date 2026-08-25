<?php
declare(strict_types=1);
// @requiere: datos-proyecto

// Verifica que las tres mutaciones de LpsApiController (comentarios y crisis del cajón
// contextual LPS) exijan token CSRF, igual que los seis módulos ya cubiertos por
// tests/test_csrf_modulos_api.php. LpsApiController quedó fuera de ese cierre
// (88ba6e0d/ca642189) — ver docs/EXPERIMENTS.md, fila «Escalamientos (T4, 2026-08-25)», y
// docs/flujos/soporte.md.

// El test corre dentro del contenedor `app` (regla del repo: PHP siempre vía
// `docker compose exec app php ...`), donde Apache escucha en el puerto 80
// interno — el 8081 es solo el mapeo del host hacia afuera.
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

// [ruta de mutación, payload que falla la validación de negocio ANTES de escribir —
// así el caso "con token válido" prueba que el CSRF no bloquea, sin mutar nada real].
$casos = [
    ['/api/lps/comments/add', ['consecutivo' => '0', 'comentario' => 'prueba csrf']], // consecutivo<=0
    ['/api/lps/crisis/register', ['consecutivo' => '1', 'modulo' => 'SOP']], // modulo fuera de PG/PI/PS
    ['/api/lps/crisis/close', ['alerta_id' => '0', 'justificacion' => str_repeat('x', 100)]], // alertaId<=0
];

$fallos = 0; $total = 0;
$jar = sesion('test.R'); // rol con permiso de edición: si 403, es CSRF, no RBAC

// El token es compartido entre las cuatro páginas anfitrionas del cajón (form-key
// 'lps_drawer'); cualquiera de ellas sirve para obtenerlo.
$total++;
[, $html] = curlReq(BASE . '/programacion-semanal', null, $jar);
if (!preg_match('/<meta name="lps-drawer-csrf-token" content="([a-f0-9]{64})"/', $html, $m)) {
    $fallos++;
    echo "FALLO meta: /programacion-semanal no emite lps-drawer-csrf-token\n";
    echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
    exit($fallos === 0 ? 0 : 1);
}
$token = $m[1];

foreach ($casos as [$api, $payload]) {
    $total++;
    [$code, $body] = curlReq(BASE . $api, $payload, $jar);
    if ($code !== 403) {
        $fallos++;
        echo "FALLO sin token: $api devolvió $code (esperaba 403): $body\n";
    }

    $total++;
    [$code2, $body2] = curlReq(BASE . $api, $payload + ['_csrf_token' => $token], $jar);
    if ($code2 === 403) {
        $fallos++;
        echo "FALLO con token: $api rechazó un token válido: $body2\n";
    }
}

echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
exit($fallos === 0 ? 0 : 1);
