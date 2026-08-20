<?php
// tests/test_admin_modulos.php
// @requiere: http
// Smoke del interruptor en /admin: la ruta existe, exige sesión, y el POST exige CSRF.
// Sigue el patrón de tests/test_admin_dev_door_guard.php para sesión y peticiones.

$base = getenv('APP_BASE_URL') ?: 'http://localhost';

$total = 0;
$fallos = 0;

function comprobar(string $caso, bool $ok): void
{
    global $total, $fallos;
    $total++;
    if ($ok) { echo "  OK   {$caso}\n"; return; }
    $fallos++;
    echo "  FALLA {$caso}\n";
}

function pedir(string $url, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
    ] + $opts);
    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['status' => $status, 'raw' => $raw];
}

// 1) Sin sesión: redirige a login.
$r = pedir($base . '/admin/modulos');
comprobar('sin sesion redirige (302) a /admin/login', $r['status'] === 302 && str_contains($r['raw'], '/admin/login'));

// 2) Con sesión de admin por la puerta dev del panel.
$cookies = tempnam(sys_get_temp_dir(), 'cook');
pedir($base . '/admin/dev/entrar?u=test.A', [CURLOPT_COOKIEJAR => $cookies]);
$r = pedir($base . '/admin/modulos', [CURLOPT_COOKIEFILE => $cookies]);
comprobar('con sesion A responde 200', $r['status'] === 200);
comprobar('la vista trae el flag', str_contains($r['raw'], 'bi.control_tower.visible'));

// 3) POST sin CSRF: rechazado.
$r = pedir($base . '/admin/modulos', [
    CURLOPT_COOKIEFILE => $cookies,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['clave' => 'bi.control_tower.visible', 'valor' => '0']),
]);
comprobar('POST sin csrf es 403', $r['status'] === 403);

unlink($cookies);
echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
