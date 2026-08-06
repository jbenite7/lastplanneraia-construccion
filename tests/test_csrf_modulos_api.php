<?php
declare(strict_types=1);
// Verifica que las mutaciones de los 6 módulos exijan token CSRF (biblia, EXPERIMENTS.md fila 24).
// HTTP real contra el contenedor, sesión por dev door. Requiere DEV_DOOR=1.

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

// [ruta de mutación, página que emite la meta, payload mínimo sin token]
$casos = [
    ['/api/subcontratistas/save', '/subcontratistas', ['accion' => 'nada']],
    ['/api/profesionales/save', '/profesionales', ['accion' => 'nada']],
    ['/api/control-cambios/save', '/control-cambios', ['accion' => 'nada']],
    ['/api/cic/save', '/programacion-semanal/cic', ['accion' => 'nada']],
    ['/api/cnc/save', '/programacion-semanal/cnc', ['accion' => 'nada']],
    ['/api/cnp/save', '/programacion-semanal/cnp', ['accion' => 'nada']],
    ['/api/cnp/reprogramar', '/programacion-semanal/cnp', ['accion' => 'nada']],
];

$fallos = 0; $total = 0;
$jar = sesion('test.A'); // rol con permiso de edición: si 403, es CSRF, no RBAC

foreach ($casos as [$api, $pagina, $payload]) {
    $total++;
    [$code] = curlReq(BASE . $api, $payload, $jar);
    if ($code !== 403) { $fallos++; echo "FALLO sin token: $api devolvió $code (esperaba 403)\n"; }

    $total++;
    [, $html] = curlReq(BASE . $pagina, null, $jar);
    if (!preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/', $html, $m)) {
        $fallos++; echo "FALLO meta: $pagina no emite csrf-token\n"; continue;
    }
    [$code2, $body2] = curlReq(BASE . $api, $payload + ['_csrf_token' => $m[1]], $jar);
    if ($code2 === 403 && str_contains($body2, 'CSRF')) {
        $fallos++; echo "FALLO con token: $api rechazó un token válido\n";
    }
}

echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
exit($fallos === 0 ? 0 : 1);
