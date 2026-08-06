<?php
declare(strict_types=1);
// «Editar el pasado del PG» debe existir en servidor, no solo como capacidad declarada
// (EXPERIMENTS.md fila 26). Rol permitido: A. Rol denegado: R.
//
// El fixture "PDC Sandbox E2E" (usado por otros tests HTTP del repo) solo tiene UNA semana
// activa sembrada, así que no sirve para distinguir "semana pasada" de "semana vigente" —
// con maxWeek=1, ninguna semana es pasada. Se usa en su lugar el proyecto "Prueba"
// (project_id=27), que tiene 7 semanas activas y ambas cuentas de prueba (test.A rol A,
// test.R rol R) como miembros reales via project_members. Si esa condición del fixture
// cambia, el test aborta con exit(2) en vez de fingir verde.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Prueba';

function sesion(string $usuario): string
{
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
function curlReq(string $url, ?array $post, string $jar): array
{
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

function csrfToken(string $jar): string
{
    [, $html] = curlReq(BASE . '/programa-general', null, $jar);
    if (!preg_match('/<meta name="csrf-token" content="([a-f0-9]{64,})"/', $html, $m)) {
        fwrite(STDERR, "ABORT: /programa-general no emitió csrf-token para esta sesión\n");
        exit(2);
    }
    return $m[1];
}

// 0. Verificar el fixture antes de asumir nada: el candado exige al menos dos semanas
// activas (una pasada, una vigente) para poder distinguirlas por HTTP.
$db = Database::getInstance();
$stmt = $db->prepare("SELECT Id FROM general_proyectos_procesos WHERE Proyecto_Proceso = ?");
$stmt->execute([PROYECTO]);
$projectId = (int) $stmt->fetchColumn();
if (!$projectId) {
    fwrite(STDERR, "ABORT: no se encontró el proyecto fixture '" . PROYECTO . "'\n");
    exit(2);
}
$stmt = $db->prepare("SELECT MIN(Semana), MAX(Semana), COUNT(*) FROM semanas_activas WHERE project_id = ?");
$stmt->execute([$projectId]);
[$semanaMin, $semanaMax, $totalSemanas] = $stmt->fetch(PDO::FETCH_NUM);
$semanaMin = (int) $semanaMin;
$semanaMax = (int) $semanaMax;
$totalSemanas = (int) $totalSemanas;
if ($totalSemanas < 2 || $semanaMax <= $semanaMin) {
    fwrite(STDERR, "ABORT: el fixture '" . PROYECTO . "' solo tiene $totalSemanas semana(s) activa(s); no hay semana pasada distinguible de la vigente.\n");
    exit(2);
}
$semanaPasada = $semanaMin;

// Buscar una actividad no-capítulo con datos en la semana pasada.
$stmt = $db->prepare("SELECT unique_id FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND Titulo = 0 LIMIT 1");
$stmt->execute([$projectId, $semanaPasada]);
$uniqueId = $stmt->fetchColumn();
if ($uniqueId === false) {
    fwrite(STDERR, "ABORT: no hay actividades (Titulo=0) en la semana $semanaPasada del fixture '" . PROYECTO . "'\n");
    exit(2);
}
$uniqueId = (int) $uniqueId;

$fallos = 0;

// 1. test.R sobre semana pasada → 403
$jarR = sesion('test.R');
$tokenR = csrfToken($jarR);
[$code] = curlReq(
    BASE . '/api/general/update?semana_objetivo=' . $semanaPasada,
    ['unique_id' => $uniqueId, 'Actividad' => 'x', '_csrf_token' => $tokenR],
    $jarR
);
if ($code !== 403) { $fallos++; echo "FALLO: R editó semana pasada ($code)\n"; }

// 2. test.A sobre semana pasada → NO 403 por el candado (400 de validación es aceptable)
$jarA = sesion('test.A');
$tokenA = csrfToken($jarA);
[$codeA, $bodyA] = curlReq(
    BASE . '/api/general/update?semana_objetivo=' . $semanaPasada,
    ['unique_id' => $uniqueId, 'Actividad' => 'x', '_csrf_token' => $tokenA],
    $jarA
);
if ($codeA === 403 && str_contains($bodyA, 'pasadas')) { $fallos++; echo "FALLO: A bloqueado en semana pasada\n"; }

// 3. test.R sobre la semana vigente → NO 403 por el candado
[$codeV, $bodyV] = curlReq(
    BASE . '/api/general/update?semana_objetivo=' . $semanaMax,
    ['unique_id' => $uniqueId, 'Actividad' => 'x', '_csrf_token' => $tokenR],
    $jarR
);
if ($codeV === 403 && str_contains($bodyV, 'pasadas')) { $fallos++; echo "FALLO: R bloqueado en semana vigente\n"; }

echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
